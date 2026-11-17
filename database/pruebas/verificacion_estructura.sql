-- Poli Medic - consultas de diagnóstico de solo lectura.
-- No crea, modifica ni elimina datos. Selecciona la base existente.
USE centro_medico_salud;

-- 1. Tablas y motores.
SELECT
    table_name AS tabla,
    engine AS motor,
    table_rows AS filas_estimadas
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_type = 'BASE TABLE'
ORDER BY table_name;

-- 2. Columnas, tipos, nulabilidad y valores predeterminados.
SELECT
    table_name AS tabla,
    ordinal_position AS posicion,
    column_name AS columna,
    column_type AS tipo,
    is_nullable AS admite_null,
    column_default AS valor_predeterminado,
    extra
FROM information_schema.columns
WHERE table_schema = DATABASE()
ORDER BY table_name, ordinal_position;

-- 3. Índices y claves únicas.
SELECT
    table_name AS tabla,
    index_name AS indice,
    non_unique AS no_unico,
    seq_in_index AS posicion,
    column_name AS columna
FROM information_schema.statistics
WHERE table_schema = DATABASE()
ORDER BY table_name, index_name, seq_in_index;

-- 4. Claves foráneas declaradas.
SELECT
    table_name AS tabla_origen,
    column_name AS columna_origen,
    constraint_name AS restriccion,
    referenced_table_name AS tabla_referenciada,
    referenced_column_name AS columna_referenciada
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE()
  AND referenced_table_name IS NOT NULL
ORDER BY table_name, constraint_name, ordinal_position;

-- 5. Restricciones CHECK.
SELECT
    table_name AS tabla,
    constraint_name AS restriccion,
    check_clause AS condicion
FROM information_schema.check_constraints
WHERE constraint_schema = DATABASE()
ORDER BY table_name, constraint_name;

-- 6. Trigger instalado.
SELECT
    trigger_name,
    event_manipulation,
    event_object_table,
    action_timing,
    action_statement
FROM information_schema.triggers
WHERE trigger_schema = DATABASE()
ORDER BY trigger_name;

-- 7. Integridad de datos: estos conteos deben dar cero.
SELECT COUNT(*) AS turnos_sin_paciente
FROM turnos t
LEFT JOIN pacientes p ON p.id = t.paciente_id
WHERE p.id IS NULL;

SELECT COUNT(*) AS turnos_sin_especialidad
FROM turnos t
LEFT JOIN especialidades e ON e.id = t.especialidad_id
WHERE e.id IS NULL;

SELECT COUNT(*) AS horarios_con_dia_invalido
FROM horarios_profesionales
WHERE dia_semana NOT BETWEEN 1 AND 7;

SELECT COUNT(*) AS horarios_con_rango_invalido
FROM horarios_profesionales
WHERE hora_fin <= hora_inicio OR duracion_minutos <= 0;

SELECT COUNT(*) AS turnos_con_duracion_invalida
FROM turnos
WHERE duracion_minutos <= 0;

SELECT COUNT(*) AS reservas_temporales_sin_profesional
FROM reservas_horarios_temporales r
LEFT JOIN profesionales p ON p.id = r.profesional_id
WHERE p.id IS NULL;

SELECT COUNT(*) AS reservas_temporales_sin_especialidad
FROM reservas_horarios_temporales r
LEFT JOIN especialidades e ON e.id = r.especialidad_id
WHERE e.id IS NULL;

-- 8. Duplicados de DNI (el resultado esperado es cero filas).
SELECT dni, COUNT(*) AS cantidad
FROM pacientes
GROUP BY dni
HAVING COUNT(*) > 1;
