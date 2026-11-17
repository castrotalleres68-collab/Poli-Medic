<?php
/**
 * Genera el hash de una contraseña para el administrador y muestra la sentencia
 * SQL para guardarla. Se usa desde la consola (no desde el navegador):
 *
 *   C:\xampp\php\php.exe tools\generar_hash_clave.php MiNuevaClave
 *
 * Luego pegá la sentencia en phpMyAdmin (pestaña SQL) con la base seleccionada.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo se puede ejecutar desde la consola.');
}

$clave = $argv[1] ?? '';
if (strlen($clave) < 8) {
    exit("Uso: php tools/generar_hash_clave.php CLAVE   (mínimo 8 caracteres)\n");
}

$hash = password_hash($clave, PASSWORD_DEFAULT);

echo "UPDATE administradores SET clave_hash = '" . $hash . "' WHERE usuario = 'admin';\n";
