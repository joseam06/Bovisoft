# Piloto: reglas de Producción y pruebas locales (1C y 1D)

Las altas y correcciones utilizan `App\Services\ProduccionService`, con las mismas validaciones para formularios y la API de sincronización de Fase 2. UUID, versiones, recibos y descarga incremental se documentan en `docs/production-sync.md`. La consulta offline del navegador y sus pruebas de Fase 3 se documentan en `docs/production-offline-read.md`.

- Alta: animal activo de tipo vaca o novilla, finca activa, ambos de la cuenta autenticada y con relación animal–finca coincidente al guardar. El formulario solo ofrece referencias elegibles; el servidor vuelve a comprobarlas dentro de una transacción.
- Corrección: animal y finca originales inmutables. Se permiten litros, turno, calidad y observaciones aunque el animal se haya trasladado o esté inactivo, o la finca se haya desactivado. Las referencias originales deben seguir existiendo y estar autorizadas.
- Si el animal está inactivo o su finca actual difiere de la original, cambiar la fecha se rechaza con un error en `fecha` que indica que requiere revisión online. El formulario conserva la fecha original y muestra el aviso. No se añade todavía un flujo de aprobación para esa revisión. Si el animal sigue activo en su finca original, la fecha puede corregirse con las validaciones habituales.
- El repositorio no registra intervalos históricos de pertenencia a fincas: la detección del traslado compara finca actual y finca original; no puede identificar un traslado de ida y vuelta que no dejó historial.
- No se reasignan asociaciones desde este formulario, incluso manipulando IDs. Las denegaciones dejan intacto el registro completo.
- Se permiten mediciones independientes con el mismo animal, fecha y turno, incluido turno nulo. No se fusionan ni se deduplican por contenido.
- Litros: 0.01–999.99 y hasta dos decimales; fechas válidas `Y-m-d`, no futuras según `America/Bogota`. Los campos opcionales pueden limpiarse.

## Ejecutar ambas suites

Desde la raíz del repositorio:

```powershell
rtk proxy php tests/run-pilot.php sqlite
rtk proxy php tests/run-pilot.php mysql
rtk proxy php tests/run-pilot.php pgsql
```

SQLite utiliza memoria. Los otros perfiles requieren un servidor local en `127.0.0.1`, en los puertos 3306/55432, con las cuentas locales ficticias `root`/`postgres` sin contraseña. El perfil PostgreSQL habilita `pdo_pgsql` para los procesos de la suite sin modificar el PHP global. No se leen `.env`, credenciales reales ni configuración cacheada del despliegue. La preparación del servidor se documenta en `docs/production-sync.md`.

Para MySQL/PostgreSQL el ejecutor crea una base nueva con nombre aleatorio `bovisoft_test_piloto_<16 caracteres hexadecimales>`, aplica las migraciones existentes solo allí, ejecuta las dos suites y elimina exclusivamente esa base al finalizar. El bootstrap acepta únicamente ese patrón para motores externos y configura una sola conexión. No utilizar el ejecutor contra bases con datos reales. Si se interrumpe abruptamente el proceso, puede quedar la base ficticia que imprimió; no hay limpieza automática de otras bases.

Las suites verifican autorización entre cuentas, relaciones, referencias eliminadas, estados históricos, cambios de fecha, intentos de reasignación, precisión decimal, mediciones independientes y conservación íntegra tras errores. Los nombres, correos y registros son ficticios.

Validación realizada en este entorno: PostgreSQL 17.11, SQLite 3.39.2 y MariaDB 10.4.28 mediante PDO MySQL, en bases aisladas. El perfil MySQL no equivale a una ejecución sobre MySQL 8. Las pruebas concurrentes con procesos independientes se ejecutan en PostgreSQL.

## Comprobación manual

Puede utilizarse el servidor aislado documentado en `tests/manual/FINCAS.md`, que siempre usa SQLite temporal. Inicia sesión como A, crea una producción para su animal y comprueba que se conserva como registro independiente al repetir la medición. Edita sus litros y verifica que animal/finca aparecen fijos. Si luego trasladas o inactivas ese animal desde su módulo, el registro conserva sus asociaciones y muestra el aviso junto a la fecha. Las correcciones de los demás campos siguen disponibles.

Las escrituras normales publican eventos y comprueban `base_version`. Un contador transaccional asigna códigos; el protocolo distingue UUID de registro y UUID de operación. Los cambios de la Fase 2 no incluyen todavía un cliente offline.
