# Poli Medic — revisión técnica y estado de la entrega

## Alcance de esta revisión

Se revisó el contenido de `Poli_Medic_final.zip` y el volcado SQL `centro_medico_salud.sql` disponible en el entorno de trabajo. No se tuvo conexión al servidor MySQL que corre en la computadora del usuario, por lo que no se simulan resultados de ejecución en esa base.

## Hallazgos confirmados en los archivos

- El volcado SQL contiene 11 tablas: `actividad`, `administradores`, `auditoria_estados_turnos`, `especialidades`, `horarios_profesionales`, `notificaciones`, `pacientes`, `profesionales`, `profesional_especialidades`, `reservas_horarios_temporales` y `turnos`.
- En ese volcado se observan 9 claves foráneas.
- El trigger `trg_auditar_estado_turno` aparece definido como `AFTER UPDATE` en `turnos` y registra cambios de estado solo cuando `OLD.estado` y `NEW.estado` son distintos.
- El volcado contiene una fila de auditoría coherente con un cambio `Confirmado` → `Cancelado`. Esto demuestra que hubo al menos un registro de auditoría en los datos exportados, pero no sustituye una prueba nueva en el servidor actual.
- La tabla `reservas_horarios_temporales` ya existe y contiene índices para identificar al propietario de la reserva y buscar horarios/vencimientos. En el volcado no aparecen claves foráneas desde esta tabla hacia profesionales y especialidades.
- En el volcado no se encontraron definiciones de usuarios ni roles MySQL. Los permisos efectivos de `polimedic_app` y `polimedic_consulta` no pueden deducirse de un archivo de esquema/datos.
- El volcado tiene `turnos.estado` como `ENUM('Confirmado','Cancelado','Atendido')`. Una migración antigua del proyecto propone cambiarlo a `VARCHAR(20)` para conservar estados históricos como `Pendiente`. No se ejecutó esa migración ni se cambió el tipo de columna automáticamente.
- Tres archivos SQL del ZIP original contenían bytes NUL en lugar de texto SQL legible: `migracion_doble_horario_diario.sql`, `migracion_notificaciones.sql` y `actualizar_estado_atendido.sql`. Se reconstruyeron como scripts de texto, conservadores y documentados.

## Cambios realizados en esta versión

### Código PHP

- `app/config.php`: se ordenó la configuración y se agregó soporte opcional para variables de entorno (`POLIMEDIC_DB_HOST`, `POLIMEDIC_DB_NAME`, `POLIMEDIC_DB_USER`, `POLIMEDIC_DB_PASSWORD`). Si no están definidas, mantiene los valores locales habituales de XAMPP (`localhost`, `centro_medico_salud`, `root`, contraseña vacía).
- `admin.php`: se ordenó la sangría y se separaron las operaciones PHP/HTML/CSS para que sea más fácil de leer. Se conservaron textos, clases y reglas visuales del formulario de acceso.
- `api.php`: en `tomar_horario`, la agenda se vuelve a validar después de bloquear la fila del profesional. En `editar_agenda_profesional`, la edición bloquea la misma fila del profesional y verifica que siga activo. Esto reduce la posibilidad de que una edición administrativa de agenda se cruce con una reserva concurrente.

### SQL nuevo o reparado

- `database/pruebas/verificacion_estructura.sql`: consultas de solo lectura para tablas, motores, columnas, índices, claves foráneas, CHECK, trigger, duplicados y registros huérfanos.
- `database/instalacion/03_restricciones_integridad.sql`: agrega CHECK de días/horas/duraciones y claves foráneas para reservas temporales únicamente si no existen y si los datos pasan las comprobaciones previas. No elimina ni corrige registros.
- `database/pruebas/prueba_trigger_auditoria.sql`: prueba aislada con un paciente y un turno temporal; verifica el cambio de estado, la ausencia de duplicado cuando el estado no cambia y limpia exclusivamente los datos de prueba.
- `database/pruebas/prueba_transacciones.sql`: demuestra `START TRANSACTION`, `ROLLBACK` y `COMMIT` sobre `actividad`, y limpia las filas de prueba al final.
- `database/dcl/01_roles_y_permisos.sql`: roles con permisos por tabla y privilegio mínimo. La cuenta de consulta solo recibe acceso a catálogos y agendas; no recibe acceso a datos personales.
- `database/dcl/03_asignar_roles_usuarios.sql`: asignación de roles a cuentas que ya existan; no crea cuentas ni cambia contraseñas.
- `database/dcl/02_crear_cuentas_plantilla.sql`: plantilla comentada, sin contraseñas inventadas ni activas.
- `database/dcl/04_verificacion.sql`: consultas para comprobar las cuentas y privilegios.
- `database/migracion_doble_horario_diario.sql`, `database/migracion_notificaciones.sql` y `database/utilidades/actualizar_estado_atendido.sql`: reconstruidos para que sean legibles. El script de estado atendido es de consulta por defecto; la actualización en lote queda comentada para evitar cambiar turnos reales sin revisión.

## Pruebas ejecutadas en este entorno

| Prueba | Resultado | Alcance |
|---|---|---|
| `php -l admin.php` | Correcto | Sintaxis PHP; no conecta a MySQL |
| `php -l api.php` | Correcto | Sintaxis PHP; no conecta a MySQL |
| `php -l index.php` | Correcto | Sintaxis PHP/HTML de entrada |
| `php -l app/config.php` | Correcto | Sintaxis PHP de conexión |
| `node --check assets/js/cliente.js` | Correcto | Sintaxis JavaScript |
| `node --check assets/js/admin.js` | Correcto | Sintaxis JavaScript |
| Pruebas SQL contra la base local | No ejecutadas | Este entorno no dispone de conexión al MySQL de Windows/XAMPP ni del controlador PDO MySQL instalado para realizar una prueba integrada |
| Prueba visual en navegador | No ejecutada | Se preservaron los archivos visuales originales; no se afirmó una prueba visual en el navegador del usuario |

## Qué falta verificar en el servidor local

Estos puntos no se marcan como aprobados hasta que se ejecuten en el XAMPP real:

1. Confirmar la definición y los privilegios actuales de las cuentas MySQL.
2. Ejecutar `database/pruebas/verificacion_estructura.sql` y revisar los conteos de integridad.
3. Revisar el resultado de `database/instalacion/03_restricciones_integridad.sql` antes de dar por instaladas las restricciones.
4. Ejecutar `database/pruebas/prueba_trigger_auditoria.sql` y guardar capturas de los resultados esperados.
5. Ejecutar `database/pruebas/prueba_transacciones.sql` y guardar las salidas de las consultas de comprobación.
6. Probar en el navegador una reserva, el bloqueo temporal, la consulta por DNI, la cancelación, el alta/edición administrativa y el registro de actividad.
7. Si se decide usar `polimedic_app`, crear o reutilizar la cuenta solo después de verificar su existencia, asignarle el rol y configurar las variables de entorno. No cambiar la conexión de la aplicación antes de comprobar las credenciales.

## Criterio de seguridad

No se ejecutaron migraciones contra una base real, no se borraron datos del volcado original, no se alteraron las páginas de cliente ni la vista administrativa principal, y no se inventaron resultados de pruebas de integración. Las pruebas SQL están diseñadas para datos temporales y explican qué salida debe observarse.
