<?php
/**
 * API del panel administrativo — pacientes.
 * Exige sesión de administrador (lo controla api.php).
 *
 *   agregar_paciente   Alta.
 *   editar_paciente    Modificación.
 *   eliminar_paciente  Baja (solo si no tiene turnos, para conservar el historial).
 */

/** POST api.php?action=agregar_paciente */
function accion_agregar_paciente(PDO $pdo): void
{
    $d = inputData();

    validarDatosPaciente($d);
    [$obra, $otra] = validarObraSocial($d, 'Indicá el nombre de la obra social elegida en “Otras”.');

    $pdo->prepare(
        'INSERT INTO pacientes (nombre, apellido, dni, telefono, email, obra_social, obra_social_otra)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        trim($d['nombre']),
        trim($d['apellido']),
        trim($d['dni']),
        trim($d['telefono'] ?? ''),
        trim($d['email'] ?? ''),
        $obra !== '' ? $obra : null,
        $otra !== '' ? $otra : null,
    ]);

    registrarActividad($pdo, 'Paciente agregado: ' . trim($d['nombre']) . ' ' . trim($d['apellido']));
    respond(['ok' => true], 201);
}

/** POST api.php?action=editar_paciente */
function accion_editar_paciente(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);

    validarDatosPaciente($d);
    [$obra, $otra] = validarObraSocial($d, 'Indicá el nombre de la obra social elegida en “Otras”.');

    $q = $pdo->prepare(
        'UPDATE pacientes
            SET nombre = ?, apellido = ?, dni = ?, telefono = ?, email = ?, obra_social = ?, obra_social_otra = ?
          WHERE id = ?'
    );
    $q->execute([
        trim($d['nombre']),
        trim($d['apellido']),
        trim($d['dni']),
        trim((string) ($d['telefono'] ?? '')),
        trim((string) ($d['email'] ?? '')),
        $obra !== '' ? $obra : null,
        $otra !== '' ? $otra : null,
        $id,
    ]);

    // rowCount() es 0 si no cambió nada; se distingue de "el paciente no existe".
    if (!$q->rowCount()) {
        $check = $pdo->prepare('SELECT id FROM pacientes WHERE id = ?');
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            respond(['error' => 'No se encontró el paciente.'], 404);
        }
    }

    registrarActividad($pdo, 'Datos de paciente actualizados #' . $id);
    respond(['ok' => true]);
}

/** POST api.php?action=eliminar_paciente */
function accion_eliminar_paciente(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);

    $q = $pdo->prepare('SELECT id FROM pacientes WHERE id = ?');
    $q->execute([$id]);
    if (!$q->fetchColumn()) {
        respond(['error' => 'No se encontró el paciente.'], 404);
    }

    $q = $pdo->prepare('SELECT COUNT(*) FROM turnos WHERE paciente_id = ?');
    $q->execute([$id]);
    if ((int) $q->fetchColumn() > 0) {
        respond(['error' => 'No se puede eliminar porque el paciente tiene turnos asociados. Conservá su historial y modificá sus datos si es necesario.'], 409);
    }

    $pdo->prepare('DELETE FROM pacientes WHERE id = ?')->execute([$id]);
    registrarActividad($pdo, 'Paciente eliminado #' . $id);
    respond(['ok' => true]);
}
