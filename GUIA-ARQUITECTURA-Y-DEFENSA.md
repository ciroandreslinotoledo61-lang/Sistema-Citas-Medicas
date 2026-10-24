# Clínica Vida Salud: arquitectura y guía para la defensa

**Entrega: 24 de octubre de 2026.** Refactorización del ZIP `Sistema-Citas-Medicas-prueba-astra-mensual.zip`, usando el backend de CADEFAR como referencia. Se conserva PHP, JavaScript, PostgreSQL, la interfaz y el contrato HTTP existente.

La lógica principal está en **Services**. Las peticiones llegan primero a Routes y Controllers; después el servicio coordina validaciones, reglas, consultas y transacciones. Los cambios se aplicaron al código, no solamente a la documentación.

## 1. Qué cambió

- Las 48 rutas están declaradas por módulos. Las rutas ya no construyen servicios, abren PostgreSQL ni envían respuestas.
- `EnrutadorApi` interpreta HTTP, ejecuta los middlewares y llama al controlador. `FabricaControladores` construye sus dependencias y abre la conexión únicamente cuando hace falta.
- `HorarioMedicoService` decide cómo generar intervalos. `HorarioMedicoRepository` guarda el bloque y `TurnoRepository` guarda o consulta los turnos.
- `DisponibilidadService` y `DisponibilidadController` separan la consulta de oferta de la administración de horarios.
- Los servicios deciden el alcance del paciente o médico autenticado. Los repositorios reciben esos filtros y ejecutan SQL, sin decidir permisos según un rol.
- `Transaccion` elimina la repetición de begin/commit/rollback. **El servicio sigue decidiendo qué operaciones pertenecen a una transacción y qué aislamiento necesitan.**
- `HorarioMedicoValidator` y `CitaValidator` separan la validación de agenda. La fachada `AgendaValidator` conserva compatibilidad con las pruebas y llamadas anteriores.
- `SesionUsuario` encapsula `$_SESSION`; el servicio de autenticación mantiene las reglas de intentos, credenciales, estado y versión de sesión.
- Se uniformó el formato del backend, se separaron instrucciones y se ordenaron los métodos públicos antes de sus auxiliares privados.

Los 65 archivos del frontend son idénticos al original por SHA-256. Las cinco migraciones SQL también permanecen idénticas. No se requiere una migración adicional para esta refactorización. No se eliminó ningún archivo original.

La comparación detallada está en [COMPARACION-CADEFAR.md](docs/refactorizacion/COMPARACION-CADEFAR.md). El inventario exacto está en [ARCHIVOS-MODIFICADOS.md](docs/refactorizacion/ARCHIVOS-MODIFICADOS.md).

## 2. Estructura definitiva

| Carpeta | Contenido |
| --- | --- |
| `backend/public/` | Entrada pública HTTP: `index.php` |
| `backend/src/presentation/routes/` | `RutasApi`, `RutasAgenda` y rutas de Sistema, Autenticacion, Usuarios, Pacientes, Especialidades, Medicos, Horarios, Disponibilidad, Citas y Reportes |
| `backend/src/presentation/http/` | `RutaHttp`, `EnrutadorApi`, `FabricaControladores` |
| `backend/src/presentation/controllers/` | Un controlador por módulo; controladores técnicos de estado y token CSRF |
| `backend/src/presentation/middlewares/` | Origen, sesión, rol y CSRF |
| `backend/src/business/services/` | Autenticacion, Usuario, Paciente, Especialidad, Medico, HorarioMedico, Disponibilidad, Cita y Reporte |
| `backend/src/business/validators/` | Validadores por módulo y formatos compartidos |
| `backend/src/data/repositories/` | SQL de Usuario, Rol, Paciente, HistoriaClinica, Especialidad, Medico, HorarioMedico, Turno, Cita y Reporte |
| `backend/src/data/models/` | Entidades y representación pública de sus datos |
| `backend/src/data/config/` | `ConexionPostgresql` y `Transaccion` |
| `backend/src/data/migrations/` | Migraciones originales 001–005 |
| `backend/src/data/seeders/` | Datos iniciales para una base vacía |
| `backend/src/shared/` | Configuración, roles, permisos, errores, sesión y utilidades HTTP |
| `backend/scripts/` | Verificación del entorno y scripts de migración/carga |
| `backend/tests/` | Pruebas originales, arquitectura, atomicidad y ejecutor de pruebas |
| `frontend/src/` | `api`, `app`, `components`, `features`, `layouts`, `routes`, `styles` |
| `docs/ai/` | Documentación previa, contratos y reglas del sistema |
| `docs/refactorizacion/` | Comparación, cambios, estructura, endpoints y verificación de esta entrega |

El listado completo de carpetas y archivos está en [ESTRUCTURA-CARPETAS.md](docs/refactorizacion/ESTRUCTURA-CARPETAS.md).

## 3. Cómo trabajan las capas

```mermaid
flowchart TD
    R["Routes: método y endpoint"] --> C["Controller: petición y respuesta"]
    C --> S["Service: caso de uso"]
    S --> V["Validator: datos de entrada"]
    S --> T["Transaccion: operación atómica"]
    S --> P["Repository: consultas SQL"]
    P --> B["PostgreSQL: datos e integridad"]
    P --> M["Model: entidad y datos públicos"]
```

**Routes** indica qué controlador y método corresponden a una URL. `RutaHttp::roles` usa `null` para una ruta pública, `[]` para cualquier usuario autenticado y una lista para restringir roles. `argumentos` indica qué identificadores o usuario autenticado entrega el enrutador al controlador. Esto es configuración HTTP, no lógica clínica.

**Middlewares** comprueba origen, token CSRF, sesión y acceso general por rol. La autorización de recursos concretos, como comprobar que una cita pertenece al paciente, permanece en Services. Ocultar un botón no sustituye estas verificaciones.

**Controllers** obtiene JSON, parámetros o filtros, llama al servicio y produce la respuesta. No ejecuta SQL, no genera horarios ni decide transiciones de una cita.

**Services** contiene los casos de uso: registrar un paciente completo, crear horarios sin cruces, reservar un turno, cancelar o reprogramar. Llama a Validators y Repositories. Sus métodos reciben datos y entidades, no `$_GET`, `$_POST`, `$_SERVER` ni respuestas HTTP.

**Validators** comprueba tipos, formatos, campos obligatorios, fechas y condiciones de la entrada. La existencia o estado de registros en PostgreSQL se consulta desde el servicio mediante repositorios. Por ejemplo, un identificador válido no significa que el médico exista o esté activo.

**Repositories** contiene consultas preparadas, parámetros, bloqueos `FOR UPDATE` y transformación de registros. Las condiciones SQL de disponibilidad filtran lo solicitado por el caso de uso; el repositorio no decide quién está autorizado ni calcula los intervalos de la jornada.

**Models** representa entidades y sus datos públicos. No es un ORM: PHP usa PDO directamente. No se necesita crear un modelo por cada consulta de reporte o una clase adicional para cada lista de turnos.

**Transaccion** es infraestructura. Abre la transacción, aplica el aislamiento que solicita el servicio, ejecuta su operación y confirma o revierte. No decide si un paciente puede reservar. Todas las consultas de una operación usan el mismo objeto PDO.

## 4. Dónde está cada módulo

Todos los nombres de esta tabla están dentro de `backend/src/`.

| Módulo | Routes / Controller | Service y Validator | Repositories principales |
| --- | --- | --- | --- |
| Autenticación | `RutasAutenticacion`, `AutenticacionController`; token en `RutasSistema` / `TokenCsrfController` | `AutenticacionService`, `UsuarioValidator` | `UsuarioRepository` |
| Usuarios y roles | `RutasUsuarios`, `UsuarioController` | `UsuarioService`, `UsuarioValidator` | `UsuarioRepository`, `RolRepository` |
| Pacientes | `RutasPacientes`, `PacienteController`; registro público en `RutasAutenticacion` | `PacienteService`, `PacienteValidator` | Paciente, Usuario, Rol, HistoriaClinica |
| Médicos | `RutasMedicos`, `MedicoController` | `MedicoService`, `MedicoValidator` | Medico, Usuario, Especialidad, Rol, Cita |
| Especialidades | `RutasEspecialidades`, `EspecialidadController` | `EspecialidadService`, `EspecialidadValidator` | `EspecialidadRepository` |
| Programación | `RutasHorarios`, `HorarioMedicoController` | `HorarioMedicoService`, `HorarioMedicoValidator` | HorarioMedico, Turno, Medico, Especialidad |
| Disponibilidad | `RutasDisponibilidad`, `DisponibilidadController` | `DisponibilidadService`, `ValidarCamposFormulario` | Medico, Turno |
| Citas | `RutasCitas`, `CitaController` | `CitaService`, `CitaValidator`, validadores comunes | Cita, Turno, Paciente, Medico, Especialidad |
| Reportes | `RutasReportes`, `ReporteController` | `ReporteService`, `ReporteValidator` | Reporte, Medico |
| Historia clínica | Apertura dentro del registro del paciente | `PacienteService::registrarPacienteConHistoriaClinica` | `HistoriaClinicaRepository` y modelo `HistoriaClinica` |
| Administración / Recepción | Utilizan los módulos según permisos | `RolesSistema`, `PermisosSistema`, middleware y reglas del servicio | No necesitan un repositorio general de administración |

El sistema recibido **no implementaba el registro completo de consultas, diagnósticos y tratamientos**. Las tablas y la apertura de historia clínica existen; esos casos de uso siguen pendientes. Marcar una cita como atendida no crea un diagnóstico. Tampoco se agregó un visor general de bitácora ni una corrección de estados finales.

## 5. Registro de paciente: recorrido exacto

### Paso 1. Pantalla y envío

La vista es `frontend/src/features/pacientes/formulario-registro.php`. Incluye los campos compartidos de usuario, datos personales y contacto. El evento `submit` está en `frontend/src/features/pacientes/registrar-paciente.js`.

Ese JavaScript determina el contexto:

| Contexto | Función JavaScript | Archivo | Endpoint |
| --- | --- | --- | --- |
| Registro público | `registrarCuentaPaciente(datosPaciente)` | `frontend/src/features/auth/autenticacion-api.js` | `POST /api/autenticacion/registro-paciente` |
| Administración / Recepción | `registrarPacienteDesdeRecepcion(datosPaciente)` | `frontend/src/features/pacientes/pacientes-api.js` | `POST /api/pacientes` |

Ambas funciones usan `solicitarApi()` de `frontend/src/api/cliente-api.js`. Obtiene el token de `/api/autenticacion/token` y envía JSON con cookies y `X-CSRF-Token`.

Ejemplo de datos de prueba —la contraseña es ilustrativa—:

```json
{
  "nombresUsuario": "Ana",
  "apellidosUsuario": "Perez",
  "correoUsuario": "ana.ejemplo@example.test",
  "contrasenaUsuario": "EjemploSoloPrueba2026!",
  "confirmacionContrasena": "EjemploSoloPrueba2026!",
  "cedulaIdentidad": "1234567",
  "fechaNacimiento": "2000-01-15",
  "sexoPaciente": "femenino",
  "telefonoPaciente": "70000000",
  "direccionPaciente": "Santa Cruz",
  "nombreContactoEmergencia": "",
  "telefonoContactoEmergencia": ""
}
```

### Paso 2. Entrada HTTP y seguridad

`backend/public/index.php` carga `backend/src/app.php`. `bootstrap.php` carga las clases y configuración, y fija `America/La_Paz`. `app.php` verifica origen, inicia sesión segura y exige CSRF para escrituras. Incluso el registro público requiere el token del formulario.

`presentation/http/EnrutadorApi.php::atenderSolicitud()` consulta las definiciones de rutas. El registro público está en `presentation/routes/RutasAutenticacion.php::definir()`. El registro interno está en `presentation/routes/RutasPacientes.php::definir()` y exige Administrador o Recepcionista.

`FabricaControladores::obtener()` construye `PacienteController` con `PacienteService`, usando la conexión de `ConexionPostgresql::obtenerConexionPostgresql()`.

### Paso 3. Controlador

Archivo: `backend/src/presentation/controllers/PacienteController.php`.

Método: **`registrarPacienteConHistoriaClinica()`**.

Obtiene el cuerpo con `SolicitudHttp::obtenerCuerpoJson()`, lo entrega al servicio y devuelve HTTP 201. El controlador no contiene ningún `INSERT` ni abre una transacción.

### Paso 4. Validación

Archivo: `backend/src/business/validators/PacienteValidator.php`.

`validarDatosRegistroPaciente()` utiliza `validarDatosActualizacionPaciente()` y la validación de contraseña. También intervienen:

- `UsuarioValidator::validarDatosActualizacionUsuario()`: nombres, apellidos y correo.
- `ValidarCamposFormulario::validarCorreoUsuario()`: formato y normalización del correo.
- Validación de CI: normaliza a mayúsculas y comprueba el formato.
- Validación de nacimiento: fecha real, no futura y dentro de los últimos 120 años.
- `PacienteValidator::validarDatosContactoPaciente()`: teléfono, dirección y contacto de emergencia. Nombre y teléfono de emergencia deben completarse juntos o quedar ambos vacíos.
- `ValidarCamposFormulario::validarContrasenaRegistro()`: contraseña y confirmación.

No se incorpora un selector de país, departamento o ciudad.

### Paso 5. Servicio y transacción

Archivo: `backend/src/business/services/PacienteService.php`.

Método: **`registrarPacienteConHistoriaClinica(array $datos)`**.

Primero valida y genera el hash con `password_hash(..., PASSWORD_DEFAULT)`. Después llama a `Transaccion::ejecutar()` y coordina, en este orden:

1. Obtener el rol Paciente.
2. Insertar la cuenta de usuario con ese rol fijo.
3. Insertar el paciente asociado a la cuenta.
4. Crear su historia clínica.
5. Leer el paciente completo y devolver su modelo.

Si se enviara `codigoRol: "administrador"`, no se usa para asignar el rol. El servicio siempre solicita `RolesSistema::PACIENTE`.

### Paso 6. Consultas SQL que intervienen

Son consultas de los archivos reales, con parámetros enlazados por PDO.

**1. `data/repositories/RolRepository.php::obtenerRolPorCodigo()`**

```sql
SELECT id_rol, codigo_rol, nombre_rol
FROM roles
WHERE codigo_rol = :codigoRol;
```

En este caso, `:codigoRol` vale `paciente`. Si el rol no está configurado, el servicio devuelve un error 409.

**2. `data/repositories/UsuarioRepository.php::registrarUsuario()`**

```sql
INSERT INTO usuarios
    (id_rol, nombres_usuario, apellidos_usuario, correo_usuario, contrasena_hash)
VALUES
    (:idRol, :nombresUsuario, :apellidosUsuario, :correoUsuario, :contrasenaHash)
RETURNING id_usuario;
```

El ID se genera en PostgreSQL. La contraseña se guarda como hash; nunca se devuelve en la respuesta pública.

**3. `data/repositories/PacienteRepository.php::registrarPaciente()`**

```sql
INSERT INTO pacientes
    (id_usuario, sexo_paciente, cedula_identidad, fecha_nacimiento,
     telefono_paciente, direccion_paciente,
     nombre_contacto_emergencia, telefono_contacto_emergencia)
VALUES
    (:idUsuario, :sexoPaciente, :cedulaIdentidad, :fechaNacimiento,
     :telefonoPaciente, :direccionPaciente,
     :nombreContactoEmergencia, :telefonoContactoEmergencia)
RETURNING id_paciente;
```

**4. `data/repositories/HistoriaClinicaRepository.php::crearHistoriaClinicaParaPaciente()`**

```sql
INSERT INTO historias_clinicas (id_paciente)
VALUES (:idPaciente)
RETURNING id_historia_clinica;
```

**5. `PacienteService::obtenerPacientePorId()` llama a `PacienteRepository::obtenerPacientePorId()`**

```sql
SELECT pacientes.*, usuarios.usuario_activo, usuarios.nombres_usuario,
       usuarios.apellidos_usuario, usuarios.correo_usuario,
       historias_clinicas.id_historia_clinica
FROM pacientes
INNER JOIN usuarios ON usuarios.id_usuario = pacientes.id_usuario
INNER JOIN historias_clinicas ON historias_clinicas.id_paciente = pacientes.id_paciente
WHERE pacientes.id_paciente = :idPaciente;
```

El repositorio devuelve un `Paciente`. Al terminar correctamente la operación, `Transaccion` ejecuta `commit`. Si cualquier consulta falla, ejecuta `rollBack` y propaga el error.

Por ejemplo, si una CI está duplicada, no queda una cuenta huérfana aunque el `INSERT` de usuario ya se haya ejecutado. PostgreSQL produce SQLSTATE `23505`, la transacción revierte y `app.php` devuelve HTTP 409. Esta situación está probada en `test_registro_paciente.php` y en el recorrido HTTP.

### Paso 7. Respuesta y comportamiento visual

`data/models/Paciente.php::obtenerDatosPublicosPaciente()` prepara los datos del paciente y los identificadores de usuario e historia clínica. El controlador responde:

```json
{
  "mensaje": "Paciente registrado. Ya puede iniciar sesion.",
  "datos": {
    "idPaciente": 10,
    "idUsuario": 20,
    "idHistoriaClinica": 10
  }
}
```

Es un ejemplo abreviado con IDs ilustrativos: la respuesta real también incluye nombres, correo, CI, nacimiento, sexo, contacto y estado. No incluye contraseña ni hash.

En recepción, el JavaScript abre `/pacientes/{idPaciente}`. En registro público, muestra la confirmación y el enlace para iniciar sesión; no inicia sesión automáticamente.

### Cómo explicarlo oralmente

> Al registrar un paciente, el formulario envía JSON a una ruta del backend. La ruta llama a PacienteController, que lee los datos y los entrega a PacienteService. El servicio valida, genera el hash y coordina una sola transacción para crear usuario, paciente e historia clínica. Cada repositorio ejecuta su consulta SQL. Si algo falla, se revierte todo. Al terminar, el modelo expone los datos públicos y el controlador devuelve la respuesta al frontend.

## 6. Reservas y programación: reglas conservadas

La configuración entregada usa citas de 30 minutos. Un bloque 08:00–09:00 produce dos turnos, 08:00–12:00 produce ocho y 08:00–17:00 produce dieciocho. Se conserva la compatibilidad anterior con duraciones almacenadas y con la variable de configuración; no se convierten silenciosamente reservas históricas.

`HorarioMedicoService::registrarHorario()` valida la entrada, bloquea al médico y su especialidad, comprueba cruces, vuelve a comprobar la hora después del bloqueo y crea bloque y turnos en una transacción. `programarHorariosPorPeriodo()` conserva el resumen de días creados, existentes, pasados, no seleccionados y conflictivos.

`CitaService::reservarCita()` fija el paciente desde la sesión cuando corresponde. `prepararReserva()` mantiene el orden de protección: médico, especialidad, paciente y turno. Comprueba cuenta activa, una sola cita activa, turno futuro, pertenencia al horario, duración y cruces. Los índices y triggers originales siguen como barrera de integridad en PostgreSQL.

`cancelarCita()` conserva la cita y libera la oferta del turno. `reprogramarCita()` marca la anterior como reprogramada y crea una nueva ligada con `idCitaOrigen`, dentro de la misma transacción. Un fallo no hace perder la reserva anterior.

Se mantienen los permisos: paciente solo sus citas; médico solo las asignadas; reprogramación por Administración o Recepción. Cancelar exige inicio futuro y asistencia pendiente; al médico se le exige motivo. Asistencia desde el inicio e inasistencia desde el final. No se agregó una ventana de cancelación de 24 horas.

## 7. Ejecutar en tu Windows con PowerShell

Abre la carpeta extraída `Clinica-Vida-Salud-Refactorizado` en VS Code y sitúa las terminales en su raíz. Los comandos siguientes usan tu ruta de PHP `C:\php85\php.exe`.

Si ya tienes una instalación con migraciones 001–005, copia **tus** `backend/.env` y `frontend/.env` a las mismas ubicaciones en esta copia. Conserva tus credenciales y el nombre de base. No necesitas crear tablas ni repetir el seeder. La refactorización no incluye tu base de datos ni tus archivos `.env`.

Verifica:

```powershell
& "C:\php85\php.exe" -v
& "C:\php85\php.exe" --ini
& "C:\php85\php.exe" backend/scripts/verificar-entorno.php --conexion
```

El proyecto requiere PHP 8.2 o superior y extensiones `pdo`, `pdo_pgsql`, `mbstring`, `curl`, `session` y `ctype`. En Windows varias vienen integradas. Usa el `php.ini` informado por el comando para habilitar las que falten. Conserva:

```dotenv
CITA_DURACION_MINUTOS=30
CLINICA_HORA_APERTURA=08:00
CLINICA_HORA_CIERRE=17:00
FRONTEND_URL=http://127.0.0.1:8080
```

En el frontend, `API_URL=http://127.0.0.1:8000/api`. Ambos deben usar el mismo nombre de cookie. Usa `127.0.0.1` en ambos, sin mezclarlo con `localhost`.

**Terminal 1: backend**, desde la raíz:

```powershell
& "C:\php85\php.exe" -S 127.0.0.1:8000 -t backend/public backend/src/server.php
```

**Terminal 2: frontend**, desde la misma raíz:

```powershell
& "C:\php85\php.exe" -S 127.0.0.1:8080 -t frontend/public frontend/src/server.php
```

Abre <http://127.0.0.1:8080>. Para cerrar, pulsa `Ctrl+C` en ambas terminales. No se necesita npm, Composer, React, Laravel ni un proceso de compilación.

Si todavía no existe la base, sigue la instalación inicial del README: crea una base vacía en pgAdmin, configura ambos `.env`, ejecuta las migraciones 001–005 en orden y carga datos iniciales una sola vez. No apliques ese procedimiento a tu base ya instalada.

La guía de actualización segura está en [ACTUALIZAR-ESTA-VERSION.md](ACTUALIZAR-ESTA-VERSION.md).

## 8. Verificación y límites

Consulta [VERIFICACION.md](docs/refactorizacion/VERIFICACION.md) para los resultados, comandos y límites exactos.

Se ejecutaron las pruebas originales y nuevas contra PHP/PDO y PostgreSQL, además de comparar las respuestas del proyecto original y el refactorizado en un recorrido HTTP que cubre las 48 rutas. Se comprobaron transacciones, propietarios, roles, duplicados, programación, reprogramación y dos procesos compitiendo por reservas.

La comprobación se hizo en un entorno Linux aislado, no en tu Windows ni en tu base real. Tampoco se completó una revisión visual en navegador: se comprobó el HTML servido, los permisos de las vistas, sintaxis e importaciones JavaScript y la igualdad de los archivos de interfaz. Conviene recorrer los cuatro roles en tu navegador antes de la defensa.
