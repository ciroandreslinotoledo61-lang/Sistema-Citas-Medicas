# Pruebas de la version 1

## Pruebas incluidas

Ejecuta desde la raiz del proyecto:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
```

La primera comprueba normalizacion de correo y CI, fechas imposibles o futuras, tipos de datos, confirmacion de clave, contacto incompleto, roles no habilitados, estado booleano y paginacion.

La segunda comprueba permisos por rol, datos publicos sin hash, verificacion de clave y rechazo de un token CSRF falso.

## Prueba de integracion con PostgreSQL

Crea una base separada desde pgAdmin, conectado a postgres:

```sql
CREATE DATABASE citas_medicas_pruebas WITH ENCODING 'UTF8';
```

En una terminal PowerShell, desde la raiz:

```powershell
$env:DB_NAME = "citas_medicas_pruebas"
php backend/scripts/migrar-base-datos.php
php backend/scripts/cargar-datos-iniciales.php
php backend/tests/test_registro_paciente.php
Remove-Item Env:DB_NAME
```

La configuracion restante se toma de backend/.env, incluida SEED_USERS_PASSWORD. Si ya vaciaste esa variable, elige una clave de prueba antes de cargar los datos en esta base nueva. No repitas migracion/seeder si esa base ya esta preparada.

En Linux/macOS puedes usar `export DB_NAME=citas_medicas_pruebas` y despues `unset DB_NAME`.

La prueba exige que el nombre termine en `_pruebas`. Comprueba:

1. Creacion de cuenta, paciente e historia.
2. Asignacion del rol Paciente aunque el JSON intente enviar Administrador.
3. Rechazo de una CI repetida con SQLSTATE 23505.
4. Ausencia de un usuario huerfano tras el rollback.

Al terminar, elimina solamente los registros que creo esa ejecucion. No vacia tablas ni borra la base.

## Revision realizada al preparar esta entrega

| Comprobacion | Resultado y alcance |
| --- | --- |
| Sintaxis PHP | Correcta en los 60 archivos PHP; analizador de PHP 8.2 en WebAssembly |
| Sintaxis JavaScript | Correcta en los 13 archivos JS; analizador de Node |
| test_validaciones.php | Ejecutado y aprobado con PHP 8.2 en WebAssembly |
| test_permisos.php | Ejecutado y aprobado con PHP 8.2 en WebAssembly |
| SQL de la migracion | Ejecutado en PGlite: 11 tablas, FK, restricciones e indices creados |
| Integridad SQL | Correo, CI e historia unicos; rollback sin usuario huerfano |
| Servicios y repositorios reales | Ejecutados con PHP y un adaptador de prueba hacia PGlite: seeder, login, usuarios, cambios de estado, invalidacion de sesion, registro de paciente, rollback, contacto y busquedas |
| Rutas HTTP sin base nativa | Estado, token, rechazo CSRF, CORS, redireccion de vistas sin cookie y bloqueo de .env revisados con un adaptador de solicitudes PHP |
| Interfaz publica | Inicio, login y registro revisados en Chromium; errores de conexion visibles; sin excepciones JS y sin desbordamiento horizontal a 390 px |
| Archivos entregados | Texto ASCII, UTF-8 sin BOM; sin .env reales ni credenciales personales |
| Conexion PDO pdo_pgsql nativa | Pendiente de ejecutar en el equipo de instalacion |
| test_registro_paciente.php con PostgreSQL nativo | Incluido; no ejecutado con ese controlador en el entorno de entrega |
| Recorrido completo de roles en navegador con PostgreSQL nativo | Pendiente de verificar tras instalar |
| Reservas concurrentes | Fuera de la V1; se probaran al implementar citas |

PGlite es un PostgreSQL embebido. El adaptador de revision permite probar el SQL y los servicios, pero no sustituye la conexion real de PDO, su controlador, la configuracion de Windows ni una prueba de concurrencia. Las herramientas del entorno de revision no son dependencias del proyecto y no se incluyen en el ZIP.

## Revision manual antes del primer push

- Inicia backend y frontend usando las direcciones del README.
- Revisa los cuatro roles con las cuentas ficticias.
- Comprueba que la sesion del usuario desactivado deje de acceder.
- Comprueba que Paciente no pueda abrir Usuarios ni listar otros pacientes.
- Registra una cuenta con correo duplicado y otra con CI duplicada.
- Revisa el telefono y contacto actualizados desde otra sesion.
- Confirma que no se envien hashes ni informacion de conexion en las respuestas.
- Comprueba que git status no incluya los .env.

Los permisos y los datos deben verificarse en la API, ademas de observar el menu.
