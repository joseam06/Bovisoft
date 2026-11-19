# Consulta de Producción sin conexión

La entrada separada de Fase 3 fue sustituida por el funcionamiento automático dentro del dashboard habitual. Las instrucciones vigentes, el alcance por módulo y las pruebas locales están en [offline-workspace.md](offline-workspace.md).

No se utiliza una pantalla de preparación, descarga manual, selección de fincas para descargar, contraseña local adicional ni vencimiento de acceso a los siete días. Las antiguas rutas `/offline/produccion` y `/offline/workspace` redirigen al dashboard; `/offline-shell` es un recurso interno del Service Worker.

Después del login habitual, el cliente descarga automáticamente las referencias y hasta 90 días de Producción autorizada mediante las instantáneas y los cambios incrementales de la API de Fase 2. La consulta se ofrece desde el menú habitual de Producción. Sus escrituras offline todavía no están habilitadas; las altas y correcciones online siguen usando `ProduccionService` y sus reglas históricas.

El protocolo del servidor, las versiones y los recibos idempotentes se documentan en [production-sync.md](production-sync.md). El CRUD offline y la cola actuales corresponden a Fincas y Animales.

Para repetir el recorrido del navegador, utiliza el servidor ficticio en `http://127.0.0.1:8094` y `tests/run-workspace-browser.ps1`, según la documentación vigente. No borres IndexedDB ni los cachés para desactivar la función: pueden existir cambios pendientes de envío.
