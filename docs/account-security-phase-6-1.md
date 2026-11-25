# Fase 6.1 — Seguridad y acceso a la cuenta

Actualización posterior: el entorno habitual MySQL/MariaDB fue migrado y verificado; ver [validación local](account-security-mysql-local.md). La referencia anterior a MySQL pendiente queda sustituida para MariaDB 10.4.28; Oracle MySQL sigue sin comprobarse.

## Alcance y transición

Base: `cd080786f0c1a04c02c3724d717e5bb926223b5c`, rama `feature/offline-first`. Cambios locales, sin despliegue.

Se confirmó que la verificación estaba desactivada, el perfil cambiaba el correo directamente y Socialite asociaba cuentas por coincidencia de correo y omitía el desafío 2FA. Se reutilizan Fortify, Jetstream, Socialite y el Password Broker de Laravel.

La migración `2026_11_24_000001_secure_account_access.php` agrega identidad normalizada única de correo, transición explícita `legacy_email_access`, procedencia de contraseña, versión de credenciales y solicitud de correo pendiente (hash del nonce, dirección y vencimiento). Conserva IDs, vínculos Google y `email_verified_at`. El índice único de `google_id` ya existía. Si encuentra correos equivalentes por mayúsculas/espacios entre cuentas, se detiene antes de modificar el esquema; no fusiona cuentas. La migración requiere una ventana sin registros/cambios de credenciales concurrentes y respaldo antes de aplicarla en otro entorno. No se aplicó en producción.

Las cuentas anteriores reciben acceso de transición sin falsear la verificación y conservan acceso a registros y sincronización. Las nuevas cuentas tradicionales deben confirmar su correo; pueden acceder al perfil para corregirlo. El aviso del Dashboard dirige al perfil. La contraseña de una cuenta Google anterior puede ser aleatoria o propia: no es posible deducirlo del hash. Se conserva su login existente, pero solo se cuenta como alternativa probada cuando el usuario la demuestra o establece una nueva.

## Flujos

- Registro tradicional → notificación firmada, 60 minutos → verificación estándar Laravel. Reenvío limitado a tres por minuto. No hay lista de dominios privilegiados.
- Cambio de correo → contraseña actual o reautenticación Google reciente (cinco minutos) → confirmación en la nueva dirección con sesión de su propietario. La dirección actual se mantiene hasta entonces. Solicitudes anteriores, enlaces usados y versiones de credenciales antiguas dejan de ser válidos. La unicidad se comprueba otra vez bajo bloqueo y restricción de base de datos. Se informa al correo anterior si estaba verificado; no se depende de él.
- Google tiene inicios separados para login, registro, vinculación y reautenticación. Socialite valida y consume `state`; se exigen `sub` estable y `email_verified=true` del proveedor autenticado. La intención dura cinco minutos y queda ligada al usuario/sesión/versión. Vincular requiere confirmación explícita; adoptar el correo Google es opcional y explícito. No hay asociación automática por correo, fusiones ni reasignaciones. Se conserva el desafío Fortify 2FA.
- Desvincular requiere identidad reciente, contraseña propia y correo verificado. Si falta una alternativa se rechaza; Google puede reautenticar para establecer la primera contraseña sin conocer la aleatoria.
- Recuperación usa el repositorio de tokens y vencimiento de Laravel, exclusivamente para correos verificados. Las respuestas son genéricas tanto para solicitud como para enlace inválido; token consumido dentro de la transacción y solicitudes limitadas. Cambios sensibles invalidan tokens de recuperación y otras sesiones mediante versión de credenciales y rotación de sesión/remember token. La sesión actual continúa tras cambios autenticados; un restablecimiento revoca las anteriores.

`AccountSecurity` centraliza estas reglas; las rutas de perfil y las acciones alternativas Fortify delegan en ellas. No se desactiva CSRF. Fortify y Jetstream usan distintas claves de comprobación de contraseña: ambas se renuevan tras un cambio legítimo.

## Offline

El propietario sigue identificado por ID interno. Credenciales, nonce, enlaces firmados, tokens de Google y formularios sensibles no forman parte de la cola ni de las vistas persistidas. Autenticación y seguridad se sirven con `no-store` y el Service Worker las solicita exclusivamente a la red. El perfil normal conserva su ruta y consulta offline, último estado conocido y edición de nombre. No se borra IndexedDB ni se cambia la bóveda al cambiar credenciales. No se modifica la versión del nombre por un cambio de correo. Se conserva la renovación CSRF previa al envío que corrigió el 419.

## Correo y configuración pendiente

Los cuatro correos (verificación, nueva dirección, recuperación y avisos sensibles) usan `emails/account.blade.php`, logo y colores de BoviSoft, texto español y notificaciones en cola después del commit. Las pruebas utilizan `Notification::fake`, `Mail::fake` o transporte `array`; no se enviaron correos reales. El router `tests/manual/account-security.php` solo usa la base ficticia y permite al navegador abrir el enlace capturado sin escribirlo en archivos; jamás debe usarse como entrada de un despliegue.

Para un entorno real se requieren, sin configurarlos durante esta fase:

- `APP_URL=https://<dominio-real>` y `APP_KEY` estable, HTTPS y cookies seguras.
- `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtp` con puerto 587/STARTTLS o `smtps` con puerto 465, según proveedor; `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` como secretos del entorno. `MAIL_ENCRYPTION` no es utilizado por la configuración actual.
- `MAIL_FROM_ADDRESS` autorizado y `MAIL_FROM_NAME=BoviSoft`; remitente/dominio verificado y requisitos SPF/DKIM/DMARC indicados por el proveedor. No se modificaron DNS ni se contrató un servicio.
- `QUEUE_CONNECTION=database`, tablas de cola existentes y proceso supervisado `php artisan queue:work --tries=3`. Vigilar trabajos fallidos y configurar la retención de cola, pues contiene los enlaces necesarios para enviar los correos. No usar transporte `log` en producción ni publicar cargas de cola.
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI=https://<dominio-real>/auth/google/callback` autorizado por Google; mantener secretos solo en servidor. No se modificó Google Cloud.
- Caché compartida persistente para límites de frecuencia y sesiones persistentes. Regenerar caché de configuración al configurar el entorno, fuera de esta entrega local.

No se declara operativo SMTP real ni OAuth con credenciales reales hasta una comprobación autorizada en su entorno.

## Evidencia y límites

PostgreSQL local 17.11, base desechable: `php tests/run-pilot.php pgsql AccountSecurityTest` → **6 pruebas, 174 aserciones correctas**. Incluye registro, enlaces vencidos/reutilizados, reenvío, transición real de migración sin falsear verificación, rutas alternativas, colisión de correos, conservación del propietario y versión del perfil, OAuth con estado/claims manipulados, registro separado, desafío 2FA, desvinculación, recuperación genérica y de un solo uso, primera contraseña Google, sesión obsoleta y prueba de identidad vencida. Se verificaron migraciones de subida/bajada/subida en PostgreSQL y subida en SQLite ficticia. No se ejecutó la suite completa.

Chromium: `tests/browser/account_security.py` con `tests/manual/account-security.php` en `127.0.0.1:8096` → consulta offline del estado de cuenta, nombre pendiente tras recarga, cambio real de correo mediante enlace capturado, cambio de contraseña y conservación de usuario/bóveda/UUID pendiente, posterior sincronización y registro nuevo restringido con acceso a corrección. Se midieron e inspeccionaron las vistas a 390/1440 px, sin desbordamientos; las acciones se ejecutaron con interacción táctil nativa. El detector de diseño solo señaló convenciones existentes (Inter, título con degradado y previsualización de foto inicialmente oculta); se conservaron por identidad y alcance. No es una prueba en dispositivos físicos.

Se corrigieron durante la verificación dos detalles del ciclo de sesión: actualizar las claves de contraseña de ambos guards (Fortify y Jetstream) y descartar el usuario ya resuelto por Sanctum al revocar una sesión. El Service Worker entrega las redirecciones de navegación sensibles al navegador, en lugar de seguirlas internamente; las acciones de seguridad regresan al perfil explícitamente.

Regresión focalizada: `remaining_integrated.py --accounts-only` en el router ficticio `tests/manual/workspace.php` (`127.0.0.1:8094`) → **correcta**. Cierre de sesión offline con pendiente, login A/B sin recarga manual ni 419, bloqueo de la otra pestaña, retorno al propietario y una sola escritura del pendiente, formulario con sesión vencida renovado, logout/login online, CSRF inválido rechazado y autenticación ausente de Cache API. Se reutiliza el ejecutor y el recorrido anterior; no se ejecutan los escenarios financieros no afectados.

MySQL, dispositivos físicos, Safari, Firefox, presión de almacenamiento y grandes volúmenes siguen pendientes para las fases correspondientes. OAuth se verifica con respuestas simuladas y el proveedor Socialite real (incluida su validación de estado), sin contactar Google. Una cuenta sin correo verificado ni otro método de identidad demostrable requiere asistencia legítima: no se agrega una vía insegura de recuperación. SMTP y Google reales requieren verificación autorizada de configuración antes de afirmar que están operativos.

`npm run build`, comprobación sintáctica del Service Worker y `git diff --check` correctos. No hay regresiones conocidas en los escenarios comprobados. **Fase 6.1 implementada y verificada dentro del alcance local descrito**, con integraciones reales pendientes de configuración/validación. HEAD permanece en `cd080786f0c1a04c02c3724d717e5bb926223b5c`; no hay commit, push, despliegue, acceso a producción ni inicio de la fase 6.2.

## Archivos de esta entrega

43 archivos modificados o nuevos; public/build permanece generado e ignorado.

```text
.env.example
app/Actions/Fortify/CreateNewUser.php
app/Actions/Fortify/ResetUserPassword.php
app/Actions/Fortify/UpdateUserPassword.php
app/Actions/Fortify/UpdateUserProfileInformation.php
app/Auth/VerifiedPasswordBroker.php
app/Auth/VerifiedPasswordBrokerManager.php
app/Http/Controllers/AccountSecurityController.php
app/Http/Controllers/OfflineReadController.php
app/Http/Controllers/ProfileController.php
app/Http/Controllers/SocialiteController.php
app/Http/Middleware/AccountRequestThrottle.php
app/Http/Middleware/AccountSessionVersion.php
app/Http/Middleware/OfflineBrowserIdentity.php
app/Http/Middleware/VerifiedOrLegacyAccount.php
app/Http/Responses/PrivatePasswordLinkResponse.php
app/Http/Responses/PrivatePasswordResetFailure.php
app/Models/User.php
app/Notifications/AccountMail.php
app/Notifications/AccountNotice.php
app/Notifications/AccountPasswordReset.php
app/Notifications/AccountVerification.php
app/Providers/FortifyServiceProvider.php
app/Services/AccountSecurity.php
bootstrap/app.php
config/fortify.php
database/migrations/2026_11_24_000001_secure_account_access.php
docs/account-security-phase-6-1.md
public/sw.js
resources/js/offline-guard.js
resources/js/sync/dashboard.js
resources/js/workspace.js
resources/views/auth/register.blade.php
resources/views/auth/verify-email.blade.php
resources/views/dashboard.blade.php
resources/views/emails/account.blade.php
resources/views/profile/edit.blade.php
resources/views/profile/security-password.blade.php
resources/views/profile/security.blade.php
routes/web.php
tests/Feature/AccountSecurityTest.php
tests/browser/account_security.py
tests/manual/account-security.php
```
