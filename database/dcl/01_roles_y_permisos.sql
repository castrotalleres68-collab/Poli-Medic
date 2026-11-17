-- Poli Medic - DCL: roles y privilegio mínimo.
-- Este script crea/actualiza roles y permisos; NO crea cuentas ni cambia contraseñas.
-- Las cuentas se inspeccionan por separado antes de asignarles roles.
-- Ejecutar con una cuenta administradora de MySQL/MariaDB.
USE centro_medico_salud;

CREATE ROLE IF NOT EXISTS 'rol_polimedic_consulta';
CREATE ROLE IF NOT EXISTS 'rol_polimedic_app';

-- Cuenta de consulta: solo catálogos y agendas, sin datos personales de pacientes.
GRANT SELECT ON centro_medico_salud.especialidades
    TO 'rol_polimedic_consulta';
GRANT SELECT ON centro_medico_salud.profesionales
    TO 'rol_polimedic_consulta';
GRANT SELECT ON centro_medico_salud.profesional_especialidades
    TO 'rol_polimedic_consulta';
GRANT SELECT ON centro_medico_salud.horarios_profesionales
    TO 'rol_polimedic_consulta';

-- Aplicación web: lectura de tablas utilizadas por el portal y la administración.
GRANT SELECT ON centro_medico_salud.administradores TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.actividad TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.especialidades TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.horarios_profesionales TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.notificaciones TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.pacientes TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.profesionales TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.profesional_especialidades TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.reservas_horarios_temporales TO 'rol_polimedic_app';
GRANT SELECT ON centro_medico_salud.turnos TO 'rol_polimedic_app';

-- Inserciones necesarias para reservas, catálogos, notificaciones y actividad.
GRANT INSERT ON centro_medico_salud.actividad TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.especialidades TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.horarios_profesionales TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.notificaciones TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.pacientes TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.profesionales TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.profesional_especialidades TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.reservas_horarios_temporales TO 'rol_polimedic_app';
GRANT INSERT ON centro_medico_salud.turnos TO 'rol_polimedic_app';

-- Modificaciones necesarias para datos, agendas, turnos y notificaciones.
GRANT UPDATE ON centro_medico_salud.especialidades TO 'rol_polimedic_app';
GRANT UPDATE ON centro_medico_salud.horarios_profesionales TO 'rol_polimedic_app';
GRANT UPDATE ON centro_medico_salud.notificaciones TO 'rol_polimedic_app';
GRANT UPDATE ON centro_medico_salud.pacientes TO 'rol_polimedic_app';
GRANT UPDATE ON centro_medico_salud.profesionales TO 'rol_polimedic_app';
GRANT UPDATE ON centro_medico_salud.reservas_horarios_temporales TO 'rol_polimedic_app';
GRANT UPDATE ON centro_medico_salud.turnos TO 'rol_polimedic_app';

-- Borrados limitados a la gestión necesaria de pacientes sin turnos, relaciones
-- profesionales/especialidades, agendas y bloqueos temporales.
GRANT DELETE ON centro_medico_salud.horarios_profesionales TO 'rol_polimedic_app';
GRANT DELETE ON centro_medico_salud.pacientes TO 'rol_polimedic_app';
GRANT DELETE ON centro_medico_salud.profesional_especialidades TO 'rol_polimedic_app';
GRANT DELETE ON centro_medico_salud.reservas_horarios_temporales TO 'rol_polimedic_app';

-- Intencionalmente NO se otorgan DROP, ALTER, CREATE, GRANT OPTION ni DELETE
-- sobre turnos, actividad, notificaciones o auditoria_estados_turnos.
-- El trigger inserta en auditoria_estados_turnos bajo el contexto de su DEFINER.
