-- Poli Medic - prueba controlada del trigger de auditoría.
-- Crea un paciente y un turno de prueba identificables, verifica el trigger y
-- elimina únicamente esos datos de prueba. No modifica turnos reales.
-- Ejecutar con una cuenta que pueda insertar/borrar en pacientes y turnos.
USE centro_medico_salud;

-- Confirmar que existen al menos una especialidad y la tabla de auditoría.
SELECT COUNT(*) AS especialidades_disponibles FROM especialidades;
SHOW CREATE TABLE auditoria_estados_turnos;
SHOW CREATE TRIGGER trg_auditar_estado_turno;

SET @dni_prueba_trigger = CONCAT('T', LEFT(REPLACE(UUID(), '-', ''), 15));
SET @id_especialidad_prueba = (SELECT id FROM especialidades ORDER BY id LIMIT 1);

-- Si no hay especialidades, no continúes: el turno necesita una especialidad válida.
START TRANSACTION;
INSERT INTO pacientes (nombre, apellido, dni, telefono, email)
VALUES ('PRUEBA BDII', 'TRIGGER TEMPORAL', @dni_prueba_trigger, '', '');
SET @id_paciente_prueba = LAST_INSERT_ID();

INSERT INTO turnos (
    paciente_id,
    especialidad_id,
    profesional_id,
    fecha,
    hora,
    duracion_minutos,
    motivo,
    estado
)
VALUES (
    @id_paciente_prueba,
    @id_especialidad_prueba,
    NULL,
    DATE_ADD(CURDATE(), INTERVAL 365 DAY),
    '23:00:00',
    30,
    'Registro temporal para probar el trigger de Base de Datos II',
    'Confirmado'
);
SET @id_turno_prueba = LAST_INSERT_ID();

-- Primer cambio: debe insertar una fila en la auditoría.
UPDATE turnos
SET estado = 'Cancelado'
WHERE id = @id_turno_prueba;

SELECT
    turno_id,
    estado_anterior,
    estado_nuevo,
    usuario_mysql,
    cambiado_en
FROM auditoria_estados_turnos
WHERE turno_id = @id_turno_prueba
ORDER BY id;

-- Actualizar al mismo estado no debe generar otra fila.
UPDATE turnos
SET estado = 'Cancelado'
WHERE id = @id_turno_prueba;

SELECT
    COUNT(*) AS cantidad_eventos_auditados,
    SUM(estado_anterior = 'Confirmado' AND estado_nuevo = 'Cancelado') AS cambios_correctos
FROM auditoria_estados_turnos
WHERE turno_id = @id_turno_prueba;
-- Resultado esperado: cantidad_eventos_auditados = 1 y cambios_correctos = 1.

-- Limpieza limitada al turno creado por este script. Se elimina primero la
-- auditoría de prueba porque el historial académico real debe conservarse.
DELETE FROM auditoria_estados_turnos WHERE turno_id = @id_turno_prueba;
DELETE FROM turnos WHERE id = @id_turno_prueba;
DELETE FROM pacientes WHERE id = @id_paciente_prueba;
COMMIT;

SELECT COUNT(*) AS turnos_de_prueba_restantes
FROM turnos
WHERE id = @id_turno_prueba;
SELECT COUNT(*) AS pacientes_de_prueba_restantes
FROM pacientes
WHERE id = @id_paciente_prueba;
