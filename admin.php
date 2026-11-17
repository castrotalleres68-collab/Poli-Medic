<?php
/**
 * Panel administrativo.
 *
 *   - Sin sesión: muestra el formulario de ingreso (app/views/admin/login.php).
 *   - Con sesión: muestra el panel (app/views/admin/panel.php), que se comunica
 *     con api.php desde assets/js/admin.js.
 *   - ?salir=1 o POST salir: cierra la sesión.
 */
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require __DIR__ . '/app/config.php';

$error = '';

/* ---------------------------- Cerrar sesión ---------------------------- */
if (isset($_POST['salir']) || isset($_GET['salir'])) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );
    }

    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ------------------------- Intento de ingreso -------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    $q = $pdo->prepare('SELECT id, clave_hash FROM administradores WHERE usuario = ?');
    $q->execute([$usuario]);
    $admin = $q->fetch();

    // La contraseña se guarda como hash (password_hash) y se compara con password_verify.
    if ($admin && password_verify($clave, $admin['clave_hash'])) {
        session_regenerate_id(true);

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_usuario'] = $usuario;

        header('Location: admin.php');
        exit;
    }

    $error = 'Usuario o contraseña incorrectos.';
}

/* ------------------------------ Vistas --------------------------------- */
if (empty($_SESSION['admin_id'])) {
    require __DIR__ . '/app/views/admin/login.php';
    exit;
}

$adminUser = htmlspecialchars(
    (string) ($_SESSION['admin_usuario'] ?? 'Administración'),
    ENT_QUOTES,
    'UTF-8'
);

require __DIR__ . '/app/views/admin/panel.php';
