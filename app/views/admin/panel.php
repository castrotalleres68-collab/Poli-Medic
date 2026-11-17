<?php
/**
 * Panel administrativo (se muestra solo con sesión iniciada).
 * Variable disponible: $adminUser (nombre de usuario ya escapado para HTML).
 *
 * Cada bloque está en su propio archivo:
 *   barra_lateral.php, barra_superior.php, paginas/*.php,
 *   notificaciones.php, modal_registro.php
 * El comportamiento está en assets/js/admin.js.
 */
$vistas = __DIR__ . '/';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Poli Medic — Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body data-admin-user="<?= $adminUser ?>">
<div class="app">
    <?php require $vistas . 'barra_lateral.php'; ?>
    <main class="main">
        <?php require $vistas . 'barra_superior.php'; ?>
        <div class="content">
            <?php require $vistas . 'paginas/dashboard.php'; ?>
            <?php require $vistas . 'paginas/turnos.php'; ?>
            <?php require $vistas . 'paginas/pacientes.php'; ?>
            <?php require $vistas . 'paginas/profesionales.php'; ?>
            <?php require $vistas . 'paginas/especialidades.php'; ?>
            <?php require $vistas . 'paginas/actividad.php'; ?>
        </div>
    </main>
    <?php require $vistas . 'notificaciones.php'; ?>
    <?php require $vistas . 'modal_registro.php'; ?>
    <div class="toast" id="toast" role="status"></div>
</div>
<script src="assets/js/admin.js" defer></script>
</body>
</html>
