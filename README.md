# Clínica Vida Salud

Sistema académico de gestión de citas médicas para Bolivia. Integra la atención desde la reserva hasta la consulta, el historial y las recetas, con acceso diferenciado por rol y auditoría de operaciones.

**Aplicación publicada:** [clinica-vida-salud-bavz.onrender.com](https://clinica-vida-salud-bavz.onrender.com).

La instancia publicada y la entrega local pueden corresponder a versiones distintas hasta que el propietario autorice una actualización. Los pagos son exclusivamente de prueba: no realizan cargos reales ni solicitan datos bancarios.

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
| Administrador | Usuarios, médicos, especialidades, pacientes, horarios, citas, reportes y bitácora. Consulta operativa de sala y pagos de prueba. Sin acceso clínico adicional. |
| Recepcionista | Pacientes, disponibilidad y gestión administrativa de citas; llegada en sala; consulta y reportes de pagos. Sin acceso a diagnósticos o recetas. |
| Médico | Citas y horarios asignados; inicio/finalización de atención; consultas, tratamientos y recetas de sus atenciones; historial autorizado por relación asistencial. |
| Paciente | Perfil, citas, historial y recetas propios; reserva y cancelación según reglas; pagos de prueba para sus propias citas. |

Los detalles están en el [manual por rol](docs/uso/manual-roles.md) y en las guías de módulos. `PermisosSistema.php` y los servicios son la fuente de verdad del acceso.

## Funcionalidades principales

- Registro de pacientes e historia clínica; gestión de médicos y especialidades.
- Horarios por día o período, disponibilidad, filtros por médico/fecha y paginación. Se mantienen los identificadores visibles `(#ID)`.
- Nuevas citas de **30 minutos**, validación de cruces, transacciones y protección ante reservas concurrentes. Una cita programada activa por paciente, con las excepciones históricas previstas en SQL.
- Cancelación con motivo/historial, reprogramación atómica y registro de asistencia. Una consulta iniciada o finalizada no puede cancelarse.
- Consultas, tratamientos e historial clínico con privacidad por identidad y control de versiones.
- **Recetas médicas:** PDF privado, contenido emitido inmutable, anulación motivada y correcciones enlazadas. No incluyen firma digital certificada.
- **Sala de espera:** llegada, espera, consulta y atención; eventos históricos y alcance por médico, sin contenido clínico en recepción.
- **Pagos de prueba:** QR/PayPal, estados Pendiente/Pagado/Cancelado, referencias y reportes separados de ingresos reales. Aclaración visible al confirmar, sin proveedores reales.
- Contraseñas temporales, cambio obligatorio, revocación de sesiones, CSRF y recuperación por correo configurable.
- Tarea CLI para revisar citas vencidas sin llegada/consulta tras una tolerancia de 30 minutos por defecto. Requiere un Cron Job independiente; los presentes quedan para revisión y los estados finales se conservan.

## Instalación local

1. Instalar PHP compatible con las características del proyecto; se recomienda PHP 8.5 para repetir el entorno validado. **No utilizar PHP 8.0 de XAMPP.** Activar `pdo_pgsql`, `mbstring`, `curl`, `session` y `ctype`.
2. Instalar PostgreSQL 18 y crear una base local independiente, por ejemplo `citas_medicas_pruebas`.
3. Copiar los archivos de ejemplo **solo si no existe configuración local**:

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
| Recetas | `CLINICA_NOMBRE` y, opcionalmente, `CLINICA_DIRECCION`, `CLINICA_TELEFONO` |
| Recuperación por correo | `MAIL_PROVIDER`, `MAIL_FROM`, `RESEND_API_KEY`; Resend requiere remitente verificado y configuración externa. Las pruebas no envían correo real |
| Cierre automático | `CITAS_AUTOMATICAS_HABILITADAS=true`, `CITAS_TOLERANCIA_MINUTOS=30`, `APP_ENV` y conexión explícita en el proceso de cron |

No habilitar `VIDASALUD_TEST_LOCAL` en producción: es exclusivo de pruebas aisladas. El cron omite `.env` mediante su mecanismo CLI, no mediante ese indicador.

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

## Documentación y entrega

- [Índice de documentación](docs/README.md).
- [Arquitectura y defensa](docs/arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md), [árbol real](docs/refactorizacion/ESTRUCTURA-CARPETAS.md) y [contrato de API](docs/refactorizacion/ENDPOINTS.md).
- [Requisitos](docs/rup/requisitos.md), [casos de uso](docs/rup/casos-uso.md), [análisis/diseño](docs/rup/analisis-diseno.md) y [trazabilidad](docs/rup/trazabilidad.md).
- [Autenticación](docs/uso/usuarios-autenticacion.md), [recetas/sala/pagos](docs/uso/recetas-sala-pagos.md) y [cancelación/cierre automático](docs/uso/citas-vencidas-pagos.md).
- [Respaldo y recuperación](docs/uso/respaldo-recuperacion.md), [publicación segura](docs/uso/despliegue.md), [SQL didáctico](database/sql-estudio/README.md) e [informe de limpieza y verificación](docs/entrega-final.md).
- [AGENTS.md](AGENTS.md): instrucciones de mantenimiento para agentes; no es una dependencia del servidor.

## Seguridad y pendientes operativos

Conservar respaldos privados fuera del repositorio y secretos fuera de GitHub. No se incluyen datos reales de pacientes, credenciales productivas ni cuentas bancarias en la entrega. Los datos sintéticos de pruebas no son credenciales para producción.

Antes de una publicación autorizada: revisar el diff, respaldar/restaurar en un entorno aislado, verificar esquema y permisos, y realizar un smoke test por rol. No repetir 009/010 en Neon. El [Cron Job de vencidas](docs/uso/citas-vencidas-pagos.md#configuración-necesaria-en-render-pendiente-no-realizada) debe configurarse aparte; el código no lo activa por sí solo. Resend y la firma/validez profesional de las recetas requieren comprobación externa. El PDF actual usa WinAnsi y rechaza caracteres no representables sin alterar el original.

La preparación de esta entrega se realiza en `main`, sin cambios de lógica, migraciones, configuración de Render, commits, push o despliegues. Las pruebas locales no certifican el servicio productivo ni la restauración de respaldos.
