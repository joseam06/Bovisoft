# Fase 3.1: correcciones responsive locales

Se corrigieron el menú dependiente de hover, el margen lateral fijo en móvil, el encabezado que tapaba acciones, filas que no se ajustaban, controles pequeños y el modal de cámara limitado por el `backdrop-filter` de su tarjeta. Se conserva la identidad visual y el escritorio de 1440 px; no se cambió la cobertura offline ni su lógica.

La capa compartida adapta Dashboard, Fincas, Potreros, Animales, Salud, Producción, Finanzas, Alertas, Reportes y Perfil. El mismo menú tiene apertura/cierre táctil, Escape, foco e inactividad del fondo. Las tablas conservan columnas y desplazan su propio contenido; los formularios se redistribuyen y usan controles táctiles. Se adaptaron también portada, acceso, registro, recuperación, restablecimiento y los diálogos compartidos. Fuentes e iconos de acceso reutilizan los recursos locales existentes.

## Archivos de esta fase

- `resources/css/app.css`, `resources/js/responsive.js`, `resources/js/app.js`.
- `resources/views/layouts/dashboard.blade.php`, `resources/views/layouts/guest.blade.php`.
- `resources/views/auth/{login,register,forgot-password,reset-password}.blade.php`.
- `resources/views/welcome.blade.php`, `resources/views/profile/edit.blade.php`.
- `tests/browser/responsive.py`, `tests/run-responsive-browser.ps1`, `tests/prepare-responsive-assets.ps1`.
- Esta nota y la referencia de cierre en `docs/offline-workspace.md`.

## Verificación acotada

Cuatro recorridos agrupados en Chromium con datos ficticios de la SQLite temporal de `http://127.0.0.1:8094`:

1. **360 px:** todos los listados de módulos, formularios representativos, acceso/registro/recuperación/restablecimiento, menú y modal de cámara con permiso denegado simulado.
2. **768 px:** grillas, desplegable de cuenta y navegación; se comprobó también el menú táctil al girar a 1024 px.
3. **1440 px:** margen/barra lateral de 80 px y encabezado fijo conservados; gráficas Chart.js reales, contenedores Leaflet y tablas. Una ejecución concurrente agotó la espera del Service Worker; la repetición aislada pasó sin cambiarlo.
4. **390 px offline:** toques reales de Playwright para crear finca y animal asociado, editar, eliminar/deshacer, recargar y sincronizar una sola vez. No se utilizó `requestSubmit()`, `evaluate(click)` ni `force` para superar solapamientos.

Las mediciones de página y controles no detectaron desbordamiento horizontal en los estados recorridos; las tablas mantienen desplazamiento interno. Capturas y reportes: `%TEMP%/bovisoft-responsive-evidence`. Compilación y `git diff --check` correctos. No se ejecutaron suites completas ni las 101 pruebas anteriores.

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/prepare-responsive-assets.ps1
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-responsive-browser.ps1
```

El primer script solo copia a TEMP Chart.js y Leaflet, ya referenciados por la aplicación, para servirlos localmente durante los ensayos. No instala dependencias ni modifica la aplicación. Se puede repetir solo un recorrido con `-Group 360`, `768`, `1440` u `offline`.

**Límites:** emulación Chromium, sin validación física en celulares/tablets, Safari o Firefox. Se verificó el tamaño y el renderizador del mapa, no las teselas externas ni el hardware de cámara. Esa validación adicional sigue pendiente antes de un despliegue. No se inició Fase 4, no se conectó a producción y no se hicieron commits, push ni despliegues.
