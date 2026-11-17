-- Poli Medic - demostración controlada de COMMIT y ROLLBACK.
-- Usa la tabla actividad existente y limpia sus propias filas al finalizar.
-- No modifica pacientes, profesionales ni turnos reales.
USE centro_medico_salud;

SET @id_prueba_bdii = REPLACE(UUID(), '-', '');
SET @marca_rollback = CONCAT('BDII-ROLLBACK-', @id_prueba_bdii);
SET @marca_commit = CONCAT('BDII-COMMIT-', @id_prueba_bdii);

-- PRUEBA 1: ROLLBACK debe deshacer la inserción.
START TRANSACTION;
INSERT INTO actividad (descripcion) VALUES (@marca_rollback);
SELECT id, descripcion, creado_en
FROM actividad
WHERE descripcion = @marca_rollback;
ROLLBACK;

-- Resultado esperado: cero filas.
SELECT COUNT(*) AS filas_despues_de_rollback
FROM actividad
WHERE descripcion = @marca_rollback;

-- PRUEBA 2: COMMIT debe confirmar la inserción.
START TRANSACTION;
INSERT INTO actividad (descripcion) VALUES (@marca_commit);
SELECT id, descripcion, creado_en
FROM actividad
WHERE descripcion = @marca_commit;
COMMIT;

-- Resultado esperado: una fila confirmada.
SELECT COUNT(*) AS filas_despues_de_commit
FROM actividad
WHERE descripcion = @marca_commit;

-- Limpieza: la fila ya fue observada después del COMMIT; se retira para no
-- dejar datos de prueba. La limpieza es otra transacción confirmada.
START TRANSACTION;
DELETE FROM actividad
WHERE descripcion IN (@marca_rollback, @marca_commit);
COMMIT;

-- Resultado esperado al finalizar: cero filas de prueba.
SELECT COUNT(*) AS filas_de_prueba_restantes
FROM actividad
WHERE descripcion IN (@marca_rollback, @marca_commit);
