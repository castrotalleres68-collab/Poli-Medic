-- Poli Medic - consultas de verificación DCL.
-- Las consultas sobre mysql.user requieren permisos de lectura administrativos.
-- Ejecutar SHOW GRANTS solo para cuentas que se haya confirmado que existen.

SELECT User, Host
FROM mysql.user
WHERE User IN ('polimedic_app', 'polimedic_consulta')
ORDER BY User, Host;

-- Ejecutar por separado, después de confirmar que la cuenta existe:
-- SHOW GRANTS FOR 'polimedic_app'@'localhost';
-- SHOW GRANTS FOR 'polimedic_consulta'@'localhost';
-- SHOW GRANTS FOR 'rol_polimedic_app';
-- SHOW GRANTS FOR 'rol_polimedic_consulta';
