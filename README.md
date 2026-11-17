# Poli Medic — Sistema de turnos (Base de Datos II)

Portal de pacientes + panel administrativo + API en **PHP 8.1+ / MySQL (MariaDB)**, pensado para correr en **XAMPP** y editarse con **Visual Studio / VS Code**.

## Cómo ponerlo a funcionar en XAMPP

1. Copiá la carpeta `Poli_Medic_salud` dentro de `C:\xampp\htdocs\`.
2. En el panel de XAMPP iniciá **Apache** y **MySQL**.
3. Instalá la base de datos (elegí una opción):
   - **Automática:** doble clic en `tools\instalar_base_xampp.bat`.
   - **phpMyAdmin** (`http://localhost/phpmyadmin`): pestaña **Importar** y subí, en este orden,
     `database/instalacion/01_esquema_y_datos.sql` y luego `database/instalacion/02_auditoria_y_trigger.sql`
     (para el segundo, elegí antes la base `centro_medico_salud`).
4. Abrí:
   - Portal de pacientes: `http://localhost/Poli_Medic_salud/`
   - Panel administrativo: `http://localhost/Poli_Medic_salud/admin.php` (usuario `admin`)
5. Opcional: doble clic en `tools\verificar_sintaxis_php.bat` para comprobar que ningún archivo PHP tenga errores de sintaxis.

> Si no recordás la contraseña del administrador, generá una nueva con
> `C:\xampp\php\php.exe tools\generar_hash_clave.php TuClaveNueva` y pegá la sentencia SQL que muestra en phpMyAdmin.

## Publicarlo en internet

GitHub Pages no ejecuta PHP. Para tener el sitio funcionando online, el código se guarda en GitHub y se publica en un hosting con PHP y MySQL. Seguí la guía paso a paso en [`DESPLIEGUE.md`](DESPLIEGUE.md). Para hosting, usá los scripts de `database/hosting/` (no crean una base con nombre fijo).

## Estructura del proyecto

```
Poli_Medic_salud/
├── index.php                  Portal de pacientes (solo arma la página)
├── admin.php                  Ingreso y panel administrativo (sesión)
├── api.php                    Router de la API: decide qué función ejecutar
│
├── app/                       Código interno (bloqueado al navegador con .htaccess)
│   ├── config.php             Conexión a MySQL
│   ├── config.local.ejemplo.php  Plantilla para usar otra cuenta MySQL
│   ├── helpers.php            Funciones compartidas y validaciones
│   ├── api/                   Un archivo por área de la API
│   │   ├── catalogo.php       Especialidades, profesionales, días y horarios libres
│   │   ├── mi_turno.php       Consultar y cancelar turnos con el DNI
│   │   ├── reservas.php       Reservar: bloqueo temporal, confirmación, cancelación
│   │   ├── admin_panel.php    Datos del panel, notificaciones, estado de turnos
│   │   ├── admin_pacientes.php
│   │   ├── admin_especialidades.php
│   │   └── admin_profesionales.php   Profesionales y agendas
│   └── views/                 HTML dividido por bloques visuales
│       ├── portal/            cabecera, inicio, reserva, mi_turno, modal_salir, pie
│       └── admin/             login, panel, barra_lateral, barra_superior,
│                              paginas/ (dashboard, turnos, ...), notificaciones, modal_registro
│
├── assets/
│   ├── css/                   cliente.css, admin.css, login.css
│   ├── js/                    cliente.js, admin.js  (con índice al inicio)
│   └── img/
│
├── database/
│   ├── instalacion/           01 esquema+datos · 02 auditoría+trigger · 03 restricciones (opcional)
│   ├── dcl/                   Roles, permisos y cuentas MySQL (01 a 04, en orden)
│   ├── pruebas/               Trigger, transacciones (COMMIT/ROLLBACK) y verificación de estructura
│   ├── utilidades/            Scripts auxiliares (precarga de datos, estado "Atendido")
│   └── migraciones_antiguas/  Solo historial; NO ejecutar sobre la base actual
│
├── docs/                      Documentación Word, diagrama ER, guía de defensa, guía SQL
└── tools/                     Scripts .bat/.php para XAMPP (instalar, verificar, clave de admin)
```

## ¿Dónde corrijo cada cosa?

| Quiero cambiar… | Archivo |
|---|---|
| Datos de conexión a MySQL | `app/config.php` (o `app/config.local.php`) |
| Textos o estructura de la portada | `app/views/portal/inicio.php` |
| Pasos de la reserva (pantallas) | `assets/js/cliente.js` → sección 7 |
| Reglas para reservar un turno | `app/api/reservas.php` → `accion_reservar` |
| Minutos de bloqueo de un horario | `app/api/reservas.php` → constante `MINUTOS_BLOQUEO` |
| Validación de DNI, teléfono, obra social | `app/helpers.php` |
| Columnas/botones de una página del panel | `app/views/admin/paginas/` |
| Qué hace el panel al cargar | `assets/js/admin.js` → sección 4 |
| Agenda y especialidades de profesionales | `app/api/admin_profesionales.php` |
| Colores y diseño | `assets/css/cliente.css` · `admin.css` · `login.css` |
| Agregar una acción nueva a la API | crear la función `accion_xxx` en `app/api/` y anotarla en la tabla de rutas de `api.php` |

## Qué cambió respecto de la versión anterior

**Lo que NO cambió:** el aspecto del portal y del panel, las direcciones (`index.php`, `admin.php`, `api.php?action=...`), los nombres de las acciones de la API, sus respuestas JSON y la base de datos. Se comprobó que el HTML resultante es equivalente al original y que el JavaScript tiene el mismo árbol sintáctico (solo cambió el formato y se agregaron comentarios).

**Lo que se reordenó:**
- `api.php` (558 líneas, con bloques en una sola línea) pasó a un router de ~100 líneas más 7 módulos comentados. Las validaciones y consultas que se repetían (obra social, DNI/teléfono/email, horarios de agenda, búsqueda de profesional, bloqueo `FOR UPDATE`, notificación de cancelación) ahora están una sola vez en `app/helpers.php`.
- El HTML de `index.php` y del panel se dividió por bloques en `app/views/`. Los estilos del login pasaron de `admin.php` a `assets/css/login.css`.
- Los JavaScript se formatearon (antes eran líneas de cientos de caracteres) y tienen un índice de secciones.
- `database/`: 20 scripts sueltos → carpetas con orden de instalación. El trigger y la tabla de auditoría ahora forman parte de la instalación (`02_...`).
- Se repararon `includes/.htaccess` y `.editorconfig`, que estaban llenos de bytes vacíos. La protección ahora está en `app/.htaccess`.
- `config.php` admite un archivo `app/config.local.php` para usar otra cuenta MySQL sin escribir contraseñas en el código.

## Pendiente de comprobar en tu XAMPP

Esta reorganización se revisó por lectura y con comprobaciones automáticas, pero **no se pudo ejecutar contra MySQL ni con PHP** en el entorno donde se hizo. Antes de entregar:

1. Ejecutá `tools\verificar_sintaxis_php.bat`.
2. Probá en el navegador: reservar un turno completo, "Mi Turno" (consultar y cancelar), ingresar al panel, cambiar el estado de un turno, alta/edición de un paciente, especialidad y profesional.
3. Ejecutá `database/pruebas/` y guardá las capturas para la documentación (ver `docs/`).
4. Para mostrar DCL real: `database/dcl/` y luego `app/config.local.php` con la cuenta de la aplicación.
