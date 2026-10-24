# Clinica - Sistema de Citas Medicas

Version 1.4.0. Proyecto universitario con PHP, PostgreSQL, PDO, HTML, CSS y JavaScript. Se conserva la arquitectura por capas y la configuracion existente.

La hora de Bolivia se aplica internamente a toda la aplicacion. La interfaz no muestra ciudades ni identificadores de zona. CITA_DURACION_MINUTOS configura nuevos bloques; por defecto es 30.

Esta version separa el trabajo de Administracion, Recepcion, Medico y Paciente. Incorpora bloques de atencion, turnos de 30 minutos por defecto, reservas, anulaciones, reprogramaciones y asistencia. Las pantallas consultan la API real; no muestran reservas ni estadisticas inventadas.

Esta actualizacion agrega programacion por periodos, horas de 08:00 a 17:00, una sola cita activa por paciente y gestion de resultados por el medico. En una instalacion con 004, aplica solamente 005; no repitas el seeder. Guia: [programacion mensual](docs/ai/programacion-mensual.md).

## 1. Responsabilidades por rol

En el sistema, **cliente corresponde al rol Paciente**. Una cuenta tiene un unico rol.

| Funcion | Administrador | Recepcionista | Medico | Paciente |
| --- | --- | --- | --- | --- |
| Gestionar usuarios administrativos y asignar su rol al crearlos | Si | No | No | No |
| Registrar, editar y activar/desactivar pacientes | Si | Si | No | No |
| Consultar perfil personal de paciente | No | No | No | Solo el propio |
| Registrar, editar y activar/desactivar medicos y especialidades | Si | No | No | No |
| Programar un dia o un periodo y generar turnos | Si | No | No | No |
| Consultar bloques de atencion | Todos | No; consulta disponibilidad al reservar | Solo los propios | No; consulta disponibilidad al reservar |
| Elegir especialidad, medico y turno libre | Si | Si | No | Si |
| Reservar una cita | Para un paciente registrado | Para un paciente registrado | No | Solo para si mismo |
| Consultar citas | Todas | Todas | Solo las asignadas | Solo las propias |
| Cancelar una cita futura vigente | Cualquier paciente | Cualquier paciente | Solo las asignadas; motivo obligatorio | Solo la propia |
| Reprogramar una cita futura vigente | Si | Si | No | Solicita el cambio a Recepcion |
| Marcar cita atendida | Si | Si | Solo las asignadas | No |
| Registrar inasistencia | Si | Si | Solo las asignadas | No |
| Confirmar una cita programada antes del inicio | Si | Si | No | No |
| Consultar datos basicos del paciente desde una cita | Si | Si | Solo pacientes de sus citas | Solo sus propios datos |
| Consultas, tratamientos e historial clinico completo | Pendiente | Sin permiso clinico | Pendiente | Pendiente de definir alcance |
| Reportes administrativos | Si | No | No | No |

El Administrador tiene control administrativo. Su rol no le concede automaticamente permiso para escribir diagnosticos o tratamientos. El Medico ve nombre, CI, nacimiento y motivo de consulta de sus citas; no recibe acceso al directorio general de pacientes.

El rol se asigna al crear la cuenta: Usuarios permite Administrador o Recepcionista; el registro de Medicos fija Medico y el registro de Pacientes fija Paciente. **No existe cambio libre de rol de una cuenta ya creada.**

Los limites se comprueban en las rutas y servicios del backend. Para citas, la consulta se restringe al usuario autenticado; cambiar un identificador en la URL o enviar otro idPaciente no concede acceso. Los botones son ayudas visuales, no la barrera de seguridad.

## 2. Organizacion

| Carpeta | Responsabilidad |
| --- | --- |
| backend/src/presentation | Rutas, sesion, permisos HTTP y controladores |
| backend/src/business | Validaciones, reglas de acceso, disponibilidad y transacciones |
| backend/src/data | Conexion PDO, modelos, consultas SQL, migraciones y datos iniciales |
| backend/src/shared | Errores, constantes, configuracion, sesion y utilidades |
| backend/scripts | Comprobacion del entorno y actualizaciones de la base |
| backend/tests | Pruebas de validaciones, permisos e integracion |
| frontend/src/features | Pantallas y JavaScript por modulo: auth, panel, usuarios, pacientes, especialidades, medicos, horarios, citas y reportes |
| frontend/src/components | Navegacion, formularios, tablas, paginacion e iconos compartidos |
| frontend/src/api | Transporte JSON y consulta de sesion desde PHP |
| frontend/src/styles | Estilos responsive compartidos |
| docs/ai | Contratos de API, reglas, pruebas y documentacion tecnica |

El frontend no contiene SQL ni claves de PostgreSQL. Los servicios deciden las operaciones permitidas y los repositorios ejecutan las consultas preparadas. No se necesita Node, React ni un proceso de compilacion para ejecutar el proyecto.

## 3. Preparar el proyecto en Windows

Para una instalacion nueva, extrae este proyecto completo y abre una terminal en su raiz. Para actualizar una instalacion que ya funciona, sigue [ACTUALIZAR-ESTA-VERSION.md](ACTUALIZAR-ESTA-VERSION.md) y conserva ambos archivos .env.

Comprueba PHP:

```powershell
php -v
php --ini
```

Si Windows no reconoce `php`, agrega PHP de XAMPP al PATH de esa terminal:

```powershell
$env:Path += ";C:\xampp\php"
php -v
```

Repite ese ajuste en cada terminal nueva si todavia no lo agregaste al PATH de Windows.

En el archivo `php.ini` indicado por `php --ini`, habilita las extensiones que falten, quitando el `;` inicial. No dupliques las lineas:

```ini
extension=pdo_pgsql
extension=mbstring
extension=curl
```

Guarda y reinicia los procesos PHP que ya estuvieran abiertos. Verifica:

```powershell
php backend/scripts/verificar-entorno.php
```

## 4. Variables de entorno

Solo para una instalacion nueva. Si los .env ya existen, conservarlos; los valores nuevos tienen predeterminados y no requieren reemplazarlos. Desde la raiz del proyecto:

```powershell
Copy-Item backend/.env.example backend/.env
Copy-Item frontend/.env.example frontend/.env
```

En Linux/macOS usa `cp` en lugar de `Copy-Item`.

Edita `backend/.env`:

```dotenv
APP_ENV=local
CITA_DURACION_MINUTOS=30
CLINICA_HORA_APERTURA=08:00
CLINICA_HORA_CIERRE=17:00
FRONTEND_URL=http://127.0.0.1:8080
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=citas_medicas
DB_USER=postgres
DB_PASS=TU_CLAVE_DE_POSTGRESQL
DB_SSLMODE=prefer
SESSION_COOKIE_NAME=citas_medicas_sesion
SESSION_SECURE=false
SESSION_IDLE_SECONDS=1800
SEED_USERS_PASSWORD=TU_CLAVE_DE_PRUEBA_DE_12_O_MAS_CARACTERES
```

Reemplaza los dos valores de clave. `SEED_USERS_PASSWORD` debe tener entre 12 y 72 bytes. El seeder genera hashes con `password_hash`; no guarda esa clave en texto plano en la base.

En `frontend/.env`:

```dotenv
APP_ENV=local
FRONTEND_URL=http://127.0.0.1:8080
API_URL=http://127.0.0.1:8000/api
SESSION_COOKIE_NAME=citas_medicas_sesion
```

Usa **127.0.0.1 en ambas aplicaciones**. No mezcles `localhost` y `127.0.0.1`, porque las cookies de sesion dependen del host. Las variables del sistema tienen prioridad sobre los archivos `.env`. No hay interpolacion de variables dentro de estos archivos.

## 5. Instalacion inicial en una base vacia

Esta seccion es solo para instalaciones nuevas. La actualizacion 1.4 no crea otra base; sigue ACTUALIZAR-ESTA-VERSION.md en tu instalacion actual.

En pgAdmin, conectado a la base `postgres`, ejecuta esta sentencia por separado:

```sql
CREATE DATABASE citas_medicas WITH ENCODING 'UTF8';
```

Luego, desde la raiz del proyecto:

```powershell
php backend/scripts/verificar-entorno.php --conexion
php backend/scripts/migrar-base-datos.php
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/actualizar-roles-citas.php
php backend/scripts/actualizar-agenda-reportes.php
php backend/scripts/actualizar-programacion-mensual.php
php backend/scripts/cargar-datos-iniciales.php
```

La migracion crea las 11 tablas dentro de una transaccion. Es una migracion inicial, no un gestor automatico de versiones: si las tablas existen, se detiene sin borrarlas. La migracion 002 agrega dos indices sin eliminar datos y se puede ejecutar nuevamente. Si detecta duplicados equivalentes anteriores, se detiene para que se revisen.

La migracion 003 agrega a citas el registro de asistencia, el motivo de anulacion, los responsables y la referencia a la reserva original al reprogramar. No borra registros ni modifica cuentas. Se puede repetir.

La migracion 004 agrega sexo opcional, telefono medico y duracion por bloque/turno. Convierte la oferta futura a 30 minutos y conserva las horas de las reservas anteriores. Asistio pasa a Atendida. Ver [reglas de actualizacion](docs/ai/actualizacion-agenda-reportes.md).

La migracion 005 agrega un indice de cita activa por paciente, preserva duplicidades antiguas con una excepcion controlada y guarda el historial de cambios en citas. No altera horarios anteriores, estados ni claves.

El seeder requiere una base sin usuarios. Crea cuatro roles, tres especialidades, una cuenta por rol, el perfil medico de prueba y un paciente con historia clinica. Una segunda ejecucion se detiene; no reemplaza cuentas ni claves.

### Cuentas de prueba

| Rol | Correo |
| --- | --- |
| Administrador | `administrador@vidasalud.test` |
| Recepcionista | `recepcionista@vidasalud.test` |
| Medico | `medico@vidasalud.test` |
| Paciente | `paciente@vidasalud.test` |

Los correos se mantienen por compatibilidad con las versiones anteriores; la interfaz se identifica solamente como Clinica.

La clave de las cuatro cuentas es el valor que elegiste en `SEED_USERS_PASSWORD`. Son datos ficticios para desarrollo local. Despues de cargar las cuentas, puedes vaciar esa variable en el archivo `.env`.

## 6. Ejecutar backend y frontend

Abre **dos terminales** en VS Code, desde la raiz del proyecto. Deja ambas abiertas.

Terminal 1: backend

```powershell
cd backend
php -S 127.0.0.1:8000 -t public src/server.php
```

Terminal 2: frontend

```powershell
cd frontend
php -S 127.0.0.1:8080 -t public src/server.php
```

| Servicio | Direccion |
| --- | --- |
| Portal | http://127.0.0.1:8080 |
| Inicio de sesion | http://127.0.0.1:8080/iniciar-sesion |
| Estado del backend | http://127.0.0.1:8000/api/estado |

`/api/estado` solo confirma que PHP y el router responden; no prueba la conexion a PostgreSQL. Para la conexion usa `verificar-entorno.php --conexion`.

Los servidores incorporados de PHP son para desarrollo local. El despliegue en nube requerira un servidor web con HTTPS, la carpeta publica correcta y configuracion de sesiones/origenes; no se publica con `php -S`.


## 7. Catalogo de casos de uso

**P0 - Critica:** protege el acceso, los datos o el flujo central de reserva. **P1 - Alta:** necesaria para operar la clinica. **P2 - Media:** mejora o completa la gestion. La prioridad indica importancia, no estado de implementacion.

### Casos implementados

| ID | Caso de uso | Actor | Prioridad | Resultado o alcance |
| --- | --- | --- | --- | --- |
| CU-01 | Iniciar sesion y acceder al panel de su rol | Todos | P0 | Solo cuentas activas; menu y operaciones segun rol |
| CU-02 | Cerrar sesion | Todos | P0 | El acceso autenticado anterior deja de ser valido |
| CU-03 | Crear cuenta de paciente | Visitante | P1 | Usuario Paciente, perfil e historia vacia en una transaccion |
| CU-04 | Registrar un paciente desde recepcion | Administrador, Recepcionista | P1 | Registro completo sin duplicar correo ni CI |
| CU-05 | Buscar y consultar pacientes | Administrador, Recepcionista | P1 | Lista paginada, datos de contacto y numero de historia |
| CU-06 | Editar datos y estado del paciente | Administrador, Recepcionista | P1 | Nombre, correo, CI, nacimiento, sexo opcional y contacto; desactivar conserva citas e historia |
| CU-07 | Consultar mi perfil | Paciente | P1 | Datos asociados al usuario autenticado |
| CU-08 | Crear usuario y asignar rol administrativo | Administrador | P0 | Elegir Administrador o Recepcionista al registrar |
| CU-09 | Buscar y consultar usuarios | Administrador | P1 | Cuentas y roles; sin hashes ni claves |
| CU-10 | Editar datos de usuario | Administrador | P1 | Nombre, apellido y correo; conserva rol y clave |
| CU-11 | Activar o desactivar una cuenta | Administrador | P0 | Protege la propia cuenta y el ultimo administrador; medicos usan CU-19 |
| CU-12 | Registrar especialidad | Administrador | P1 | Nombre unico normalizado |
| CU-13 | Buscar y consultar especialidades | Administrador | P1 | Listado, busqueda y estado |
| CU-14 | Editar especialidad | Administrador | P1 | Nombre y descripcion |
| CU-15 | Activar o desactivar especialidad | Administrador | P1 | No desactivar si tiene medicos activos |
| CU-16 | Registrar medico y asignar especialidad | Administrador | P1 | Cuenta y perfil con rol Medico fijo, especialidad activa |
| CU-17 | Buscar y consultar medicos | Administrador | P1 | Busqueda por nombre, correo, matricula o especialidad |
| CU-18 | Editar medico o reasignar especialidad | Administrador | P1 | Valida matricula, correo y especialidad; protege citas vigentes |
| CU-19 | Activar o desactivar medico | Administrador | P0 | Controla acceso y disponibilidad; protege citas vigentes |
| CU-20 | Programar horarios de un dia o periodo | Administrador | P0 | Dias laborales, rango 08:00-17:00, turnos consecutivos y resumen de conflictos; repetir no duplica |
| CU-21 | Consultar horarios de atencion | Administrador, Medico | P1 | Administrador ve todos; Medico solo los propios |
| CU-22 | Consultar disponibilidad y elegir medico | Administrador, Recepcionista, Paciente | P0 | Catalogo activo, filtro de especialidad, fecha y turnos libres |
| CU-23 | Reservar mi cita | Paciente | P0 | Reserva para si mismo; rechaza cualquier segunda cita activa, aunque no coincida en fecha o medico |
| CU-24 | Agendar cita de un paciente | Administrador, Recepcionista | P0 | Busca paciente, verifica que no tenga cita activa y confirma un turno disponible |
| CU-25 | Consultar citas y su detalle | Todos | P0 | Administrador/Recepcion: todas; Paciente: propias; Medico: asignadas |
| CU-26 | Cancelar cita con motivo | Administrador, Recepcionista, Medico asignado, Paciente propietario | P0 | Antes del inicio; motivo obligatorio para Medico; libera turno y conserva auditoria |
| CU-27 | Reprogramar cita | Administrador, Recepcionista | P0 | Crea nueva reserva vinculada y conserva la anterior; cambio atomico |
| CU-28 | Marcar cita atendida | Administrador, Recepcionista, Medico asignado | P1 | Desde el inicio del turno; estado Atendida y asistencia Asistio; conserva auditoria |
| CU-29 | Registrar inasistencia | Administrador, Recepcionista, Medico asignado | P1 | Desde el fin del turno, una vez; estado Inasistencia y responsable |
| CU-30 | Consultar mi agenda y datos basicos para la atencion | Medico | P1 | Codigo, paciente, especialidad, fecha, inicio, fin, estado y motivo; acciones segun permiso y momento |
| CU-36 | Consultar reportes administrativos | Administrador | P1 | Periodo, medico, especialidad y estado; totales, agenda y pacientes registrados |

| CU-43 | Confirmar cita programada | Administrador, Recepcionista | P1 | Solo antes del inicio y en estado Programada; conserva actor e historial |

La generacion de turnos es parte de CU-20. CU-23 y CU-24 incluyen la validacion de una sola cita activa, sin crear identificadores nuevos para reglas transversales. El control de acceso, la validacion y la proteccion contra cruces son reglas transversales de estos casos.

### Casos parciales y pendientes del sistema completo

| ID | Caso previsto | Actor previsto | Prioridad | Estado y limite actual |
| --- | --- | --- | --- | --- |
| CU-31 | Editar, bloquear o retirar bloques/turnos | Administrador | P1 | Pendiente; definir manejo de las citas afectadas antes de permitir cambios |
| CU-32 | Registrar consulta o atencion medica | Medico | P0 | Pendiente; no existe formulario ni endpoint de consulta |
| CU-33 | Consultar historial clinico autorizado | Medico | P0 | Pendiente; existe la historia vacia, no el acceso clinico completo |
| CU-34 | Actualizar historial con antecedentes y atenciones | Medico | P0 | Pendiente; delimitar acceso y trazabilidad clinica |
| CU-35 | Registrar y actualizar tratamientos | Medico | P1 | Pendiente; las tablas existen, el modulo no |
| CU-37 | Cambiar mi contrasena | Todos | P1 | Pendiente; editar usuario no modifica claves |
| CU-38 | Recuperar acceso | Usuario sin acceso | P1 | Pendiente; no se envian correos de recuperacion |
| CU-39 | Reasignar rol de una cuenta existente | Administrador | P2 | Pendiente; revisar compatibilidad de perfiles y citas |
| CU-40 | Consultar bitacora completa del sistema | Administrador | P2 | Parcial: historial de cambios por cita desde 005; pendiente visor general de operaciones |
| CU-41 | Gestionar consultorios | Administrador | P2 | Pendiente del menu previo; requiere ampliar el modelo |
| CU-42 | Integrar laboratorio digital | Por definir | P2 | Fuera del avance actual; requiere requisitos y permisos propios |

La matriz anterior describe solamente las operaciones implementadas. Las funciones clinicas pendientes no se habilitan por pertenecer al rol Medico. Un futuro acceso del paciente a resultados o tratamientos necesita definirse antes de desarrollarlo.

## 8. Casos de uso de mayor importancia

Estos casos se deben explicar y demostrar primero: acceso correcto, reserva sin conflictos, anulacion propia, horarios sin cruces y reprogramacion sin perder una reserva. CU-32 y CU-33 son prioritarios para la siguiente etapa clinica, pero estan pendientes.

### CU-01 - Iniciar sesion y aplicar permisos (P0)

- **Objetivo:** entrar al espacio correspondiente a la cuenta y proteger los datos de los demas.
- **Precondiciones:** cuenta registrada y activa.
- **Flujo principal:** 1) ingresar correo y clave; 2) verificar credenciales; 3) renovar la sesion; 4) cargar panel y menu del rol; 5) volver a comprobar sesion y permisos en cada operacion.
- **Alternativas:** credenciales incorrectas o cuenta inactiva producen 401; una accion de otro rol produce 403; una cita ajena produce 404 sin revelar si existe. Una escritura sin token CSRF valido produce 419.
- **Postcondicion:** acceso limitado a su rol y, cuando corresponda, a sus propios registros.
- **Prueba de aceptacion:** un Paciente no abre Usuarios, no lista otros pacientes y no consulta ni anula una cita ajena aunque cambie la URL.

### CU-23 / CU-24 - Reservar una cita (P0)

- **Actores:** Paciente para si mismo; Administrador o Recepcionista para un paciente registrado.
- **Precondiciones:** sesion activa, paciente activo, medico y especialidad activos, bloque futuro y turno habilitado.
- **Flujo principal:** 1) identificar al paciente desde la sesion o buscarlo en Recepcion; 2) elegir especialidad y medico; 3) elegir fecha; 4) consultar turnos libres; 5) seleccionar hora e indicar motivo opcional; 6) confirmar; 7) comprobar otra vez disponibilidad y cruces en una transaccion; 8) mostrar el detalle de la reserva confirmada.
- **Alternativas:** si no hay turnos, elegir otra fecha o medico. Si otro usuario ocupa el turno, devolver 409 y actualizar opciones. Si el paciente tiene cualquier cita Programada o Confirmada, rechazar con 409 aunque el nuevo turno no se cruce; tambien bloquean las citas pasadas aun sin resultado. El Paciente no puede enviar el identificador de otra persona para reservar a su nombre.
- **Postcondicion:** una cita confirmada ocupa un solo turno de 30 minutos por defecto; el sistema conserva quien la registro y la especialidad elegida.
- **Prueba de aceptacion:** dos reservas para el mismo turno nunca deben quedar confirmadas; la segunda recibe un conflicto. Reservas simultaneas en PostgreSQL nativo requieren la comprobacion indicada en docs/ai/pruebas.md.

### CU-26 - Cancelar cita (P0)

- **Precondiciones:** cita pendiente o confirmada, sin asistencia registrada y con inicio posterior a la hora actual. El Paciente debe ser su propietario y el Medico debe ser el asignado.
- **Flujo principal:** 1) abrir detalle; 2) elegir Cancelar cita; 3) indicar motivo obligatorio para Medico (opcional para otros roles); 4) confirmar; 5) verificar permiso, propietario, estado y hora; 6) cambiar a cancelada y liberar el turno.
- **Alternativas:** una cita ajena no se revela; una cita ya iniciada, anulada, reprogramada o con asistencia registrada produce 409. No existe una ventana adicional de 24 horas en esta version.
- **Postcondicion:** se conserva la cita como Cancelada, su motivo, responsable e historial de cambios. Otra persona puede reservar el turno si sigue siendo futuro y valido.
- **Prueba de aceptacion:** anular la propia libera el turno; anular la ajena no cambia ningun registro.

### CU-20 - Programar horarios y generar turnos (P0)

- **Actor:** Administrador.
- **Precondiciones:** medico y especialidad activos; fecha futura dentro de los proximos 12 meses.
- **Flujo principal:** 1) elegir medico, fecha inicial/final y dias laborales (o un solo dia); 2) indicar horas con minutos 00/30 dentro de 08:00-17:00; 3) validar cada fecha; 4) guardar los bloques validos y sus turnos en una transaccion; 5) mostrar creados, existentes, fechas pasadas y conflictos.
- **Alternativas:** 09:00-10:00 y 09:30-10:30 se cruzan y se rechazan; 09:00-10:00 y 10:00-11:00 son contiguos y se admiten. Hora final igual o anterior, fuera del rango, minutos invalidos o medico inactivo se rechazan. En periodos, las fechas con inicio pasado se omiten; los bloques iguales se conservan y los cruces se informan sin sobrescribirlos.
- **Postcondicion:** 08:00-17:00 produce 18 turnos consecutivos; 08:00-12:00 produce 8. 08:00-09:00 produce 08:00-08:30 y 08:30-09:00 con la configuracion inicial de 30 minutos. Un fallo revierte tambien los turnos.

### CU-27 - Reprogramar sin perder la reserva (P0)

- **Actores:** Administrador o Recepcionista. El Paciente solicita el cambio a Recepcion.
- **Precondiciones:** reserva vigente, futura y sin asistencia; nuevo turno diferente y disponible.
- **Flujo principal:** 1) abrir cita; 2) elegir Reprogramar; 3) seleccionar otro medico/fecha/turno; 4) verificar disponibilidad y cruces; 5) marcar la anterior como reprogramada y crear la nueva con idCitaOrigen dentro de la misma transaccion.
- **Alternativas:** si falla cualquier validacion o escritura, se revierte todo y la cita original permanece confirmada.
- **Postcondicion:** el turno anterior queda libre y la nueva reserva conserva paciente y motivo. El historial de reprogramaciones no se borra.

### CU-28 / CU-29 - Asistencia e inasistencia (P1)

- **Actores:** Medico asignado para el resultado de sus citas; Administrador y Recepcionista conservan asistencia e inasistencia administrativa.
- **Precondiciones:** cita pendiente o confirmada, sin asistencia registrada.
- **Flujo principal:** 1) abrir detalle; 2) confirmar Asistio desde el inicio del turno, o No asistio desde su fin; 3) guardar el responsable y el resultado una sola vez.
- **Alternativas:** antes de la hora permitida, en citas anuladas o ante un registro repetido se devuelve 409. Paciente no tiene esta operacion; Medico solo modifica citas asignadas mediante atencion o inasistencia. El endpoint administrativo asistencia sigue limitado a Administrador/Recepcionista.
- **Postcondicion:** Asistio cambia estado_cita a atendida; No asistio cambia estado_cita a no_asistio. Ninguna de estas acciones escribe diagnosticos ni tratamientos.
- **Limite:** la correccion de una asistencia ya registrada no esta implementada.

## 9. Recorrido para demostrar los cuatro roles

1. **Administrador:** iniciar sesion, registrar una especialidad y medico o usar los de prueba; crear un horario futuro de 09:00 a 10:00. Deben aparecer dos turnos de 30 minutos.
2. **Paciente:** iniciar sesion, abrir Mis citas, reservar con ese medico y horario. Revisar el detalle y comprobar que Usuarios, Pacientes y la reprogramacion no estan disponibles.
3. **Otro paciente:** el turno ocupado ya no aparece. Abrir por URL la cita del primer paciente no debe entregar sus datos.
4. **Recepcionista:** buscar el paciente, consultar sus citas y reprogramar una futura; al anular, comprobar que se libera el turno. No puede crear horarios ni administrar usuarios.
5. **Medico:** revisar Mi agenda y Mis horarios; solamente debe encontrar sus registros. Puede leer los datos basicos de una cita asignada, marcarla atendida desde el inicio, registrar inasistencia desde el fin o cancelarla antes del inicio indicando un motivo.
6. **Recepcionista:** en una cita cuyo turno haya comenzado, registrar asistencia; para inasistencia, esperar a que termine el turno. No cambies manualmente las fechas de una base con datos reales.
7. **Administrador:** intentar desactivar un medico con citas vigentes futuras; primero debe resolver esas reservas. Abrir Reportes, filtrar el periodo y comprobar totales, citas y pacientes registrados. El registro clinico sigue pendiente.

## 10. Pruebas

Sin base de datos:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
php backend/tests/test_validaciones_agenda.php
php backend/tests/test_validaciones_actualizacion.php
```

Con una base separada cuyo nombre termine en `_pruebas`, migraciones 001 a 005 y datos iniciales:

```powershell
php backend/tests/test_registro_paciente.php
php backend/tests/test_gestion_especialidades_medicos.php
php backend/tests/test_gestion_citas.php
php backend/tests/test_reportes_perfiles.php
php backend/tests/test_programacion_periodos.php
php backend/tests/test_migracion_005.php
php backend/tests/test_reserva_concurrente.php
```

Preparacion, cobertura y limites del entorno de comprobacion: [docs/ai/pruebas.md](docs/ai/pruebas.md). No ejecutes pruebas de integracion en la base de trabajo.

## 11. Avances pequenos para Git

Conserva tu repositorio y revisa los cambios antes de crear commits. No se subio nada a GitHub desde esta entrega.

```powershell
git status
git diff --stat
git add backend
git commit -m "feat: programar periodos y proteger la cita activa del paciente"
git add frontend
git commit -m "feat: agregar programacion mensual y acciones de agenda medica"
git add README.md ACTUALIZAR-ESTA-VERSION.md docs
git commit -m "docs: documentar migracion 005 y pruebas de la version 1.4"
```

Antes de subir, ejecuta las pruebas en tu entorno y comprueba que ningun `.env` este preparado para el commit. Estos comandos son para tu copia existente: no reemplazan ni reinician su historial.

## 12. Problemas frecuentes y documentacion

| Problema | Revision |
| --- | --- |
| Falta PHP o una extension | Comprueba php --ini y verificar-entorno.php |
| Error de conexion | Revisa PostgreSQL y las variables DB_* |
| Citas devuelve servicio no disponible tras actualizar | Comprueba migraciones 001 a 005; si 004 ya existe, ejecuta solo actualizar-programacion-mensual.php |
| No hay turnos | Administracion debe crear un bloque futuro; medico y especialidad deben estar activos |
| La sesion no permanece | Mismo host, nombre de cookie y HTTP/HTTPS compatibles en ambos servicios |
| Vista no autorizada | Verifica el rol; el frontend no cambia permisos del backend |
| Un turno acaba de ocuparse | Actualiza opciones y elige otro; no reenvies una reserva a ciegas |

- [Aplicar esta actualizacion sin perder configuracion](ACTUALIZAR-ESTA-VERSION.md)
- [Programacion, cita activa, estados y limites de la version 1.4](docs/ai/programacion-mensual.md)
- [API y contratos JSON](docs/ai/api.md)
- [Reglas de negocio](docs/ai/requisitos-y-reglas.md)
- [Arquitectura y nombres](docs/ai/arquitectura-y-nombres.md)
- [Diccionario de datos](docs/ai/diccionario-datos.md)
- [Pruebas](docs/ai/pruebas.md)
- [Plan de avances](docs/ai/plan-avances-git.md)
- [Contexto para continuar](docs/ai/contexto-proyecto.md)
