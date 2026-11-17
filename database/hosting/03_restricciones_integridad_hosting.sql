-- Poli Medic - restricciones CHECK y claves foráneas para reservas temporales.
-- Migración conservadora para MariaDB 10.4 o compatible.
-- No corrige ni elimina registros: si detecta datos inválidos, omite esa restricción.
-- Hacé una copia de seguridad antes de aplicarla en la base local.

-- CHECK: día de atención entre lunes (1) y domingo (7).
SET @existe_chk_dia := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints tc
    JOIN information_schema.check_constraints cc
      ON cc.constraint_schema = tc.constraint_schema
     AND cc.constraint_name = tc.constraint_name
    WHERE tc.constraint_schema = DATABASE()
      AND tc.table_name = 'horarios_profesionales'
      AND tc.constraint_type = 'CHECK'
      AND LOWER(cc.check_clause) LIKE '%dia_semana%'
      AND LOWER(cc.check_clause) LIKE '%between 1 and 7%'
);
SET @datos_invalidos_dia := (
    SELECT COUNT(*) FROM horarios_profesionales
    WHERE dia_semana NOT BETWEEN 1 AND 7
);
SET @sql_chk_dia := IF(
    @existe_chk_dia > 0,
    'SELECT ''CHECK de día ya existe'' AS resultado',
    IF(
        @datos_invalidos_dia = 0,
        'ALTER TABLE horarios_profesionales ADD CONSTRAINT chk_horarios_dia_semana CHECK (dia_semana BETWEEN 1 AND 7)',
        'SELECT ''Omitido: hay horarios con dia_semana fuera de 1..7'' AS resultado'
    )
);
PREPARE stmt FROM @sql_chk_dia;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- CHECK: la hora de finalización debe ser posterior a la de inicio.
SET @existe_chk_rango := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints tc
    JOIN information_schema.check_constraints cc
      ON cc.constraint_schema = tc.constraint_schema
     AND cc.constraint_name = tc.constraint_name
    WHERE tc.constraint_schema = DATABASE()
      AND tc.table_name = 'horarios_profesionales'
      AND tc.constraint_type = 'CHECK'
      AND LOWER(cc.check_clause) LIKE '%hora_fin%'
      AND LOWER(cc.check_clause) LIKE '%hora_inicio%'
      AND LOWER(cc.check_clause) LIKE '%>%'
);
SET @datos_invalidos_rango := (
    SELECT COUNT(*) FROM horarios_profesionales
    WHERE hora_fin <= hora_inicio
);
SET @sql_chk_rango := IF(
    @existe_chk_rango > 0,
    'SELECT ''CHECK de rango horario ya existe'' AS resultado',
    IF(
        @datos_invalidos_rango = 0,
        'ALTER TABLE horarios_profesionales ADD CONSTRAINT chk_horarios_rango_horario CHECK (hora_fin > hora_inicio)',
        'SELECT ''Omitido: hay rangos horarios inválidos'' AS resultado'
    )
);
PREPARE stmt FROM @sql_chk_rango;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- CHECK: la duración de cada consulta debe ser positiva.
SET @existe_chk_duracion_horario := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints tc
    JOIN information_schema.check_constraints cc
      ON cc.constraint_schema = tc.constraint_schema
     AND cc.constraint_name = tc.constraint_name
    WHERE tc.constraint_schema = DATABASE()
      AND tc.table_name = 'horarios_profesionales'
      AND tc.constraint_type = 'CHECK'
      AND LOWER(cc.check_clause) LIKE '%duracion_minutos%' AND LOWER(cc.check_clause) LIKE '%> 0%'
);
SET @datos_invalidos_duracion_horario := (
    SELECT COUNT(*) FROM horarios_profesionales
    WHERE duracion_minutos <= 0
);
SET @sql_chk_duracion_horario := IF(
    @existe_chk_duracion_horario > 0,
    'SELECT ''CHECK de duración de horario ya existe'' AS resultado',
    IF(
        @datos_invalidos_duracion_horario = 0,
        'ALTER TABLE horarios_profesionales ADD CONSTRAINT chk_horarios_duracion_positiva CHECK (duracion_minutos > 0)',
        'SELECT ''Omitido: hay duraciones de horario no positivas'' AS resultado'
    )
);
PREPARE stmt FROM @sql_chk_duracion_horario;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @existe_chk_duracion_turno := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints tc
    JOIN information_schema.check_constraints cc
      ON cc.constraint_schema = tc.constraint_schema
     AND cc.constraint_name = tc.constraint_name
    WHERE tc.constraint_schema = DATABASE()
      AND tc.table_name = 'turnos'
      AND tc.constraint_type = 'CHECK'
      AND LOWER(cc.check_clause) LIKE '%duracion_minutos%' AND LOWER(cc.check_clause) LIKE '%> 0%'
);
SET @datos_invalidos_duracion_turno := (
    SELECT COUNT(*) FROM turnos
    WHERE duracion_minutos <= 0
);
SET @sql_chk_duracion_turno := IF(
    @existe_chk_duracion_turno > 0,
    'SELECT ''CHECK de duración de turno ya existe'' AS resultado',
    IF(
        @datos_invalidos_duracion_turno = 0,
        'ALTER TABLE turnos ADD CONSTRAINT chk_turnos_duracion_positiva CHECK (duracion_minutos > 0)',
        'SELECT ''Omitido: hay turnos con duración no positiva'' AS resultado'
    )
);
PREPARE stmt FROM @sql_chk_duracion_turno;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- CHECK: la duración de una reserva temporal debe ser positiva.
SET @existe_chk_duracion_reserva := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints tc
    JOIN information_schema.check_constraints cc
      ON cc.constraint_schema = tc.constraint_schema
     AND cc.constraint_name = tc.constraint_name
    WHERE tc.constraint_schema = DATABASE()
      AND tc.table_name = 'reservas_horarios_temporales'
      AND tc.constraint_type = 'CHECK'
      AND LOWER(cc.check_clause) LIKE '%duracion_minutos%' AND LOWER(cc.check_clause) LIKE '%> 0%'
);
SET @datos_invalidos_duracion_reserva := (
    SELECT COUNT(*) FROM reservas_horarios_temporales
    WHERE duracion_minutos <= 0
);
SET @sql_chk_duracion_reserva := IF(
    @existe_chk_duracion_reserva > 0,
    'SELECT ''CHECK de duración de reserva ya existe'' AS resultado',
    IF(
        @datos_invalidos_duracion_reserva = 0,
        'ALTER TABLE reservas_horarios_temporales ADD CONSTRAINT chk_reserva_temporal_duracion_positiva CHECK (duracion_minutos > 0)',
        'SELECT ''Omitido: hay reservas temporales con duración no positiva'' AS resultado'
    )
);
PREPARE stmt FROM @sql_chk_duracion_reserva;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK temporal -> profesional. Si hay filas huérfanas, no aplica la FK.
SET @existe_fk_temporal_prof := (
    SELECT COUNT(*) FROM information_schema.key_column_usage
    WHERE constraint_schema = DATABASE()
      AND table_name = 'reservas_horarios_temporales'
      AND column_name = 'profesional_id'
      AND referenced_table_name = 'profesionales'
);
SET @huerfanos_prof := (
    SELECT COUNT(*)
    FROM reservas_horarios_temporales r
    LEFT JOIN profesionales p ON p.id = r.profesional_id
    WHERE p.id IS NULL
);
SET @sql_fk_temporal_prof := IF(
    @existe_fk_temporal_prof > 0,
    'SELECT ''FK de profesional temporal ya existe'' AS resultado',
    IF(
        @huerfanos_prof = 0,
        'ALTER TABLE reservas_horarios_temporales ADD CONSTRAINT fk_reserva_temporal_profesional FOREIGN KEY (profesional_id) REFERENCES profesionales (id)',
        'SELECT ''Omitido: hay reservas temporales sin profesional'' AS resultado'
    )
);
PREPARE stmt FROM @sql_fk_temporal_prof;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK temporal -> especialidad. Si hay filas huérfanas, no aplica la FK.
SET @existe_fk_temporal_esp := (
    SELECT COUNT(*) FROM information_schema.key_column_usage
    WHERE constraint_schema = DATABASE()
      AND table_name = 'reservas_horarios_temporales'
      AND column_name = 'especialidad_id'
      AND referenced_table_name = 'especialidades'
);
SET @huerfanos_esp := (
    SELECT COUNT(*)
    FROM reservas_horarios_temporales r
    LEFT JOIN especialidades e ON e.id = r.especialidad_id
    WHERE e.id IS NULL
);
SET @sql_fk_temporal_esp := IF(
    @existe_fk_temporal_esp > 0,
    'SELECT ''FK de especialidad temporal ya existe'' AS resultado',
    IF(
        @huerfanos_esp = 0,
        'ALTER TABLE reservas_horarios_temporales ADD CONSTRAINT fk_reserva_temporal_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades (id)',
        'SELECT ''Omitido: hay reservas temporales sin especialidad'' AS resultado'
    )
);
PREPARE stmt FROM @sql_fk_temporal_esp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Resumen de las restricciones que quedaron instaladas.
SELECT
    table_name,
    constraint_name,
    constraint_type
FROM information_schema.table_constraints
WHERE constraint_schema = DATABASE()
  AND (
      constraint_name LIKE 'chk_horarios_%'
      OR constraint_name IN (
          'chk_turnos_duracion_positiva',
          'chk_reserva_temporal_duracion_positiva',
          'fk_reserva_temporal_profesional',
          'fk_reserva_temporal_especialidad'
      )
  )
ORDER BY table_name, constraint_name;
