# Clinica Vida Salud

**Version 1.5.0. Sistema universitario de citas medicas para Bolivia.** PHP 8.5, PostgreSQL 18, PDO y frontend PHP/HTML/CSS/JavaScript modular. Conserva la arquitectura del proyecto recibido y completa consultas, tratamientos, historial clinico y bitacora.

Las nuevas citas y turnos duran **exactamente 30 minutos**, sin configuración externa de duración. Los registros históricos se conservan. La hora de Bolivia se aplica internamente; no se pide seleccionar ciudad ni se muestra el identificador tecnico de zona horaria.

**Correcciones y seed aislado:** [agenda y demostración local](docs/uso/correcciones-agenda-demostracion.md). **Para explicar el codigo:** [GUIA-ARQUITECTURA-Y-DEFENSA.md](GUIA-ARQUITECTURA-Y-DEFENSA.md). **Para agentes:** [AGENTS.md](AGENTS.md).

## Novedades

- Cancelación visible con motivo de bloqueo, incidencia de ausencia del médico para Administración/Recepción y tarea CLI independiente para revisar vencidas sin llegada/consulta (30 minutos de tolerancia por defecto). **Requiere configurar un Cron Job en Render después de autorizar la publicación; no se activó producción.** Pagos con interfaz simplificada y aclaración discreta al confirmar. [Configuración y reglas](docs/uso/citas-vencidas-pagos.md).
- Recetas médicas con PDF privado, anulación motivada y correcciones enlazadas; sala de espera con historial y alcance por médico; pagos **solo simulados** QR/PayPal y reporte separado. Requieren migraciones **011 → 012 → 013**. [Guía y límites](docs/uso/recetas-sala-pagos.md).
- Formularios de creación limpios tras éxito, datos conservados al fallar/editar y bloqueo de doble envío. Se mantienen los selectores e identificadores médicos `(#ID)`.
- Selector de médicos por ID, especialidad y fecha en bloques; vista propia del médico sin selector ajeno. Disponibilidad con rol, perfil y estados válidos. Nuevas reservas/reprogramaciones de 30 minutos; seed idempotente de seis cuentas solo para pruebas locales.
- Recuperacion por correo con Resend configurable, token con hash, vencimiento y uso unico; requiere la migracion incremental 010. Pruebas locales con correo simulado, sin envio real. Consulta [el informe de actualizacion](docs/uso/actualizacion-privacidad-recuperacion.md).
- Mis citas del paciente con tarjetas, Proximas citas e Historial, filtros y paginacion en servidor. El personal conserva sus tablas y herramientas.
- Historial propio sin buscador administrativo, alcance SQL por identidad y relacion medica, textos visibles en UTF-8. El acceso clinico administrativo permanece denegado; [propuesta para revision](docs/uso/propuesta-permisos-clinicos.md).
- Cambio de contrasena para los cuatro roles y restablecimiento administrativo con clave temporal, cambio obligatorio, revocacion de sesiones y limites persistentes. Requiere la nueva migracion 009; consulta [la guia de autenticacion](docs/uso/usuarios-autenticacion.md) antes de aplicarla. No se aplica automaticamente al desplegar.
- Nuevas citas en `programada`; 006 normaliza `pendiente` y `confirmada` sin borrar eventos anteriores. La asistencia `pendiente` se conserva. El origen de una reprogramacion muestra tambien el resultado de su ultimo destino.
- Bitacora administrativa automatica, con filtros y paginacion, sin secretos ni contenido clinico.
- Consulta y tratamientos en una transaccion; la cita pasa a Atendida. Correcciones con versiones previas y deteccion de ediciones simultaneas.
- Historial propio para el paciente y acceso del medico con relacion de atencion. Admin y Recepcion no reciben diagnosticos, tratamientos ni antecedentes.
- Cuatro SQL educativos para PostgreSQL, documentacion RUP, guia de defensa y respaldo/restauracion aislada.

## Funciones por rol

| Operacion | Administrador | Recepcionista | Medico | Paciente |
| --- | --- | --- | --- | --- |
| Usuarios administrativos, medicos y especialidades | Gestiona | No | No | No |
| Cambiar contrasena propia | Si | Si | Si | Si |
| Recuperar por correo registrado | Si | Si | Si | Si |
| Restablecer contrasena de otra cuenta activa | Si | No | No | No |
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
| Recetas y PDF | No | No | Emite/anula/consulta propias | Consulta propias |
| Sala de espera | Consulta operativa | Registra llegada y consulta | Inicia/finaliza sus citas | No |
| Pagos simulados | Consulta/reporte | Consulta/reporte | No | Simula y consulta propios |

La autorizacion se verifica en backend, ademas de ocultar opciones. Una cuenta tiene rol fijo. Desactivar conserva los registros y citas anteriores.

## Organizacion

| Ruta | Responsabilidad |
| --- | --- |
| `backend/src/presentation/` | Rutas por modulo, controladores y seguridad HTTP |
| `backend/src/business/` | Services con reglas y Validators de entrada |
| `backend/src/data/` | Modelos, repositories SQL, conexion, migraciones y seeder |
| `backend/src/shared/` | Configuracion, errores, permisos, sesion y utilidades |
| `backend/scripts/` y `backend/tests/` | Instalacion, respaldo y verificaciones |
| `frontend/src/features/` | Módulos existentes y nuevos `recetas/`, `sala-espera/` y `pagos/`, con `pages/` y `services/` |
| `frontend/src/components/` | Navegacion, formularios, iconos y paginacion compartidos |
| `frontend/src/api/`, `app/`, `layouts/`, `routes/` | Transporte, vistas, plantilla y proteccion |
| `frontend/src/styles/estilos.css` | Paleta centralizada y estilos responsive |
| `database/sql-estudio/` | Cuatro SQL para una base didactica |
| `deploy/`, `Dockerfile` | Configuración de publicación; no ejecuta seeds de demostración |
| `docs/refactorizacion/`, `docs/rup/`, `docs/uso/` | Contratos, estructura, análisis y manuales |

El frontend usa CSS propio y JavaScript sin framework. No necesita Node, React, Bootstrap, Sequelize ni Composer para funcionar. CADEFAR sirve como referencia de organizacion; se mantiene el stack real.

## Instalacion nueva en Windows

Requiere PHP 8.5 y PostgreSQL 18. PHP debe disponer de `pdo`, `pdo_pgsql`, `mbstring`, `curl`, `session` y `ctype`. Guia ampliada: [instalacion](docs/uso/instalacion-windows.md).

```powershell
$env:Path += ";C:\php85;C:\Program Files\PostgreSQL\18\bin"
php -v
php --ini
php backend/scripts/verificar-entorno.php
```

Crea en tu PostgreSQL **local** una base **vacia** llamada `citas_medicas_pruebas`. Esta entrega se verifica en una base de pruebas; no ejecutes estos pasos contra Neon. Copia los ejemplos solo si falta la configuracion:

```powershell
if (!(Test-Path backend/.env)) { Copy-Item backend/.env.example backend/.env }
if (!(Test-Path frontend/.env)) { Copy-Item frontend/.env.example frontend/.env }
```

En `backend/.env` configura `APP_ENV=local`, `DB_HOST=127.0.0.1`, `DB_NAME=citas_medicas_pruebas`, DB_PORT, DB_USER y DB_PASS para tu PostgreSQL local. La duración es fija en 30 minutos (`CITA_DURACION_MINUTOS` ya no tiene efecto); apertura `08:00`, cierre `17:00` y `FRONTEND_URL=http://127.0.0.1:8080`. Para las cuentas iniciales elige una clave local de 12 a 72 bytes en SEED_USERS_PASSWORD. No subas `.env` a Git.

En `frontend/.env`: `API_URL=http://127.0.0.1:8000/api`, `FRONTEND_URL=http://127.0.0.1:8080` y el mismo SESSION_COOKIE_NAME del backend. No mezcles `localhost` y `127.0.0.1`.

Solo en una base nueva, ejecuta en orden y detente ante un error:

```powershell
php backend/scripts/migrar-base-datos.php
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/actualizar-roles-citas.php
php backend/scripts/actualizar-agenda-reportes.php
php backend/scripts/actualizar-programacion-mensual.php
php backend/scripts/actualizar-consultas-bitacora.php
php backend/scripts/actualizar-seguridad-credenciales.php --aplicar
php backend/scripts/actualizar-recuperacion-contrasena.php --aplicar
php backend/scripts/actualizar-recetas-sala-pagos.php
php backend/scripts/cargar-datos-iniciales.php
php backend/scripts/verificar-entorno.php --conexion
```

El actualizador de consultas instala 006-008; el de seguridad agrega 009 en otra transaccion y solo admite una base local terminada en `_pruebas`. La autenticacion actual necesita 009 y la recuperacion por correo necesita 010. El seeder es solo para una base nueva/local: cuatro roles, tres especialidades y una cuenta por rol. Cuentas: `administrador@vidasalud.test`, `recepcionista@vidasalud.test`, `medico@vidasalud.test` y `paciente@vidasalud.test`. Usan la clave elegida en tu `.env`.

El comando nuevo de recetas/sala/pagos mostrado arriba es **solo vista previa**. Antes de abrir esos módulos, configura por proceso la conexión local de pruebas y aplica 011–013 con `--aplicar` siguiendo [su guía](docs/uso/recetas-sala-pagos.md); ese actualizador no lee `.env` ni acepta producción.

Para añadir Médico 1-3 y Paciente 1-3 sin sustituir esas cuentas, usa el [seed de demostración independiente](docs/uso/correcciones-agenda-demostracion.md). Exige base local `_pruebas`, habilitación explícita y una clave externa; no admite producción ni cambia cuentas existentes.

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

Trabaja primero en una copia local terminada en `_pruebas`. Conserva 001-010: esquema base, unicidad, responsabilidades, agenda/perfiles, programación/cita activa, estado programada, bitácora, consultas/historial, credenciales y recuperación. **009 y 010 ya fueron trabajadas en Neon: no repetirlas allí.** La ampliación requiere **011 recetas, 012 sala y 013 pagos de demostración**, en ese orden y sin conversión de registros previos. El [actualizador nuevo](docs/uso/recetas-sala-pagos.md) exige conexión local explícita mediante variables de proceso y rechaza producción. No repitas migración inicial ni seeder en bases existentes. Conserva tus `.env` y un respaldo. No se migró Neon ni se realizó despliegue.

## Reglas principales

Un paciente puede tener una sola Programada; las excepciones legadas de 005 se conservan. Tras fin+tolerancia, el cron configurado cierra como No asistió únicamente las citas sin llegada ni consulta. Los pacientes presentes y sin atención quedan para revisión administrativa, no como ausentes. Reservar revalida disponibilidad dentro de la transacción. Cancelar conserva motivo e historial; la excepción de ausencia médica exige Administración/Recepción y consulta no iniciada. Reprogramar enlaza otra Programada y revierte todo si falla. [Reglas completas](docs/uso/citas-vencidas-pagos.md).

Atendida se permite desde el inicio; Inasistencia, desde el fin. Registrar una consulta tambien marca Atendida. Marcar Atendida por si solo no inventa un diagnostico. Las correcciones clinicas exigen version actual y conservan contenido/tratamientos anteriores.

08:00-09:00 genera dos turnos y 08:00-17:00 dieciocho. La duración de nuevas altas siempre es 30 minutos. Turnos legados de otra duración no se ofrecen para nuevas reservas; citas y bloques existentes no se convierten. Para descansos, programa bloques separados.

## Documentacion

- [Defensa y recorrido del codigo](GUIA-ARQUITECTURA-Y-DEFENSA.md), [endpoints](docs/refactorizacion/ENDPOINTS.md) y [estructura](docs/refactorizacion/ESTRUCTURA-CARPETAS.md).
- [Requisitos](docs/rup/requisitos.md), [casos de uso](docs/rup/casos-uso.md), [analisis y diseno](docs/rup/analisis-diseno.md), [trazabilidad](docs/rup/trazabilidad.md) e [informe RUP](docs/rup/informe-proyecto.md).
- [Manual por rol](docs/uso/manual-roles.md), [respaldo/recuperacion](docs/uso/respaldo-recuperacion.md), [despliegue](docs/uso/despliegue.md) y [SQL educativos](database/sql-estudio/).
- [Pruebas y límites](docs/uso/pruebas-php.md), [correcciones y demostración](docs/uso/correcciones-agenda-demostracion.md) y [archivos modificados históricos](docs/refactorizacion/ARCHIVOS-MODIFICADOS.md).

## Verificacion y limites

Sin BD: configura `$env:VIDASALUD_TEST_LOCAL='1'` y ejecuta `php backend/tests/ejecutar-pruebas.php` para omitir la lectura de `.env`. La suite `php backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin"` crea su propio clúster local desechable, aplica 001-013 y ejecuta privacidad, autenticación, recuperación con correo simulado, módulos nuevos, suites PHP, contrato HTTP anterior y configuración de despliegue. Requiere PHP con cURL, pdo_pgsql y mbstring y binarios PostgreSQL; no necesita Python ni usa Neon o los `.env`. Agrega `--navegador` para los recorridos existentes de Edge. Ejecuta también `node --experimental-vm-modules backend/tests/test_formularios.mjs` y `node --experimental-vm-modules backend/tests/test_filtros_listados.mjs`. Consulta [pruebas locales](docs/uso/pruebas-php.md) y [la guía de nuevos módulos](docs/uso/recetas-sala-pagos.md).

La revision de credenciales del 31/10/2026 verifico Windows nativo, PHP 8.5.11 y PostgreSQL 18.6: pasaron 18 suites PHP (incluida concurrencia), 330 comprobaciones HTTP de autenticacion, 137 del contrato HTTP anterior y 10 de configuracion. Edge comprobo los formularios con API simulada a 390 y 1440 px. El detalle y los limites estan en [la guia de autenticacion](docs/uso/usuarios-autenticacion.md).

Como antecedente, se verificaron PHP 8.5 WebAssembly, PostgreSQL 18/PGlite y Chromium: 15 suites PHP, 54 comprobaciones clinicas/auditoria, 5 de migracion 006, 137 del contrato HTTP previo y 46 de nuevos recorridos, con seis vistas a 320, 390, 768 y 1440 px. Esta revision de credenciales no verifica los scripts operativos de PowerShell, pg_dump ni pg_restore.

La recuperacion por correo requiere configurar Resend y verificar el remitente; no se enviaron mensajes reales en las pruebas. No incluye reasignacion de rol, consultorios, laboratorio, notificaciones de citas o correccion de resultados finales. La bitacora se protege mediante permisos y triggers; el propietario/superusuario SQL conserva poder administrativo. No se afirma certificacion sanitaria.

## Git y procedencia

Base: `Sistema-Citas-Medicas-main(2).zip`. No se pudo verificar el remoto actual. No se creo otro repositorio ni se hicieron commits, merge o push. Conserva tu `.git`. El parche incluido se comprueba primero con `git apply --check`; ante conflictos, integra los archivos comparando tus cambios.
