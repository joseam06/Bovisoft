# BoviSoft: uso automático sin conexión

La [Fase 5.1](offline-phase-5-1.md) amplía el núcleo a Potreros, Producción, Salud y estadísticas locales del Dashboard. Las [Fases 5.2/5.3](offline-phase-5-2-5-3.md) documentan la ampliación compatible a Finanzas, Alertas, Reportes y el nombre del perfil, sus verificaciones y límites actuales.

## Comportamiento actual

Después de iniciar sesión con Internet, BoviSoft guarda en segundo plano la interfaz y los datos autorizados de la cuenta. No hay módulo separado, panel de preparación, selección de fincas para descargar, descarga manual ni clave adicional. No se aplica el límite anterior de siete días.

La página abierta permanece al perder conexión. Fincas, Animales, Potreros, Producción y Salud reutilizan sus vistas Blade habituales mediante fragmentos autenticados, guardados automáticamente en IndexedDB. No hay formularios ni fichas alternativos escritos en JavaScript: las mismas vistas cubren registros nuevos y fichas no visitadas. El Dashboard calcula indicadores y gráficas con los datos completos de esos módulos y conserva identificada la última información de servicios no disponibles. Para registros nuevos se utiliza un UUID bajo la ruta habitual hasta recibir su ID numérico.

Junto a la campana hay un mensaje no interactivo:

- **Estás usando BoviSoft sin conexión**, mientras se utiliza la copia local sin red.
- Icono y **Sincronizando cambios…**, durante los envíos al reconectar.
- Sin indicador al terminar correctamente con conexión.
- Si quedan pendientes o conflictos, el indicador mantiene un aviso y no presenta la sincronización como terminada.

Los errores y conflictos se muestran como avisos dentro de la pantalla. Una eliminación sin enviar muestra **Deshacer eliminación**. No se abre otra ventana ni hay un botón para activar el modo offline.

## Datos y operaciones

Fincas y Animales permiten consultar, crear, editar y eliminar offline desde sus formularios habituales. Cada cambio se confirma en IndexedDB antes de mostrarlo como guardado y permanece al recargar. Una finca nueva se puede elegir para registrar un animal antes de reconectar. La cola envía primero los padres y luego las operaciones dependientes.

El comienzo del envío y su cuerpo exacto se guardan antes de la solicitud. Si el servidor confirma y se pierde la respuesta, se reintenta el mismo UUID sin repetir la escritura. Una eliminación puede deshacerse únicamente antes de ese comienzo. Un rechazo confirmado conserva el borrador y permite resolverlo explícitamente.

Las versiones evitan sobrescribir ediciones de otro dispositivo. El aviso conserva base, borrador y servidor; el usuario puede revisar y elegir qué hacer. Las decisiones se conservan en el historial cifrado del dispositivo, aunque no exista un panel de configuración para consultarlo.

Las eliminaciones son lógicas y respetan las relaciones: no se borran fincas con animales/potreros ni registros con historial sanitario, productivo o económico. Se puede desactivar una finca o marcar un animal como Inactivo. Las ventas continúan en Finanzas, sin simular movimientos monetarios desde una ficha.

La sincronización y la descarga incremental se ejecutan automáticamente con la web abierta y la cuenta activa. No se promete ejecución con la web cerrada. Si se interrumpe la descarga inicial, se reintenta automáticamente; no se presenta una copia parcial como completa ni se reemplazan los pendientes existentes.

## Cuenta, almacenamiento y límites reales

Los datos, las vistas, la información habitual del encabezado y la cola se cifran con una clave no extraíble guardada en IndexedDB, sin otra contraseña. Se conservan nombre, correo y la foto local cuando puede descargarse correctamente. La separación entre cuentas se aplica en el estado local, entre pestañas y en todos los endpoints del servidor. Esto no sustituye la protección del perfil del navegador frente a quien controle el dispositivo o ejecute código dentro del mismo origen.

Cache API solo contiene recursos estáticos y la interfaz genérica sin datos personales ni CSRF. Las páginas de la cuenta se conservan cifradas en IndexedDB; se eliminan scripts, manejadores inline y campos CSRF antes de guardarlas. Los datos se actualizan con la API autorizada, sin confiar en el HTML para autorizar escrituras.

Cerrar sesión o cambiar de cuenta bloquea el contenido anterior en las pestañas. Los pendientes de cada cuenta permanecen separados y se recuperan automáticamente cuando vuelve a iniciar sesión su propietario. Si la sesión del servidor vence, al detectarlo online se exige el login habitual; no se borra la cola. La copia offline no tiene un vencimiento fijo de siete días.

Es necesario que la primera carga automática haya podido completar el guardado. Si Internet se corta antes, BoviSoft conserva la página y los valores del formulario y explica lo que todavía no se pudo guardar. Borrar el almacenamiento, cambiar de perfil/origen o la expulsión de datos por el navegador puede eliminar una copia local; se solicita almacenamiento persistente cuando está disponible.

Las copias del prototipo anterior protegidas con una clave adicional se conservan intactas en otro espacio de almacenamiento. No se borran ni se sobrescriben. Si contienen pendientes que nunca llegaron al servidor, no pueden descifrarse e importarse sin su clave original; esa recuperación puntual queda separada del funcionamiento automático nuevo.

## Arquitectura y pruebas

La [consolidación de Fase 4](offline-sync-core.md) documenta el contrato compartido, las reservas entre pestañas, el rechazo de respuestas tardías y sus verificaciones selectivas. El alcance de módulos indicado aquí se conserva.

Se mantienen UUID, versiones, recibos y diario del servidor. `WorkspaceRecords` comparte validaciones entre formularios y API; las referencias animal–finca–potrero y los contadores se verifican transaccionalmente. Las escrituras Eloquent de Fincas y Animales, incluidas las de formularios normales, publican cambios. No se admite modificar datos sincronizados mediante SQL directo o actualizaciones masivas que eludan estas reglas.

`resources/js/workspace.js` conecta los formularios y la navegación. Los cambios recibidos refrescan las vistas; los formularios online se actualizan sin reemplazar sus manejadores y se conserva cualquier texto aún sin guardar. `workspace-crypto.js` cifra las copias. `public/workspace-store.js` comprueba cuenta, época y revisión atómicamente. Web Locks es una optimización; la Fase 4 incorpora además una reserva compartida en IndexedDB para arbitrar los envíos sin esa API. Las descargas descartan respuestas de revisiones anteriores y las confirmaciones tardías no reemplazan versiones posteriores ni reviven bajas conocidas. `public/sw.js` mantiene Laravel online y recupera la interfaz guardada al fallar la red. `/offline-shell` es solo un recurso interno, no un módulo.

Las rutas `/api/offline/v1/workspace` ofrecen instantáneas paginadas, cambios incrementales y operaciones idempotentes. El cursor de Fincas/Animales ya no impone un vencimiento de siete días. Un cursor de transporte inválido se renueva automáticamente sin borrar pendientes. El piloto conserva el límite de diez mil fincas y diez mil animales por cuenta.

La demostración utiliza exclusivamente una SQLite temporal ficticia, `.env.example` y una conexión local aislada. No utiliza la base habitual, credenciales reales ni Supabase de producción.

```powershell
rtk proxy npm run build
rtk proxy php tests/manual/workspace.php prepare
rtk proxy php -S 127.0.0.1:8094 -t public tests/manual/workspace.php
```

El comando `prepare` anterior solo crea los datos ficticios del ensayo; no existe un paso de preparación dentro de la aplicación.
Antes de crear una base ficticia o ejecutar Playwright, define `BOVISOFT_BROWSER_TEST_PASSWORD` en el entorno del proceso con una clave exclusiva de pruebas. No la guardes en archivos versionados. Si la base ya existe, usa la clave con la que se creó; no se restablecen sus cuentas ni sus pendientes.

1. Abre `http://127.0.0.1:8094/login`.
2. Entra con `offline-a@example.test` y la clave de pruebas indicada al crear la base ficticia.
3. Navega normalmente a una ficha de Ganado. Mientras navegas, la copia se guarda automáticamente.
4. En DevTools → Network activa Offline. En Application → Service Workers deja desmarcado Bypass for network.
5. Pulsa Editar en la ficha, cambia las observaciones y guarda. Recarga todavía offline: la ficha y el cambio deben conservarse en la misma ruta.
6. Vuelve a habilitar la red. Aparece Sincronizando cambios… y desaparece al confirmar los envíos. No pulses ningún control adicional.

Para probar padres nuevos, crea una finca offline con coordenadas y registra un animal en ella; para probar deshacer, elimina un animal sin historial y usa el aviso antes de reconectar. La cuenta B (`offline-b@example.test`, misma contraseña ficticia) tiene sus propios datos y no debe ver los de A.

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-workspace-browser.ps1
rtk proxy php tests/run-pilot.php sqlite
rtk proxy php tests/run-pilot.php pgsql
```

La suite del navegador comprueba la ficha normal sin cambiar la pantalla al desconectar, edición, recarga, una sola escritura, padre antes que hijo, deshacer/borrar, cuentas y ausencia del límite anterior. También simula perder una respuesta después de confirmar el servidor, un conflicto que persiste al recargar, una descarga inicial interrumpida y la actualización de la interfaz conservando pendientes. Guarda capturas en `%TEMP%/bovisoft-automatic-evidence`. PHP mantiene las pruebas de Fincas, Producción, autorización, conflictos e idempotencia; PostgreSQL añade concurrencia real con procesos separados. Los ejecutores crean y eliminan únicamente bases de pruebas desechables.

Los flags de despliegue `OFFLINE_WORKSPACE_ENABLED` y `OFFLINE_PRODUCTION_SYNC_ENABLED` permiten pausar los endpoints conservando los pendientes. El router ficticio los habilita solo en el ensayo local. La migración nueva no se ha aplicado a la base habitual ni a producción. No borrar IndexedDB, diarios ni recibos para desactivar la función.

El respaldo en `feature/offline-first` no constituye un despliegue. Antes de una publicación autorizada se requieren copia de seguridad, aplicación controlada de las migraciones de Producción y Workspace, build de Vite y servidor/API compatible antes del cliente nuevo. Los formularios normales de Fincas/Animales también usan las columnas y el diario de Workspace aunque su flag offline esté apagado; apagarlo no sustituye las migraciones. No utilizar los routers de `tests/manual` ni su configuración aislada en producción. La compatibilidad se verificó con PostgreSQL 17.11 y, anteriormente, MariaDB 10.4; no se ha ejecutado en Supabase de producción ni se acredita una prueba específica de MySQL 8.

## Ampliación por módulos

| Módulo | Cobertura actual / siguiente paso |
| --- | --- |
| Dashboard | Indicadores locales de los cinco módulos, clasificación, barras/dona, rangos 7/30 días y año. Mapas externos y alertas agregadas conservan su última información identificada. |
| Fincas | CRUD, ubicación por coordenadas, pendientes y conflictos. Las teselas OpenStreetMap y la búsqueda Nominatim necesitan Internet; no se anuncia un mapa base descargado. |
| Animales | CRUD general, fincas/potreros nuevos, asignación y traslado; historial sanitario en Salud. Ventas continúan en Finanzas. |
| Producción | CRUD e historial de leche completo dentro del límite técnico de registros, totales y gráficas. Conserva `ProduccionService` y sus reglas históricas. |
| Potreros | CRUD, animales asociados, capacidad/ocupación, disponibilidad y fechas/avisos de rotación existentes. El repositorio no incluye geometrías. |
| Salud | CRUD, categorías, seguimientos, costos/carencias y conflictos. Egresos siguen en su flujo separado de Finanzas; los vínculos financieros se protegen en servidor. |
| Finanzas | Consulta, alta, detalle y eliminación/reversión de ingresos/egresos y ventas bajo una transacción, con recibos y versiones. No existen formularios/rutas de edición financiera. |
| Alertas | Cálculo local mediante catálogo compartido de reglas sanitarias y rotación; mismas pantallas y campana. No existen estados persistidos de lectura. |
| Reportes | Seis plantillas originales consultables con filtros y pendientes locales. Impresión/Guardar como PDF del navegador offline; descarga binaria DomPDF online. |
| Cuenta/fotos | Login, recuperación de contraseña, OAuth y cargas requieren conexión. No se almacenan contraseñas para autenticar offline ni se descargan fotos privadas. |

El objetivo final sigue siendo todo BoviSoft. Cada módulo debe aportar autorización, descarga inicial/incremental, reglas transaccionales, versiones, idempotencia, pantallas habituales y la misma matriz de desconexión y cuentas antes de habilitar sus escrituras offline.

## Cierre funcional de Fase 3

Se validaron tres recorridos agrupados con datos ficticios y SQLite: vistas habituales y sincronización visible (incluidos formulario limpio, texto sin guardar, respuesta perdida y conflicto), cierre completo/reapertura de Chromium con el mismo perfil y un pendiente, y funcionamiento en emulación móvil sin Web Locks con dos pestañas, fincas concurrentes y un animal dependiente. No se repitieron las 101 pruebas del servidor; sus resultados anteriores de PostgreSQL se reutilizan para el protocolo que se conserva.

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-consolidation-browser.ps1
```

La prueba original de Fase 3 verificó persistencia y sincronización, no un iPhone físico ni Safari. Detectó solapamientos y usó envíos programáticos; sus capturas siguen en `%TEMP%/bovisoft-phase3-consolidation`. La [Fase 3.1](responsive.md) corrigió esos problemas en las vistas compartidas y repitió el recorrido offline con toques reales, sin esos atajos. La comprobación en dispositivos físicos y otros motores sigue pendiente. No se amplió el alcance offline de los demás módulos.
