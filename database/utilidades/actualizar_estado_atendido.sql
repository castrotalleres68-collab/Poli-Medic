-- Poli Medic - revisión de turnos pasados que siguen Confirmados.
-- Por seguridad, este archivo NO modifica turnos al ejecutarse.
-- Primero revisá el listado. El cambio de estado puede hacerse desde el panel
-- administrativo o habilitando explícitamente el bloque de actualización inferior.
USE centro_medico_salud;

SELECT
    id,
    paciente_id,
    profesional_id,
    fecha,
    hora,
    estado
FROM turnos
WHERE estado = 'Confirmado'
  AND fecha < CURDATE()
ORDER BY fecha, hora, id;

-- ACTUALIZACIÓN OPCIONAL EN LOTE (no ejecutar sin revisar la lista anterior):
-- 1. Quitá los comentarios del bloque completo.
-- 2. Ejecutalo en una sesión administrativa después de confirmar los turnos.
-- 3. El trigger trg_auditar_estado_turno registrará cada cambio de estado.
-- START TRANSACTION;
-- UPDATE turnos
-- SET estado = 'Atendido'
-- WHERE estado = 'Confirmado'
--   AND fecha < CURDATE();
-- SELECT ROW_COUNT() AS turnos_actualizados;
-- COMMIT;
