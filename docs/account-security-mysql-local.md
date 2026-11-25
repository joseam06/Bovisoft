# Fase 6.1: validación del entorno habitual MySQL

## Corrección posterior: login redirigido a sw.js

Se reprodujo en MariaDB y `php artisan serve` con offline habilitado: el POST interceptado llevaba Referer `/sw.js`; una solicitud protegida no GET sin sesión, con ese Referer, dejaba `/sw.js` como `url.intended`. El login completaba la autenticación (`/offline/session` confirmaba usuario), pero Fortify seguía ese destino y mostraba el código del worker. Laravel también utiliza Referer y `_previous.url` para regresar al formulario tras errores. La modificación anterior de redirecciones manuales del worker, por sí sola, no corregía la procedencia ni las sesiones ya contaminadas.

Solución: `public/sw.js` conserva explícitamente el referrer del documento cliente cuando el Request utiliza `about:client`. `AuthenticationNavigation`, registrado en `bootstrap/app.php`, valida destinos de sesión y Referer contra rutas GET web del mismo origen, excluyendo recursos estáticos y endpoints internos. Retira destinos inválidos y permite volver al formulario de login/registro/recuperación desde workers anteriores; conserva destinos legítimos, como Fincas y confirmaciones firmadas. No fuerza todos los accesos al Dashboard. No se modificaron IndexedDB, colas, CSRF ni políticas de caché. La actualización existente del worker continúa sin requerir desregistro ni limpieza del navegador.

Evidencia: 5 pruebas focalizadas / 76 aserciones correctas en MariaDB desechable (`AuthenticationNavigationTest` y el escenario Google simulado existente). `tests/browser/auth_redirect.py` reproduce una sesión contaminada, instala el worker del commit anterior, comprueba URL final **y contenido Dashboard**, valida errores de credenciales y actualización automática, y reutiliza el recorrido MySQL de registro/verificación pendiente, recuperación array, logout online/offline y pendiente tras recarga/login/sincronización. Todo pasó en el servidor habitual, sin 419 ni 500. Google real continúa dependiendo de su configuración externa; no se contactó ni se enviaron correos reales. No se ejecutaron suites completas.

Codificación: respuesta `/sw.js` idéntica a los bytes del archivo, UTF-8 válido, sin las cadenas corruptas indicadas. Artisan lo sirve como `application/javascript` sin charset explícito; la captura mostraba su interpretación como documento tras la redirección incorrecta. No se reescribió la codificación de archivos sanos.

Archivos de esta corrección: `public/sw.js`, `bootstrap/app.php`, `app/Http/Middleware/AuthenticationNavigation.php`, `tests/Feature/AuthenticationNavigationTest.php`, `tests/browser/auth_redirect.py` y este informe. Sintaxis JS y `git diff --check` correctos. Se conservan las limitaciones externas y de navegadores ya documentadas; sin commit/push/producción/fase 6.2.

## Preparación manual posterior

- Respaldo trasladado a `%USERPROFILE%\BoviSoft-Backups\bovisoft_db-20261124-143604.sql`, con archivo `.sha256` adjunto y el mismo checksum indicado abajo. Carpeta con herencia desactivada y acceso para el usuario, SYSTEM y administradores. Copia temporal original retirada después de verificar el destino.
- `.env` local: `OFFLINE_WORKSPACE_ENABLED=true`, `OFFLINE_PRODUCTION_SYNC_ENABLED=true`, `APP_URL=http://127.0.0.1:8000`, `GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback`. Solo se cambiaron esas cuatro variables; secretos, base y demás ajustes conservados. No usar indistintamente localhost/127.0.0.1: las bóvedas y cookies pertenecen a cada origen. No se trasladó ni eliminó IndexedDB.
- Ejecutar `php artisan serve --host=127.0.0.1 --port=8000`, reiniciándolo si ya estaba abierto. Usar siempre `http://127.0.0.1:8000`. No se repitieron pruebas automatizadas. Los archivos del manifiesto existen, no hay `public/hot` ni fuentes JS/CSS posteriores a la compilación; se limpió únicamente la caché de vistas Blade.
- SMTP actual: Mailtrap **Sandbox**, puerto 2525, usuario/contraseña presentes, sin override MAIL_URL. Sirve para capturar correos en la bandeja Sandbox, no para entrega real. Autenticación SMTP no comprobada; no se enviaron mensajes. `QUEUE_CONNECTION=database` se conserva: las notificaciones quedan en cola y requieren un worker. No se inició ninguno ni se procesaron trabajos anteriores. Antes de ejecutar `php artisan queue:work`, revisar los trabajos pendientes y usar exclusivamente cuentas de prueba.
- Google: ID y secreto presentes; su validez y las URLs autorizadas no se consultaron externamente. En un cliente OAuth **Web de desarrollo**, autorizar exactamente `http://127.0.0.1:8000/auth/google/callback`; si la pantalla está en Testing, agregar la cuenta Google de prueba como usuario autorizado. Socialite es un flujo de servidor y no necesita agregar un origen JavaScript para estas acciones. No eliminar URLs existentes ni cambiar el cliente productivo; si las credenciales locales pertenecen al cliente productivo, usar credenciales separadas de desarrollo. No se modificó Google Cloud.
- Pruebas manuales: login tradicional con cuenta existente; registro tradicional con dirección de prueba; abrir el correo capturado en Mailtrap para verificación, cambio de correo y recuperación. En Mi perfil → Seguridad y acceso, probar cambio de contraseña, vinculación, reautenticación Google, primera contraseña y desvinculación (solo con contraseña propia y correo verificado). Usar Registro → Google para una cuenta nueva y Login → Google para una ya vinculada. Todo Google y las credenciales requieren Internet y la configuración externa correcta.
- Offline: iniciar sesión, dejar terminar el guardado automático, abrir Fincas/Animales, desconectar desde DevTools, editar un registro ficticio, recargar y reconectar. No limpiar datos del sitio. El aviso de sincronización debe desaparecer tras confirmar los pendientes; errores/conflictos deben conservarlos. Usar el mismo origen y navegador.
- Entrega real a Gmail/Outlook/etc. pendiente: proveedor SMTP transaccional, remitente autorizado y variables MAIL_HOST/PORT/SCHEME/USERNAME/PASSWORD/FROM correspondientes. Mantener Sandbox hasta contar con una dirección de prueba expresamente autorizada para una comprobación real. No se contrataron servicios ni se cambiaron DNS, Render o producción.

El resto del informe describe la ejecución anterior y sus configuraciones temporales; los estados actuales son los de esta sección.

La causa del 1054 era un esquema atrasado: siete migraciones pendientes, sin columnas parcialmente aplicadas. Entorno efectivo `local`, conexión `mysql`, servidor `127.0.0.1`, base `bovisoft_db`, versión **10.4.28-MariaDB**. No es evidencia de ejecución en Oracle MySQL 8.

Se revisaron normalización de correos, unicidad Google, ventas activas duplicadas y referencias a animales inexistentes: cero colisiones en las comprobaciones. Se revisaron UUID, índices únicos, FK, tipos sanitarios y la restricción financiera `active_sale_animal_id`.

Primero se ejecutó `php tests/run-pilot.php mysql AccountSecurityTest` en una base desechable: **6 pruebas, 174 aserciones correctas**, migraciones incluidas. La base desechable fue eliminada por el ejecutor.

Respaldo habitual realizado con mysqldump, transacción consistente, rutinas/eventos/triggers y sin contraseñas en argumentos. Ubicación local privada, fuera del repositorio:

`%TEMP%\bovisoft-private-backups\bovisoft_db-20261124-143604.sql`

SHA256: `ead6d785185e693d6d47aa9c9462427a42d2702621234f9b4d2f5df932931491`. Se comprobó generación/tamaño y checksum; no se realizó un ensayo de restauración. Es un respaldo sensible: no publicarlo. Está en Temp; conservarlo en una ubicación privada duradera antes de limpiar temporales.

`php artisan migrate --no-interaction` aplicó correctamente, lote 8:

- `2026_11_18_000001_create_production_sync_tables`
- `2026_11_18_000002_create_workspace_sync_tables`
- `2026_11_19_000001_extend_workspace_modules`
- `2026_11_19_000002_align_salud_types`
- `2026_11_21_000001_extend_workspace_finance`
- `2026_11_21_000002_create_workspace_profiles`
- `2026_11_24_000001_secure_account_access`

Estado final: todas aplicadas; columnas nuevas y los índices únicos de correo/Google presentes. Se compararon hashes de **todos los campos originales** antes y después, manteniendo IDs y contenidos: 2 usuarios, 1 finca, 2 animales, 1 potrero, 3 registros sanitarios, 6 producciones, 2 ingresos y 1 egreso intactos. Las dos cuentas anteriores recibieron transición; sus correos siguen sin verificar. No se falseó `email_verified_at`.

Chromium sobre `php artisan serve` y la misma base habitual, puerto **8098**: cuenta ficticia con estado heredado sin verificar, perfil, registro tradicional, duplicados, restricción de verificación, recuperación con transporte array y token registrado; nombre offline tras recarga, cierre offline, nuevo login y sincronización. Sin 500 ni 419 observados. Se agregaron dos cuentas ficticias de apoyo y una de registro; no se probaron contraseñas ni se modificaron los usuarios originales. No se borró IndexedDB: se usó un contexto nuevo de pruebas, sin manipular el navegador habitual del usuario.

**Configuración de ejecución:** el proceso de pruebas usa `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `OFFLINE_WORKSPACE_ENABLED=true` y `OFFLINE_PRODUCTION_SYNC_ENABLED=true`. No se modificó `.env`. La configuración habitual detectada mantiene SMTP/cola database y ambos indicadores offline deshabilitados: para repetir el recorrido completo usar esos overrides en la terminal que ejecuta Artisan. No iniciar un worker SMTP durante pruebas con direcciones ficticias. No se verificó entrega real de correos.

Cambios de archivos: este informe, referencia en el informe 6.1 y `tests/browser/mysql_local_security.py`. No hicieron falta cambios de aplicación ni migraciones. Los ayudantes temporales de auditoría y preparación fueron retirados; no contienen credenciales publicadas. El recorrido de navegador requiere las cuentas ficticias preparadas y su contraseña mediante `BOVISOFT_BROWSER_TEST_PASSWORD`.

Se conservan los límites de SMTP/Google reales, Oracle MySQL, Safari/Firefox, dispositivos físicos y grandes volúmenes. No hubo commit, push, producción, reconstrucción de base ni fase 6.2.

## Tercer punto de control

Se incluye la fase 6.1 y la corrección de redirecciones. Envío real de correo y Google real siguen pendientes de validación externa; estas limitaciones no bloquean el respaldo en feature/offline-first. Se reutiliza la evidencia anterior sin repetir suites. No se publican .env, respaldos, bases, cachés, recursos compilados ni secretos. No se autoriza despliegue ni fase 6.2.
