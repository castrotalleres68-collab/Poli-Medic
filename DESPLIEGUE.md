# Guía de publicación — GitHub + hosting con PHP y MySQL

GitHub guarda el código. El hosting (ej.: InfinityFree) ejecuta PHP y MySQL y da el enlace público.
GitHub Pages NO sirve para este proyecto porque no ejecuta PHP.

## Parte 1 — Subir el código a GitHub
1. Descomprimí el zip. Entrá a la carpeta `Poli_Medic_salud`.
2. En github.com: **+ → New repository**. Nombre: `poli-medic-salud`. Sin README ni .gitignore.
3. Elegí **Public** solo si querés que cualquiera vea el código.
4. Clic en **uploading an existing file**.
5. Abrí la carpeta y seleccioná TODO lo que está **adentro** (no la carpeta). Arrastralo.
   - Si el límite de 100 archivos da error, subí en dos tandas (primero `app`, `assets`, `tools`; después el resto).
   - Verificá que aparezcan `.gitignore` y `app/.htaccess`.
6. **Commit changes**.

## Parte 2 — Crear la base en el hosting
1. Creá la cuenta y un sitio en el hosting.
2. Creá una base MySQL. Anotá: **servidor (host), nombre de la base, usuario y contraseña**.
3. Abrí phpMyAdmin del hosting y **seleccioná esa base**.
4. Pestaña **Importar** y subí, en este orden:
   1. `database/hosting/01_esquema_y_datos_hosting.sql`
   2. `database/hosting/02_auditoria_y_trigger_hosting.sql`
   3. (opcional) `database/hosting/03_restricciones_integridad_hosting.sql`
   - Usá los archivos de `database/hosting/`: no crean ni seleccionan una base fija.
   - Si el paso 2 falla por el trigger, el sitio igual funciona; solo no se guardaría la auditoría de estados.

## Parte 3 — Subir los archivos al hosting
1. Usá el administrador de archivos o FTP del hosting y entrá a `htdocs`.
2. Subí el contenido de la carpeta del proyecto (sin `docs/` ni `database/`, que no hacen falta online).
3. Creá `app/config.local.php` copiando `app/config.local.ejemplo.php` y completá host, base, usuario y contraseña del hosting.

## Parte 4 — Probar
- Portal: `https://TU-SITIO/`
- Panel: `https://TU-SITIO/admin.php` (usuario `admin`)
- Cambiá la clave del admin: `php tools/generar_hash_clave.php TuClaveNueva` y ejecutá el SQL que muestra en phpMyAdmin.
- Probá: reservar un turno, Mi Turno, ingresar al panel, cambiar el estado de un turno.
