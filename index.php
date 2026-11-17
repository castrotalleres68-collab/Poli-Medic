<?php
/**
 * Portal de pacientes (página pública).
 *
 * Solo arma la página: cada bloque visual vive en app/views/portal/ y el
 * comportamiento (reservar, "Mi Turno", tema claro/oscuro) en assets/js/cliente.js.
 * Los datos reales llegan desde api.php.
 */
$vistas = __DIR__ . '/app/views/portal/';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Poli Medic — Policonsultorios médicos</title>
    <link rel="stylesheet" href="assets/css/cliente.css">
</head>
<body>
<div class="app" id="app">
    <div class="topline"></div>
    <div class="wrap">
        <?php require $vistas . 'cabecera.php'; ?>
        <?php require $vistas . 'inicio.php'; ?>
        <?php require $vistas . 'reserva.php'; ?>
        <?php require $vistas . 'mi_turno.php'; ?>
        <?php require $vistas . 'modal_salir.php'; ?>
        <?php require $vistas . 'pie.php'; ?>
    </div>
</div>
<script src="assets/js/cliente.js" defer></script>
</body>
</html>
