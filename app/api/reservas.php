<?php
/**
 * API pública — proceso de reserva de un turno.
 *
 * Flujo que sigue el portal:
 *   1. tomar_horario     El paciente elige una hora → se bloquea 10 minutos.
 *   2. renovar_horario   (opcional) Extiende el bloqueo mientras completa sus datos.
 *   3. reservar          Confirma el turno (transacción: paciente + turno + avisos).
 *   4. cancelar_turno    Cancela un turno reservado en la misma sesión.
 *   liberar_horario      Suelta el bloqueo si el paciente abandona.
 *
 * Concurrencia: todas las operaciones sobre una agenda bloquean primero la
 * fila del profesional (SELECT ... FOR UPDATE), de modo que dos pacientes no
 * puedan quedarse con el mismo horario.
 */

/** Minutos que dura el bloqueo temporal de un horario. */
const MINUTOS_BLOQUEO = 10;

/** POST api.php?action=tomar_horario   cuerpo: {professional, specialty, date, time} */
function accion_tomar_horario(PDO $pdo): void
{
    $data = inputData();
    $professional = trim((string) ($data['professional'] ?? ''));
    $specialty = trim((string) ($data['specialty'] ?? ''));
    $dateText = trim((string) ($data['date'] ?? ''));
    $timeText = trim((string) ($data['time'] ?? ''));
    $date = DateTime::createFromFormat('!Y-m-d', $dateText);

    if (
        !$professional || !$specialty || !$date
        || $date->format('Y-m-d') !== $dateText
        || !preg_match(REGEX_HORA, $timeText)
        || $date < new DateTime('today')
    ) {
        respond(['error' => 'La fecha o el horario elegido no son válidos. Actualizá la disponibilidad e intentá nuevamente.'], 422);
    }

    $par = buscarProfesionalYEspecialidad($pdo, $professional, $specialty);
    if (!$par) {
        respond(['error' => 'El profesional o la especialidad ya no están disponibles.'], 409);
    }
    $professionalId = (int) $par['profesional_id'];
    $specialtyId = (int) $par['especialidad_id'];
    $weekday = (int) $date->format('N');

    // Control rápido (sin bloqueo): el horario debe estar dentro de la agenda.
    if (duracionDeFranja($pdo, $professionalId, $specialtyId, $weekday, $timeText) < 1) {
        respond(['error' => 'Ese horario ya no pertenece a la agenda habilitada. Actualizá la disponibilidad.'], 409);
    }

    try {
        $pdo->beginTransaction();

        if (!bloquearProfesional($pdo, $professionalId)) {
            $pdo->rollBack();
            respond(['error' => 'El profesional ya no está disponible.'], 409);
        }

        // Se vuelve a validar la agenda dentro del bloqueo: pudo cambiar mientras
        // el paciente estaba seleccionando la fecha y la hora.
        $duration = duracionDeFranja($pdo, $professionalId, $specialtyId, $weekday, $timeText);
        if ($duration < 1) {
            $pdo->rollBack();
            respond(['error' => 'Ese horario ya no pertenece a la agenda habilitada. Actualizá la disponibilidad.'], 409);
        }

        // Limpieza: bloqueos vencidos y el bloqueo anterior de esta misma sesión
        // (cada sesión mantiene una sola hora temporal a la vez).
        $pdo->prepare('DELETE FROM reservas_horarios_temporales WHERE vence_en <= NOW()')->execute();
        $pdo->prepare('DELETE FROM reservas_horarios_temporales WHERE propietario_sesion = ?')
            ->execute([reservationOwner()]);

        // ¿Se superpone con un turno confirmado?
        $booked = $pdo->prepare(
            "SELECT COUNT(*) FROM turnos
              WHERE profesional_id = ? AND fecha = ? AND estado <> 'Cancelado'
                AND ADDTIME(hora, SEC_TO_TIME(COALESCE(duracion_minutos, 30) * 60)) > ?
                AND hora < ADDTIME(?, SEC_TO_TIME(? * 60))"
        );
        $booked->execute([$professionalId, $dateText, $timeText, $timeText, $duration]);

        // ¿Se superpone con el bloqueo de otra persona?
        $held = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas_horarios_temporales
              WHERE profesional_id = ? AND fecha = ? AND vence_en > NOW()
                AND ADDTIME(hora, SEC_TO_TIME(duracion_minutos * 60)) > ?
                AND hora < ADDTIME(?, SEC_TO_TIME(? * 60))'
        );
        $held->execute([$professionalId, $dateText, $timeText, $timeText, $duration]);

        if ((int) $booked->fetchColumn() > 0) {
            $pdo->rollBack();
            respond(['error' => 'Ese horario acaba de ser ocupado por un turno confirmado. Elegí otro horario.'], 409);
        }
        if ((int) $held->fetchColumn() > 0) {
            $pdo->rollBack();
            respond(['error' => 'Otra persona ya seleccionó ese horario y está completando sus datos. Probá con otro horario o esperá a que se libere.'], 409);
        }

        $insert = $pdo->prepare(
            'INSERT INTO reservas_horarios_temporales
                 (profesional_id, especialidad_id, fecha, hora, duracion_minutos, propietario_sesion, vence_en)
             VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE))'
        );
        $insert->execute([$professionalId, $specialtyId, $dateText, $timeText, $duration, reservationOwner()]);

        $pdo->commit();
        respond(['ok' => true, 'message' => 'Horario reservado temporalmente por ' . MINUTOS_BLOQUEO . ' minutos.']);
    } catch (PDOException $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 23000 = violación de clave única: otra persona tomó el horario justo antes.
        if ($error->getCode() === '23000') {
            respond(['error' => 'Otra persona ya seleccionó ese horario y está completando sus datos. Probá con otro horario o esperá a que se libere.'], 409);
        }
        throw $error;
    }
}

/** POST api.php?action=renovar_horario — extiende el bloqueo propio. */
function accion_renovar_horario(PDO $pdo): void
{
    $q = $pdo->prepare(
        'UPDATE reservas_horarios_temporales
            SET vence_en = DATE_ADD(NOW(), INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE)
          WHERE propietario_sesion = ? AND vence_en > NOW()'
    );
    $q->execute([reservationOwner()]);

    respond(['ok' => true, 'renovada' => $q->rowCount() > 0]);
}

/** POST api.php?action=liberar_horario — suelta el bloqueo propio. */
function accion_liberar_horario(PDO $pdo): void
{
    $pdo->prepare('DELETE FROM reservas_horarios_temporales WHERE propietario_sesion = ?')
        ->execute([reservationOwner()]);

    respond(['ok' => true]);
}

/**
 * POST api.php?action=reservar
 * Cuerpo: name, surname, dni, phone, email, specialty, professional, date, time,
 *         [obra_social, obra_social_otra, reason]
 *
 * TRANSACCIÓN (ACID): paciente + turno + liberación del bloqueo + actividad +
 * notificación se confirman juntos (COMMIT) o no se guarda nada (ROLLBACK).
 */
function accion_reservar(PDO $pdo): void
{
    $data = inputData();

    /* --- 1. Validación de los datos recibidos (todavía sin tocar la base) --- */
    foreach (['name', 'surname', 'dni', 'phone', 'email', 'specialty', 'professional', 'date', 'time'] as $campo) {
        if (empty(trim((string) ($data[$campo] ?? '')))) {
            respond(['error' => 'Completá todos los campos obligatorios.'], 422);
        }
    }
    if (!preg_match('/^[0-9]{7,8}$/', (string) $data['dni'])) {
        respond(['error' => 'El DNI debe contener únicamente números (7 u 8 dígitos).'], 422);
    }
    if (!preg_match('/^[0-9]{8,15}$/', (string) $data['phone'])) {
        respond(['error' => 'El teléfono debe contener únicamente números (entre 8 y 15 dígitos).'], 422);
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Ingresá un correo electrónico válido.'], 422);
    }

    $date = DateTime::createFromFormat('!Y-m-d', $data['date']);
    if (!$date || $date->format('Y-m-d') !== $data['date'] || $date < new DateTime('today')) {
        respond(['error' => 'Elegí una fecha válida, desde hoy en adelante.'], 422);
    }
    if (!DateTime::createFromFormat('!H:i', $data['time'])) {
        respond(['error' => 'Elegí un horario válido.'], 422);
    }

    /* --- 2. Especialidad y profesional deben existir y estar activos --- */
    $q = $pdo->prepare('SELECT id FROM especialidades WHERE nombre = ? AND activa = 1');
    $q->execute([$data['specialty']]);
    $specialtyId = $q->fetchColumn();
    if (!$specialtyId) {
        respond(['error' => 'La especialidad ya no está disponible. Actualizá la página e intentá nuevamente.'], 422);
    }

    $q = $pdo->prepare(
        'SELECT p.id
           FROM profesionales p
           JOIN profesional_especialidades pe ON pe.profesional_id = p.id
          WHERE p.nombre = ? AND pe.especialidad_id = ? AND p.activo = 1
          LIMIT 1'
    );
    $q->execute([$data['professional'], $specialtyId]);
    $professionalId = (int) $q->fetchColumn();
    if (!$professionalId) {
        respond(['error' => 'Elegí un profesional activo vinculado a esa especialidad. No se puede confirmar un turno sin profesional asignado.'], 422);
    }

    /* --- 3. Comienza la transacción --- */
    $pdo->beginTransaction();

    // Bloquear la fila del profesional serializa las solicitudes simultáneas de su agenda.
    if (!bloquearProfesional($pdo, $professionalId)) {
        $pdo->rollBack();
        respond(['error' => 'El profesional ya no está disponible. Actualizá la página e intentá nuevamente.'], 409);
    }

    // La reserva solo se acepta dentro de una franja real configurada para ese profesional y especialidad.
    $weekday = (int) $date->format('N'); // 1 = lunes ... 7 = domingo
    $duration = duracionDeFranja($pdo, $professionalId, (int) $specialtyId, $weekday, $data['time']);
    if ($duration < 1) {
        $pdo->rollBack();
        respond(['error' => 'Ese horario no está dentro de la agenda configurada para este profesional y especialidad. Elegí una franja habilitada.'], 409);
    }

    // No puede superponerse con otro turno del profesional (de cualquier especialidad).
    $busy = $pdo->prepare(
        "SELECT COUNT(*) FROM turnos t
          WHERE t.profesional_id = ? AND t.fecha = ? AND t.estado <> 'Cancelado'
            AND ADDTIME(t.hora, SEC_TO_TIME(COALESCE(t.duracion_minutos, 30) * 60)) > ?
            AND t.hora < ADDTIME(?, SEC_TO_TIME(? * 60))"
    );
    $busy->execute([$professionalId, $data['date'], $data['time'], $data['time'], $duration]);
    if ((int) $busy->fetchColumn() > 0) {
        $pdo->rollBack();
        respond(['error' => 'Ese horario se superpone con otro turno del profesional, aunque sea de otra especialidad. Elegí otro horario.'], 409);
    }

    // La confirmación exige una reserva temporal propia, todavía vigente y del horario exacto.
    // No se permite confirmar si el bloqueo venció, aunque el horario parezca libre al volver a consultar.
    $ownHold = $pdo->prepare(
        'SELECT id FROM reservas_horarios_temporales
          WHERE propietario_sesion = ? AND profesional_id = ? AND especialidad_id = ?
            AND fecha = ? AND hora = ? AND vence_en > NOW()
            FOR UPDATE'
    );
    $ownHold->execute([reservationOwner(), $professionalId, $specialtyId, $data['date'], $data['time']]);
    $ownHoldId = $ownHold->fetchColumn();
    if (!$ownHoldId) {
        $pdo->rollBack();
        respond(['error' => 'Tu reserva temporal venció o ya no está activa. Volvé a elegir un horario disponible para continuar.'], 409);
    }

    // Tampoco puede haber un bloqueo vigente de otra persona sobre ese tramo.
    $otherHold = $pdo->prepare(
        'SELECT COUNT(*) FROM reservas_horarios_temporales
          WHERE profesional_id = ? AND fecha = ? AND propietario_sesion <> ? AND vence_en > NOW()
            AND ADDTIME(hora, SEC_TO_TIME(duracion_minutos * 60)) > ?
            AND hora < ADDTIME(?, SEC_TO_TIME(? * 60))'
    );
    $otherHold->execute([$professionalId, $data['date'], reservationOwner(), $data['time'], $data['time'], $duration]);
    if ((int) $otherHold->fetchColumn() > 0) {
        $pdo->rollBack();
        respond(['error' => 'Otra persona está completando la reserva de ese horario. Elegí otro horario.'], 409);
    }

    /* --- 4. Paciente: se reutiliza si el DNI ya existe, si no se crea --- */
    [$obraSocial, $obraOtra] = validarObraSocial($data, 'Escribí el nombre de la obra social.');
    $obraSocialDb = $obraSocial !== '' ? $obraSocial : null;
    $obraOtraDb = $obraOtra !== '' ? $obraOtra : null;

    $q = $pdo->prepare('SELECT id FROM pacientes WHERE dni = ?');
    $q->execute([$data['dni']]);
    $patientId = $q->fetchColumn();

    if ($patientId) {
        $q = $pdo->prepare('SELECT nombre, apellido FROM pacientes WHERE id = ? FOR UPDATE');
        $q->execute([(int) $patientId]);
        $existing = $q->fetch();

        $normalizar = static fn($valor) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $valor)), 'UTF-8');

        // El nombre y apellido deben coincidir con el registro del DNI existente.
        if (
            !$existing
            || $normalizar($existing['nombre']) !== $normalizar($data['name'])
            || $normalizar($existing['apellido']) !== $normalizar($data['surname'])
        ) {
            $pdo->rollBack();
            respond(['error' => 'Los datos ingresados no coinciden con el registro existente. Verificá tu nombre, apellido y DNI.'], 409);
        }

        $pdo->prepare('UPDATE pacientes SET telefono = ?, email = ?, obra_social = ?, obra_social_otra = ? WHERE id = ?')
            ->execute([$data['phone'], $data['email'], $obraSocialDb, $obraOtraDb, $patientId]);
    } else {
        $pdo->prepare(
            'INSERT INTO pacientes (nombre, apellido, dni, telefono, email, obra_social, obra_social_otra)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$data['name'], $data['surname'], $data['dni'], $data['phone'], $data['email'], $obraSocialDb, $obraOtraDb]);
        $patientId = (int) $pdo->lastInsertId();
    }

    /* --- 5. Turno, liberación del bloqueo, actividad y notificación --- */
    $pdo->prepare(
        "INSERT INTO turnos (paciente_id, especialidad_id, profesional_id, fecha, hora, duracion_minutos, motivo, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmado')"
    )->execute([
        $patientId, $specialtyId, $professionalId, $data['date'], $data['time'],
        $duration, trim((string) ($data['reason'] ?? '')),
    ]);
    $appointmentId = (int) $pdo->lastInsertId();

    $pdo->prepare('DELETE FROM reservas_horarios_temporales WHERE id = ? AND propietario_sesion = ?')
        ->execute([(int) $ownHoldId, reservationOwner()]);

    registrarActividad($pdo, 'Solicitud de turno #' . $appointmentId . ' recibida');

    $pdo->prepare("INSERT INTO notificaciones (turno_id, tipo, mensaje) VALUES (?, 'nuevo_turno', ?)")
        ->execute([
            $appointmentId,
            'Nueva solicitud de turno: ' . trim($data['name'] . ' ' . $data['surname'])
                . ' · ' . $data['specialty'] . ' · ' . $data['date'] . ' ' . $data['time'],
        ]);

    $pdo->commit(); // Todo o nada: hasta acá nada quedó guardado definitivamente.

    // Token aleatorio para que el paciente pueda cancelar este turno desde esta misma sesión.
    $cancelToken = bin2hex(random_bytes(24));
    $_SESSION['turnos_cancelables'][$appointmentId] = password_hash($cancelToken, PASSWORD_DEFAULT);

    respond(['ok' => true, 'id' => $appointmentId, 'cancel_token' => $cancelToken], 201);
}

/**
 * POST api.php?action=cancelar_turno   cuerpo: {id, token}
 * El paciente solo puede cancelar un turno que reservó en esta misma sesión.
 */
function accion_cancelar_turno(PDO $pdo): void
{
    $data = inputData();
    $id = (int) ($data['id'] ?? 0);
    $token = (string) ($data['token'] ?? '');
    $savedHash = $_SESSION['turnos_cancelables'][$id] ?? '';

    if (!$id || !$savedHash || !$token || !password_verify($token, $savedHash)) {
        respond(['error' => 'No se pudo verificar que este turno te pertenece. Para cancelar desde otro dispositivo se necesita una verificación adicional.'], 403);
    }

    try {
        $pdo->beginTransaction();

        $q = $pdo->prepare('SELECT estado FROM turnos WHERE id = ? FOR UPDATE');
        $q->execute([$id]);
        $status = $q->fetchColumn();

        if (!$status) {
            $pdo->rollBack();
            respond(['error' => 'No se encontró el turno.'], 404);
        }
        if ($status === 'Cancelado') {
            $pdo->rollBack();
            respond(['error' => 'Este turno ya está cancelado.'], 409);
        }

        // Este UPDATE dispara el trigger trg_auditar_estado_turno.
        $pdo->prepare("UPDATE turnos SET estado = 'Cancelado' WHERE id = ?")->execute([$id]);

        notificarTurnoCancelado($pdo, $id);
        registrarActividad($pdo, 'El paciente canceló el turno #' . $id);

        $pdo->commit();
        unset($_SESSION['turnos_cancelables'][$id]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    respond(['ok' => true, 'message' => 'Tu turno fue cancelado correctamente.']);
}
