> Documento histórico de la entrega anterior. Para la refactorización actual lee el archivo ACTUALIZAR-ESTA-VERSION.md de la raíz. Las rutas de sus ejemplos corresponden al paquete anterior.

# Actualizar a la version 1.4.0

Base compatible: Sistema-Citas-Medicas-main(1).zip, version 1.3 con migraciones 001 a 004. Esta entrega es incremental y conserva el repositorio actual. No hace commits, push ni cambios en .env.

## 1. Conservar los cambios antes de aplicar

Deten backend y frontend con Ctrl+C. Abre PowerShell en la raiz del repositorio existente. Cambia solo las rutas del proyecto y del ZIP:

```powershell
Set-Location "C:\ruta\Sistema-Citas-Medicas-main"
$env:Path += ";C:\xampp\php"
$proyectoClinica = (Get-Location).Path
$respaldoClinica = "$proyectoClinica-respaldo-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item -LiteralPath $proyectoClinica -Destination $respaldoClinica -Recurse -Force -ErrorAction Stop
if (-not (Test-Path -LiteralPath "$respaldoClinica\README.md")) { throw "No se encontro la copia del proyecto." }
git status --short
```

La copia contiene tus cambios actuales, incluidos archivos no confirmados y configuracion; guardala fuera del repositorio. Haz ademas un respaldo completo de PostgreSQL con pgAdmin > Backup y verifica el archivo. El respaldo de codigo no contiene la base. No se requiere crear un commit para instalar.

## 2. Revisar y aplicar el parche

```powershell
$zipClinica = "C:\Users\TU_USUARIO\Downloads\actualizacion-programacion-mensual.zip"
$carpetaParcheClinica = Join-Path $env:TEMP ("clinica-actualizacion-" + [guid]::NewGuid().ToString())
Expand-Archive -LiteralPath $zipClinica -DestinationPath $carpetaParcheClinica -ErrorAction Stop
$parcheClinica = Join-Path $carpetaParcheClinica "actualizacion-programacion-mensual\cambios-programacion-mensual.patch"
git apply --check --whitespace=error $parcheClinica
if ($LASTEXITCODE -ne 0) { throw "El parche tiene conflictos. No se aplico: compara tus cambios con archivos/ del paquete." }
git apply --whitespace=error $parcheClinica
if ($LASTEXITCODE -ne 0) { throw "No se pudo aplicar el parche. Conserva los respaldos y revisa el conflicto." }
git diff --check
if ($LASTEXITCODE -ne 0) { throw "Revisa los espacios o conflictos antes de continuar." }
git diff --stat
git status --short
```

No uses --reject, reset --hard ni copies toda la carpeta archivos/ encima del proyecto si tienes cambios locales. Ante un conflicto, compara solamente los archivos afectados usando VS Code, conserva ambos cambios y vuelve a revisar. No cambies las carpetas .git, backend/.env o frontend/.env. git apply no requiere una rama nueva ni crea un repositorio.

## 3. Aplicar solo la migracion nueva

Con los dos servidores aun detenidos y 004 ya instalada:

```powershell
php backend/scripts/verificar-entorno.php --conexion
if ($LASTEXITCODE -ne 0) { throw "Revisa la conexion antes de migrar." }
php backend/scripts/actualizar-programacion-mensual.php
if ($LASTEXITCODE -ne 0) { throw "005 no se aplico. Revisa conexion, permisos y version de la base." }
```

005 usa una transaccion y puede repetirse sin recrear tablas ni duplicar excepciones. No modifica usuarios, claves, estados ni horas de las reservas anteriores. Agrega proteccion de cita activa y trazabilidad. Si falla, se revierte. No ejecutes migrar-base-datos.php, los scripts 002/003/004 ni el seeder en esta actualizacion.

No necesitas editar .env: los valores predeterminados son CITA_DURACION_MINUTOS=30, CLINICA_HORA_APERTURA=08:00 y CLINICA_HORA_CIERRE=17:00. Si tu configuracion previa define otros valores, tendra prioridad; revisala conscientemente sin reemplazarla por .env.example.

## 4. Iniciar y comprobar

Terminal 1, desde la raiz del proyecto:

```powershell
php -S 127.0.0.1:8000 -t backend/public backend/src/server.php
```

Terminal 2, desde la misma raiz:

```powershell
php -S 127.0.0.1:8080 -t frontend/public frontend/src/server.php
```

Abre http://127.0.0.1:8080 y recarga con Ctrl+F5. Verifica:

1. Administrador > Horarios > Programar horarios: elige un periodo futuro, lunes a viernes, 08:00-17:00. Debe crear 18 turnos por fecha elegida. Repite: cero turnos nuevos y fechas existentes.
2. Paciente: reserva una cita. Otra reserva, incluso con otro medico o dia, queda bloqueada con explicacion y enlace a la activa. Recepcion tampoco puede reservar una segunda para ese paciente.
3. Medico: en una cita propia futura, cancela con motivo obligatorio. El paciente queda habilitado y el turno vuelve a ofrecerse.
4. En una cita que realmente haya comenzado, registra Atendida. Para Inasistencia espera al fin del turno. Verifica el historial y el responsable. Una cita de otro medico no se puede modificar.
5. Recepcion conserva asistencia, inasistencia y reprogramacion. Verifica filtros y reportes existentes.
6. Revisa a 320/390 px: horarios, reserva y detalle; las tablas se desplazan dentro de su contenedor.

Las pruebas automatizadas se ejecutan solo en una base de pruebas existente y aislada, con 001 a 005 y datos de prueba; instrucciones completas en docs/ai/pruebas.md. El test de concurrencia requiere PHP/PDO PostgreSQL nativos. No se ejecuto en Windows desde esta entrega.

## 5. Si necesitas volver atras

Conserva ambos respaldos. Antes de cualquier regreso, respalda tambien los datos nuevos creados despues de actualizar. Revertir el parche solo revierte codigo; no es una migracion inversa. Si ya ejecutaste 005, no uses el codigo anterior con nuevas reglas sin planificar la compatibilidad. Restaurar un respaldo antiguo de PostgreSQL descarta escrituras posteriores: revisalas primero.

Para comprobar si el codigo puede revertirse sin conflictos: git apply -R --check $parcheClinica. Si aun no migraste ni hiciste cambios nuevos y la comprobacion pasa, puedes usar git apply -R $parcheClinica. Para una vuelta despues de migrar, realiza una recuperacion controlada de codigo y datos; no se incluye un script que borre columnas o registros.

Cuando hayas probado en tu equipo, puedes preparar commits manuales siguiendo docs/ai/plan-avances-git.md.
