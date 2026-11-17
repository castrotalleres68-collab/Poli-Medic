<?php
/**
 * API pública — "Mi Turno": el paciente consulta y cancela sus turnos con su DNI.
 *
 *   mi_turno            (GET)  Lista los turnos de un DNI.
 *   mi_turno_cancelar   (POST) Cancela un turno confirmado de ese DNI.
 */

/** GET api.php?action=mi_turno&dni=12345678 */
function accion_mi_turno(PDO $pdo): void
{
    $dni = preg_replace('/\D+/', '', (string) ($_GET['dni'] ?? ''));
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
        respond(['error' => 'Ingresá un DNI válido de 7 u 8 números.'], 422);
    }

    $q = $pdo->prepare('SELECT id, nombre, apellido, dni FROM pacientes WHERE dni = ? LIMIT 1');
    $q->execute([$dni]);
    $person = $q->fetch();

    if (!$person) {
        respond(['found' => false, 'has_turns' => false, 'turns' => []]);
    }

    $q = $pdo->prepare(
        "SELECT t.id,
                CONCAT(pa.nombre, ' ', pa.apellido) AS paciente,
                pa.dni,
                e.nombre AS especialidad,
                COALESCE(p.nombre, 'Profesional no asignado') AS profesional,
                DATE_FORMAT(t.fecha, '%d/%m/%Y') AS fecha,
                DATE_FORMAT(t.fecha, '%Y-%m-%d') AS fecha_iso,
                TIME_FORMAT(t.hora, '%H:%i') AS hora,
                t.motivo,
                t.estado
           FROM turnos t
           JOIN pacientes pa ON pa.id = t.paciente_id
           JOIN especialidades e ON e.id = t.especialidad_id
           LEFT JOIN profesionales p ON p.id = t.profesional_id
          WHERE pa.id = ?
          ORDER BY t.fecha DESC, t.hora DESC, t.id DESC"
    );
    $q->execute([(int) $person['id']]);
    $turns = $q->fetchAll();

    respond([
        'found' => true,
        'has_turns' => count($turns) > 0,
        'patient' => [
            'nombre' => $person['nombre'],
            'apellido' => $person['apellido'],
            'dni' => $person['dni'],
        ],
        'turns' => $turns,
    ]);
}

/** POST api.php?action=mi_turno_cancelar   cuerpo: {dni, id} */
function accion_mi_turno_cancelar(PDO $pdo): void
{
    $data = inputData();
    $dni = preg_replace('/\D+/', '', (string) ($data['dni'] ?? ''));
    $id = (int) ($data['id'] ?? 0);

    if (!preg_match('/^[0-9]{7,8}$/', $dni) || $id < 1) {
        respond(['error' => 'Revisá el DNI y el turno seleccionado.'], 422);
    }

    try {
        $pdo->beginTransaction();

        // Se bloquea la fila del turno mientras se decide si se puede cancelar.
        $q = $pdo->prepare(
            'SELECT t.id, t.estado, p.id AS paciente_id
               FROM turnos t
               JOIN pacientes p ON p.id = t.paciente_id
              WHERE t.id = ? AND p.dni = ?
                FOR UPDATE'
        );
        $q->execute([$id, $dni]);
        $turn = $q->fetch();

        if (!$turn) {
            $pdo->rollBack();
            respond(['error' => 'No se encontró un turno asociado a ese DNI.'], 404);
        }
        if ($turn['estado'] !== 'Confirmado') {
            $pdo->rollBack();
            respond(['error' => 'Este turno ya no admite cancelación porque su estado es ' . $turn['estado'] . '.'], 409);
        }

        // Este UPDATE dispara el trigger trg_auditar_estado_turno.
        $upd = $pdo->prepare("UPDATE turnos SET estado = 'Cancelado' WHERE id = ? AND paciente_id = ? AND estado = 'Confirmado'");
        $upd->execute([$id, (int) $turn['paciente_id']]);

        if ($upd->rowCount() !== 1) {
            $pdo->rollBack();
            respond(['error' => 'No se pudo cancelar el turno. Actualizá la lista e intentá nuevamente.'], 409);
        }

        notificarTurnoCancelado($pdo, $id);
        registrarActividad($pdo, 'El paciente canceló el turno #' . $id . ' desde Mi Turno');

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    respond(['ok' => true, 'message' => 'El turno fue cancelado correctamente.']);
}
