# Pruebas hasta el avance 2

## Pruebas de validaciones y permisos

Desde la raiz del proyecto:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
```

- test_validaciones.php: datos de pacientes y cuentas administrativas.
- test_permisos.php: roles, datos publicos, hash y CSRF.
- test_validaciones_especialidades_medicos.php: nombres, matriculas, ids, booleanos, campos no permitidos y restricciones de rol.

## Integracion con PostgreSQL

Usa una base de pruebas separada. Para prepararla por primera vez, conectado a postgres en pgAdmin:

```sql
CREATE DATABASE citas_medicas_pruebas WITH ENCODING 'UTF8';
```

En PowerShell, desde la raiz del proyecto:

```powershell
$env:DB_NAME = "citas_medicas_pruebas"
php backend/scripts/migrar-base-datos.php
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/cargar-datos-iniciales.php
php backend/tests/test_registro_paciente.php
php backend/tests/test_gestion_especialidades_medicos.php
Remove-Item Env:DB_NAME
```

DB_NAME del entorno tiene prioridad sobre backend/.env. El resto de la configuracion, incluida SEED_USERS_PASSWORD, se toma del archivo. Si vaciaste esa clave despues de instalar, elige una clave de prueba antes de cargar esta base nueva.

Si la base de pruebas ya contiene la V1, ejecuta solamente la migracion 002 y las pruebas; no repitas la migracion 001 ni el seeder. En Linux/macOS puedes usar export DB_NAME=citas_medicas_pruebas y despues unset DB_NAME.

Ambas pruebas de integracion exigen que DB_NAME termine en _pruebas y eliminan solo sus propios registros al finalizar.

## Cobertura de las pruebas de integracion

test_registro_paciente.php verifica registro completo, historia obligatoria, rol Paciente fijo y rollback al duplicar CI.

test_gestion_especialidades_medicos.php verifica:

1. Crear y editar especialidades; rechazar nombres equivalentes.
2. Rechazar registro en una especialidad inactiva sin crear usuario.
3. Crear usuario y perfil con rol Medico fijo.
4. Rechazar correo o matricula duplicados.
5. Impedir desactivar una especialidad que tenga medicos activos.
6. Invalidar la sesion del medico al desactivarlo.
7. Impedir su reactivacion mientras su especialidad este inactiva.
8. Impedir eludir esa regla mediante Usuarios.
9. Editar datos, reasignar a una especialidad activa y reactivar.
10. Buscar por matricula y nombre de especialidad.

## Revision realizada para el avance 2

| Comprobacion | Resultado y alcance |
| --- | --- |
| Sintaxis | Correcta en 77 archivos PHP y 19 archivos JavaScript |
| Validaciones nuevas | Prueba ejecutada y aprobada con PHP 8.2 en WebAssembly |
| Integracion incluida | test_registro_paciente.php y test_gestion_especialidades_medicos.php ejecutados con los servicios PHP reales y un adaptador PDO de prueba hacia PGlite |
| Migracion 002 | Ejecutada dos veces; segunda ejecucion valida, sin recrear tablas |
| Duplicados previos | Migracion rechazada y revertida conservando las filas existentes |
| Indices normalizados | Rechazo directo en SQL de nombres y matriculas equivalentes |
| API y permisos | Rutas reales de Especialidades y Medicos rechazan Recepcionista, Medico y Paciente; sin sesion devuelven 401 |
| CSRF | La API rechaza una escritura sin token con 419 |
| Rollback de medico | Fallo forzado en PostgreSQL al insertar el perfil despues de insertar el usuario; el usuario se revierte |
| Navegador | Chromium: login, altas, edicion, cambios de estado, mensajes de conflicto y vista 403; sin excepciones JavaScript |
| Celular | Formulario de medico revisado a 390 px, sin desbordamiento horizontal |
| Parche | Aplicacion verificada sobre una copia de la V1 y comparada con el proyecto actualizado |
| Archivos | Texto ASCII, UTF-8 sin BOM; sin .env reales ni credenciales personales |
| PDO pdo_pgsql nativo | Pendiente de ejecutar en el equipo de instalacion |
| Concurrencia entre conexiones nativas | No verificada en este entorno; los servicios usan bloqueo de filas y transacciones |
| Horarios y reservas | Fuera de este avance |

El entorno de revision usa PHP en WebAssembly y PGlite, un motor PostgreSQL embebido. Para los recorridos del navegador se adaptaron la conexion PDO y el transporte de consulta de sesion entre frontend y backend; las rutas, permisos, controladores, servicios, SQL, vistas y acciones JavaScript fueron los del proyecto.

Estas pruebas no sustituyen la comprobacion con pdo_pgsql y curl nativos en Windows. Los adaptadores y herramientas del entorno de revision no son dependencias del proyecto ni se incluyen en el paquete.

## Revision local antes del commit

Sigue el recorrido de actualizacion-especialidades-medicos.md. Comprueba el acceso de los cuatro roles, los duplicados, los estados y el registro de pacientes. Revisa git status y confirma que los .env no se preparen para el commit.
