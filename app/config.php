<?php
/**
 * Conexión a MySQL compartida por el portal, el panel y la API.
 *
 * Valores por defecto (XAMPP local): localhost, centro_medico_salud, root, sin contraseña.
 *
 * Para usar otra cuenta SIN escribir la contraseña en el código hay dos opciones:
 *   a) Crear app/config.local.php (ver app/config.local.ejemplo.php). Ese archivo
 *      tiene prioridad y no se sube a ningún repositorio.
 *   b) Definir las variables de entorno POLIMEDIC_DB_HOST, POLIMEDIC_DB_NAME,
 *      POLIMEDIC_DB_USER y POLIMEDIC_DB_PASSWORD.
 */

/** Indica si la página se abrió desde esta misma computadora (localhost). */
function esServidorLocal(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

    return in_array($host, ['localhost', '127.0.0.1', 'localhost:80', '127.0.0.1:80'], true);
}

// 1) Valores por defecto.
$configuracion = [
    'host'     => 'localhost',
    'database' => 'centro_medico_salud',
    'user'     => 'root',
    'password' => '',
];

// 2) Variables de entorno (si existen).
$entorno = [
    'host'     => getenv('POLIMEDIC_DB_HOST'),
    'database' => getenv('POLIMEDIC_DB_NAME'),
    'user'     => getenv('POLIMEDIC_DB_USER'),
    'password' => getenv('POLIMEDIC_DB_PASSWORD'),
];
foreach ($entorno as $clave => $valor) {
    if ($valor !== false && ($valor !== '' || $clave === 'password')) {
        $configuracion[$clave] = $valor;
    }
}

// 3) Archivo local opcional (tiene la máxima prioridad).
if (is_file(__DIR__ . '/config.local.php')) {
    $configuracion = array_merge($configuracion, (array) require __DIR__ . '/config.local.php');
}

try {
    $pdo = new PDO(
        "mysql:host={$configuracion['host']};dbname={$configuracion['database']};charset=utf8mb4",
        $configuracion['user'],
        $configuracion['password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $error) {
    http_response_code(500);

    if (esServidorLocal()) {
        $baseSegura = htmlspecialchars($configuracion['database'], ENT_QUOTES, 'UTF-8');
        $errorSeguro = htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');

        exit(
            'No se pudo conectar con MySQL para la base de datos ' . $baseSegura
            . '. Detalle técnico: ' . $errorSeguro
            . '. Revisá que MySQL esté iniciado y que el usuario y la contraseña '
            . 'coincidan con la configuración de la conexión.'
        );
    }

    exit('No se pudo conectar con MySQL. Revisá la configuración de la base de datos.');
}
