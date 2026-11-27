# Protocolo local del piloto de Producción (Fase 2)

Esta fase implementa el servidor y su contrato HTTP. No incluye Service Worker, IndexedDB, cola del navegador, desbloqueo local ni sincronización automática del cliente.

La consulta offline añadida posteriormente en Fase 3 se documenta en [production-offline-read.md](production-offline-read.md). La creación y edición offline continúan pendientes.

La ampliación desde el dashboard y la cola de Fincas/Animales se documentan en [offline-workspace.md](offline-workspace.md). La escritura pendiente mencionada arriba corresponde a Producción.

## Entorno de pruebas reproducible

Desde la raíz del repositorio:

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/setup-postgres.ps1
rtk proxy php tests/run-pilot.php pgsql
rtk proxy php tests/run-pilot.php sqlite
rtk proxy php tests/run-pilot.php mysql
```

El instalador descarga PostgreSQL 17.11-3 de EDB, proveedor de binarios enlazado por [PostgreSQL para Windows](https://www.postgresql.org/download/windows/). Extrae solamente binarios, bibliotecas y recursos a `%TEMP%/bovisoft-postgres-17.11-3`, inicializa un cluster independiente y escucha exclusivamente en `127.0.0.1:55432`. No registra un servicio de Windows ni cambia instalaciones existentes. El cluster contiene solo datos de demostración y utiliza autenticación local de pruebas. Para detenerlo:

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tests/setup-postgres.ps1 -Stop
```

El ejecutor habilita `pdo_pgsql` con `php -d extension=pdo_pgsql` tanto para sí mismo como para PHPUnit y los procesos concurrentes. Utiliza el mismo `PHP_BINARY` de XAMPP; no modifica `C:/xampp/php/php.ini`. Si falta la DLL, se detiene. Cada ejecución PostgreSQL/MySQL crea y elimina únicamente una base aleatoria `bovisoft_test_piloto_<16 hexadecimales>`. No carga `.env`, secretos reales ni configuración cacheada; fuerza una sola conexión de pruebas. MySQL en esta máquina es MariaDB 10.4.28 y no equivale a MySQL 8.

Las pruebas multiproceso se ejecutan en PostgreSQL con dos procesos PHP, una barrera de inicio y conexiones independientes. No dependen de las transacciones de PHPUnit para simular concurrencia. También se comprueba una actualización del esquema con producciones existentes, preservando ID y código.

## Activación y migraciones

La API queda desactivada por defecto mediante `OFFLINE_PRODUCTION_SYNC_ENABLED=false` en `.env.example`. La configuración está en `config/offline.php`. Las pruebas aisladas la activan. No se ha migrado ninguna base habitual de desarrollo ni Supabase.

Antes de usar la nueva aplicación contra otra base autorizada deben aplicarse las migraciones. El servicio normal de Producción necesita las tablas nuevas incluso si los endpoints están pausados: sus escrituras siempre publican cambios. No debe desplegarse este código sin el esquema correspondiente.

Para actualizar solamente la SQLite ficticia del servidor de navegador ya preparado:

```powershell
rtk proxy php tests/manual/fincas.php migrate
rtk proxy php -S 127.0.0.1:8091 -t public tests/manual/fincas.php
```

Se conservan las cuentas ficticias documentadas en `tests/manual/FINCAS.md`.

## Identidades y versiones

- Los ID numéricos y códigos actuales se conservan. La migración asigna UUID a los registros previos, incluidos los eliminados lógicamente, y versión inicial 1.
- `record_uuid` identifica un registro; se guarda como `producciones.client_uuid`, único y no nulo. Una medición independiente usa otro UUID, aunque coincidan animal, fecha, turno y litros.
- `operation_uuid` identifica una operación inmutable, única por cuenta. Los reintentos conservan UUID, tipo, datos y versión base. Corregir una operación rechazada o resolver un conflicto exige otra operación con otro UUID.
- `base_version` identifica la versión que se editó. No es una fecha ni un identificador de petición. Cada escritura aceptada incrementa `version`.
- `device_uuid` sirve para trazabilidad; no concede permisos. `account_id` debe coincidir con la sesión activa antes de aceptar un lote.

Un contador transaccional asigna códigos `PROD-*` sin calcular el último ID más uno. La vista de alta muestra que el código se asignará al guardar.

## HTTP y autenticación

Todos los endpoints están bajo `/api/offline/v1/produccion`, dentro del middleware web de Laravel: sesión de Sanctum/Jetstream, verificación configurada, CSRF en escrituras y límite de solicitudes. El cliente del mismo origen utiliza cookies de sesión y `Accept: application/json`; las escrituras necesitan CSRF vigente. Las respuestas de datos llevan `Cache-Control: no-store, private`.

### Descarga inicial

`POST /snapshots` admite:

```json
{"finca_ids":[1],"history_days":90,"page_size":100}
```

Solo se admiten fincas autorizadas, como máximo 50 por alcance. Omitir `finca_ids` selecciona las fincas propias dentro de ese límite; si la cuenta tiene más, debe seleccionar explícitamente un subconjunto. Una lista vacía selecciona ninguna. El intervalo comienza al inicio del día de Bogotá de hace `history_days - 1` días. Hay un límite de 10.000 producciones/animales por alcance para el piloto.

La respuesta incluye `account_id`, `snapshot_uuid`, `page`, `total_records`, `records`, `references`, `valid_record_uuids`, `next_page`, `complete` y `cursor`. Para continuar: `GET /snapshots/{snapshot_uuid}?page=2`.

La instantánea materializada mantiene las mismas filas y versiones entre páginas. El cursor solo se entrega en la última página. La descarga caduca a las 24 horas. Si las referencias o permisos dejan de estar disponibles entre páginas, se devuelve 409 y debe repetirse la preparación. Aún después de terminar las páginas, el futuro cliente debe descargar los cambios posteriores al cursor antes de anunciar que terminó la preparación local.

### Descarga incremental

`GET /changes?cursor=<cursor opaco>&limit=100` devuelve eventos ordenados de Producción, `has_more` y otro cursor. El cliente debe repetir mientras `has_more` sea verdadero y persistir cada página junto con su cursor en una sola transacción local.

Los eventos son `upsert` con un registro completo o `remove` con UUID y versión. Los borrados online quedan registrados. Una corrección que salga del intervalo también retira el registro del alcance.

Los cursores están cifrados y autenticados por Laravel, ligados a cuenta, fincas, intervalo, posición y vencimiento de siete días. No utilizan el reloj del dispositivo ni `updated_at` como posición. Un cursor manipulado se rechaza; uno de otra cuenta devuelve 404; uno caducado devuelve 410 y exige otra descarga inicial, conservando primero los pendientes.

Para reflejar traslados, inactivaciones, eliminaciones o pérdidas de acceso sin habilitar escrituras offline de Fincas/Animales, cada respuesta incremental incluye sus catálogos completos autorizados (`references.authoritative=true`) y un manifiesto completo `valid_record_uuids` del alcance actual. El cliente deberá retirar de su copia confirmada lo que ya no aparezca. **Nunca debe borrar un borrador o pendiente por aplicar ese manifiesto**: debe aislarlo como conflicto si perdió su referencia. Estos catálogos y manifiestos no son una descarga incremental de las entidades de referencia; es una elección acotada al piloto.

### Envío de operaciones

`POST /operations` admite hasta 50 operaciones independientes:

```json
{
  "account_id": 1,
  "device_uuid": "11111111-1111-4111-8111-111111111111",
  "operations": [{
    "operation_uuid": "22222222-2222-4222-8222-222222222222",
    "record_uuid": "33333333-3333-4333-8333-333333333333",
    "type": "create",
    "data": {"animal_id":1,"finca_id":1,"fecha":"2026-11-18","sesion":"manana","litros":"12.35","calidad":"buena","observaciones":null}
  }]
}
```

Para `update`, se usa el UUID del registro descargado y se añade `base_version`. `data` contiene el estado corregido completo (fecha y litros requeridos; asociaciones opcionales e inmutables). No se admiten operaciones offline de borrado. Se reutiliza `ProduccionService` para elegibilidad, autorización, relación animal–finca, precisión decimal y reglas históricas.

La respuesta HTTP del lote es 200 con `results`, cada uno con `operation_uuid`, `status` y `body`. Los estados por operación son 201/200, 422 de validación, 404 de recurso no disponible o 409 de conflicto. Los fallos no deshacen éxitos independientes. Un UUID reutilizado con datos diferentes devuelve `operation_uuid_reused`; otro UUID de operación creando el mismo registro devuelve `record_uuid_exists`.

Los resultados semánticos se conservan en recibos; las operaciones con estructura inválida se rechazan antes de guardar recibo. El cliente debe inspeccionar cada resultado, no considerar confirmado todo el lote solo por recibir HTTP 200.

## Atomicidad y conflictos

Las escrituras del servicio y las operaciones de sincronización bloquean primero una cabecera por propietario dentro de una transacción. El cambio, su versión, el evento y el recibo se confirman juntos. Ese mismo bloqueo ordena la secuencia por cuenta según confirmación; no se utiliza un autoincremento global como cursor de commits.

Si el servidor confirmó y se perdió la respuesta, el reintento devuelve el recibo guardado sin repetir la escritura. Antes de devolver información del recibo vuelve a comprobar el acceso actual al registro y sus referencias. Los recibos y eventos no se purgan automáticamente en el piloto.

Dos ediciones de la misma versión producen una aceptación y un conflicto 409, con la versión actual autorizada. No hay fusión ni suma automática de litros. El futuro cliente debe conservar base, borrador y remoto; la resolución usa nueva operación y versión base actual.

Los formularios normales también envían `base_version`. Una edición obsoleta conserva los campos enviados y muestra un error que exige revisar la versión actual. Los borrados normales comprueban versión y publican `remove`. Escribir directamente con SQL o eludir `ProduccionService` no forma parte del contrato de sincronización.

Las lecturas PostgreSQL usan una instantánea consistente y la misma cabecera para que la paginación inicial y el cursor no omitan escrituras. Los catálogos de referencia se materializan en esa misma vista consistente.

## Pausa y recuperación

Desactivar `OFFLINE_PRODUCTION_SYNC_ENABLED` pausa los endpoints con 503. No elimina recibos, eventos, instantáneas ni producciones; los formularios siguen registrando cambios. Al reactivar, se puede reenviar la misma operación. Una sesión vencida devuelve 401, o 419 si falla antes la comprobación CSRF; no se aplica la operación. Cambiar de cuenta con un `account_id` anterior devuelve 409. El cliente deberá pausar los envíos ante 401/419, renovar sesión y CSRF, comprobar la identidad y reintentar con los mismos UUID. Ante 429/503 o fallos de red debe conservar pendientes y respetar la espera indicada antes de reintentar.

No se incluye limpieza automática de tablas ni rollback de datos como método de desactivación. No debe revertirse la migración una vez que existan operaciones pendientes que dependan de sus recibos.
