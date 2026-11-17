<?php
/**
 * API del panel administrativo — profesionales y sus agendas.
 * Exige sesión de administrador (lo controla api.php).
 *
 *   agenda_profesional          Datos y horarios de un profesional (para editar).
 *   agregar_profesional         Alta con especialidades y agenda (transacción).
 *   editar_agenda_profesional   Modifica nombre, especialidades y agenda (transacción).
 *   eliminar_profesional        Baja lógica (activo = 0) para conservar el historial.
 */

/* ------------------------------------------------------------------ */
/* Funciones auxiliares de este módulo                                 */
/* ------------------------------------------------------------------ */

/** Devuelve los IDs (de la lista recibida) que corresponden a especialidades activas. */
function idsDeEspecialidadesActivas(PDO $pdo, array $ids): array
{
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT id FROM especialidades WHERE activa = 1 AND id IN ($marcas)");
    $q->execute($ids);

    return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
}

/** Vincula un profesional con sus especialidades (tabla profesional_especialidades). */
function vincularEspecialidades(PDO $pdo, int $profesionalId, array $especialidadIds): void
{
    $link = $pdo->prepare(
        'INSERT INTO profesional_especialidades (profesional_id, especialidad_id) VALUES (?, ?)'
    );
    foreach ($especialidadIds as $especialidadId) {
        $link->execute([$profesionalId, $especialidadId]);
    }
}

/**
 * Inserta las franjas de agenda: una fila por especialidad × día × franja.
 *
 * @param array $dias [dia_semana => [[inicio, fin], ...]] (resultado de parsearHorarios)
 */
function insertarAgenda(PDO $pdo, int $profesionalId, array $especialidadIds, array $dias, int $intervalo): void
{
    $agenda = $pdo->prepare(
        'INSERT INTO horarios_profesionales
             (profesional_id, especialidad_id, dia_semana, hora_inicio, hora_fin, duracion_minutos, activo)
         VALUES (?, ?, ?, ?, ?, ?, 1)'
    );

    foreach ($especialidadIds as $especialidadId) {
        foreach ($dias as $dia => $franjas) {
            foreach ($franjas as $franja) {
                $agenda->execute([$profesionalId, $especialidadId, $dia, $franja[0], $franja[1], $intervalo]);
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* Acciones                                                            */
/* ------------------------------------------------------------------ */

/** GET api.php?action=agenda_profesional&id=... */
function accion_agenda_profesional(PDO $pdo): void
{
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? inputData()['id'] ?? 0);
    if ($id < 1) {
        respond(['error' => 'Profesional inválido.'], 422);
    }

    $q = $pdo->prepare('SELECT id, nombre FROM profesionales WHERE id = ? AND activo = 1');
    $q->execute([$id]);
    $profesional = $q->fetch();
    if (!$profesional) {
        respond(['error' => 'No se encontró el profesional.'], 404);
    }

    $q = $pdo->prepare(
        "SELECT DISTINCT dia_semana,
                TIME_FORMAT(hora_inicio, '%H:%i') AS hora_inicio,
                TIME_FORMAT(hora_fin, '%H:%i') AS hora_fin,
                duracion_minutos
           FROM horarios_profesionales
          WHERE profesional_id = ? AND activo = 1
          ORDER BY dia_semana, hora_inicio"
    );
    $q->execute([$id]);
    $horarios = $q->fetchAll();

    $q = $pdo->prepare('SELECT DISTINCT especialidad_id FROM profesional_especialidades WHERE profesional_id = ?');
    $q->execute([$id]);
    $especialidadIds = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));

    respond([
        'id' => (int) $profesional['id'],
        'nombre' => $profesional['nombre'],
        'horarios' => $horarios,
        'especialidad_ids' => $especialidadIds,
        'intervalo_minutos' => (int) ($horarios[0]['duracion_minutos'] ?? 30),
    ]);
}

/** POST api.php?action=editar_agenda_profesional */
function accion_editar_agenda_profesional(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);
    $nombre = trim((string) ($d['nombre'] ?? ''));
    $ids = normalizarIds($d['especialidad_ids'] ?? []);
    $horarios = (array) ($d['horarios'] ?? []);
    $intervalo = (int) ($d['intervalo_minutos'] ?? 30);

    if ($id < 1 || $nombre === '' || !$ids || !$horarios) {
        respond(['error' => 'Completá nombre, especialidades y al menos un horario.'], 422);
    }
    if ($intervalo < 10 || $intervalo > 120) {
        respond(['error' => 'La duración de consulta no es válida.'], 422);
    }

    $dias = parsearHorarios($horarios, 'Revisá los días y rangos horarios; cada franja debe tener un horario válido.');

    if (count(idsDeEspecialidadesActivas($pdo, $ids)) !== count($ids)) {
        respond(['error' => 'Una de las especialidades no está disponible.'], 422);
    }

    // Se usa el mismo bloqueo que las reservas para que una edición de agenda
    // no se cruce con una selección o confirmación de turno.
    $pdo->beginTransaction();

    if (!bloquearProfesional($pdo, $id)) {
        $pdo->rollBack();
        respond(['error' => 'No se encontró el profesional o está desactivado.'], 404);
    }

    $pdo->prepare('UPDATE profesionales SET nombre = ?, especialidad_id = ? WHERE id = ? AND activo = 1')
        ->execute([$nombre, $ids[0], $id]);

    // Se reemplazan por completo las especialidades y la agenda anteriores.
    $pdo->prepare('DELETE FROM profesional_especialidades WHERE profesional_id = ?')->execute([$id]);
    vincularEspecialidades($pdo, $id, $ids);

    $pdo->prepare('DELETE FROM horarios_profesionales WHERE profesional_id = ?')->execute([$id]);
    insertarAgenda($pdo, $id, $ids, $dias, $intervalo);

    registrarActividad($pdo, 'Agenda profesional actualizada: ' . $nombre);
    $pdo->commit();

    respond(['ok' => true]);
}

/** POST api.php?action=agregar_profesional */
function accion_agregar_profesional(PDO $pdo): void
{
    $d = inputData();
    $nombre = trim((string) ($d['nombre'] ?? ''));
    $ids = normalizarIds($d['especialidad_ids'] ?? []);

    // Formatos antiguos: lista de nombres de especialidad, o un único nombre.
    $nombres = $d['especialidades'] ?? [];
    if (is_string($nombres)) {
        $nombres = preg_split('/\s*,\s*/', trim($nombres), -1, PREG_SPLIT_NO_EMPTY);
    }
    if (!$ids && is_array($nombres) && $nombres) {
        $marcas = implode(',', array_fill(0, count($nombres), '?'));
        $q = $pdo->prepare("SELECT id FROM especialidades WHERE activa = 1 AND nombre IN ($marcas)");
        $q->execute(array_map('trim', $nombres));
        $ids = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    }
    if (!$ids && !empty($d['especialidad'])) {
        $q = $pdo->prepare('SELECT id FROM especialidades WHERE nombre = ? AND activa = 1');
        $q->execute([trim((string) $d['especialidad'])]);
        $idUnico = (int) $q->fetchColumn();
        if ($idUnico) {
            $ids = [$idUnico];
        }
    }

    if ($nombre === '' || !$ids) {
        respond(['error' => 'Completá el nombre y elegí al menos una especialidad.'], 422);
    }

    $idsValidos = idsDeEspecialidadesActivas($pdo, $ids);
    if (count($idsValidos) !== count($ids)) {
        respond(['error' => 'Una de las especialidades no existe o está desactivada.'], 422);
    }

    $intervalo = (int) ($d['intervalo_minutos'] ?? 30);
    $dias = parsearHorarios((array) ($d['horarios'] ?? []), 'Revisá los horarios: cada franja debe tener un rango válido.');

    if (!$dias) {
        respond(['error' => 'Seleccioná al menos un día de atención.'], 422);
    }
    if ($intervalo < 10 || $intervalo > 120) {
        respond(['error' => 'La duración de la consulta debe estar entre 10 y 120 minutos.'], 422);
    }

    // Transacción: profesional + especialidades + agenda + actividad, todo o nada.
    $pdo->beginTransaction();

    $pdo->prepare('INSERT INTO profesionales (nombre, especialidad_id) VALUES (?, ?)')
        ->execute([$nombre, $idsValidos[0]]);
    $profesionalId = (int) $pdo->lastInsertId();

    vincularEspecialidades($pdo, $profesionalId, $idsValidos);
    insertarAgenda($pdo, $profesionalId, $idsValidos, $dias, $intervalo);

    registrarActividad($pdo, 'Profesional agregado: ' . $nombre . ' · agenda configurada');
    $pdo->commit();

    respond(['ok' => true, 'id' => $profesionalId], 201);
}

/** POST api.php?action=eliminar_profesional */
function accion_eliminar_profesional(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);

    if (!$id) {
        respond(['error' => 'Elegí un profesional válido.'], 422);
    }

    // Se desactiva para que los turnos anteriores sigan mostrando su profesional.
    $q = $pdo->prepare('UPDATE profesionales SET activo = 0 WHERE id = ? AND activo = 1');
    $q->execute([$id]);
    if (!$q->rowCount()) {
        respond(['error' => 'No se encontró el profesional o ya estaba desactivado.'], 404);
    }

    registrarActividad($pdo, 'Profesional desactivado #' . $id);
    respond(['ok' => true]);
}
