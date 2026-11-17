<?php
/**
 * API del panel administrativo — especialidades.
 * Exige sesión de administrador (lo controla api.php).
 *
 *   agregar_especialidad    Alta.
 *   editar_especialidad     Modificación.
 *   eliminar_especialidad   Baja lógica (activa = 0) para conservar el historial.
 */

/** POST api.php?action=agregar_especialidad */
function accion_agregar_especialidad(PDO $pdo): void
{
    $d = inputData();
    $nombre = trim((string) ($d['nombre'] ?? ''));

    if ($nombre === '') {
        respond(['error' => 'El nombre es obligatorio.'], 422);
    }

    $pdo->prepare('INSERT INTO especialidades (nombre, descripcion) VALUES (?, ?)')
        ->execute([$nombre, trim((string) ($d['descripcion'] ?? ''))]);

    registrarActividad($pdo, 'Especialidad agregada: ' . $nombre);
    respond(['ok' => true], 201);
}

/** POST api.php?action=editar_especialidad */
function accion_editar_especialidad(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);
    $nombre = trim((string) ($d['nombre'] ?? ''));

    if (!$id || $nombre === '') {
        respond(['error' => 'El nombre de la especialidad es obligatorio.'], 422);
    }

    $pdo->prepare('UPDATE especialidades SET nombre = ?, descripcion = ? WHERE id = ? AND activa = 1')
        ->execute([$nombre, trim((string) ($d['descripcion'] ?? '')), $id]);

    $check = $pdo->prepare('SELECT id FROM especialidades WHERE id = ? AND activa = 1');
    $check->execute([$id]);
    if (!$check->fetchColumn()) {
        respond(['error' => 'No se encontró la especialidad.'], 404);
    }

    registrarActividad($pdo, 'Especialidad actualizada: ' . $nombre);
    respond(['ok' => true]);
}

/** POST api.php?action=eliminar_especialidad */
function accion_eliminar_especialidad(PDO $pdo): void
{
    $d = inputData();
    $id = (int) ($d['id'] ?? 0);

    if (!$id) {
        respond(['error' => 'Elegí una especialidad válida.'], 422);
    }

    // Se desactiva en lugar de borrar físicamente para conservar el historial de turnos.
    $q = $pdo->prepare('UPDATE especialidades SET activa = 0 WHERE id = ? AND activa = 1');
    $q->execute([$id]);
    if (!$q->rowCount()) {
        respond(['error' => 'No se encontró la especialidad o ya estaba desactivada.'], 404);
    }

    // Los profesionales que se quedan sin ninguna especialidad también se desactivan.
    $pdo->prepare('DELETE FROM profesional_especialidades WHERE especialidad_id = ?')->execute([$id]);
    $pdo->exec(
        'UPDATE profesionales p SET p.activo = 0
          WHERE NOT EXISTS (SELECT 1 FROM profesional_especialidades pe WHERE pe.profesional_id = p.id)'
    );

    registrarActividad($pdo, 'Especialidad desactivada #' . $id);
    respond(['ok' => true]);
}
