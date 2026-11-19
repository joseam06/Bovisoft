# Consolidación de sincronización — Fase 4

Se conservan el CRUD y las vistas habituales de Fincas/Animales, IndexedDB cifrado, Service Worker, UUID, versiones, borrados lógicos y resolución explícita de conflictos. No se amplía la cobertura funcional de otros módulos ni se cambia el diseño responsive de Fase 3.1.

## Responsabilidades compartidas

- `resources/js/sync/protocol.js`: validación completa de confirmaciones y descargas, aplicación de cambios, preparación inmutable del envío, orden según dependencias y cálculo de reintentos. El registro de módulos solo habilita Fincas y Animales.
- `resources/js/sync/leadership.js` y `public/workspace-store.js`: un emisor por origen/cuenta/época mediante reserva atómica en IndexedDB. Web Locks sigue siendo una optimización opcional. La reserva dura 15 segundos, se renueva cada 5 y cada solicitud tiene un máximo de 30 segundos. Estos plazos son internos del transporte; no limitan el acceso offline.
- `resources/js/workspace.js`: integración con formularios, interfaz y autenticación existentes. Captura cuenta/época por ejecución y comprueba nuevamente su vigencia antes de aplicar resultados. Una respuesta de una sesión anterior no modifica la copia de la cuenta actual.
- `WorkspaceSyncController::operation`: confirma protocolo, cuenta, operación y registro. Se conservan los campos anteriores para compatibilidad. El cliente nuevo conserva pendientes si recibe una confirmación antigua/incompleta; requiere desplegar el servidor compatible antes del cliente en una futura publicación.
- `WorkspaceSync`, `WorkspaceRecords` y `WorkspaceJournal`: autorización, reglas de negocio, escritura, efectos asociados, versiones, diario y recibo dentro de la misma transacción. Los formularios normales siguen publicando cambios mediante los modelos existentes. No se usan escrituras SQL masivas para saltar los servicios/eventos.

## Garantías y recuperación

Antes del envío se persisten su UUID, cuerpo exacto y estado iniciado. Solo una confirmación válida de la cuenta, operación, registro y versión esperados consume la cola. Los errores y conflictos conservan los datos; un envío ambiguo se repite con el mismo cuerpo y UUID. El servidor devuelve el recibo anterior sin repetir efectos.

Cada adquisición de reserva aumenta un número de turno. El commit local comprueba ese turno dentro de la misma transacción de IndexedDB que escribe la copia. Un emisor vencido no puede aplicar su confirmación después de que otra pestaña tome el control. Si se suspende/cierra una pestaña, otra puede continuar al vencer la reserva. Puede existir una solicitud antigua todavía procesándose después del vencimiento: la idempotencia transaccional del servidor garantiza un solo efecto, incluso en ese caso.

Los errores de transporte usan espera progresiva. `Retry-After` acepta segundos o fecha HTTP; la espera se comparte entre pestañas y se acota a cinco minutos. No se consume el pendiente por un timeout, desconexión, JSON inválido o confirmación ajena. Las descargas se validan completamente antes de cambiar la copia, incluidas cuentas sin registros. Las bajas remotas conservan los borradores locales para revisión; un recibo antiguo no revive bajas conocidas.

Cerrar sesión o cambiar de cuenta conserva las colas separadas y bloqueadas. El propietario las recupera al autenticarse de nuevo. No hay contraseña adicional, descarga manual ni vencimiento arbitrario de la copia. La sincronización requiere BoviSoft abierto y la cuenta activa; no se garantiza ejecución con el navegador cerrado. Borrar el almacenamiento del navegador puede eliminar pendientes que nunca llegaron al servidor.

## Preparación de Fase 5

Para habilitar otro módulo: incorporar su adaptador a `WorkspaceRecords`, serialización/versionado/eventos a `WorkspaceJournal`, autorización de descarga y registro explícito en `WorkspaceSync`; registrar sus dependencias en el contrato del cliente y conectar las vistas habituales. El transporte, reserva, cifrado, cola, recibos y validaciones del protocolo se reutilizan. Deben declararse los efectos secundarios y su transacción antes de habilitar escrituras (por ejemplo, Salud/Finanzas). No basta agregar un nombre al registro de módulos.

La API previa de Producción y su consulta automática se conservan. Su escritura offline y la de Potreros, Salud, Finanzas y demás módulos siguen fuera de esta fase. No se añaden migraciones ni sintaxis SQL específica de un motor.

## Verificación selectiva

Archivos intervenidos en esta fase:

- Cliente: `resources/js/workspace.js`, nuevos `resources/js/sync/protocol.js` y `resources/js/sync/leadership.js`, `public/workspace-store.js`.
- Servidor: `app/Http/Controllers/WorkspaceSyncController.php`, `app/Services/WorkspaceSync.php`.
- Verificación: `tests/run-pilot.php`, nuevos `tests/Feature/WorkspaceSyncPhaseFourTest.php`, `tests/Unit/sync-protocol.test.mjs`, `tests/browser/sync_integrity.py`, `tests/run-sync-browser.ps1`.
- Documentación: `docs/offline-workspace.md` y este archivo. Se regeneró el build local de Vite. Las modificaciones de fases anteriores permanecen en el árbol de trabajo.

```powershell
rtk proxy node --test tests/Unit/sync-protocol.test.mjs
rtk proxy php tests/run-pilot.php pgsql "WorkspaceSyncPhaseFourTest|test_replaying_a_committed_farm_and_its_dependent_animal_is_idempotent|test_animal_potrero_counts_and_receipts_commit_once_and_invalid_moves_are_atomic|test_concurrent_workspace_replays_have_one_effect_in_postgresql|test_concurrent_workspace_edits_return_one_conflict_in_postgresql"
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-sync-browser.ps1
rtk proxy npm run build
```

El ejecutor PHP crea y elimina únicamente una base local desechable y admite un filtro específico; omitirlo ejecuta las suites anteriores y no es necesario para esta fase. El navegador utiliza el servidor ficticio de `tests/manual/workspace.php` en `127.0.0.1:8094` (SQLite temporal), sin conexiones externas. PostgreSQL valida por separado los riesgos transaccionales y de concurrencia del servidor. Las pruebas de navegador no acreditan dispositivos físicos, Safari o Firefox.

Resultado local: cuatro pruebas agrupadas del contrato JavaScript, seis pruebas PostgreSQL 17.11 (74 aserciones), dos recorridos Chromium y compilación Vite correctos. PostgreSQL cubrió recibos y reenvíos, concurrencia real entre procesos, conflicto de versiones, aislamiento y rollback después de escribir el dominio pero antes de confirmar el recibo, incluidos diario, contador de códigos y potrero. Chromium cubrió dos pestañas sin Web Locks, espera compartida tras 429, confirmación inválida, pérdida de la reserva con respuesta tardía, finca→animal, expiración de sesión, cuentas A/B y recuperación del pendiente al reiniciar el navegador. No se repitió la suite de 101 pruebas ni las verificaciones responsive de Fase 3.1, cuyas vistas/estilos permanecen sin cambios en esta fase. MySQL/MariaDB conserva evidencia previa; no se ejecutó de nuevo en esta fase.

Estado: Fase 4 completada en el alcance local verificado de Fincas y Animales. Quedan para validación posterior los dispositivos físicos/otros motores de navegador y una revisión de publicación; no se realizó despliegue, conexión a producción, commit ni push. Para pausar el transporte se conserva `OFFLINE_WORKSPACE_ENABLED`; nunca se borran IndexedDB ni recibos para deshabilitarlo.
