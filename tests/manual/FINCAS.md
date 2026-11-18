# Verificación local de autorización de Fincas

La suite automatizada usa exclusivamente SQLite en memoria y datos ficticios:

```powershell
rtk proxy php vendor/bin/phpunit --filter FincaAuthorizationTest --no-progress
```

Para revisar desde el navegador sin utilizar la base habitual ni leer `.env`, ejecuta desde la raíz del repositorio:

```powershell
rtk proxy php tests/manual/fincas.php prepare
rtk proxy php -S 127.0.0.1:8091 -t public tests/manual/fincas.php
```

El primer comando crea una SQLite exclusiva en la carpeta temporal del sistema, aplica allí las migraciones existentes y crea dos cuentas, dos fincas, dos animales y dos potreros ficticios. Imprime la ruta de esa base y los IDs de las fincas. Si la base ya existe, se niega a reiniciarla para conservar la revisión. El segundo comando mantiene el servidor local abierto; se detiene con Ctrl+C. Este router es solo para desarrollo local y no debe desplegarse.

Abre http://127.0.0.1:8091/login con estas cuentas de demostración:

| Cuenta | Contraseña ficticia |
| --- | --- |
| fincas-a@example.test | DemoFincas2026! |
| fincas-b@example.test | DemoFincas2026! |

1. Entra como A. Comprueba `/fincas`, `/animales`, `/potreros` y `/api/fincas/mapa`: solo deben aparecer los datos de A. Abre el detalle y edición de su finca; modifica su nombre y alterna su estado. El mapa solo incluye fincas activas con coordenadas.
2. Prueba `/fincas/ID_A/animales`, `/api/fincas/ID_A/potreros` y `/fincas/ID_A/potreros`. Las dos primeras rutas devuelven JSON; la tercera reutiliza el listado de potreros filtrado.
3. Cierra sesión y entra como B, o usa otra sesión privada. Abre `/fincas/ID_A`, `/fincas/ID_A/edit` y las tres consultas anteriores: deben devolver 404. `/potreros?finca_id=ID_A` también debe devolver 404.
4. Para comprobar escrituras denegadas, abre las herramientas del navegador estando como B y ejecuta, sustituyendo `ID_A` por el ID de A:

```javascript
const idAjeno = ID_A;
const headers = {
  'Accept': 'application/json',
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};
for (const [method, suffix, body] of [
  ['PUT', '', { nombre: 'Cambio denegado', latitud: 4.5, longitud: -74.1 }],
  ['POST', '/toggle-estado', {}],
  ['DELETE', '', {}],
]) {
  const response = await fetch(`/fincas/${idAjeno}${suffix}`, {
    method, headers, body: JSON.stringify(body),
  });
  console.log(method, response.status); // 404 en los tres casos
}
```

5. Vuelve como A y comprueba que su finca sigue existiendo y conserva el nombre y estado previos a los intentos de B. La suite automatizada compara además todas las columnas originales, incluidos `updated_at` y `deleted_at`.

El servidor conserva CSRF y sesiones reales en esta SQLite ficticia. Usa solo datos de demostración: la clave de aplicación y las contraseñas son públicas de prueba. Este servidor de navegador no utiliza conexiones MySQL/PostgreSQL ni configuración cacheada de despliegue. Para las suites multibase y el protocolo de sincronización consulta `docs/production-sync.md`.

Antes de usar Producción con una base ficticia preparada en una fase anterior, ejecuta `rtk proxy php tests/manual/fincas.php migrate`. Se conservan sus datos y se aplican exclusivamente allí las migraciones nuevas.
