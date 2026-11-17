-- Poli Medic - tabla de notificaciones.
-- Idempotente: si la tabla ya existe, no la recrea ni elimina sus registros.
USE centro_medico_salud;

CREATE TABLE IF NOT EXISTS notificaciones (
    id BIGINT NOT NULL AUTO_INCREMENT,
    turno_id INT NULL,
    tipo VARCHAR(40) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_notificacion_evento (turno_id, tipo),
    CONSTRAINT fk_notificacion_turno
        FOREIGN KEY (turno_id)
        REFERENCES turnos (id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

SHOW CREATE TABLE notificaciones;
