# Fase 6.3 — Rendimiento

## Estado y alcance

Mejoras implementadas y medidas en Dashboard, Alertas, Finanzas y persistencia cliente. La ampliación 6.3.1 autoriza intervenir en el transporte del snapshot: el catálogo de 2.000 animales ya se reconstruye correctamente en MariaDB y PostgreSQL mediante bloques. Los resultados originales de 6.3 se conservan como línea base; el apartado 6.3.1 al final recoge las verificaciones posteriores y sus limitaciones. Fase 6.4 no iniciada.

Base `f6ef4a362ffe21217d7d3f4d55523b7605da536d`, rama `feature/offline-first`. No hubo commit, push, migraciones en la base habitual, cambios de `.env`, producción ni respaldos. Se crearon y eliminaron exclusivamente bases desechables con nombres `bovisoft_test_piloto_*`.

## Método

- PHP 8.2.4; MariaDB 10.4.28 local; comprobación funcional adicional en PostgreSQL 17.11 local.
- Misma estructura, volúmenes, valores y fechas en ambas mediciones: 100/500/2.000 animales, otros tantos registros productivos y sanitarios, 1/5/20 fincas y potreros, 20/100/400 ingresos y egresos. Identificadores UUID y datos de usuario de factory varían por corrida; no se comparan documentos byte a byte. Fecha de backend fijada al 24/11/2026, eventos sanitarios de tratamiento, importes decimales ficticios, sin efectos reales.
- Dos muestras consecutivas por página/volumen, mismo orden; tabla muestra promedio. Contador SQL incluye consultas durante el request y render Blade, con query log de pruebas; duración excluye migración/siembra/arranque PHP y no equivale a TTFB de producción. Memoria PHP es pico del asignador del proceso, no memoria aislada atribuible a cada controlador.
- Chromium a 390×820, CPU ×4, latencia de 100 ms, descarga 200.000 B/s y subida 100.000 B/s. Un recorrido completo antes/después con 100 animales; no son dispositivos físicos ni mediciones estadísticas P95. Navegadores y bases aislados: no se borró el almacenamiento habitual.

## Cuellos de botella y cambios

1. Alertas serializaba registros con `WorkspaceJournal`, consultando finca/animal/egreso por cada fila, pese a necesitar solo datos de reglas. Ahora selecciona esos campos y carga las relaciones de nombres anticipadamente, manteniendo filtros de propiedad y las mismas reglas.
2. Dashboard generaba todas las alertas dos veces y hacía siete consultas productivas diarias. Reutiliza la colección de la solicitud, agrupa producción por fecha y reúne tres conteos sanitarios en una consulta condicional. No hay caché entre solicitudes/cuentas ni resultados persistentes obsoletos.
3. Finanzas ejecutaba dos sumas por finca y doce sumas mensuales. Ahora usa agrupación por finca y dos agregaciones condicionales para los seis intervalos originales. Se conservan filtros, borrados lógicos y `Money` para precisión; no se modifican ventas ni escrituras financieras.
4. Cliente omitirá cifrado/commit/redibujado cuando la identidad confirmada no cambia el perfil. El no-op se decide dentro del bloqueo existente después de leer la revisión actual; las confirmaciones de operaciones, cursores, último éxito, CAS y leases conservan sus escrituras. También se comparte una proyección entre perfil y campana durante el mismo render. No se agregan cachés globales de proyecciones ni se modifica el protocolo.

## Comparación de backend

| Animales | Página | SQL antes → después | ms antes → después | Reducción de tiempo |
|---:|---|---:|---:|---:|
| 100 | `/dashboard` | 632 → 17 | 983.37 → 118.34 | 88.0% |
| 100 | `/finanzas` | 25 → 15 | 79.92 → 99.22 | -24.1% |
| 100 | `/api/alertas/recientes` | 306 → 5 | 356.34 → 53.27 | 85.1% |
| 500 | `/dashboard` | 3040 → 17 | 3222.48 → 231.19 | 92.8% |
| 500 | `/finanzas` | 33 → 15 | 100.73 → 97.41 | 3.3% |
| 500 | `/api/alertas/recientes` | 1510 → 5 | 1655.42 → 198.95 | 88.0% |
| 2000 | `/dashboard` | 12070 → 17 | 13333.89 → 839.84 | 93.7% |
| 2000 | `/finanzas` | 63 → 15 | 179.45 → 136.99 | 23.7% |
| 2000 | `/api/alertas/recientes` | 6025 → 5 | 6384.89 → 868.88 | 86.4% |

Un valor negativo significa empeoramiento observado. En 100 animales Finanzas no ganó tiempo, aunque bajó consultas; no se oculta esa variación. Son resultados locales de esta corrida, sensibles a carga del equipo y calentamiento. No se extrapolan a Render. No se añadieron índices ni migraciones: el problema principal era la multiplicación de consultas, no una restricción o índice ausente. Se usan agregaciones SQL portables, comprobadas en PostgreSQL.

El listado de Animales ya usa eager loading (3 consultas), pero entrega todos los registros: aproximadamente 0,62/2,95/11,70 MB de HTML para 100/500/2.000. Su consulta no es el problema principal. No se cambió a paginación de servidor porque los buscadores y filtros actuales trabajan sobre todo el listado; hacerlo correctamente requiere integrar búsqueda, filtros y navegación offline, evitando perder resultados. Permanece pendiente.


Picos del asignador PHP para 2.000 animales (MiB; no son memoria exclusiva de cada request):

| Página | Antes | Después |
|---|---:|---:|
| /dashboard | 92 | 68 |
| /finanzas | 92 | 68 |
| /animales | 140 | 120 |
| /api/alertas/recientes | 110 | 88 |

## Cliente y offline: 100 animales

| Medida | Antes | Después |
|---|---:|---:|
| Preparación completa, s | 24.603 | 22.913 |
| Commits cifrados durante 32 s sin edición | 4 | 3 |
| Copia cifrada, bytes | 2434342 | 2434342 |
| Solicitudes del recorrido completo | 79 | 79 |
| Transferencia codificada del recorrido, bytes | 3281777 | 3271489 |
| Heap JS observado, bytes | 13039116 | 7622820 |
| Nodos DOM observados | 5615 | 5604 |
| Navegación offline al listado, s | 35.863 | 37.54 |
| Una edición pendiente hasta sincronización, s | 25.651 | 19.912 |

La versión final también revalida el actor después del callback antes de aceptar el retorno sin escritura. Tras añadir esta comprobación se ejecutó la validación funcional de sesión descrita abajo, sin repetir el perfilado móvil completo; las duraciones cliente corresponden a la pasada comparativa previa a ese chequeo adicional.

Los commits sin edición bajaron 25% (4→3). La copia local no se recortó. Preparación y sincronización bajaron en esta muestra, pero la navegación offline empeoró y sigue siendo lenta: no se declara mejorada. Heap y número de nodos son observaciones instantáneas, no límites de memoria ni pruebas de fuga. La transferencia incluye vistas, recursos y navegación del recorrido; el pequeño cambio de CSS recompilado impide atribuir toda su variación a la lógica de persistencia.

La compilación actual contiene JS de 145.572 bytes y CSS de 113.799 bytes (gzip nivel 6: 49.497 y 17.364 bytes). No se cambiaron CSS/diseño ni se añadieron dependencias. Se recompiló para actualizar el cliente. El logo `public/images/logored.png` ocupa 1.437.815 bytes (1024×1024): candidato prioritario a una variante ligera con comprobación visual, pendiente; no se alteró la identidad visual ni se presentó una reducción de imágenes inexistente.

## Preparación creciente y bloqueo del snapshot

- 500: preparación completa en 61.812 s; copia cifrada de 5357119 bytes.
- 2000: no terminó dentro del presupuesto de espera de 60 s; recorrido detenido a los 64.602 s. Esto es un presupuesto de prueba, no un límite de acceso del producto.

Diagnóstico focalizado del caso grande: catálogo de 6841 registros, 3867931 bytes, 14835 consultas y 28532.41 ms para construirlo. MariaDB admite `max_allowed_packet=1048576`; el catálogo excede ese límite y la inserción del snapshot produjo `SQLSTATE 2006 / server has gone away`. La preparación de 2.000 **no está validada** ni lista en esta configuración.

Optimizar catálogo, serialización y almacenamiento/paginación del snapshot depende de `WorkspaceSync.php`, reservado por el usuario. Queda expresamente pendiente: no se modificó ese servicio ni se aumentó globalmente la configuración de MariaDB compartida. El error afecta la preparación grande; no se reemplazó una bóveda válida ni se eliminó una cola pendiente. Tampoco se cambió el límite preexistente de registros del servicio. No se infiere que PostgreSQL/Supabase tenga el mismo límite de paquete.

No se hizo limpieza de Cache API: faltaría demostrar cuáles versiones siguen siendo necesarias para cada copia válida. Se conserva su política existente. El almacenamiento incluye HTML de vistas/páginas y datos, y el cifrado actual trabaja sobre la bóveda completa: segmentarlo requiere otra intervención medida, con recuperación y compatibilidad de versiones.

## Los seis reportes PDF existentes

Se inspeccionaron sus consultas y relaciones: ya usan eager loading y filtros. No se inventó un reporte productivo adicional. Se generaron los seis PDF reales con 100 animales y se comprobó firma `%PDF-` y consultas acotadas:

| Reporte | SQL | ms | bytes PDF | Pico PHP MiB |
|---|---:|---:|---:|---:|
| inventario | 3 | 6905.15 | 138540 | 102 |
| salud | 10 | 8270.78 | 354965 | 162 |
| vacunacion | 2 | 323.75 | 26005 | 162 |
| tratamientos | 3 | 5426.59 | 204491 | 162 |
| historial | 4 | 397.87 | 25826 | 162 |
| financiero | 5 | 1052.42 | 76294 | 164 |

El conjunto tiene eventos de tratamiento; vacunación tiene resultado vacío, e historial corresponde a un animal. Los picos acumulan capacidad del proceso entre reportes. No se modificó Dompdf ni se repitieron esos PDF para atribuir mejoras inexistentes. Con 164 MiB observados en este recorrido, se dejaron pendientes PDF de 500/2.000 y pruebas extensas de memoria. No se introdujo recorte de datos o paginado que suprima contenido.

## Verificación focalizada

- MariaDB, benchmark final y límites de fechas: **2 pruebas / 76 aserciones**, 100/500/2.000, resultados productivos, totales, flujo mensual y balances por finca.
- MariaDB, seis PDF: **1 prueba / 18 aserciones**.
- PostgreSQL 17.11, volúmenes y regla existente de dinero/alertas: **3 pruebas / 84 aserciones**.
- Chromium: antes/después del cliente, pendiente editado offline, recarga, reconexión y confirmación; sin desbordamiento horizontal a 390 px en el recorrido. Preparación adicional de 500 y límite diagnóstico de 2.000. Emulación, no dispositivos físicos.
- Comprobación cliente adicional (modo guards, sin limitación de CPU/red): dos pestañas, expiración de sesión, retirada del nombre privado en la otra pestaña, CSRF inválido rechazado con 419, CSRF válido aceptado y pendiente recuperado/sincronizado tras nuevo login. El router fuerza entorno local para conservar CSRF real; estas cifras no se mezclan con el benchmark móvil.
- `npm run build`, sintaxis y `git diff --check`; no suites completas ni las 101 pruebas anteriores. Se reutiliza la evidencia anterior donde no se modificó código.

## Infraestructura: recomendaciones no aplicadas

Solo inspección de Dockerfile, entrypoint, Composer, Vite y configuración versionada. Ya se usan autoloader optimizado y cachés de configuración/rutas/vistas al arrancar. No se ejecutaron cachés de despliegue en el entorno habitual.

Pendiente: medir imagen y contexto Docker antes de introducir etapas separadas; agregar exclusiones de contexto (.dockerignore) para secretos, repositorio, cachés y dependencias generadas; evaluar `npm ci` con lock y retirar herramientas de construcción de la imagen final. Verificar compresión y caché de recursos versionados en el servidor real. Medir límites de memoria, workers de cola y conexiones PostgreSQL/pool de Supabase en un entorno autorizado; no aumentar conexiones sin presupuesto del servicio. No se construyó ni desplegó una imagen, no se modificaron Render/Supabase/DNS ni se contrataron servicios.

## Archivos de esta fase y conservación

Producción: tres controladores (`DashboardController`, `AlertaController`, `FinanzasController`), `app/Services/AlertRules.php`, `resources/js/workspace.js`. Pruebas/herramientas: `PerformancePhase63Test.php`, `PerformanceReports63Test.php`, `tests/Support/PerformanceDataset.php`, `tests/performance/local.php`, `tests/browser/performance_phase63.py`. Documentación: este informe. Los compilados se mantienen ignorados por Git.

Huellas SHA256 de los archivos anteriores reservados, comprobadas al inicio y al cierre de 6.3 (WorkspaceSync se intervino después, con autorización de 6.3.1):

- `app/Services/WorkspaceSync.php`: `813400db01d46824f51ad2581635e92d3c798a3f58785cebf7f65e35b2446b68`.
- `tests/Feature/OfflineSecurityTest.php`: `bfa035bb89f45930e7b36e40f54c773778fafdf7d22a10d1e9380e9e1aa6477b`.
- `tests/browser/offline_security.py`: `2333dc3f596c538caaabfff3279b5ea670a2f205296707283514530cd36401df`.

## Repetición

`php tests/run-pilot.php mysql PerformancePhase63Test` y equivalente `pgsql`; usar `BOVISOFT_PERF_LABEL=before|after` para separar resultados. `PerformanceReports63Test` ejecuta los seis PDF con 100 animales. Resultados JSON de cada medición se escriben en `%TEMP%/bovisoft-performance-*.json`.

Para navegador: definir `BOVISOFT_BROWSER_TEST_PASSWORD` con una clave ficticia, ejecutar `php tests/performance/local.php prepare mysql 100`, iniciar `php -S 127.0.0.1:8097 -t public tests/performance/local.php` y ejecutar el Python del runtime existente con `tests/browser/performance_phase63.py`. Cerrar el servidor y ejecutar `php tests/performance/local.php drop` al terminar. Para 500/2.000 usar otra base desechable, `BOVISOFT_PERF_PREP_ONLY=1` y `BOVISOFT_PERF_VOLUME` correspondiente. No usar este router en un despliegue ni crear datos en `bovisoft_db`.

## Ampliación 6.3.1 — Catálogo y grandes volúmenes

### Causa y cambios

Las 14.835 consultas provenían de resolver por registro los UUID de finca, animal, potrero y venta, además de comprobar individualmente el egreso sanitario. `WorkspaceJournal::serializeMany` precarga esos vínculos por lote y conserva el serializador original para operaciones individuales. La prueba compara ambos contratos, incluidos campos, fechas y vínculos.

El snapshot anterior insertaba un JSON de 3,87 MB en un solo paquete SQL, superior al `max_allowed_packet` local de 1.048.576 bytes. La nueva tabla `workspace_snapshot_chunks` guarda bloques de 128 KiB, codificados en base64, mediante inserciones individuales; la cabecera contiene tamaño y SHA256. Se usan `mediumText` portable y FK con borrado en cascada; la migración se aplicó únicamente en bases desechables de MariaDB y PostgreSQL. No se aumentó el límite ni se recortó historial. Antes de habilitar catálogos grandes en otra base habrá que aplicar esta migración mediante el procedimiento autorizado; sin ella se conserva el contrato pequeño y los catálogos grandes devuelven 503, sin reemplazar la copia local.

El cliente solicita `catalog-chunks-v1`, valida cuenta, orden, tamaños, integridad y cursor final antes de publicar el catálogo en la bóveda. Los clientes y snapshots anteriores conservan el contrato de páginas de registros. Una descarga interrumpida puede reutilizar los bloques recibidos en esa pestaña; al recargar se conserva la copia completa anterior y se reinicia la descarga si hace falta. Los fragmentos incompletos no se publican como datos utilizables. Se mantienen CAS, cifrado, controles de actor y cola existentes.

El listado local de animales renderiza 50 filas con `DocumentFragment`, manteniendo todos los registros para búsqueda y filtros antes de paginar. Se conserva la tabla habitual y se evita guardar/cargar su HTML de miles de filas cuando ya existe la plantilla Blade. No hay interfaz offline independiente.

La paginación por sí sola no resolvía la navegación: con 500 animales se midieron **139,977 s** antes de completar estas optimizaciones. `canonicalPath` reconstruía/enriquecía todo el catálogo por cada enlace, y `enrich` creaba un `Intl.DateTimeFormat` por cada consulta de fecha de cada animal/evento. Se reutiliza el modelo durante el ajuste de enlaces de una pantalla y un único formateador, calculando el día colombiano una vez por proyección. Se comprobó el cambio de día de Bogotá, edades, ocupación, rotación y carencia. No hay caché permanente de registros ni resultados que sobrevivan a una edición.

### Backend medido

Mismos volúmenes proporcionales y motores locales. Se corrigió el generador ficticio para que al crecer mantenga 100 animales por potrero y ocupación real, en lugar de sobrellenar potreros previos. Los UUID y fechas de ejecución varían: no se atribuyen los pequeños cambios de bytes a recortes.

| Métrica | Antes (6.3) | Después MariaDB | Después PostgreSQL |
|---|---:|---:|---:|
| Consultas catálogo, 2.000 animales | 14.835 | 19 | 19 |
| Catálogo 2.000, ms | 28.532 | 3.055,49 | 2.973,75 |
| Registros totales, 2.000 | 6.841 | 6.841 | 6.841 |
| Catálogo completo, bytes | 3.867.931 | 3.867.377 | 3.868.005 |
| Respuesta HTTP máxima, bytes | No completó snapshot | 175.061 | 175.061 |
| Partes HTTP, 2.000 | No completó | 30 | 30 |
| Snapshot y todas sus páginas, ms | Falló inserción | 13.193,11 | 13.662,81 |
| Pico PHP del proceso, MiB | Sin comparación válida | 128 | 126 |
| Consultas catálogo, 500 | Sin muestra aislada | 25 | 25 |
| Catálogo 500, ms | Sin muestra aislada | 857,15 | 677,57 |
| Partes / HTTP máximo, 500 | Sin muestra aislada | 8 / 175.057 | 8 / 175.057 |

Reducción de consultas: **99,87%**; tiempo del catálogo MariaDB: **89,29%** en estas muestras locales. No son promedios de producción. El servidor aún materializa el catálogo completo y lo revalida en cada página para conservar los controles de autorización anteriores; no se presenta como memoria constante para volúmenes ilimitados. Base64 agrega aproximadamente un tercio a la transferencia del JSON a cambio de bloques binarios seguros y acotados.

### PDF con 500 animales

Seis procesos aislados, presupuesto de 384 MiB y 30 s por PDF; fixture con vacunaciones, desparasitaciones, tratamientos y revisiones. No es comparable directamente con el reporte de vacunación vacío de 6.3. El historial sigue siendo de un animal, no de todo el inventario.

| PDF | SQL | Tiempo, s | Pico MiB | Resultado |
|---|---:|---:|---:|---|
| Inventario | 3 | 23,367 | 242 | Completo |
| Salud | — | >30 | No medido al abortar | Presupuesto temporal superado |
| Vacunación | 6 | 18,029 | 222 | Completo |
| Tratamientos | 3 | 16,644 | 216 | Completo |
| Historial de un animal | 4 | 0,687 | 72 | Completo |
| Financiero | 5 | 4,984 | 120 | Completo |

Las consultas ya son pocas; domina Dompdf y la composición del documento. No se modificó contenido/diseño ni se incrementaron límites para aparentar una mejora. No se ejecutaron seis PDF de 2.000 animales: el resultado sanitario de 500 ya justifica evitar ese consumo. Para documentos extensos queda pendiente evaluar generación por lotes/cola en una etapa autorizada, conservando el contenido; mientras tanto usar los filtros existentes. No se declara resuelta la exportación masiva.

### Conservación y archivos 6.3.1

Antes de editar WorkspaceSync se guardaron su archivo y diff originales en `%TEMP%/bovisoft-phase631-preserved`, con SHA256 `813400db01d46824f51ad2581635e92d3c798a3f58785cebf7f65e35b2446b68`. Allí se conserva también el diff exclusivo de rendimiento respecto de esa copia. Los controles pendientes de 6.2 sobre recibos/autorización permanecen; no se retomó su auditoría. Los dos archivos de pruebas de seguridad mantienen las huellas anteriores.

Archivos adicionales: `WorkspaceSyncController.php`, `WorkspaceSync.php`, `WorkspaceJournal.php`, migración `2026_11_25_000001_create_workspace_snapshot_chunks.php`, `resources/js/workspace.js`, `resources/js/sync/{snapshot,collection-pages,module-views}.js`, `WorkspaceSnapshot631Test.php`, `PerformanceReports631Test.php`, `PerformanceDataset.php`, `tests/performance/{local.php,pdf631.py}`, `tests/browser/{performance_phase631.py,snapshot631.mjs,projection631.mjs}` y este informe. Se conservan todas las modificaciones de 6.3. Compilados generados e ignorados; sin dependencias nuevas, `.env`, respaldo o base habitual alterados.

### Verificación y repetición 6.3.1

- `php tests/run-pilot.php mysql WorkspaceSnapshot631Test`: catálogo 500/2.000, 170 aserciones; la comprobación adicional `legacy_pages` pasó con 5 aserciones.
- PostgreSQL 17.11: mismas verificaciones, 170 + 5 aserciones, sin repetir suites ni benchmarks ajenos.
- `node tests/browser/snapshot631.mjs`: interrupción/reanudación, Unicode, orden, cuenta, tamaño, hash y finalización.
- `node tests/browser/projection631.mjs`: fechas en el cambio de día de Bogotá e indicadores relacionados con la proyección optimizada.
- `python tests/performance/pdf631.py`: seis PDF aislados con los presupuestos indicados; cinco completos y uno detenido por tiempo.
- Chromium: recorrido agrupado `tests/browser/performance_phase631.py`, preparación inicial de 500 y ampliación a 2.000 en la misma base ficticia. El ejecutor usa el mismo servidor desechable del apartado anterior, empezando con `prepare mysql 500`. Inyecta vencimiento ficticio de cursor y fallo de red en `fetch`: la intercepción de solicitudes gestionadas por Service Worker no es fiable en todas las versiones de Chromium. Las credenciales ficticias se reciben por entorno; no se almacenan en el informe. Emulación móvil, no dispositivo físico.
- `npm run build`, lint PHP y `git diff --check`. No se ejecutaron suites completas.
