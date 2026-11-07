# Instalacion en Windows

Sigue la secuencia de [README](../../README.md#instalación-local) para una base nueva. Para una existente, revisa primero su esquema y las guías de [credenciales](usuarios-autenticacion.md), [recuperación](actualizacion-privacidad-recuperacion.md) y [módulos nuevos](recetas-sala-pagos.md). No repitas migraciones ya instaladas ni seeds en producción.

Usa PHP 8.5 de C:\php85 y PostgreSQL 18. `php --ini` muestra el archivo cargado; habilita pdo_pgsql, curl y mbstring si faltan. Verifica `php -m` y `php backend/scripts/verificar-entorno.php`. Agrega C:\Program Files\PostgreSQL\18\bin al PATH para las utilidades SQL. Configura ambos .env y ejecuta las migraciones en orden, deteniendote si alguna falla.

XAMPP puede aportar Apache, pero su PHP 8.0 no es compatible. PostgreSQL se instala aparte: MySQL y phpMyAdmin no sirven para importar este esquema. Para repetir el entorno documentado usa dos servidores PHP en terminales separadas. Apache requiere sitios/rewrite adaptados a los puntos públicos y assets; no se probó esa configuración local en esta entrega. Nunca sirvas la raíz con `.env` o `src`.

## Base local nueva: secuencia completa

Estos comandos son instrucciones para el propietario, no una autorización para migrar bases existentes. Crear primero una base **vacía y exclusivamente local** llamada `citas_medicas_pruebas`, con un usuario propio y permisos de creación. Copiar los `.env.example` solo si no existen `.env`, como explica el README, y configurar sus URLs locales.

En una terminal PowerShell desde la raíz, declarar toda la conexión explícitamente; esta sesión no leerá `.env`. No utilizar valores de Neon ni guardar la clave en archivos versionados:

```powershell
$env:VIDASALUD_TEST_LOCAL = '1'
$env:APP_ENV = 'local'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '5432' # Cambiar si el PostgreSQL local usa otro puerto
$env:DB_NAME = 'citas_medicas_pruebas'
$env:DB_USER = Read-Host 'Usuario de PostgreSQL local'
$clave = Read-Host 'Clave de PostgreSQL local' -AsSecureString
$env:DB_PASS = [System.Net.NetworkCredential]::new('', $clave).Password
$env:DB_SSLMODE = 'disable'
```

Ejecutar **uno por uno**, comprobando `$LASTEXITCODE -eq 0` antes del siguiente. Detenerse ante cualquier error; no repetir sobre una instalación existente:

```powershell
& C:/php85/php.exe backend/scripts/migrar-base-datos.php                     # 001
& C:/php85/php.exe backend/scripts/actualizar-especialidades-medicos.php   # 002
& C:/php85/php.exe backend/scripts/actualizar-roles-citas.php               # 003
& C:/php85/php.exe backend/scripts/actualizar-agenda-reportes.php           # 004
& C:/php85/php.exe backend/scripts/actualizar-programacion-mensual.php      # 005
& C:/php85/php.exe backend/scripts/actualizar-consultas-bitacora.php        # 006–008
& C:/php85/php.exe backend/scripts/actualizar-seguridad-credenciales.php --aplicar # 009
& C:/php85/php.exe backend/scripts/actualizar-recuperacion-contrasena.php --aplicar # 010
& C:/php85/php.exe backend/scripts/actualizar-recetas-sala-pagos.php --aplicar # 011–013
```

Los tres últimos scripts sin `--aplicar` muestran una vista previa sin conexión. El de 011–013 exige variables **del proceso**, host local, puerto explícito y nombre de base terminado en `_pruebas`; no basta con editar `.env`. Ningún comando de esta guía se debe ejecutar contra Neon. Las migraciones 009/010 ya trabajadas allí no se repiten.

Opcionalmente, solo en esta base nueva, cargar cuentas sintéticas iniciales con una clave elegida fuera del repositorio:

```powershell
$claveSeed = Read-Host 'Clave exclusiva para cuentas sintéticas locales' -AsSecureString
$env:SEED_USERS_PASSWORD = [System.Net.NetworkCredential]::new('', $claveSeed).Password
& C:/php85/php.exe backend/scripts/cargar-datos-iniciales.php
Remove-Item Env:SEED_USERS_PASSWORD
Remove-Item Env:DB_PASS
```

No repetir el seed en cuentas existentes. Para el conjunto adicional e idempotente de demostración, consultar [su guía específica](correcciones-agenda-demostracion.md). Cerrar esta terminal cuando termine. En terminales nuevas, iniciar backend y frontend siguiendo el README; usarán sus `.env` locales, sin `VIDASALUD_TEST_LOCAL`. No establecer ese indicador de forma permanente.

## Comprobación y diagnóstico

| Sintoma | Revisar |
| --- | --- |
| php no reconocido | PATH o `& "C:\php85\php.exe"` |
| could not find driver | pdo_pgsql del PHP/php.ini seleccionado |
| Conexion rechazada | Servicio PostgreSQL, host, puerto y credenciales |
| Error 419 | Recargar CSRF; cookies y mismo host en ambos entornos |
| Vista sin sesion | Ambos servidores activos, API_URL y nombre de cookie |
| Columna inexistente | Base seleccionada y migraciones en orden |
| Sin turnos | Fecha futura, horario generado y medico/especialidad activos |
| Segunda reserva rechazada | Existe una Programada sin resultado |
| Conflicto al guardar consulta | Recargar y revisar version actual |

Los archivos y datos utilizan UTF-8. No publiques `.env`, claves locales ni respaldos.
