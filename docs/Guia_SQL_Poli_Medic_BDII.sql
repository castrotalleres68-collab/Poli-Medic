-- POLI MEDIC - GUIA SQL PARA LA ENTREGA DE BASE DE DATOS II
-- Base esperada: centro_medico_salud
-- IMPORTANTE:
-- 1) Este archivo no crea ni elimina tablas, usuarios, roles ni triggers.
-- 2) Las consultas SHOW/SELECT son de inspección.
-- 3) Las pruebas de escritura deben ejecutarse en una copia o en un momento controlado.
-- 4) No utilizar datos personales reales para la demostración.

USE centro_medico_salud;

-- A. INSPECCIÓN DE ESTRUCTURA (solo lectura)
SHOW TABLES;

SELECT TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'centro_medico_salud'
ORDER BY TABLE_NAME;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, COLUMN_KEY, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'centro_medico_salud'
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME,
       REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'centro_medico_salud'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- B. INSPECCIÓN DEL TRIGGER (solo lectura)
SHOW TRIGGERS FROM centro_medico_salud LIKE 'turnos';
SHOW CREATE TRIGGER trg_auditar_estado_turno;

SELECT id, turno_id, estado_anterior, estado_nuevo,
       cambiado_en, usuario_mysql
FROM auditoria_estados_turnos
ORDER BY id DESC;

-- C. INSPECCIÓN DE DCL (solo lectura; algunas consultas pueden requerir permisos)
SHOW GRANTS FOR 'polimedic_app'@'localhost';
SHOW GRANTS FOR 'polimedic_consulta'@'localhost';

-- D. CONSULTAS DE EJEMPLO DML (solo lectura)
SELECT id, nombre, descripcion
FROM especialidades
WHERE activa = 1
ORDER BY nombre;

SELECT t.id, p.nombre, p.apellido, e.nombre AS especialidad,
       t.fecha, t.hora, t.estado
FROM turnos AS t
JOIN pacientes AS p ON p.id = t.paciente_id
JOIN especialidades AS e ON e.id = t.especialidad_id
ORDER BY t.fecha, t.hora;

SELECT estado, COUNT(*) AS cantidad
FROM turnos
GROUP BY estado;

-- E. PRUEBA DE COMMIT CON LA TABLA actividad
-- Ejecutar en entorno de prueba. Verificar el resultado antes de continuar.
START TRANSACTION;
INSERT INTO actividad (descripcion)
VALUES ('PRUEBA_ACADEMICA_COMMIT_PoliMedic');
SELECT * FROM actividad
WHERE descripcion = 'PRUEBA_ACADEMICA_COMMIT_PoliMedic';
COMMIT;

-- Ejecutar esta consulta por separado para demostrar persistencia:
SELECT * FROM actividad
WHERE descripcion = 'PRUEBA_ACADEMICA_COMMIT_PoliMedic';

-- Opcional: después de capturar evidencia, retirar únicamente el registro de prueba.
-- No ejecutar hasta haber guardado la captura.
-- DELETE FROM actividad
-- WHERE descripcion = 'PRUEBA_ACADEMICA_COMMIT_PoliMedic';

-- F. PRUEBA DE ROLLBACK
START TRANSACTION;
INSERT INTO actividad (descripcion)
VALUES ('PRUEBA_ACADEMICA_ROLLBACK_PoliMedic');
SELECT * FROM actividad
WHERE descripcion = 'PRUEBA_ACADEMICA_ROLLBACK_PoliMedic';
ROLLBACK;

-- Resultado esperado: 0 filas.
SELECT COUNT(*) AS registros_tras_rollback
FROM actividad
WHERE descripcion = 'PRUEBA_ACADEMICA_ROLLBACK_PoliMedic';

-- G. PRUEBA CONTROLADA DEL TRIGGER: LEER ANTES DE MODIFICAR
-- NO ejecutar automáticamente sobre un turno real.
-- Recomendación: utilizar una copia de la base o un turno de prueba dedicado.
-- Si el grupo decide probar con un turno existente, detener el sistema,
-- comprobar el estado actual, iniciar una transacción, actualizar un único ID,
-- consultar auditoria_estados_turnos y finalizar con ROLLBACK.
--
-- Ejemplo ilustrativo, requiere elegir y verificar un ID de prueba:
-- START TRANSACTION;
-- SELECT id, estado FROM turnos WHERE id = <ID_DE_PRUEBA> FOR UPDATE;
-- UPDATE turnos SET estado = 'Cancelado' WHERE id = <ID_DE_PRUEBA>
--   AND estado = 'Confirmado';
-- SELECT id, turno_id, estado_anterior, estado_nuevo, cambiado_en
-- FROM auditoria_estados_turnos
-- WHERE turno_id = <ID_DE_PRUEBA>
-- ORDER BY id DESC;
-- ROLLBACK;
--
-- Tras ROLLBACK, comprobar que el estado original y la tabla de auditoría
-- no contienen el cambio temporal. Ejecutar solo si se entiende la prueba
-- y las tablas involucradas utilizan un motor transaccional.

-- H. MODELO DCL ILUSTRATIVO; NO EJECUTAR SIN AUDITAR USUARIOS Y ROLES
-- No se conoce por este volcado el nombre exacto de los roles existentes.
-- Comprobar primero SHOW GRANTS y la sección Usuarios de phpMyAdmin.
--
-- CREATE ROLE polimedic_lectura;
-- GRANT SELECT ON centro_medico_salud.* TO polimedic_lectura;
-- GRANT polimedic_lectura TO 'polimedic_consulta'@'localhost';
-- SET DEFAULT ROLE polimedic_lectura FOR 'polimedic_consulta'@'localhost';
--
-- Este ejemplo no prueba que el rol exista. No crear duplicados ni conceder
-- ALL PRIVILEGES por comodidad. Adaptar permisos a la función real de cada cuenta.
