# Fases 5.2 y 5.3: cobertura compatible y consolidación local

La ampliación conserva la rama `feature/offline-first`, las vistas Blade y el núcleo de las fases anteriores. No hay otra base local, cola, interfaz de preparación, clave adicional ni vencimiento temporal. La aplicación sigue siendo una web; no exige instalación.

## Seguridad e integridad financiera

- `FinancialService` es la entrada común para los formularios y `WorkspaceRecords`/`WorkspaceSync`. Valida finca autorizada, animal propio de esa finca y evento sanitario compatible. `AuthorizesFinance` limita listados, detalles, agregados y descargas.
- Ventas y reversiones incluyen ingreso, animal, ocupación y diario dentro de la transacción del propietario. Los bloqueos, la versión del animal y una clave foránea nullable única para la venta activa impiden reemplazos o ventas concurrentes. Revertir no inventa una asignación anterior de potrero.
- Los formularios también envían UUID de operación/registro/dispositivo; el recibo durable permite repetir el envío si se pierde la respuesta. Mediciones o movimientos independientes mantienen UUID diferentes.
- Los importes se validan con dos decimales. Los cálculos financieros usan centavos enteros en PHP y `BigInt` en JavaScript; las gráficas convierten a número solo para dibujar.
- Un egreso vinculado a Salud actualiza su versión/protección dentro de la misma transacción. El costo sanitario y el egreso siguen siendo conceptos separados: no se genera un segundo cobro automático ni se suma el costo otra vez al balance.

## Cobertura por módulo

| Componente | Disponible sin Internet con copia automática completa | Límite real |
| --- | --- | --- |
| Fincas y Animales | CRUD, dependencias, deshacer bajas no iniciadas y sincronización | Historial relacionado protege eliminaciones; fotografías no se cargan offline. |
| Potreros | CRUD, asignaciones/traslados existentes, ocupación, capacidad y fechas de rotación | Coordenadas numéricas disponibles; mapas, teselas y geocodificación necesitan Internet. No existen geometrías en el modelo actual. |
| Producción | CRUD de leche, historial, indicadores y reglas históricas de `ProduccionService` | No existen otros tipos de producción ni ingreso automático por leche. |
| Salud | CRUD y categorías existentes, seguimientos, carencia y vínculo con egresos | Se conservan las restricciones de eventos con movimientos vinculados. |
| Finanzas | Consulta, alta, detalle y eliminación/reversión de ingresos y egresos; ventas; categorías, filtros, balance y gráfica | **No existe edición financiera en las rutas/formularios originales.** No se inventó una acción de actualización; la API la rechaza. |
| Dashboard | Estadísticas/gráficas locales y alertas derivadas, incluidas operaciones pendientes | Solo indicadores existentes. Información externa conserva su límite y no se sustituye por totales desconocidos. |
| Alertas | Reglas sanitarias compartidas con PHP: próximas/vencidas, tratamientos/carencias; rotación derivada de fechas existentes; campana y centro habituales | No existen estados persistidos de lectura, campos de parto ni servicio de notificaciones con navegador cerrado. |
| Reportes | Seis plantillas originales: inventario, salud, vacunación/desparasitación, tratamientos, historial animal y financiero; filtros y pendientes locales | Vista local e impresión/Guardar como PDF del navegador. La descarga binaria habitual de DomPDF sigue online. No existen PDF productivos o de finca independientes. |
| Mi perfil | Información de la cuenta activa y edición del nombre con versión, recibo y conflicto | Correo, contraseña, foto, autenticación y recuperación permanecen online. No se guardan contraseñas/tokens de autenticación en la cola. |

## Contrato y actualizaciones

`WorkspaceModules` registra ocho tipos persistentes. Alertas, reportes y Dashboard son proyecciones de esos registros y no tienen colas propias. El esquema local 4 conserva operaciones/archivo de conflicto de copias anteriores y solo sustituye la copia completa tras validar todas las páginas, las vistas y el manifiesto.

Las confirmaciones pueden incluir efectos relacionados autorizados (animal, potrero, salud). `protocol.js` valida toda la respuesta antes de consumir la operación y aplica versiones monotónicas. Los recibos incluyen esos efectos: perder la respuesta no exige volver a ejecutar la venta. Se conservan la reserva compartida entre pestañas, el control de cuenta/época, reintentos, tombstones y borradores frente a eliminaciones remotas.

Los reportes identifican la vista local y operaciones no confirmadas/conflictivas; exigen cobertura completa de sus módulos. La exportación offline reutiliza el HTML original, el logo embebido y la impresión del navegador, sin dependencias nuevas. Los controles sensibles se retiran del HTML persistido.

## Verificación focalizada

- PostgreSQL 17.11: `FinancialIntegrityTest` (4 pruebas, 58 aserciones) y los dos primeros escenarios de `PhaseRemainingOfflineTest` (61 aserciones): autorización, UUID, venta/reversión, rollback, concurrencia real entre procesos, plantillas, perfil y reglas de alertas.
- PostgreSQL 17.11: escenario adicional `test_linked_expense_ack_replay_rollback_and_reversal_preserve_health_history` (13 aserciones), incluido fallo después de escribir dominio y antes de confirmar recibo.
- JavaScript: `remaining.test.mjs` y `sync-protocol.test.mjs`, 6 pruebas: precisión, confirmaciones/efectos relacionados inválidos, reintentos, dependencias y borrados remotos.
- Chromium con servidor ficticio SQLite: recorrido integrado de ocho operaciones Finca → Potrero → Animal → Producción/Salud → Egreso/Venta y nombre; seis reportes, Dashboard/alertas, deshacer, recarga y cierre/reapertura completa; reconexión, dos pestañas, respuesta perdida y un solo efecto por UUID. La interfaz se ejercita con toques a 390 px. La evidencia transaccional de PostgreSQL es independiente de SQLite.
- La comprobación inicial de cuentas confirmó el aislamiento A/B y detectó un `419` después del cierre offline. Esa incidencia quedó corregida y verificada en el cierre de Fase 5.3 descrito abajo; la prueba ya no permite recargar el login ni reintentar un acceso fallido.
- La matriz anterior de sesión vencida, reservas, cuentas y responsive se reutiliza donde el mecanismo no cambió. No se ejecuta la suite completa ni se repiten las 101 pruebas.

Comandos selectivos (el ejecutor PHP crea y elimina únicamente una base local desechable):

```powershell
rtk proxy php tests/run-pilot.php pgsql "FinancialIntegrityTest|PhaseRemainingOfflineTest"
rtk proxy node --test tests/Unit/remaining.test.mjs tests/Unit/sync-protocol.test.mjs
rtk proxy npm run build
```

El recorrido `tests/browser/remaining_integrated.py` usa `tests/manual/workspace.php` en `127.0.0.1:8094` y requiere `BOVISOFT_BROWSER_TEST_PASSWORD` con la contraseña ficticia elegida al preparar ese dataset. `--accounts-only` ejecuta únicamente el grupo de sesión, cuentas y conservación/sincronización de pendientes, sin recorrer los módulos financieros. No se guardan credenciales en archivos. `tests/install-browser.ps1 -RuntimeRoot <directorio>` permite reutilizar herramientas locales fuera de una carpeta temporal que haya sido limpiada.

`tests/run-remaining-browser.ps1 -RuntimeRoot <directorio>` ejecuta ese recorrido con consola UTF-8; `-AccountsOnly` limita la ejecución al último escenario.

## Archivos de esta ampliación

- Dominio: `app/Services/FinancialService.php`, `Money.php`, `AlertRules.php`, `OfflineRemainingViews.php`; `app/Models/Concerns/AuthorizesFinance.php`, `app/Models/WorkspaceProfile.php`, `Ingreso.php`, `Egreso.php`, `User.php`.
- Integración: `app/Services/WorkspaceModules.php`, `WorkspaceRecords.php`, `WorkspaceJournal.php`, `WorkspaceSync.php`; controladores `FinanzasController`, `AlertaController`, `ProfileController`, `ReportesController`, `OfflineReadController` y `OfflineViewController`.
- Base: migraciones `2026_11_21_000001_extend_workspace_finance.php` y `2026_11_21_000002_create_workspace_profiles.php`.
- Cliente: `resources/js/workspace.js`, `resources/js/sync/{protocol,finance,money,alerts,reports,dashboard,module-views}.js`, `public/sw.js` y build local regenerado.
- Vistas originales: `resources/views/finanzas/` (incluido el parcial de UUID), `alertas/` (incluido el parcial de tarjeta del Dashboard), `reportes/index.blade.php`, los seis PDF y su `_layout`, `profile/edit.blade.php`, `dashboard.blade.php` y `layouts/dashboard.blade.php`.
- Mantenimiento: pruebas financieras/remaining de Feature, `tests/Unit/remaining.test.mjs`, `tests/browser/remaining_integrated.py`, ejecutor del navegador y parámetro opcional del instalador de herramientas; README y documentación offline. Las modificaciones locales anteriores de Fase 5.1 se conservaron.

## Límites y siguiente revisión

Las migraciones usan UUID, versiones y restricciones portables; PostgreSQL tiene evidencia ejecutada. MySQL conserva la evidencia de fases anteriores, pero **no se afirma que estas dos migraciones nuevas hayan sido ejecutadas allí**. La migración financiera se detiene si encuentra varias ventas activas de un animal; los datos históricos deben revisarse antes de cualquier publicación, sin corregirlos automáticamente ni alterar importes.

Quedan fuera de esta validación dispositivos físicos, Safari/Firefox, presión/limpieza del almacenamiento del navegador y la impresión física/diálogo PDF. El almacenamiento persistente se solicita al navegador, que sigue pudiendo borrarlo por decisión del usuario o del sistema. No hay garantía de sincronización ni notificaciones con la web completamente cerrada.

## Cierre de sesión de Fase 5.3 — 24 de noviembre de 2026

**Causa raíz:** una carrera entre la reconciliación del logout pendiente, las comprobaciones de identidad de varias pestañas y el GET de login. Laravel invalidaba la sesión y regeneraba el CSRF; además, el logout solicitado como JSON seguía una redirección HTML. La traza del servidor confirmó que el formulario y `/offline/session` podían coincidir entre sí, pero el POST llegaba con la cookie de la otra sesión y Laravel esperaba otro token. No era un login recuperado de Cache API. La instrumentación temporal se retiró y no se conservaron cookies, tokens ni credenciales en registros.

**Solución:** el envío de los formularios de autenticación completa primero la reconciliación compartida de sesión y actualiza exclusivamente el CSRF en memoria. Después se realiza un único POST nativo con el formulario habitual, sin leer ni copiar contraseñas, sin reintentos de autenticación y sin recargar la página. El logout online utiliza el mismo coordinador y un token vigente; el servidor responde `204` a JSON, conservando la redirección para el formulario HTML normal. Autenticación y `/offline/session` usan `no-store`; el Service Worker los trata como solicitudes exclusivamente de red y nunca sustituye una autenticación fallida por el shell cacheado. IndexedDB y las operaciones no se borran ni se migran.

**Verificación nueva:** un grupo Chromium a 390 px, con la base SQLite ficticia local y dos pestañas, terminó con código 0:

1. Login online, edición pendiente y cierre offline con bloqueo inmediato de las otras pestañas.
2. Reconexión y un solo inicio de sesión de B, sin recarga/reintento ni `419` de login.
3. Aislamiento B/A y conservación del UUID y contenido de la operación de A en su bóveda.
4. Logout online de B y regreso de A desde el mismo documento de login cuya sesión se invalidó deliberadamente: renovación automática y una sola escritura de su pendiente.
5. Invalidación de una sesión autenticada con otra operación pendiente: bloqueo entre pestañas, reautenticación y confirmación única.
6. Ausencia de autenticación en Cache API; un token CSRF deliberadamente inválido siguió recibiendo `419` (control negativo esperado). No se deshabilitó CSRF ni se añadieron excepciones.

También pasaron la compilación Vite y las comprobaciones de sintaxis PHP/Service Worker. Se reutilizó el ejecutor existente; para repetir únicamente este grupo:

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-remaining-browser.ps1 -AccountsOnly -RuntimeRoot <directorio-de-herramientas>
```

No se repitieron las pruebas financieras, PostgreSQL ni las suites completas porque este cierre no cambia reglas de negocio, consultas o esquema de datos. La expiración se simuló invalidando la sesión del servidor; no se esperó su TTL. La emulación Chromium no sustituye pruebas físicas o de otros navegadores.

**Archivos de este cierre:** `resources/js/offline-guard.js`, `public/sw.js`, `app/Http/Middleware/OfflineBrowserIdentity.php`, `routes/web.php`, `tests/browser/remaining_integrated.py` y este informe; build local regenerado.

**Estado final:** los diez componentes de la tabla mantienen su cobertura compatible, incluidas sus limitaciones explícitas de edición financiera, exportación y servicios externos. Fase 5.3 cerrada dentro del alcance local verificado, sin incidencias conocidas bloqueantes ni pérdida/mezcla de pendientes en los escenarios comprobados. Incluida junto con Fases 5.1/5.2 en el segundo punto de control autorizado de `feature/offline-first`. MySQL nuevo, dispositivos físicos, Safari/Firefox, almacenamiento y grandes volúmenes permanecen para sus verificaciones posteriores.

El segundo punto de control autoriza exclusivamente el commit y push de estas fases a `origin/feature/offline-first`, sin fusionar `main`, crear PR ni desplegar. Conserva las exclusiones existentes de `.env`, bases locales, registros, cachés, dependencias y `public/build`; Chart.js y su licencia se versionan como recursos estáticos necesarios para funcionar sin Internet. Se reutiliza la evidencia anterior y solo se comprueban diferencias, secretos, sintaxis y el estado de Git antes de publicar.

La arquitectura queda preparada para una revisión de fase 6 dentro de la cobertura compatible indicada. No se inicia esa fase ni se realiza despliegue, modificación de Render o conexión/migración a producción.
