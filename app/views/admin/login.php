<?php
/**
 * Formulario de ingreso al panel. Variable disponible: $error (texto o '').
 * Los estilos están en assets/css/login.css.
 */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso administrativo | Poli Medic</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <main class="login">
        <div class="eyebrow">Poli Medic</div>
        <h1>Ingreso administrativo</h1>
        <p>Ingresá con las credenciales del panel de administración.</p>

        <?php if ($error): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="post">
            <label for="usuario">Usuario</label>
            <input
                id="usuario"
                name="usuario"
                autocomplete="username"
                required
            >

            <label for="clave">Contraseña</label>
            <input
                id="clave"
                name="clave"
                type="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Ingresar al panel</button>
        </form>

        <a class="back" href="index.php">← Volver al portal del cliente</a>
    </main>
</body>
</html>
