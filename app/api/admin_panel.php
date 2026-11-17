<?php
/**
 * API del panel administrativo — resumen, notificaciones y estado de turnos.
 * Todas estas acciones exigen sesión de administrador (lo controla api.php).
 *
 *   admin_data                     Todo lo que muestra el panel en una sola respuesta.
 *   marcar_notificacion            Marca una notificación como leída.
 *   marcar_todas_notificaciones    Marca todas como leídas.
 *   cambiar_estado                 Cambia un turno a Confirmado o Atendido.
 */

/** GET api.php?action=admin_data */
function accion_admin_data(PDO $pdo): void
{
    $appointments = $pdo->query(
        "SELECT t.id,
                pa.dni AS dni_paciente,
                CONCAT(pa.nombre, ' ', pa.apellido) AS paciente,
                e.nombre AS especialidad,
                p.nombre AS profesional,
                DATE_FORMAT(t.fecha, '%d/%m/%Y') AS fecha,
                TIME_FORMAT(t.hora, '%H:%i') AS hora,
                t.estado,
                CONCAT(LEFT(pa.nombre, 1), LEFT(pa.apellido, 1)) AS iniciales
           FROM turnos t
           JOIN pacientes pa ON pa.id = t.paciente_id
           JOIN especialidades e ON e.id = t.especialidad_id
           LEFT JOIN profesionales p ON p.id = t.profesional_id
          ORDER BY t.fecha DESC, t.hora DESC"
    )->fetchAll();

    $patients = $pdo->query(
        "SELECT pa.id, pa.nombre, pa.apellido, pa.dni, pa.telefono, pa.email,
                pa.obra_social, pa.obra_social_otra,
                (SELECT DATE_FORMAT(MAX(t.fecha), '%d/%m/%Y')
                   FROM turnos t WHERE t.paciente_id = pa.id) AS ultimo_turno,
                (SELECT t.id FROM turnos t WHERE t.paciente_id = pa.id
                  ORDER BY t.fecha DESC, t.hora DESC, t.id DESC LIMIT 1) AS ultimo_turno_id,
                (SELECT t.estado FROM turnos t WHERE t.paciente_id = pa.id
                  ORDER BY t.fecha DESC, t.hora DESC, t.id DESC LIMIT 1) AS estado_turno
           FROM pacientes pa
          ORDER BY pa.apellido, pa.nombre"
    )->fetchAll();

    $professionals = $pdo->query(
        "SELECT p.id, p.nombre,
                GROUP_CONCAT(DISTINCT e.nombre ORDER BY e.nombre SEPARATOR ', ') AS especialidad,
                (SELECT GROUP_CONCAT(
                            DISTINCT CONCAT(
                                CASE h.dia_semana
                                    WHEN 1 THEN 'Lun' WHEN 2 THEN 'Mar' WHEN 3 THEN 'Mié'
                                    WHEN 4 THEN 'Jue' WHEN 5 THEN 'Vie' WHEN 6 THEN 'Sáb'
                                    ELSE 'Dom'
                                END,
                                ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'),
                                '-', TIME_FORMAT(h.hora_fin, '%H:%i'))
                            ORDER BY h.dia_semana SEPARATOR ' · ')
                   FROM horarios_profesionales h
                  WHERE h.profesional_id = p.id AND h.activo = 1) AS agenda,
                CONCAT(LEFT(p.nombre, 1), LEFT(SUBSTRING_INDEX(p.nombre, ' ', -1), 1)) AS iniciales
           FROM profesionales p
           JOIN profesional_especialidades pe ON pe.profesional_id = p.id
           JOIN especialidades e ON e.id = pe.especialidad_id
          WHERE p.activo = 1
          GROUP BY p.id, p.nombre
          ORDER BY p.nombre"
    )->fetchAll();

    $specialties = $pdo->query(
        'SELECT id, nombre, descripcion FROM especialidades WHERE activa = 1 ORDER BY nombre'
    )->fetchAll();

    $activity = $pdo->query(
        "SELECT descripcion, DATE_FORMAT(creado_en, '%d/%m/%Y %H:%i') AS fecha
           FROM actividad
          ORDER BY id DESC
          LIMIT 20"
    )->fetchAll();

    $notifications = $pdo->query(
        "SELECT n.id, n.turno_id, n.tipo, n.mensaje, n.leida,
                DATE_FORMAT(n.creado_en, '%d/%m/%Y %H:%i') AS fecha
           FROM notificaciones n
          ORDER BY n.id DESC
          LIMIT 100"
    )->fetchAll();

    $stats = [
        'today' => (int) $pdo->query(
            "SELECT COUNT(*) FROM turnos WHERE fecha = CURDATE() AND estado <> 'Cancelado'"
        )->fetchColumn(),
        'pending' => (int) $pdo->query(
            "SELECT COUNT(*) FROM turnos WHERE estado = 'Confirmado' AND fecha >= CURDATE()"
        )->fetchColumn(),
        'patients' => count($patients),
        'professionals' => count($professionals),
    ];

    respond(compact('appointments', 'patients', 'professionals', 'specialties', 'stats', 'activity', 'notifications'));
}

/** POST api.php?action=marcar_notificacion   cuerpo: {id} */
function accion_marcar_notificacion(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);

    if ($id < 1) {
        respond(['error' => 'Notificación inválida.'], 422);
    }

    $q = $pdo->prepare('UPDATE notificaciones SET leida = 1 WHERE id = ?');
    $q->execute([$id]);

    // rowCount() es 0 si ya estaba leída; se distingue de "no existe".
    if (!$q->rowCount()) {
        $check = $pdo->prepare('SELECT id FROM notificaciones WHERE id = ?');
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            respond(['error' => 'No se encontró la notificación.'], 404);
        }
    }

    respond(['ok' => true]);
}

/** POST api.php?action=marcar_todas_notificaciones */
function accion_marcar_todas_notificaciones(PDO $pdo): void
{
    $pdo->exec('UPDATE notificaciones SET leida = 1 WHERE leida = 0');
    respond(['ok' => true]);
}

/** POST api.php?action=cambiar_estado   cuerpo: {id, estado} */
function accion_cambiar_estado(PDO $pdo): void
{
    $data = inputData();

    if (!in_array($data['estado'] ?? '', ['Confirmado', 'Atendido'], true)) {
        respond(['error' => 'Elegí un estado válido. Los turnos cancelados se gestionan desde la opción de cancelación.'], 422);
    }

    // Este UPDATE dispara el trigger trg_auditar_estado_turno (guarda el cambio).
    $update = $pdo->prepare('UPDATE turnos SET estado = ? WHERE id = ?');
    $update->execute([$data['estado'], (int) ($data['id'] ?? 0)]);

    if (!$update->rowCount()) {
        respond(['error' => 'No se encontró el turno o el estado no cambió.'], 404);
    }

    registrarActividad($pdo, 'Turno #' . (int) $data['id'] . ' actualizado a ' . $data['estado']);
    respond(['ok' => true]);
}
