# Guía breve para la defensa oral — Poli Medic

## 1. ¿Qué problema resuelve el sistema?

Poli Medic organiza la información de especialidades, profesionales, pacientes, agendas y turnos. El portal permite consultar el catálogo, seleccionar una franja disponible, registrar o reconocer al paciente por DNI y reservar o cancelar según las verificaciones implementadas. El panel administrativo gestiona pacientes, profesionales, especialidades, agendas, turnos, actividad y notificaciones.

## 2. ¿Por qué es una base de datos relacional?

La información se separa en tablas relacionadas mediante claves primarias y foráneas. Por ejemplo, `turnos.paciente_id` referencia a `pacientes.id`; el turno también se relaciona con una especialidad y puede relacionarse con un profesional. `profesional_especialidades` permite que un profesional se vincule con más de una especialidad.

## 3. DDL, DML y DCL

- **DDL:** define o modifica estructuras: `CREATE TABLE`, `ALTER TABLE`, índices, claves foráneas y restricciones.
- **DML:** consulta y manipula datos: `SELECT`, `INSERT`, `UPDATE`, `DELETE`.
- **DCL:** controla privilegios: `CREATE USER`, `CREATE ROLE`, `GRANT`, `REVOKE` y asignación de roles. Los comandos concretos deben ejecutarse y verificarse en la instancia local.

## 4. ¿Qué diferencia hay entre usuario y rol?

El usuario es la cuenta con la que se autentica una conexión. El rol agrupa permisos. Asignar un rol permite administrar los permisos en un único lugar y otorgar solo lo que necesita cada tipo de conexión.

## 5. ¿Qué hace el trigger?

`trg_auditar_estado_turno` se ejecuta después de una actualización de `turnos`. Compara el estado anterior con el nuevo y, solo si cambió, registra el ID del turno, los dos estados, la fecha y el usuario MySQL en `auditoria_estados_turnos`. Sin el trigger, el sistema podría cambiar el estado sin conservar ese historial automático.

## 6. ¿Qué es una transacción?

Es un conjunto de operaciones que se confirma como una unidad o se revierte. En la reserva, se relacionan el paciente, el turno, la actividad, la notificación y la liberación del bloqueo temporal dentro de una transacción para evitar que queden cambios parciales.

## 7. Propiedades ACID

- **Atomicidad:** se guardan todos los cambios de una operación o ninguno.
- **Consistencia:** las restricciones y validaciones deben mantener datos válidos antes y después de la operación.
- **Aislamiento:** las operaciones concurrentes no deben apropiarse del mismo horario; el sistema bloquea la fila del profesional y comprueba turnos y reservas temporales.
- **Durabilidad:** después de `COMMIT`, los cambios confirmados permanecen guardados.

## 8. ¿Qué muestran COMMIT y ROLLBACK?

La prueba `database/pruebas/prueba_transacciones.sql` inserta una fila identificada como prueba, demuestra que desaparece después de `ROLLBACK`, confirma otra con `COMMIT` y comprueba que existe. Al final elimina las filas de demostración para no dejar datos de prueba.

## 9. ¿Cómo se evitan las reservas simultáneas?

Al tomar una hora, se registra un bloqueo temporal asociado a la sesión durante diez minutos. Antes de confirmarla, el servidor vuelve a validar la agenda, comprueba que no haya un turno que se superponga y exige que el bloqueo propio siga vigente. Las comprobaciones usan el profesional como punto de serialización para reducir las carreras entre solicitudes.

## 10. Uso de IA

La IA se utilizó como apoyo para revisar código y SQL, ordenar la documentación, preparar pruebas y mejorar explicaciones. Las decisiones finales, las pruebas ejecutadas y la comprensión de cada parte deben poder explicarse por el estudiante. No se deben presentar resultados no comprobados como si se hubieran ejecutado.

## Recomendación para el examen

Abrir el portal y el panel, mostrar una reserva de prueba controlada, consultar los datos desde phpMyAdmin, ejecutar las pruebas SQL con capturas y explicar una relación, un permiso, el trigger y cada propiedad ACID con un ejemplo del sistema.
