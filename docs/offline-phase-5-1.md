# Fase 5.1 — Potreros, Producción, Salud y Dashboard

Trabajo local en `feature/offline-first`, desde `b861609`. Se reutilizan cifrado, IndexedDB, Service Worker, cola, UUID, recibos, versiones, reservas entre pestañas y resolución explícita de conflictos. No hay nueva interfaz, activación, instalación, descarga manual ni límite temporal de acceso.

| Componente | Cobertura implementada |
| --- | --- |
| Potreros | CRUD, consulta de animales, asignación/traslado desde Animal, capacidad, disponibilidad, ocupación, última rotación/descanso y avisos. No se permite reducir capacidad por debajo de la ocupación ni trasladar/eliminar un potrero con animales. |
| Producción | CRUD de leche, historial general/animal, filtros, totales, ranking mensual y gráficas. Reutiliza `ProduccionService`: elegibilidad al crear, referencias históricas originales, revisión online de fecha si el animal se trasladó/inactivó, mediciones independientes sin restricción animal/fecha/turno. |
| Salud | CRUD, categorías y tipos existentes, vacunas/tratamientos/medicamentos/controles, costos, dosis/vías, historial, seguimiento, próxima aplicación y carencias. Derivados recalculados localmente. |
| Dashboard | Inventario, fincas, clasificación, potreros/ocupación, eventos sanitarios, carencias/próximas aplicaciones y producción mensual/diaria; barras/dona y selectores 7/30 días/año con datos locales. Actualiza con cambios locales/remotos sin contabilizar dos veces. Alertas agregadas conservan identificada su última información confirmada. |

No existen geometrías/coordenadas de Potreros ni una entidad separada de rotaciones en el repositorio; no se inventan operaciones. Las coordenadas existentes de Fincas se conservan. Teselas/geocodificación requieren Internet y no bloquean formularios. Producción solo implementa leche.

Salud no crea egresos automáticamente: el flujo existente los registra por separado en Finanzas. Se conserva ese comportamiento, sin duplicar cargos. Si existe un egreso activo vinculado, el servidor impide eliminar el evento o cambiar costo, fecha, producto, animal o finca: requiere revisión en Finanzas con conexión. La interfaz aplica esa protección cuando conoce el vínculo; el servidor la repite transaccionalmente aunque el dispositivo tenga datos anteriores. No se habilita CRUD offline de Finanzas ni nuevos cargos desde el cliente. Sus consultas de costos sanitarios continúan usando los registros existentes.

## Integridad y actualización

- `WorkspaceModules` registra los cinco módulos; `WorkspaceRecords` delega en `PotreroService`, `SaludService` y el `ProduccionService` existente, compartidos con los formularios normales.
- Workspace descarga los registros autorizados completos, sin el recorte anterior de 90 días de Producción. Conserva el límite técnico de 10.000 registros por módulo; si no puede completar la copia, mantiene la anterior y no anuncia totales completos.
- UUID resuelve padres nuevos antes de los hijos. Los cuerpos iniciados no se modifican y ninguna operación se retira sin una confirmación válida. Las bajas tienen versión y pueden deshacerse antes del envío.
- `ProductionJournal` publica también en Workspace; mantiene la API anterior por compatibilidad. Adquiere primero el bloqueo de Workspace y después el de Producción. No se crea otra cola/copia operativa.
- La ocupación se actualiza/publica dentro de la transacción del Animal, incluidas escrituras Eloquent de flujos online como ventas. El servidor revalida capacidad y versiones.
- El vínculo de un egreso online usa el mismo bloqueo transaccional que Salud, autoriza sus referencias y publica una nueva versión sanitaria al vincular/desvincular. Evita carreras con una edición/baja sanitaria sin habilitar escrituras offline de Egresos.
- La copia pasa automáticamente a esquema 3 conservando pendientes, cuerpos/UUID iniciados, decisiones y separación por cuenta. Una actualización incompleta no habilita módulos sin sus datos.
- `OfflineModuleViews` reutiliza las vistas Blade; los adaptadores solo enlazan campos, tablas, filtros y estadísticas. Se corrigió una carrera donde deshacer una baja podía reemplazar un formulario recién abierto; las respuestas tardías respetan navegación y borradores.
- Chart.js, ya utilizado por la web, se sirve/guarda localmente, también al recargar offline. Conserva tipos, colores y contenedores responsive. No se instala otra dependencia.

## Migraciones y publicación pendiente

`2026_11_19_000001_extend_workspace_modules.php` incorpora UUID/versiones y contadores de Potreros/Salud conservando IDs, códigos y bajas lógicas. Producción conserva sus columnas existentes.

`2026_11_19_000002_align_salud_types.php` corrige un desajuste previo: la interfaz ofrecía 17 tipos y la base solo admitía seis. Amplía el CHECK de PostgreSQL o el enum de MySQL/SQLite. El rollback se niega si existen tipos nuevos para evitar pérdida de datos.

Solo se aplicaron a bases locales ficticias/desechables. Una futura publicación requiere migraciones antes del código y servidor compatible antes del cliente: los formularios normales también usan columnas/diarios nuevos con el flag apagado. No borrar IndexedDB, diarios o recibos al pausar el transporte.

## Verificación selectiva

```powershell
rtk proxy php tests/run-pilot.php pgsql PhaseFiveOfflineTest
rtk proxy node --test tests/Unit/sync-protocol.test.mjs tests/Unit/module-views.test.mjs
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-phase-five-browser.ps1
```

Playwright utiliza el servidor ficticio de `tests/manual/workspace.php` en `127.0.0.1:8094` y `BOVISOFT_BROWSER_TEST_PASSWORD` con la clave local de sus cuentas. No lee `.env` ni accede a servicios externos. `-Smoke` comprueba únicamente una copia Fase 4 con pendiente y los controles/gráficas del Dashboard.

Resultados: cuatro escenarios PostgreSQL 17.11 correctos, 88 aserciones; después de integrar la ocupación en el modelo, dos comprobaciones focalizadas correctas, 41 aserciones, incluido rollback de dominio/recibo/potrero. La protección final del vínculo sanitario/egreso pasó otra comprobación de 21 aserciones. MariaDB 10.4.28 pasó una comprobación de migraciones/vistas/aislamiento, 30 aserciones. Siete pruebas JavaScript correctas. Chromium verificó los cuatro componentes con formularios táctiles, recarga/persistencia, traslados, deshacer/bajas, respuesta sanitaria perdida sin duplicar, alta remota y conflicto productivo resuelto explícitamente. El recorrido acotado adicional comprobó la actualización automática de una copia Fase 4 con pendiente, recuperación de una página tras 429 y los selectores/gráficas del Dashboard. Sin desbordamiento en 390/1440 px; el segundo navegador utilizó 768 px. No se repitieron las 101 pruebas ni suites completas.

Pendientes: dispositivos físicos, Safari/Firefox, MySQL 8 específico y gran volumen. No se conectó a Supabase de producción. Borrar/expulsar el almacenamiento puede eliminar pendientes no confirmados; sincronizar requiere la web abierta y la cuenta activa.

## Archivos y continuación

Se modifican los controladores de Potreros/Salud/Dashboard y de vistas offline; modelos Potrero/Salud, vínculo de Egreso y `SyncsWorkspace`; servicios Workspace y ProductionJournal; vistas originales de los tres módulos, Dashboard/layout; `workspace.js`, contrato, Service Worker; documentación y pruebas. Se agregan `WorkspaceModules`, `PotreroService`, `SaludService`, `OfflineModuleViews`, dos migraciones, `sync/module-views.js`, `sync/dashboard.js`, Chart.js/licencia, `PhaseFiveOfflineTest`, `module-views.test.mjs`, `phase_five.py` y su ejecutor.

Finanzas puede reutilizar este núcleo, pero necesita un adaptador con autorizaciones de referencias, transacciones de ventas/estado de Animal, vínculos sanitarios y conciliación explícita de importes. No se habilita en esta etapa, ni se amplía CRUD offline de Alertas, Reportes o Mi perfil. Todo permanece local, sin commit/push ni despliegue.
# Nota sobre el estado posterior

Este documento conserva el alcance y los resultados del cierre de Fase 5.1. La ampliación posterior, la corrección de sesión y el segundo punto de control de Git están descritos en [Fases 5.2/5.3](offline-phase-5-2-5-3.md).
