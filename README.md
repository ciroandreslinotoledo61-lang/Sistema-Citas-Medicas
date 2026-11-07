# Clínica Vida Salud

Sistema académico de gestión de citas médicas para Bolivia. Integra la atención desde la reserva hasta la consulta, el historial y las recetas, con acceso diferenciado por rol y auditoría de operaciones.

La instancia publicada y la entrega local pueden corresponder a versiones distintas hasta que el propietario autorice una actualización. Los pagos son exclusivamente de prueba: no realizan cargos reales ni solicitan datos bancarios.

## Acceso al sistema — Usuarios de prueba

Los siguientes correos ficticios están definidos en el [seeder inicial](backend/src/data/seeders/DatosInicialesSeeder.php). Se crean únicamente al cargar ese seed en una base local nueva; no se ha comprobado que existan en Render o Neon.

El seeder no contiene una contraseña fija: recibe la clave elegida para la instalación local mediante `SEED_USERS_PASSWORD`. Por seguridad, esa clave no se publica aquí.

### Administrador

- **Usuario o correo:** `administrador@vidasalud.test`
- **Contraseña:** Contraseña proporcionada por separado.

### Recepcionista

- **Usuario o correo:** `recepcionista@vidasalud.test`
- **Contraseña:** Contraseña proporcionada por separado.

### Médico

- **Usuario o correo:** `medico@vidasalud.test`
- **Contraseña:** Contraseña proporcionada por separado.

### Paciente

- **Usuario o correo:** `paciente@vidasalud.test`
- **Contraseña:** Contraseña proporcionada por separado.

Para utilizar estas cuentas, seguir la [instalación local](docs/uso/instalacion-windows.md). No repetir el seed sobre cuentas existentes. También se conserva un [seed adicional de demostración](docs/uso/correcciones-agenda-demostracion.md) para médicos y pacientes ficticios, con una clave externa independiente.

Para evaluar la aplicación publicada, solicitar al responsable los accesos autorizados por un canal privado. Esta lista no garantiza acceso al servicio publicado.

## Descripción y objetivos

Clínica Vida Salud coordina disponibilidad, reservas, recepción y atención médica. Cada usuario accede a las funciones y registros permitidos por su rol y su relación con la atención.

**Objetivo general:** gestionar el ciclo de una cita médica, desde la disponibilidad y reserva hasta la atención y su registro, preservando la integridad de los datos y la privacidad de cada paciente.

**Objetivos específicos:**

- Organizar pacientes, médicos, especialidades y horarios mediante identificadores únicos.
- Evitar reservas incompatibles y conservar la trazabilidad de cancelaciones, reprogramaciones y atención.
- Registrar consultas, tratamientos, historial y recetas con permisos por rol y relación asistencial.
- Facilitar el trabajo operativo de recepción, sala de espera, reportes y auditoría.
- Demostrar pagos sin cargos reales y documentar la instalación y verificación del sistema en un entorno aislado.

El alcance es académico y de gestión clínica básica; no constituye un sistema hospitalario integral ni una plataforma de cobro real.

## Tecnologías utilizadas

- **Backend:** PHP, PDO y PostgreSQL; organización en `business`, `data`, `presentation` y `shared`.
- **Frontend:** PHP, HTML, CSS propio y JavaScript modular sin framework.
- **Entorno de pruebas:** PHP 8.5.11 y PostgreSQL 18; Node.js y Microsoft Edge para pruebas opcionales de navegador.
- **Publicación:** Docker, Apache y Render; PostgreSQL alojado en Neon. El Dockerfile existente usa PHP 8.3-Apache y se conserva sin cambios.
- Sin dependencias Composer/npm para ejecutar la aplicación. Node.js se utiliza únicamente para determinadas pruebas.

## Arquitectura del proyecto

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

### Documentación de apoyo

- [Índice de documentación](docs/README.md).
- [Arquitectura y defensa](docs/arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md), [árbol real](docs/refactorizacion/ESTRUCTURA-CARPETAS.md) y [contrato de API](docs/refactorizacion/ENDPOINTS.md).
- [Requisitos](docs/rup/requisitos.md), [casos de uso](docs/rup/casos-uso.md), [análisis/diseño](docs/rup/analisis-diseno.md) y [trazabilidad](docs/rup/trazabilidad.md).
- [Autenticación](docs/uso/usuarios-autenticacion.md), [recetas/sala/pagos](docs/uso/recetas-sala-pagos.md) y [cancelación/cierre automático](docs/uso/citas-vencidas-pagos.md).
- [Respaldo y recuperación](docs/uso/respaldo-recuperacion.md), [publicación segura](docs/uso/despliegue.md), [archivos SQL didácticos](database/sql-estudio/) e [informe de limpieza y verificación](docs/entrega-final.md).
- [AGENTS.md](AGENTS.md): instrucciones de mantenimiento para agentes; no es una dependencia del servidor.

## Roles y sus funcionalidades

### Administrador

- Gestiona usuarios, médicos, especialidades y pacientes.
- Programa y consulta bloques horarios; gestiona citas, reportes y bitácora.
- Consulta información operativa de sala y pagos de prueba.
- No accede al contenido clínico.

### Recepcionista

- Gestiona pacientes, disponibilidad y operaciones administrativas de citas.
- Registra la llegada del paciente en sala de espera.
- Consulta pagos de prueba y sus reportes.
- No administra bloques horarios ni accede al historial clínico o a recetas.

### Médico

- Consulta sus citas y horarios propios.
- Inicia y finaliza la atención de sus pacientes asignados.
- Registra consultas, tratamientos y recetas de sus atenciones.
- Consulta historial únicamente con una relación asistencial autorizada.
- No programa bloques ni reprograma citas.

### Paciente

- Consulta su perfil, citas, historial y recetas propios.
- Reserva y cancela sus citas según las reglas del sistema.
- Crea, confirma o cancela intentos de pago de prueba para sus propias citas.

Los detalles están en el [manual por rol](docs/uso/manual-roles.md) y en las guías de módulos. `PermisosSistema.php` y los servicios son la fuente de verdad del acceso.

## Módulos implementados

### Pacientes, médicos y especialidades

- Registro de pacientes y creación de su historia clínica.
- Gestión de perfiles médicos y especialidades.
- Identificación por IDs; los homónimos no equivalen a duplicados.

### Agenda y citas

- Horarios por día o período, disponibilidad, filtros por médico/fecha y paginación.
- Etiquetas de médicos `(#1)`, `(#2)`, etc.; los nombres no sustituyen a los identificadores.
- Nuevas citas de **30 minutos**, validación de cruces y protección ante reservas concurrentes.
- Una cita programada activa por paciente, con las excepciones históricas previstas en SQL.
- Cancelación con motivo/historial, reprogramación atómica y registro de asistencia.
- Una consulta iniciada o finalizada no puede cancelarse.

### Consultas, tratamientos e historial

- Registro clínico de la atención y sus tratamientos.
- Privacidad por identidad y relación asistencial autorizada.
- Control de versiones para proteger cambios concurrentes.

### Recetas médicas

- Emisión y descarga privada en PDF.
- Contenido emitido inmutable, anulación motivada y correcciones enlazadas.
- Sin firma digital certificada.

### Sala de espera

- Registro de llegada, espera, consulta y finalización de atención.
- Eventos históricos y alcance por médico asignado.
- Información operativa sin contenido clínico en recepción.

### Pagos de prueba

- Opciones QR/PayPal y estados Pendiente/Pagado/Cancelado.
- Referencias y reportes separados de ingresos reales.
- Aclaración visible al confirmar; sin cargos ni proveedores de cobro reales.

### Autenticación, seguridad y auditoría

- Contraseñas temporales, cambio obligatorio y revocación de sesiones.
- Protección CSRF, permisos en backend y bitácora de operaciones.
- Recuperación por enlace de uso único y vencimiento, con envío de correo configurable.

### Revisión de citas vencidas

- Tarea CLI con tolerancia de 30 minutos por defecto, bloqueos y transacciones.
- Revisa citas sin llegada ni consulta; los presentes quedan para revisión y los estados finales se conservan.
- Requiere un Cron Job independiente para ejecutarse automáticamente en producción.

## Base de datos PostgreSQL

Las [migraciones 001–013](backend/src/data/migrations/) definen **18 tablas funcionales**. El inventario siguiente se obtiene de sus sentencias `CREATE TABLE`, no de una conexión a Neon:

### Identidad y perfiles

1. **`roles`:** catálogo de los cuatro roles del sistema.
2. **`usuarios`:** identidad, acceso y estado de las cuentas.
3. **`pacientes`:** perfil y datos administrativos del paciente.
4. **`historias_clinicas`:** historia asociada al paciente.
5. **`especialidades`:** catálogo de especialidades médicas.
6. **`medicos`:** perfil profesional, usuario y especialidad.

### Agenda y atención

7. **`horarios_medicos`:** bloques de disponibilidad por médico.
8. **`turnos`:** intervalos de atención generados desde los bloques.
9. **`citas`:** reserva, estado y trazabilidad de cambios.
10. **`consultas`:** registro clínico de la atención de una cita.
11. **`tratamientos`:** tratamientos vinculados a la consulta.

### Auditoría y recuperación de acceso

12. **`bitacora_sistema`:** auditoría de operaciones sin copiar contenido clínico ni secretos.
13. **`intentos_autenticacion`:** control persistente de intentos y límites de acceso.
14. **`recuperaciones_contrasena`:** hash de tokens de recuperación, vencimiento y uso único.

### Recetas, sala y pagos

15. **`recetas_medicas`:** emisión, medicamentos, anulación y enlace de corrección.
16. **`sala_espera`:** estado operativo y versión de la atención por cita.
17. **`eventos_sala_espera`:** historial de eventos de sala.
18. **`pagos_demostracion`:** intentos simulados en BOB, referencias y estados.

**Relaciones principales:** usuarios con roles y perfiles; pacientes con historias y citas; médicos con especialidades y bloques; bloques con turnos; citas con consultas; consultas con tratamientos y recetas. Sala, eventos y pagos se vinculan a la cita. Las relaciones utilizan claves e identificadores, no nombres.

La integridad se apoya en claves primarias y foráneas, restricciones, índices y triggers. Las operaciones sensibles emplean SQL parametrizado, transacciones y bloqueos; determinados cambios usan control de versiones. Los importes de pagos se almacenan en centavos enteros BOB, con referencias `DEMO-` y estados finales inmutables.

## Migraciones 001–013

### Esquema inicial y agenda

- **001 — Esquema inicial de 11 tablas:** [001_crear_tablas_sistema.sql](backend/src/data/migrations/001_crear_tablas_sistema.sql).
- **002 — Unicidad de especialidades y matrículas:** [002_unicidad_especialidades_medicos.sql](backend/src/data/migrations/002_unicidad_especialidades_medicos.sql).
- **003 — Responsabilidades y trazabilidad de citas:** [003_responsabilidades_citas.sql](backend/src/data/migrations/003_responsabilidades_citas.sql).
- **004 — Agenda y perfiles:** [004_agenda_30_minutos_y_perfiles.sql](backend/src/data/migrations/004_agenda_30_minutos_y_perfiles.sql).
- **005 — Programación y protección de cita activa:** [005_programacion_y_cita_activa.sql](backend/src/data/migrations/005_programacion_y_cita_activa.sql).

### Atención, auditoría y seguridad

- **006 — Normalización del estado programada:** [006_estado_programada.sql](backend/src/data/migrations/006_estado_programada.sql).
- **007 — Bitácora de auditoría:** [007_bitacora_sistema.sql](backend/src/data/migrations/007_bitacora_sistema.sql).
- **008 — Ampliación de consultas e historial clínico:** [008_consultas_historial_clinico.sql](backend/src/data/migrations/008_consultas_historial_clinico.sql).
- **009 — Seguridad de credenciales e intentos:** [009_seguridad_credenciales.sql](backend/src/data/migrations/009_seguridad_credenciales.sql).
- **010 — Recuperación de contraseña:** [010_recuperacion_contrasena.sql](backend/src/data/migrations/010_recuperacion_contrasena.sql).

### Módulos de recetas, sala y pagos

- **011 — Recetas y protección del contenido emitido:** [011_recetas_medicas.sql](backend/src/data/migrations/011_recetas_medicas.sql).
- **012 — Sala de espera, eventos y sincronización con citas:** [012_sala_espera.sql](backend/src/data/migrations/012_sala_espera.sql).
- **013 — Pagos exclusivamente de demostración:** [013_pagos_demostracion.sql](backend/src/data/migrations/013_pagos_demostracion.sql).

No todas las migraciones crean tablas: varias amplían las existentes. Los archivos SQL didácticos de `database/sql-estudio/` son material de estudio, no reemplazan esta secuencia ni deben aplicarse sobre la base de la aplicación.

<a id="instalación-local"></a>

## Instalación y configuración

### Preparar el entorno local

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

### Variables de entorno

Los `.env` reales son privados y están excluidos de Git. Se distribuyen solo los `.env.example`, sin claves. Las variables del proceso prevalecen sobre los archivos locales.

#### Conexión del backend local

- `APP_ENV=local`.
- `DB_HOST=127.0.0.1` y `DB_PORT=5432`, ajustando el puerto al servidor local.
- `DB_NAME`: base independiente terminada en `_pruebas`.
- `DB_USER` y `DB_PASS`: credenciales locales introducidas fuera del repositorio.
- `DB_SSLMODE=disable`: únicamente para el entorno PostgreSQL local descrito.

#### URLs y sesiones

- Backend: `FRONTEND_URL=http://127.0.0.1:8080`.
- Frontend: `API_URL=http://127.0.0.1:8000/api` y `FRONTEND_URL=http://127.0.0.1:8080`.
- `SESSION_COOKIE_NAME`: mismo nombre en ambos servicios.
- `SESSION_SECURE=false`: solo para HTTP local.
- `SESSION_IDLE_SECONDS`: tiempo de inactividad según la configuración elegida.

#### Agenda y cuentas sintéticas

- `CLINICA_HORA_APERTURA` y `CLINICA_HORA_CIERRE`: límites del horario clínico.
- La duración nueva es fija en 30 minutos; `CITA_DURACION_MINUTOS` ya no la altera.
- `SEED_USERS_PASSWORD`: clave externa para las cuentas del seed inicial local.
- `DEMO_SEED_ENABLE=1` y `DEMO_SEED_PASSWORD`: habilitación y clave del seed adicional, solo en una base local de pruebas.

#### Pagos y datos de recetas

- `DEMO_TARIFA_BOB`: importe académico definido externamente. Sin él no se preparan pagos; no existe tarifa oficial predeterminada.
- `CLINICA_NOMBRE`: opcional; por defecto, Clínica Vida Salud.
- `CLINICA_DIRECCION` y `CLINICA_TELEFONO`: datos institucionales opcionales para recetas.

#### Correo y cierre automático

- `MAIL_PROVIDER`, `MAIL_FROM` y `RESEND_API_KEY`: configuración externa de recuperación por correo; las pruebas no envían correo real.
- `CITAS_AUTOMATICAS_HABILITADAS=true`: habilitación explícita del cierre automático.
- `CITAS_TOLERANCIA_MINUTOS=30`: tolerancia predeterminada del cierre.
- El proceso de cron requiere `APP_ENV` y conexión explícita mediante variables de proceso.

No habilitar `VIDASALUD_TEST_LOCAL` en producción: es exclusivo de pruebas aisladas. El cron omite `.env` mediante su mecanismo CLI, no mediante ese indicador.

Las variables de cuentas sintéticas son solo para seeds locales; las de correo y cron son necesarias únicamente al habilitar esos servicios. `DB_SSLMODE=disable` y `SESSION_SECURE=false` son ajustes de desarrollo local: en producción se requiere HTTPS, cookies seguras y SSL de base de datos según el proveedor. No copiar la configuración local a Render.

## Ejecución del sistema

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

## Pruebas realizadas

### Evidencia de la entrega

El [informe de entrega del 7 de noviembre de 2026](docs/entrega-final.md) registra:

- 31 suites PHP aprobadas en la ejecución completa.
- 67 comprobaciones JavaScript sin red.
- 10 casos de configuración.
- Recorridos HTTP y Edge sobre PostgreSQL local aislado.

Son resultados de esa ejecución documentada, **no una nueva ejecución realizada al editar este README** ni una certificación de producción.

### Cómo repetir las pruebas

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

## Configuración pendiente de producción

Que una funcionalidad esté implementada no significa que sus servicios externos estén activados en la instancia publicada. Esta revisión documental no configura servicios productivos ni verifica su disponibilidad en Render.

### Cron Job de citas vencidas

- **Disponible:** script CLI con tolerancia configurable, bloqueos, transacciones y auditoría. Sin `--aplicar`, muestra una vista previa sin conexión.
- **Pendiente:** crear y habilitar un Cron Job independiente con variables de proceso y conexión autorizada.
- No se ejecuta desde visitas web ni se activa por publicar el código.
- Consultar la [configuración del Cron Job](docs/uso/citas-vencidas-pagos.md#configuración-necesaria-en-render-pendiente-no-realizada).

### Correos de recuperación

- **Disponible:** solicitud, enlace de uso único con vencimiento, validaciones, revocación de sesiones e integración con Resend.
- **Pendiente:** configurar `MAIL_PROVIDER=resend`, `MAIL_FROM`, `RESEND_API_KEY`, remitente verificado y `FRONTEND_URL` HTTPS.
- El envío real requiere configuración externa; las pruebas usan correo simulado.
- Consultar la [guía de recuperación](docs/uso/actualizacion-privacidad-recuperacion.md).

### Pagos y recetas

- Definir `DEMO_TARIFA_BOB` para habilitar los intentos de pago de prueba.
- No incorporar cuentas, claves o APIs de cobro QR/PayPal reales: el módulo sigue siendo exclusivamente demostrativo.
- Revisar datos institucionales de recetas. El PDF no incluye firma digital certificada ni acredita por sí solo validez profesional.
- El PDF actual usa WinAnsi y rechaza caracteres no representables sin alterar el original.

### Seguridad y revisión antes de publicar

Conservar respaldos privados fuera del repositorio y secretos fuera de GitHub. No se incluyen datos reales de pacientes, credenciales productivas ni cuentas bancarias en la entrega. Los datos sintéticos de pruebas no son credenciales para producción.

Antes de una publicación autorizada:

1. Revisar el diff y los permisos.
2. Ensayar respaldo y restauración en un entorno aislado.
3. Verificar el esquema instalado; no repetir 009/010 en Neon.
4. Realizar una comprobación básica por rol.
5. Validar por separado el Cron Job y el correo después de su configuración autorizada.

La preparación de esta entrega se realiza en `main`, sin cambios de lógica, migraciones, configuración de Render, commits, push o despliegues. Las pruebas locales no certifican el servicio productivo ni la restauración de respaldos.

## Enlace de GitHub y Render

- **Repositorio GitHub:** [ClinicaVidaSalud](https://github.com/ciroandreslinotoledo61-lang/ClinicaVidaSalud).
- **Aplicación publicada en Render:** [Clínica Vida Salud](https://clinica-vida-salud-bavz.onrender.com).

El enlace de GitHub corresponde al remoto `origin` configurado en el repositorio. La URL de Render fue confirmada por el propietario. No se ha verificado en esta revisión la disponibilidad de estos servicios ni que la versión publicada coincida con la entrega local.
