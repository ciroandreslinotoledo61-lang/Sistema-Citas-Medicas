# Clinica Vida Salud

**Version 1.5.0. Sistema universitario de citas medicas para Bolivia.** PHP 8.5, PostgreSQL 18, PDO y frontend PHP/HTML/CSS/JavaScript modular. Conserva la arquitectura del proyecto recibido y completa consultas, tratamientos, historial clinico y bitacora.

Los nuevos turnos duran **30 minutos** con la configuracion entregada. La hora de Bolivia se aplica internamente; no se pide seleccionar ciudad ni se muestra el identificador tecnico de zona horaria.

**Base existente:** comienza por [ACTUALIZAR-ESTA-VERSION.md](ACTUALIZAR-ESTA-VERSION.md). **Para explicar el codigo:** [GUIA-ARQUITECTURA-Y-DEFENSA.md](GUIA-ARQUITECTURA-Y-DEFENSA.md).

## Novedades

- Nuevas citas en `programada`; 006 normaliza `pendiente` y `confirmada` sin borrar eventos anteriores. La asistencia `pendiente` se conserva. El origen de una reprogramacion muestra tambien el resultado de su ultimo destino.
- Bitacora administrativa automatica, con filtros y paginacion, sin secretos ni contenido clinico.
- Consulta y tratamientos en una transaccion; la cita pasa a Atendida. Correcciones con versiones previas y deteccion de ediciones simultaneas.
- Historial propio para el paciente y acceso del medico con relacion de atencion. Admin y Recepcion no reciben diagnosticos, tratamientos ni antecedentes.
- Cuatro SQL educativos para PostgreSQL, documentacion RUP, guia de defensa y respaldo/restauracion aislada.

## Funciones por rol

| Operacion | Administrador | Recepcionista | Medico | Paciente |
| --- | --- | --- | --- | --- |
| Usuarios administrativos, medicos y especialidades | Gestiona | No | No | No |
| Pacientes y datos personales | Gestiona | Gestiona | Datos basicos de sus citas | Perfil propio |
| Programar horarios por dia/periodo | Si | No | Consulta propios | No |
| Reservar | Para pacientes | Para pacientes | No | Para si mismo |
| Consultar citas | Todas | Todas | Asignadas | Propias |
| Cancelar antes del inicio | Si | Si | Asignada, motivo obligatorio | Propia |
| Reprogramar antes del inicio | Si | Si | No | Solicita a Recepcion |
| Atendida / Inasistencia | Si | Si | Asignadas | No |
| Registrar y editar consulta/tratamientos | No | No | Consulta de su cita | No |
| Historial clinico | No | No | Paciente asignado | Solo propio |
| Editar sangre, alergias y antecedentes | No | No | Paciente asignado | No |
| Reportes y bitacora | Si | No | No | No |

La autorizacion se verifica en backend, ademas de ocultar opciones. Una cuenta tiene rol fijo. Desactivar conserva los registros y citas anteriores.

## Organizacion

| Ruta | Responsabilidad |
| --- | --- |
| `backend/src/presentation/` | Rutas por modulo, controladores y seguridad HTTP |
| `backend/src/business/` | Services con reglas y Validators de entrada |
| `backend/src/data/` | Modelos, repositories SQL, conexion, migraciones y seeder |
| `backend/src/shared/` | Configuracion, errores, permisos, sesion y utilidades |
| `backend/scripts/` y `backend/tests/` | Instalacion, respaldo y verificaciones |
| `frontend/src/features/` | auth, panel, usuarios, pacientes, medicos, especialidades, horarios, citas, reportes, consultas y bitacora |
| `frontend/src/components/` | Navegacion, formularios, iconos y paginacion compartidos |
| `frontend/src/api/`, `app/`, `layouts/`, `routes/` | Transporte, vistas, plantilla y proteccion |
| `frontend/src/styles/estilos.css` | Paleta centralizada y estilos responsive |
| `database/sql-estudio/` | Cuatro SQL para una base didactica |
| `docs/ai/`, `docs/rup/`, `docs/uso/` | Contratos tecnicos, analisis y manuales |

El frontend usa CSS propio y JavaScript sin framework. No necesita Node, React, Bootstrap, Sequelize ni Composer para funcionar. CADEFAR sirve como referencia de organizacion; se mantiene el stack real.

## Instalacion nueva en Windows

Requiere PHP 8.5 y PostgreSQL 18. PHP debe disponer de `pdo`, `pdo_pgsql`, `mbstring`, `curl`, `session` y `ctype`. Guia ampliada: [instalacion](docs/uso/instalacion-windows.md).

```powershell
$env:Path += ";C:\php85;C:\Program Files\PostgreSQL\18\bin"
php -v
php --ini
php backend/scripts/verificar-entorno.php
```

Crea en pgAdmin una base **vacia** llamada `citas_medicas`. Copia los ejemplos solo si falta la configuracion:

```powershell
if (!(Test-Path backend/.env)) { Copy-Item backend/.env.example backend/.env }
if (!(Test-Path frontend/.env)) { Copy-Item frontend/.env.example frontend/.env }
```

En `backend/.env` configura DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS para tu PostgreSQL. Conserva `CITA_DURACION_MINUTOS=30`, apertura `08:00`, cierre `17:00` y `FRONTEND_URL=http://127.0.0.1:8080`. Para cuentas de demostracion elige una clave local de 12 a 72 bytes en SEED_USERS_PASSWORD. No subas `.env` a Git.

En `frontend/.env`: `API_URL=http://127.0.0.1:8000/api`, `FRONTEND_URL=http://127.0.0.1:8080` y el mismo SESSION_COOKIE_NAME del backend. No mezcles `localhost` y `127.0.0.1`.

Solo en una base nueva, ejecuta en orden y detente ante un error:

```powershell
php backend/scripts/migrar-base-datos.php
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/actualizar-roles-citas.php
php backend/scripts/actualizar-agenda-reportes.php
php backend/scripts/actualizar-programacion-mensual.php
php backend/scripts/actualizar-consultas-bitacora.php
php backend/scripts/cargar-datos-iniciales.php
php backend/scripts/verificar-entorno.php --conexion
```

El ultimo actualizador instala 006-008 en una transaccion. El seeder es solo para una base nueva/local: cuatro roles, tres especialidades y una cuenta por rol. Cuentas: `administrador@vidasalud.test`, `recepcionista@vidasalud.test`, `medico@vidasalud.test` y `paciente@vidasalud.test`. Usan la clave elegida en tu `.env`.

Abre dos terminales en la raiz. Backend:

```powershell
php -S 127.0.0.1:8000 -t backend/public backend/src/server.php
```

Frontend:

```powershell
php -S 127.0.0.1:8080 -t frontend/public frontend/src/server.php
```

Abre <http://127.0.0.1:8080>. `php -S` es para desarrollo. No publiques la raiz del repositorio.

## Actualizar una base existente

Si ya tienes 001-005, conserva tus `.env`, respalda y ejecuta **solo** `php backend/scripts/actualizar-consultas-bitacora.php`. No repitas migracion inicial ni seeder. Sigue [la guia incremental](ACTUALIZAR-ESTA-VERSION.md) para probar en una copia y conservar tus cambios.

## Reglas principales

Un paciente puede tener una sola Programada, incluso si paso su hora y falta registrar resultado; las excepciones legadas de 005 se conservan. Reservar revalida disponibilidad dentro de la transaccion. Cancelar libera el turno sin borrar la cita. Reprogramar enlaza otra Programada y revierte todo si falla.

Atendida se permite desde el inicio; Inasistencia, desde el fin. Registrar una consulta tambien marca Atendida. Marcar Atendida por si solo no inventa un diagnostico. Las correcciones clinicas exigen version actual y conservan contenido/tratamientos anteriores.

Con 30 minutos, 08:00-09:00 genera dos turnos y 08:00-17:00 dieciocho. Cambiar la configuracion afecta nuevas altas, no reservas antiguas.

## Documentacion

- [Defensa y recorrido del codigo](GUIA-ARQUITECTURA-Y-DEFENSA.md), [catalogo de metodos](docs/ai/catalogo-metodos.md), [API](docs/ai/api.md) y [diccionario](docs/ai/diccionario-datos.md).
- [Requisitos](docs/rup/requisitos.md), [casos de uso](docs/rup/casos-uso.md), [analisis y diseno](docs/rup/analisis-diseno.md), [trazabilidad](docs/rup/trazabilidad.md) e [informe RUP](docs/rup/informe-proyecto.md).
- [Manual por rol](docs/uso/manual-roles.md), [respaldo/recuperacion](docs/uso/respaldo-recuperacion.md), [despliegue](docs/uso/despliegue.md) y [SQL educativos](database/sql-estudio/README.md).
- [Pruebas y limites](docs/ai/pruebas.md), [archivos modificados](docs/refactorizacion/ARCHIVOS-MODIFICADOS.md) y [plan Git](docs/ai/plan-avances-git.md).

## Verificacion y limites

Sin BD: `php backend/tests/ejecutar-pruebas.php`. Para integracion usa una base desechable terminada en `_pruebas`, con 001-008 y seeder, segun la guia de pruebas.

Se verifico PHP 8.5 WebAssembly y PostgreSQL 18/PGlite con un adaptador PDO de pruebas, mas navegador Chromium. Pasaron 15 suites PHP, 54 comprobaciones clinicas/auditoria, 5 de migracion 006, 137 del contrato HTTP previo y 46 de nuevos recorridos. Las seis vistas nuevas se revisaron a 320, 390, 768 y 1440 px. Falta repetir en Windows nativo la concurrencia entre procesos, PowerShell, pg_dump y pg_restore. Los resultados historicos no sustituyen esa comprobacion.

No incluye recuperacion por correo, cambio de clave desde pantalla, reasignacion de rol, consultorios, laboratorio, notificaciones o correccion de resultados finales. La bitacora se protege mediante permisos y triggers; el propietario/superusuario SQL conserva poder administrativo. No se afirma certificacion sanitaria.

## Git y procedencia

Base: `Sistema-Citas-Medicas-main(2).zip`. No se pudo verificar el remoto actual. No se creo otro repositorio ni se hicieron commits, merge o push. Conserva tu `.git`. El parche incluido se comprueba primero con `git apply --check`; ante conflictos, integra los archivos comparando tus cambios.
