-- Poli Medic - permite dos o más franjas en el mismo día.
-- Migración conservadora para MariaDB/MySQL: no borra agendas ni turnos.
-- Revisa y elimina únicamente índices UNIQUE antiguos que impidan varias franjas.
USE centro_medico_salud;

SET @tiene_unique_prof_dia := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'horarios_profesionales'
      AND index_name = 'uq_profesional_dia'
);
SET @sql_quitar_unique_prof_dia := IF(
    @tiene_unique_prof_dia > 0,
    'ALTER TABLE horarios_profesionales DROP INDEX uq_profesional_dia',
    'SELECT ''No existe uq_profesional_dia; no se requiere cambio'' AS resultado'
);
PREPARE stmt FROM @sql_quitar_unique_prof_dia;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_unique_prof_esp_dia := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'horarios_profesionales'
      AND index_name = 'uq_agenda_prof_esp_dia'
);
SET @sql_quitar_unique_prof_esp_dia := IF(
    @tiene_unique_prof_esp_dia > 0,
    'ALTER TABLE horarios_profesionales DROP INDEX uq_agenda_prof_esp_dia',
    'SELECT ''No existe uq_agenda_prof_esp_dia; no se requiere cambio'' AS resultado'
);
PREPARE stmt FROM @sql_quitar_unique_prof_esp_dia;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_indice_agenda := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'horarios_profesionales'
      AND index_name = 'idx_agenda_prof_esp_dia'
);
SET @sql_agregar_indice_agenda := IF(
    @tiene_indice_agenda = 0,
    'ALTER TABLE horarios_profesionales ADD INDEX idx_agenda_prof_esp_dia (profesional_id, especialidad_id, dia_semana)',
    'SELECT ''El índice de agenda ya existe'' AS resultado'
);
PREPARE stmt FROM @sql_agregar_indice_agenda;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificación: debe poder haber varias franjas por profesional/especialidad/día.
SHOW INDEX FROM horarios_profesionales;
