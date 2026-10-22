# API hasta el avance 2 (version 1.1.0)

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
  "matriculaProfesional": "SC-12345",
  "idEspecialidad": 1
}
```

Reemplaza idEspecialidad por uno real obtenido desde /especialidades/activas. El rol se fija en Medico y la cuenta se crea activa; esos valores no se toman del JSON.

Editar medico usa los mismos datos, sin contrasenaUsuario ni confirmacionContrasena. No cambia la clave existente. El correo se valida contra todas las cuentas, independientemente de su rol.

Cambiar estado de medico: `{"medicoActivo": false}`. El estado se refleja en usuarios.usuario_activo y el cambio invalida sesiones anteriores. Intentar activar con especialidad inactiva produce 409.

La ruta /usuarios/{idUsuario}/estado devuelve 409 para cuentas de rol Medico; se debe usar /medicos/{idMedico}/estado.

Los listados de ambos modulos usan busqueda y pagina, con 20 filas por pagina. La busqueda de medicos incluye nombre, apellido, correo, matricula y especialidad. /especialidades/activas devuelve un arreglo de opciones, sin paginacion.
