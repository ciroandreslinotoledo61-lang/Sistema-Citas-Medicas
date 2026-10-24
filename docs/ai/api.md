# API de Clinica (version 1.4.0)

Base local: `http://127.0.0.1:8000/api`.

## Rutas

| Metodo | Ruta | Acceso |
| --- | --- | --- |
| GET | /estado | Publico; sin comprobar base de datos |
| GET | /autenticacion/token | Publico; crea token CSRF de la sesion |
| POST | /autenticacion/iniciar-sesion | Publico con CSRF |
| POST | /autenticacion/registro-paciente | Publico con CSRF; asigna rol Paciente |
| POST | /autenticacion/cerrar-sesion | CSRF; limpia la sesion actual |
| GET | /autenticacion/sesion | Usuario activo autenticado |
| GET | /roles | Administrador |
| GET | /usuarios | Administrador |
| POST | /usuarios | Administrador |
| GET | /usuarios/{idUsuario} | Administrador |
| PATCH | /usuarios/{idUsuario} | Administrador |
| PATCH | /usuarios/{idUsuario}/estado | Administrador |
| GET | /pacientes | Administrador o Recepcionista |
| POST | /pacientes | Administrador o Recepcionista |
| GET | /pacientes/mi-perfil | Paciente autenticado |
| GET | /pacientes/{idPaciente} | Administrador o Recepcionista |
| PATCH | /pacientes/{idPaciente}/contacto | Administrador o Recepcionista |

## Rutas del avance 2

Todas requieren una cuenta Administrador activa. Las mutaciones requieren CSRF, igual que el resto de la API.

| Metodo | Ruta | Accion |
| --- | --- | --- |
| GET | /especialidades | Listar por busqueda y pagina |
| POST | /especialidades | Registrar especialidad |
| GET | /especialidades/activas | Obtener opciones para asignacion de medicos |
| GET | /especialidades/{idEspecialidad} | Obtener una especialidad |
| PATCH | /especialidades/{idEspecialidad} | Actualizar nombre y descripcion |
| PATCH | /especialidades/{idEspecialidad}/estado | Activar o desactivar |
| GET | /medicos | Listar por busqueda y pagina |
| POST | /medicos | Registrar usuario y perfil medico |
| GET | /medicos/{idMedico} | Obtener datos publicos del medico |
| PATCH | /medicos/{idMedico} | Actualizar cuenta y perfil profesional |
| PATCH | /medicos/{idMedico}/estado | Cambiar acceso de la cuenta medica |

Los endpoints no usan autenticacion JWT. Se debe conservar la cookie y enviar `credentials: "include"` desde el navegador. CORS permite solamente FRONTEND_URL.

## Solicitudes

Las mutaciones usan `Content-Type: application/json` y `X-CSRF-Token`, obtenido previamente en `/autenticacion/token`. El cliente frontend obtiene el token antes de cada mutacion. No reintenta automaticamente una escritura.

Registro de paciente:

```json
{
  "nombresUsuario": "Ana",
  "apellidosUsuario": "Prueba",
  "correoUsuario": "ana@example.test",
  "contrasenaUsuario": "FraseDePrueba123",
  "confirmacionContrasena": "FraseDePrueba123",
  "cedulaIdentidad": "TEST-123",
  "fechaNacimiento": "2000-01-15",
  "sexoPaciente": "no_especificado",
  "telefonoPaciente": "70000000",
  "direccionPaciente": "",
  "nombreContactoEmergencia": "",
  "telefonoContactoEmergencia": ""
}
```

Cuenta administrativa: nombresUsuario, apellidosUsuario, correoUsuario, contrasenaUsuario, confirmacionContrasena y codigoRol. codigoRol permite `administrador` o `recepcionista`.

Actualizacion de usuario: nombresUsuario, apellidosUsuario y correoUsuario.

Cambio de estado: `{"usuarioActivo": false}`. Debe ser booleano JSON, no texto.

Contacto de paciente: telefonoPaciente, direccionPaciente, nombreContactoEmergencia y telefonoContactoEmergencia. No modifica CI, nacimiento, rol ni claves.

Listados: `?busqueda=ana&pagina=1`; hasta 20 elementos. La respuesta incluye elementos, pagina y hayPaginaSiguiente.

## Respuestas y errores

Exito: `{"mensaje": "...", "datos": ...}`; algunos GET omiten mensaje.

Error: `{"mensaje": "...", "erroresCampos": {"nombreCampo": "..."}}`; erroresCampos puede estar ausente o vacio.

| Codigo | Significado |
| --- | --- |
| 200 | Solicitud completada |
| 201 | Registro creado |
| 204 | Preflight CORS autorizado |
| 400 | JSON no valido |
| 401 | Sesion o credenciales no validas |
| 403 | Rol u origen sin permiso |
| 404 | Recurso, ruta o combinacion de metodo no disponible |
| 409 | Duplicado o conflicto de negocio |
| 413 | Cuerpo mayor a 64 KiB |
| 415 | Tipo de contenido incorrecto |
| 419 | Token CSRF no valido |
| 422 | Datos del formulario no validos |
| 429 | Limite basico de intentos por sesion |
| 500 | Error interno o configuracion incompleta |
| 503 | Error de acceso a la base de datos |

No se devuelven hashes, claves, SQL ni trazas internas.

## Cuerpos de Especialidades y Medicos

Crear o editar especialidad:

```json
{
  "nombreEspecialidad": "Dermatologia",
  "descripcionEspecialidad": "Atencion de la piel"
}
```

Cambiar estado de especialidad: `{"especialidadActiva": false}`. Rechaza con 409 si intenta desactivar una especialidad con medicos activos.

Registrar medico:

```json
{
  "nombresUsuario": "Ana",
  "apellidosUsuario": "Prueba",
  "correoUsuario": "ana.medico@example.test",
  "contrasenaUsuario": "FraseDePrueba123",
  "confirmacionContrasena": "FraseDePrueba123",
  "telefonoMedico": "70000000",
  "matriculaProfesional": "SC-12345",
  "idEspecialidad": 1
}
```

Reemplaza idEspecialidad por uno real obtenido desde /especialidades/activas. El rol se fija en Medico y la cuenta se crea activa; esos valores no se toman del JSON.

Editar medico usa los mismos datos, sin contrasenaUsuario ni confirmacionContrasena. No cambia la clave existente. El correo se valida contra todas las cuentas, independientemente de su rol.

Cambiar estado de medico: `{"medicoActivo": false}`. El estado se refleja en usuarios.usuario_activo y el cambio invalida sesiones anteriores. Intentar activar con especialidad inactiva produce 409.

La ruta /usuarios/{idUsuario}/estado devuelve 409 para cuentas de rol Medico; se debe usar /medicos/{idMedico}/estado.

Los listados de ambos modulos usan busqueda y pagina, con 20 filas por pagina. La busqueda de medicos incluye nombre, apellido, correo, matricula y especialidad. /especialidades/activas devuelve un arreglo de opciones, sin paginacion.

## Rutas de horarios y citas

Todas requieren sesion activa. En todas las mutaciones se exige CSRF. Los identificadores se obtienen de los listados correspondientes; los valores de ejemplo no son datos precargados.

| Metodo | Ruta | Acceso y alcance |
| --- | --- | --- |
| GET | /disponibilidad/medicos | Administrador, Recepcionista, Paciente; solo medicos y especialidades activos |
| GET | /disponibilidad/turnos?idMedico=1&fecha=2030-01-02 | Administrador, Recepcionista, Paciente; turnos futuros libres del medico y dia |
| GET | /horarios | Administrador: todos; Medico: propios |
| POST | /horarios | Administrador |
| GET | /citas | Todos; Paciente: propias, Medico: asignadas, Administrador/Recepcionista: todas |
| POST | /citas | Paciente: para si mismo; Administrador/Recepcionista: para paciente seleccionado |
| GET | /citas/{idCita} | Mismo alcance que el listado |
| PATCH | /citas/{idCita}/cancelacion | Paciente: propia; Medico: asignada con motivo obligatorio; Administrador/Recepcionista: cualquiera vigente futura |
| PATCH | /citas/{idCita}/reprogramacion | Administrador, Recepcionista |
| PATCH | /citas/{idCita}/asistencia | Administrador, Recepcionista |

GET /horarios admite busqueda, pagina, fecha e idMedico opcional. El filtro idMedico no permite al Medico consultar otro profesional. GET /citas admite busqueda, pagina, fecha y estado (pendiente, confirmada, cancelada, reprogramada, no_asistio, atendida). Las paginas tienen hasta 20 registros.

### Crear horario

POST /horarios:

```json
{"idMedico": 1, "fechaAtencion": "2030-01-02", "horaInicio": "08:00", "horaFin": "09:00"}
```

Cambiar la fecha por una futura dentro del siguiente ano. Respuesta 201, datos con idHorarioMedico, cantidadTurnos y los campos anteriores. Horas en HH:mm, minutos 00/30 dentro del horario de la clinica (08:00-17:00 por defecto) y bloque multiplo de la duracion configurada (30 por defecto), mismo dia y sin cruces. GET /horarios devuelve datos.elementos con idHorarioMedico, idMedico, nombreMedico, fechaAtencion, horaInicio, horaFin y cantidadTurnos; ademas pagina y hayPaginaSiguiente.

### Catalogo y turnos

GET /disponibilidad/medicos devuelve datos como arreglo de idMedico, nombreMedico, idEspecialidad y nombreEspecialidad. No incluye correos, matriculas, pacientes ni cuentas completas.

GET /disponibilidad/turnos requiere idMedico y fecha AAAA-MM-DD. Devuelve datos como arreglo de idTurno, fechaHoraInicio y fechaHoraFin. Vacio significa que no hay turnos disponibles para esa seleccion; no es una reserva.

### Reservar y modificar cita

POST /citas del Paciente:

```json
{"idTurno": 1, "motivoCita": "Control general"}
```

Recepcionista/Administrador deben enviar ademas idPaciente. Para Paciente, el servidor lo obtiene de la sesion; enviar otro idPaciente produce 403. motivoCita es opcional, hasta 500 caracteres. Reserva exitosa: 201 y detalle en datos.

PATCH /citas/{idCita}/cancelacion:

```json
{"motivoCancelacion": "Cambio de planes"}
```

Motivo hasta 300 caracteres. Obligatorio para Medico; opcional para Administrador, Recepcionista y Paciente, quienes pueden enviar {}. Respuesta 200 con el detalle actualizado. Solo citas vigentes antes del inicio y sin asistencia registrada.

PATCH /citas/{idCita}/reprogramacion:

```json
{"idTurno": 2}
```

El paciente y motivo se conservan desde la cita original. Respuesta 201 con la nueva reserva; idCitaOrigen identifica la anterior. Si falla, la reserva previa sigue vigente.

PATCH /citas/{idCita}/asistencia:

```json
{"asistenciaRegistrada": "asistio"}
```

Valores: asistio o no_asistio. Asistio desde inicio cambia estado a atendida; no_asistio desde fin cambia estado a no_asistio. Respuesta 200. No se puede registrar dos veces ni modificar datos clinicos.

### Datos publicos de una cita

El listado en datos.elementos y el detalle en datos usan:

- Identificadores: idCita, idPaciente, idTurno, idMedico, idCitaOrigen (null si no es reprogramacion).
- Datos basicos: nombrePaciente, cedulaIdentidad, fechaNacimiento, nombreMedico, nombreEspecialidad.
- Reserva: fechaHoraInicio, fechaHoraFin, estadoCita, motivoCita, motivoCancelacion, asistenciaRegistrada.
- Acciones disponibles: puedeCancelar, puedeReprogramar, puedeMarcarAtendida, puedeRegistrarAsistencia, puedeRegistrarInasistencia (booleanos calculados en el servidor).

Las fechas/horas de turnos son locales a la clinica, formato YYYY-MM-DD HH:mm:ss; el frontend no las convierte a la zona del navegador. Frontend y backend usan internamente la hora de Bolivia. historialCambios incluye los responsables y tiempos de cada accion desde 005. No se incluyen datos de autenticacion.

Los booleanos orientan la interfaz. Cada escritura vuelve a comprobar permisos, propiedad, hora y estado. Una cita ajena devuelve 404; un rol sin operacion, 403; turno ocupado, cruce, medico inactivo o cita no modificable, 409; entrada invalida, 422. Sin sesion, 401; sin CSRF, 419.

Desactivar medico o cambiar su especialidad produce 409 si tiene citas pendientes/confirmadas cuyo turno aun no termina. No hay rutas para borrar citas, editar bloques ni registrar consultas clinicas en esta version.

## Endpoints agregados en 1.3.0

| Metodo | Ruta | Permiso / datos |
| --- | --- | --- |
| GET | /disponibilidad/configuracion | Sesion; datos.duracionCitaMinutos, horaApertura, horaCierre y horasPermitidas |
| GET | /agenda/medicos | Administrador/Recepcion: todos, incluidos inactivos; Medico: solo el propio |
| PATCH | /citas/{idCita}/atencion | Administrador, Recepcionista, Medico asignado; JSON {}; cambia a atendida/asistio |
| PATCH | /pacientes/{idPaciente} | Administrador/Recepcion; datos personales y contacto completos, sin claves |
| PATCH | /pacientes/{idPaciente}/estado | Administrador/Recepcion; {"pacienteActivo": false} o true booleano |
| GET | /reportes | Solo Administrador |

GET /pacientes acepta estado vacio, activo o inactivo. Sus objetos agregan sexoPaciente y pacienteActivo. GET /citas acepta idMedico e idPaciente opcionales, siempre intersectados con el alcance del usuario. GET /horarios agrega duracionCitaMinutos a cada bloque. Medicos agrega telefonoMedico a la entrada y salida.

GET /reportes acepta fechaDesde/fechaHasta (AAAA-MM-DD, inclusivas; por defecto inicio de mes y hoy), idMedico, idEspecialidad, estado, paginaCitas y paginaPacientes. Responde datos.filtros, totales (total, programadas, confirmadas, atendidas, canceladas, inasistencias, reprogramadas, pacientesRegistrados), porMedico, porEspecialidad, porEstado, citas, pacientes, medicosDisponibles y especialidadesDisponibles. Cada listado tiene elementos, pagina y hayPaginaSiguiente; veinte filas por pagina. Todas las mutaciones nuevas exigen CSRF.

## Contratos agregados en 1.4.0

Todas estas rutas usan el prefijo /api, sesion vigente y, al escribir, token CSRF. Se aplican permisos en servicio y propiedad de la cita en cada escritura.

| Metodo | Ruta | Acceso |
| --- | --- | --- |
| POST | /horarios/programacion | Solo Administrador |
| GET | /citas/activas | Paciente: propias; Administrador/Recepcionista: idPaciente obligatorio |
| PATCH | /citas/{idCita}/inasistencia | Administrador, Recepcionista y Medico asignado |
| PATCH | /citas/{idCita}/confirmacion | Administrador y Recepcionista |

### Programar un periodo

```json
{"idMedico":1,"fechaInicial":"2027-01-01","fechaFinal":"2027-01-31","diasSemana":[1,2,3,4,5],"horaInicio":"08:00","horaFin":"17:00"}
```

Dias enteros 1 a 7; arreglo no vacio. Selecciona fechas dentro del limite de un ano. La duracion se obtiene de configuracion, nunca del JSON enviado. Se conserva POST /horarios para un dia.

Respuesta 200: mensaje y datos con fechasProcesadas, fechasCreadas, turnosCreados, fechasExistentes, fechasPasadas, conflictosDetectados, diasNoSeleccionados y detalle. Cada elemento de detalle contiene fecha, estado (creada, existente, conflicto u omitida), turnosCreados, mensaje y, solo si se creo, idHorarioMedico. Fechas procesadas cuenta dias seleccionados. Las fechas pasadas se omiten. Cruces esperados no abortan las otras fechas; errores SQL inesperados revierten todo el lote. Repetir conserva los bloques existentes.

### Cita activa y estados

GET /citas/activas devuelve datos como arreglo de citas. Puede contener varias reservas legadas; en ese caso todas deben resolverse. Paciente no puede consultar otro idPaciente; Medico no tiene este endpoint. POST /citas rechaza con 409 una segunda cita pendiente/confirmada, incluso sin cruce. El mismo control se aplica a reprogramacion excluyendo solo la cita original.

PATCH /inasistencia recibe {} y requiere que haya terminado el turno. PATCH /confirmacion recibe {} y requiere cita pendiente y futura. Ambos devuelven 200 con mensaje y cita en datos. /atencion sigue vigente desde el inicio. El endpoint /asistencia sigue siendo administrativo y conserva asistio/no_asistio.

| Etiqueta visible | Valor estadoCita/API/SQL |
| --- | --- |
| Programada | pendiente |
| Confirmada | confirmada |
| Atendida | atendida |
| Cancelada | cancelada |
| Inasistencia | no_asistio |
| Reprogramada (historial existente) | reprogramada |

La cita agrega puedeConfirmar, requiereMotivoCancelacion y explicacionAcciones, ademas de historialCambios. Cada evento contiene estadoAnterior (null al crear), estadoNuevo, asistencia, idUsuario, nombreUsuario, rolUsuario, fechaHora (con desplazamiento horario) y motivoCancelacion. No se inventan eventos para reservas anteriores a 005. Las citas finales no admiten nuevas transiciones.
