# Clínica Vida Salud

Sistema académico de gestión de citas médicas para Bolivia. Integra la atención desde la reserva hasta la consulta, el historial y las recetas, con acceso diferenciado por rol y auditoría de operaciones.

**Aplicación publicada:** [clinica-vida-salud-bavz.onrender.com](https://clinica-vida-salud-bavz.onrender.com).

La instancia publicada y la entrega local pueden corresponder a versiones distintas hasta que el propietario autorice una actualización. Los pagos son exclusivamente de prueba: no realizan cargos reales ni solicitan datos bancarios.

## Objetivos

**Objetivo general:** gestionar el ciclo de una cita médica, desde la disponibilidad y reserva hasta la atención y su registro, preservando la integridad de los datos y la privacidad de cada paciente.

**Objetivos específicos:**

- Organizar pacientes, médicos, especialidades y horarios mediante identificadores únicos.
- Evitar reservas incompatibles y conservar la trazabilidad de cancelaciones, reprogramaciones y atención.
- Registrar consultas, tratamientos, historial y recetas con permisos por rol y relación asistencial.
- Facilitar el trabajo operativo de recepción, sala de espera, reportes y auditoría.
- Demostrar pagos sin cargos reales y documentar la instalación y verificación del sistema en un entorno aislado.

El alcance es académico y de gestión clínica básica; no constituye un sistema hospitalario integral ni una plataforma de cobro real.

## Tecnologías y arquitectura

- **Backend:** PHP, PDO y PostgreSQL; organización en `business`, `data`, `presentation` y `shared`.
- **Frontend:** PHP, HTML, CSS propio y JavaScript modular sin framework.
- **Entorno de pruebas:** PHP 8.5.11 y PostgreSQL 18; Node.js y Microsoft Edge para pruebas opcionales de navegador.
- **Publicación:** Docker, Apache y Render; PostgreSQL alojado en Neon. El Dockerfile existente usa PHP 8.3-Apache y se conserva sin cambios.
- Sin dependencias Composer/npm para ejecutar la aplicación. Node.js se utiliza únicamente para determinadas pruebas.

```text
citas-medicas/
├── backend/
│   ├── public/                 # Punto de entrada HTTP
│   ├── scripts/                # Instalación local, respaldo y tareas CLI
│   ├── src/
│   │   ├── business/           # services/ y validators/
│   │   ├── data/               # config/, migrations/, models/, repositories/, seeders/
│   │   ├── presentation/       # controllers/, http/, middlewares/, routes/
│   │   └── shared/             # config/, constants/, errors/, utils/
│   └── tests/                  # Suites PHP/JS, contratos y escenarios HTTP
├── frontend/
│   ├── public/
│   └── src/                    # api/, app/, components/, features/, layouts/, routes/, styles/
├── database/sql-estudio/       # SQL didáctico, no instalación productiva
├── deploy/                    # Configuración de publicación existente
├── docs/                      # Arquitectura, RUP, manuales y antecedentes
├── AGENTS.md                  # Reglas de trabajo para agentes
├── .dockerignore
├── .editorconfig
├── .gitignore
├── Dockerfile
└── README.md
```

Este resumen no sustituye el [árbol real de carpetas](docs/refactorizacion/ESTRUCTURA-CARPETAS.md): se conservan todas las subcarpetas internas. En cada módulo frontend, `pages` contiene vistas y lógica de página; `services`, acceso a API; `components`, fragmentos reutilizables; y `utils`, utilidades cuando corresponda.

Flujo del backend: **rutas → controladores → servicios/validadores → repositorios SQL → modelos**. Los servicios deciden permisos y transacciones; los controladores no ejecutan SQL. La autorización se aplica en el servidor, no solo en los botones.

## Roles

| Rol | Funciones principales |
| --- | --- |
| Administrador | Usuarios, médicos, especialidades, pacientes, programación y consulta de bloques horarios, citas, reportes y bitácora. Consulta operativa de sala y pagos de prueba. Sin acceso al contenido clínico. |
| Recepcionista | Pacientes, disponibilidad y gestión administrativa de citas; llegada en sala; consulta y reportes de pagos. No administra bloques horarios ni accede al historial clínico o a recetas. |
| Médico | Consulta de citas y horarios propios; inicio/finalización de atención; consultas, tratamientos y recetas de sus atenciones; historial autorizado por relación asistencial. No programa bloques ni reprograma citas. |
| Paciente | Perfil, citas, historial y recetas propios; reserva y cancelación según reglas; pagos de prueba para sus propias citas. |

Los detalles están en el [manual por rol](docs/uso/manual-roles.md) y en las guías de módulos. `PermisosSistema.php` y los servicios son la fuente de verdad del acceso.

## Funcionalidades implementadas

- Registro de pacientes e historia clínica; gestión de médicos y especialidades.
- Horarios por día o período, disponibilidad, filtros por médico/fecha y paginación. Se mantienen las etiquetas de médicos `(#1)`, `(#2)`, etc.; los nombres no sustituyen a los identificadores.
- Nuevas citas de **30 minutos**, validación de cruces, transacciones y protección ante reservas concurrentes. Una cita programada activa por paciente, con las excepciones históricas previstas en SQL.
- Cancelación con motivo/historial, reprogramación atómica y registro de asistencia. Una consulta iniciada o finalizada no puede cancelarse.
- Consultas, tratamientos e historial clínico con privacidad por identidad y control de versiones.
- **Recetas médicas:** PDF privado, contenido emitido inmutable, anulación motivada y correcciones enlazadas. No incluyen firma digital certificada.
- **Sala de espera:** llegada, espera, consulta y atención; eventos históricos y alcance por médico, sin contenido clínico en recepción.
- **Pagos de prueba:** QR/PayPal, estados Pendiente/Pagado/Cancelado, referencias y reportes separados de ingresos reales. Aclaración visible al confirmar, sin proveedores reales.
- Contraseñas temporales, cambio obligatorio, revocación de sesiones, CSRF y recuperación por correo configurable.
- Tarea CLI para revisar citas vencidas sin llegada/consulta tras una tolerancia de 30 minutos por defecto. Requiere un Cron Job independiente; los presentes quedan para revisión y los estados finales se conservan.

### Implementación disponible y configuración de producción

Que una funcionalidad esté implementada no significa que sus servicios externos estén activados en la instancia publicada.

| Funcionalidad | Implementado en el proyecto | Configuración o comprobación adicional |
| --- | --- | --- |
| Citas vencidas | Script CLI, tolerancia configurable, bloqueos, transacciones y auditoría; sin `--aplicar` muestra una vista previa sin conexión. | Crear y habilitar un **Cron Job independiente** con variables de proceso y conexión autorizada. No se ejecuta desde visitas web ni se activa por publicar el código. [Guía de configuración](docs/uso/citas-vencidas-pagos.md). |
| Recuperación de contraseña | Solicitud, enlace de uso único y vencimiento, validaciones, revocación de sesiones e integración de envío con Resend. | Configurar `MAIL_PROVIDER=resend`, `MAIL_FROM`, `RESEND_API_KEY`, remitente verificado y `FRONTEND_URL` HTTPS. El envío real sigue pendiente de configuración externa; las pruebas usan correo simulado. [Guía de recuperación](docs/uso/actualizacion-privacidad-recuperacion.md). |
| Pagos de prueba | Intentos propios, confirmación/cancelación, estados y reportes de demostración. | Definir `DEMO_TARIFA_BOB`. No requiere ni incorpora cuentas, claves o APIs de cobro QR/PayPal reales. |
| Recetas | Emisión, anulación motivada, corrección enlazada y descarga privada en PDF. | Revisar datos institucionales. El PDF no incluye firma digital certificada ni acredita por sí solo validez profesional. |

Esta revisión documental no configura servicios productivos ni verifica su disponibilidad en Render.

## Base de datos PostgreSQL

Las [migraciones 001–013](backend/src/data/migrations/) definen **18 tablas funcionales**. El inventario siguiente se obtiene de sus sentencias `CREATE TABLE`, no de una conexión a Neon:

| N.º | Tabla | Responsabilidad |
| --- | --- | --- |
| 1 | `roles` | Catálogo de los cuatro roles del sistema. |
| 2 | `usuarios` | Identidad, acceso y estado de las cuentas. |
| 3 | `pacientes` | Perfil y datos administrativos del paciente. |
| 4 | `historias_clinicas` | Historia asociada al paciente. |
| 5 | `especialidades` | Catálogo de especialidades médicas. |
| 6 | `medicos` | Perfil profesional, usuario y especialidad. |
| 7 | `horarios_medicos` | Bloques de disponibilidad por médico. |
| 8 | `turnos` | Intervalos de atención generados desde los bloques. |
| 9 | `citas` | Reserva, estado y trazabilidad de cambios. |
| 10 | `consultas` | Registro clínico de la atención de una cita. |
| 11 | `tratamientos` | Tratamientos vinculados a la consulta. |
| 12 | `bitacora_sistema` | Auditoría de operaciones sin copiar contenido clínico ni secretos. |
| 13 | `intentos_autenticacion` | Control persistente de intentos y límites de acceso. |
| 14 | `recuperaciones_contrasena` | Hash de tokens de recuperación, vencimiento y uso único. |
| 15 | `recetas_medicas` | Emisión, medicamentos, anulación y enlace de corrección. |
| 16 | `sala_espera` | Estado operativo y versión de la atención por cita. |
| 17 | `eventos_sala_espera` | Historial de eventos de sala. |
| 18 | `pagos_demostracion` | Intentos simulados en BOB, referencias y estados. |

**Relaciones principales:** usuarios con roles y perfiles; pacientes con historias y citas; médicos con especialidades y bloques; bloques con turnos; citas con consultas; consultas con tratamientos y recetas. Sala, eventos y pagos se vinculan a la cita. Las relaciones utilizan claves e identificadores, no nombres.

La integridad se apoya en claves primarias y foráneas, restricciones, índices y triggers. Las operaciones sensibles emplean SQL parametrizado, transacciones y bloqueos; determinados cambios usan control de versiones. Los importes de pagos se almacenan en centavos enteros BOB, con referencias `DEMO-` y estados finales inmutables.

### Migraciones conservadas

| Migración | Archivo | Propósito |
| --- | --- | --- |
| 001 | [001_crear_tablas_sistema.sql](backend/src/data/migrations/001_crear_tablas_sistema.sql) | Esquema inicial de 11 tablas. |
| 002 | [002_unicidad_especialidades_medicos.sql](backend/src/data/migrations/002_unicidad_especialidades_medicos.sql) | Unicidad de especialidades y matrículas. |
| 003 | [003_responsabilidades_citas.sql](backend/src/data/migrations/003_responsabilidades_citas.sql) | Responsabilidades y trazabilidad de citas. |
| 004 | [004_agenda_30_minutos_y_perfiles.sql](backend/src/data/migrations/004_agenda_30_minutos_y_perfiles.sql) | Agenda y perfiles. |
| 005 | [005_programacion_y_cita_activa.sql](backend/src/data/migrations/005_programacion_y_cita_activa.sql) | Programación y protección de cita activa. |
| 006 | [006_estado_programada.sql](backend/src/data/migrations/006_estado_programada.sql) | Normalización del estado programada. |
| 007 | [007_bitacora_sistema.sql](backend/src/data/migrations/007_bitacora_sistema.sql) | Bitácora de auditoría. |
| 008 | [008_consultas_historial_clinico.sql](backend/src/data/migrations/008_consultas_historial_clinico.sql) | Ampliación de consultas e historial clínico. |
| 009 | [009_seguridad_credenciales.sql](backend/src/data/migrations/009_seguridad_credenciales.sql) | Seguridad de credenciales e intentos. |
| 010 | [010_recuperacion_contrasena.sql](backend/src/data/migrations/010_recuperacion_contrasena.sql) | Recuperación de contraseña. |
| 011 | [011_recetas_medicas.sql](backend/src/data/migrations/011_recetas_medicas.sql) | Recetas y protección del contenido emitido. |
| 012 | [012_sala_espera.sql](backend/src/data/migrations/012_sala_espera.sql) | Sala de espera, eventos y sincronización con citas. |
| 013 | [013_pagos_demostracion.sql](backend/src/data/migrations/013_pagos_demostracion.sql) | Pagos exclusivamente de demostración. |

No todas las migraciones crean tablas: varias amplían las existentes. Los archivos SQL didácticos de `database/sql-estudio/` son material de estudio, no reemplazan esta secuencia ni deben aplicarse sobre la base de la aplicación.

## Instalación local

1. Instalar PHP compatible con las características del proyecto; se recomienda PHP 8.5 para repetir el entorno validado. **No utilizar PHP 8.0 de XAMPP.** Activar `pdo_pgsql`, `mbstring`, `curl`, `session` y `ctype`.
2. Instalar PostgreSQL 18 y crear una base local independiente, por ejemplo `citas_medicas_pruebas`.
3. Desde la raíz del repositorio, copiar [backend/.env.example](backend/.env.example) y [frontend/.env.example](frontend/.env.example) **solo si no existe configuración local**:

   ```powershell
   if (!(Test-Path backend/.env)) { Copy-Item backend/.env.example backend/.env }
   if (!(Test-Path frontend/.env)) { Copy-Item frontend/.env.example frontend/.env }
   ```

4. Completar la conexión local y URLs de la sección siguiente. No copiar credenciales de producción ni guardar secretos en Git.
5. Instalar en orden el esquema **001–013**, siguiendo [instalación en Windows](docs/uso/instalacion-windows.md). Los actualizadores se ejecutan únicamente en una base local preparada; detenerse ante cualquier error.
6. El seeder inicial es solo para una base local nueva. Para cuentas ficticias adicionales, seguir [el seed de demostración](docs/uso/correcciones-agenda-demostracion.md); es idempotente y no modifica cuentas existentes.

Las migraciones están en `backend/src/data/migrations/` y se conservan todas, incluidas **009–013**. No usar `database/sql-estudio/` para instalar la aplicación. No repetir migraciones o seeds en una base existente sin revisar primero qué está instalado. Esta entrega no modifica Neon.

## Variables de entorno

Los `.env` reales son privados y están excluidos de Git. Se distribuyen solo los `.env.example`, sin claves. Las variables del proceso prevalecen sobre los archivos locales.

| Ubicación | Variables |
| --- | --- |
| Backend local | `APP_ENV=local`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_NAME` terminado en `_pruebas`, `DB_USER`, `DB_PASS`, `DB_SSLMODE=disable` para PostgreSQL local |
| URLs locales | Backend: `FRONTEND_URL=http://127.0.0.1:8080`. Frontend: `API_URL=http://127.0.0.1:8000/api`, `FRONTEND_URL=http://127.0.0.1:8080` |
| Sesión | Mismo `SESSION_COOKIE_NAME` en ambos servicios; `SESSION_SECURE=false` solo en HTTP local; `SESSION_IDLE_SECONDS` según configuración |
| Agenda | `CLINICA_HORA_APERTURA`, `CLINICA_HORA_CIERRE`. La duración nueva es fija en 30 minutos; `CITA_DURACION_MINUTOS` ya no la altera |
| Cuentas locales | `SEED_USERS_PASSWORD`; para el seed adicional, `DEMO_SEED_ENABLE=1` y `DEMO_SEED_PASSWORD`, solo en una base local de pruebas |
| Pagos | `DEMO_TARIFA_BOB`, importe académico definido externamente. Sin configuración no se preparan pagos; no existe tarifa oficial predeterminada |
| Recetas, opcionales | `CLINICA_NOMBRE` (por defecto, Clínica Vida Salud), `CLINICA_DIRECCION`, `CLINICA_TELEFONO` |
| Recuperación por correo | `MAIL_PROVIDER`, `MAIL_FROM`, `RESEND_API_KEY`; Resend requiere remitente verificado y configuración externa. Las pruebas no envían correo real |
| Cierre automático | `CITAS_AUTOMATICAS_HABILITADAS=true`, `CITAS_TOLERANCIA_MINUTOS=30`, `APP_ENV` y conexión explícita en el proceso de cron |

No habilitar `VIDASALUD_TEST_LOCAL` en producción: es exclusivo de pruebas aisladas. El cron omite `.env` mediante su mecanismo CLI, no mediante ese indicador.

Las variables de cuentas sintéticas son solo para seeds locales; las de correo y cron son necesarias únicamente al habilitar esos servicios. `DB_SSLMODE=disable` y `SESSION_SECURE=false` son ajustes de desarrollo local: en producción se requiere HTTPS, cookies seguras y SSL de base de datos según el proveedor. No copiar la configuración local a Render.

## Iniciar el proyecto

Desde la raíz, en dos terminales con PHP compatible:

```powershell
# Backend
& C:/php85/php.exe -S 127.0.0.1:8000 -t backend/public backend/src/server.php
```

```powershell
# Frontend
& C:/php85/php.exe -S 127.0.0.1:8080 -t frontend/public frontend/src/server.php
```

Abrir <http://127.0.0.1:8080>. No mezclar `localhost` y `127.0.0.1`. `php -S` es solo para desarrollo; no publicar la raíz del repositorio ni sus `.env`.

## Ejecutar pruebas

```powershell
# Validaciones sin conexión y sin leer .env
$env:VIDASALUD_TEST_LOCAL = '1'
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php

# Regresiones JavaScript sin red (Node.js)
node --experimental-vm-modules backend/tests/test_filtros_listados.mjs
node --experimental-vm-modules backend/tests/test_formularios.mjs
node --experimental-vm-modules backend/tests/test_cancelacion_pagos.mjs

# Suite PHP/HTTP completa: crea su propio PostgreSQL local desechable
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin" --navegador

# Interfaz de módulos nuevos en Edge, sin repetir todas las suites PHP
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --solo-modulos --solo-interfaz --navegador --pg-bin "C:/Program Files/PostgreSQL/18/bin"

git diff --check
```

`--navegador` requiere Node.js 22+ y Microsoft Edge; puede omitirse para pruebas PHP/HTTP. El modo completo fija su conexión a un clúster aislado y aplica las migraciones allí; no usa Neon ni `.env`. `--integracion` usa una base configurada explícitamente y no debe ejecutarse sin confirmar su aislamiento. [Comandos, resultados y límites](docs/uso/pruebas-php.md).

### Evidencia de la entrega

El [informe de entrega del 7 de noviembre de 2026](docs/entrega-final.md) registra 31 suites PHP aprobadas en la ejecución completa, 67 comprobaciones JavaScript sin red, 10 casos de configuración y recorridos HTTP/Edge sobre PostgreSQL local aislado. Son resultados de esa ejecución documentada, **no una nueva ejecución realizada al editar este README** ni una certificación de producción.

## Documentación y entrega

- [Índice de documentación](docs/README.md).
- [Arquitectura y defensa](docs/arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md), [árbol real](docs/refactorizacion/ESTRUCTURA-CARPETAS.md) y [contrato de API](docs/refactorizacion/ENDPOINTS.md).
- [Requisitos](docs/rup/requisitos.md), [casos de uso](docs/rup/casos-uso.md), [análisis/diseño](docs/rup/analisis-diseno.md) y [trazabilidad](docs/rup/trazabilidad.md).
- [Autenticación](docs/uso/usuarios-autenticacion.md), [recetas/sala/pagos](docs/uso/recetas-sala-pagos.md) y [cancelación/cierre automático](docs/uso/citas-vencidas-pagos.md).
- [Respaldo y recuperación](docs/uso/respaldo-recuperacion.md), [publicación segura](docs/uso/despliegue.md), [archivos SQL didácticos](database/sql-estudio/) e [informe de limpieza y verificación](docs/entrega-final.md).
- [AGENTS.md](AGENTS.md): instrucciones de mantenimiento para agentes; no es una dependencia del servidor.

## Seguridad y pendientes operativos

Conservar respaldos privados fuera del repositorio y secretos fuera de GitHub. No se incluyen datos reales de pacientes, credenciales productivas ni cuentas bancarias en la entrega. Los datos sintéticos de pruebas no son credenciales para producción.

Antes de una publicación autorizada: revisar el diff, respaldar/restaurar en un entorno aislado, verificar esquema y permisos, y realizar un smoke test por rol. No repetir 009/010 en Neon. El [Cron Job de vencidas](docs/uso/citas-vencidas-pagos.md#configuración-necesaria-en-render-pendiente-no-realizada) debe configurarse aparte; el código no lo activa por sí solo. Resend y la firma/validez profesional de las recetas requieren comprobación externa. El PDF actual usa WinAnsi y rechaza caracteres no representables sin alterar el original.

La preparación de esta entrega se realiza en `main`, sin cambios de lógica, migraciones, configuración de Render, commits, push o despliegues. Las pruebas locales no certifican el servicio productivo ni la restauración de respaldos.
