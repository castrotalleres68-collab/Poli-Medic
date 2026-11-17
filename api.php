<?php
/**
 * Punto de entrada de la API (JSON). El portal (assets/js/cliente.js) y el
 * panel (assets/js/admin.js) la llaman con api.php?action=NOMBRE.
 *
 * Este archivo solo decide qué función ejecutar. La lógica de cada acción
 * está en app/api/ y las funciones compartidas en app/helpers.php.
 *
 *   Públicas ........ catalogo.php, mi_turno.php, reservas.php
 *   Administrador ... admin_panel.php, admin_pacientes.php,
 *                     admin_especialidades.php, admin_profesionales.php
 */
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require __DIR__ . '/app/config.php';
require __DIR__ . '/app/helpers.php';

foreach (
    [
        'catalogo', 'mi_turno', 'reservas',
        'admin_panel', 'admin_pacientes', 'admin_especialidades', 'admin_profesionales',
    ] as $modulo
) {
    require __DIR__ . '/app/api/' . $modulo . '.php';
}

/*
 * Tabla de rutas: 'accion' => [método HTTP exigido (null = cualquiera), función].
 */
$rutasPublicas = [
    'catalogos'         => [null,   'accion_catalogos'],
    'dias_atencion'     => [null,   'accion_dias_atencion'],
    'disponibilidad'    => [null,   'accion_disponibilidad'],
    'mi_turno'          => ['GET',  'accion_mi_turno'],
    'mi_turno_cancelar' => ['POST', 'accion_mi_turno_cancelar'],
    'tomar_horario'     => ['POST', 'accion_tomar_horario'],
    'renovar_horario'   => ['POST', 'accion_renovar_horario'],
    'liberar_horario'   => ['POST', 'accion_liberar_horario'],
    'reservar'          => ['POST', 'accion_reservar'],
    'cancelar_turno'    => ['POST', 'accion_cancelar_turno'],
];

$rutasAdmin = [
    'agenda_profesional'          => [null,   'accion_agenda_profesional'],
    'editar_agenda_profesional'   => ['POST', 'accion_editar_agenda_profesional'],
    'admin_data'                  => [null,   'accion_admin_data'],
    'marcar_notificacion'         => ['POST', 'accion_marcar_notificacion'],
    'marcar_todas_notificaciones' => ['POST', 'accion_marcar_todas_notificaciones'],
    'cambiar_estado'              => ['POST', 'accion_cambiar_estado'],
    'agregar_paciente'            => ['POST', 'accion_agregar_paciente'],
    'editar_paciente'             => ['POST', 'accion_editar_paciente'],
    'eliminar_paciente'           => ['POST', 'accion_eliminar_paciente'],
    'agregar_especialidad'        => ['POST', 'accion_agregar_especialidad'],
    'editar_especialidad'         => ['POST', 'accion_editar_especialidad'],
    'eliminar_especialidad'       => ['POST', 'accion_eliminar_especialidad'],
    'agregar_profesional'         => ['POST', 'accion_agregar_profesional'],
    'eliminar_profesional'        => ['POST', 'accion_eliminar_profesional'],
];

/** Ejecuta la acción si existe en la tabla y el método HTTP coincide. */
function ejecutarRuta(array $rutas, string $accion, string $metodo, PDO $pdo): void
{
    $ruta = $rutas[$accion] ?? null;

    if ($ruta !== null && ($ruta[0] === null || $ruta[0] === $metodo)) {
        $ruta[1]($pdo); // cada acción termina con respond(), que finaliza el script
    }
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // 1) Acciones públicas (portal de pacientes).
    ejecutarRuta($rutasPublicas, $action, $method, $pdo);

    // 2) Desde acá todo requiere sesión de administrador.
    requireAdmin();
    ejecutarRuta($rutasAdmin, $action, $method, $pdo);

    respond(['error' => 'Acción no reconocida.'], 404);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 23000 = violación de restricción (por ejemplo DNI o nombre duplicado).
    if ($error->getCode() === '23000') {
        respond(['error' => 'Ya existe un registro con esos datos o ese horario.'], 409);
    }

    $message = 'No se pudo guardar en la base de datos. Revisá la instalación y las migraciones SQL.';
    if (esServidorLocal()) {
        $message .= ' Detalle técnico: ' . $error->getMessage();
    }
    respond(['error' => $message], 500);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(['error' => 'No se pudo completar la operación.'], 500);
}
