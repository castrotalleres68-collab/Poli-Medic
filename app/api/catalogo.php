<?php
/**
 * API pública — catálogo y disponibilidad (lo usa el portal de pacientes).
 *
 *   catalogos        Especialidades y profesionales activos.
 *   dias_atencion    Agenda semanal de un profesional en una especialidad.
 *   disponibilidad   Horarios libres/ocupados de un profesional en una fecha.
 */

/** GET api.php?action=catalogos */
function accion_catalogos(PDO $pdo): void
{
    $specialties = $pdo->query(
        "SELECT nombre, descripcion
           FROM especialidades
          WHERE activa = 1
          ORDER BY FIELD(nombre, 'Clínica Médica', 'Clínica médica', 'Ginecología',
                         'Kinesiología', 'Laboratorio', 'Psicopedagogía'), nombre"
    )->fetchAll();

    $professionals = $pdo->query(
        "SELECT p.id, p.nombre,
                GROUP_CONCAT(DISTINCT e.nombre ORDER BY e.nombre SEPARATOR '||') AS especialidades
           FROM profesionales p
           JOIN profesional_especialidades pe ON pe.profesional_id = p.id
           JOIN especialidades e ON e.id = pe.especialidad_id
          WHERE p.activo = 1 AND e.activa = 1
          GROUP BY p.id, p.nombre
          ORDER BY p.nombre"
    )->fetchAll();

    respond(['specialties' => $specialties, 'professionals' => $professionals]);
}

/** GET api.php?action=dias_atencion&professional=...&specialty=... */
function accion_dias_atencion(PDO $pdo): void
{
    $professional = trim((string) ($_GET['professional'] ?? ''));
    $specialty = trim((string) ($_GET['specialty'] ?? ''));

    if (!$professional || !$specialty) {
        respond(['days' => [], 'error' => 'Elegí un profesional y una especialidad.'], 422);
    }

    $par = buscarProfesionalYEspecialidad($pdo, $professional, $specialty);
    if (!$par) {
        respond(['days' => []]);
    }

    $q = $pdo->prepare(
        'SELECT dia_semana, hora_inicio, hora_fin
           FROM horarios_profesionales
          WHERE profesional_id = ? AND especialidad_id = ? AND activo = 1
          ORDER BY dia_semana, hora_inicio'
    );
    $q->execute([(int) $par['profesional_id'], (int) $par['especialidad_id']]);
    $agenda = $q->fetchAll();

    $days = array_values(array_unique(array_map(fn($fila) => (int) $fila['dia_semana'], $agenda)));

    respond(['days' => $days, 'agenda' => $agenda]);
}

/**
 * GET api.php?action=disponibilidad&professional=...&specialty=...&date=YYYY-MM-DD
 *
 * Estados posibles de cada horario:
 *   disponible | ocupado | en_proceso | en_proceso_propio | no_disponible
 */
function accion_disponibilidad(PDO $pdo): void
{
    $professional = trim((string) ($_GET['professional'] ?? ''));
    $specialty = trim((string) ($_GET['specialty'] ?? ''));
    $dateText = trim((string) ($_GET['date'] ?? ''));
    $date = DateTime::createFromFormat('!Y-m-d', $dateText);

    if (
        !$professional || !$specialty || !$date
        || $date->format('Y-m-d') !== $dateText
        || $date < new DateTime('today')
    ) {
        respond(['slots' => [], 'error' => 'Elegí un profesional y una fecha válida.'], 422);
    }

    $par = buscarProfesionalYEspecialidad($pdo, $professional, $specialty);
    if (!$par) {
        respond(['slots' => []]);
    }
    $profesionalId = (int) $par['profesional_id'];
    $especialidadId = (int) $par['especialidad_id'];

    // 1) Franjas de agenda configuradas para ese día de la semana.
    $weekday = (int) $date->format('N'); // 1 = lunes ... 7 = domingo
    $q = $pdo->prepare(
        'SELECT hora_inicio, hora_fin, duracion_minutos
           FROM horarios_profesionales
          WHERE profesional_id = ? AND especialidad_id = ? AND dia_semana = ? AND activo = 1
          ORDER BY hora_inicio'
    );
    $q->execute([$profesionalId, $especialidadId, $weekday]);
    $schedules = $q->fetchAll();

    // 2) Turnos ya confirmados ese día (de cualquier especialidad del profesional).
    $q = $pdo->prepare(
        "SELECT hora, COALESCE(duracion_minutos, 30) AS duracion_minutos
           FROM turnos
          WHERE profesional_id = ? AND fecha = ? AND estado <> 'Cancelado'"
    );
    $q->execute([$profesionalId, $dateText]);
    $busyRanges = [];
    foreach ($q->fetchAll() as $busy) {
        $start = strtotime($busy['hora']);
        $busyRanges[] = [$start, $start + ((int) $busy['duracion_minutos'] * 60)];
    }

    // 3) Selecciones en proceso: bloquean temporalmente la franja para otros pacientes.
    $pdo->prepare('DELETE FROM reservas_horarios_temporales WHERE vence_en <= NOW()')->execute();
    $holdsQuery = $pdo->prepare(
        'SELECT hora, duracion_minutos, propietario_sesion
           FROM reservas_horarios_temporales
          WHERE profesional_id = ? AND fecha = ? AND vence_en > NOW()'
    );
    $holdsQuery->execute([$profesionalId, $dateText]);
    $heldRanges = [];
    foreach ($holdsQuery->fetchAll() as $hold) {
        $start = strtotime($hold['hora']);
        $heldRanges[] = [
            $start,
            $start + ((int) $hold['duracion_minutos'] * 60),
            hash_equals((string) $hold['propietario_sesion'], reservationOwner()), // ¿es mía?
        ];
    }

    // 4) Se recorre cada franja en tramos de `duracion_minutos` y se asigna un estado.
    $slotStates = [];
    $now = new DateTime();

    foreach ($schedules as $schedule) {
        $duration = max(10, (int) $schedule['duracion_minutos']);
        $start = strtotime($schedule['hora_inicio']);
        $end = strtotime($schedule['hora_fin']);

        for ($minute = $start; $minute + $duration * 60 <= $end; $minute += $duration * 60) {
            $slotEnd = $minute + $duration * 60;
            $status = 'disponible';

            foreach ($busyRanges as [$busyStart, $busyEnd]) {
                if ($minute < $busyEnd && $slotEnd > $busyStart) {
                    $status = 'ocupado';
                    break;
                }
            }

            if ($status === 'disponible') {
                foreach ($heldRanges as [$holdStart, $holdEnd, $mine]) {
                    if ($minute < $holdEnd && $slotEnd > $holdStart) {
                        $status = $mine ? 'en_proceso_propio' : 'en_proceso';
                        break;
                    }
                }
            }

            // Horarios que ya pasaron hoy.
            if ($dateText === $now->format('Y-m-d') && $minute <= strtotime($now->format('H:i:s'))) {
                $status = 'no_disponible';
            }

            $label = date('H:i', $minute);
            if (!isset($slotStates[$label]) || $slotStates[$label] === 'disponible' && $status !== 'disponible') {
                $slotStates[$label] = $status;
            }
        }
    }

    ksort($slotStates);

    $slots = array_keys(array_filter($slotStates, fn($estado) => $estado === 'disponible'));
    $states = [];
    foreach ($slotStates as $hora => $estado) {
        $states[] = ['hora' => $hora, 'estado' => $estado];
    }

    respond(['slots' => $slots, 'slot_states' => $states, 'agenda' => $schedules]);
}
