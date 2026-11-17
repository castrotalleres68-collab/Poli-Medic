-- Poli Medic - asignación de roles a cuentas ya existentes.
-- EJECUTAR SOLO después de verificar que las dos cuentas existen y que sus
-- credenciales son conocidas por la persona administradora del servidor.
-- Este archivo no crea cuentas ni cambia contraseñas.

GRANT 'rol_polimedic_app' TO 'polimedic_app'@'localhost';
SET DEFAULT ROLE 'rol_polimedic_app' FOR 'polimedic_app'@'localhost';

GRANT 'rol_polimedic_consulta' TO 'polimedic_consulta'@'localhost';
SET DEFAULT ROLE 'rol_polimedic_consulta' FOR 'polimedic_consulta'@'localhost';

-- Ejecutar después, de a una cuenta, en una sesión con permisos suficientes:
-- SHOW GRANTS FOR 'polimedic_app'@'localhost';
-- SHOW GRANTS FOR 'polimedic_consulta'@'localhost';
