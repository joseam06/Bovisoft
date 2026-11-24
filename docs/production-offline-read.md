# Consulta de Producción sin conexión

La entrada separada de Fase 3 fue sustituida por el funcionamiento automático dentro del dashboard habitual. Las instrucciones vigentes, el alcance por módulo y las pruebas locales están en [offline-workspace.md](offline-workspace.md).

No se utiliza una pantalla de preparación, descarga manual, selección de fincas para descargar, contraseña local adicional ni vencimiento de acceso a los siete días. Las antiguas rutas `/offline/produccion` y `/offline/workspace` redirigen al dashboard; `/offline-shell` es un recurso interno del Service Worker.

La consulta inicial de Fase 3 utilizaba hasta 90 días de la API de Fase 2. Desde la [Fase 5.1](offline-phase-5-1.md), el cliente utiliza Workspace para el historial completo autorizado, CRUD offline, cantidades y gráficas, dentro del límite técnico por módulo. Reutiliza `ProduccionService` y sus reglas históricas. La API anterior se conserva por compatibilidad.

El protocolo anterior del servidor se documenta en [production-sync.md](production-sync.md). El CRUD offline actual de los cinco módulos comparte la cola y el contrato consolidados de Workspace.

Para repetir el recorrido del navegador, utiliza el servidor ficticio en `http://127.0.0.1:8094` y `tests/run-workspace-browser.ps1`, según la documentación vigente. No borres IndexedDB ni los cachés para desactivar la función: pueden existir cambios pendientes de envío.
