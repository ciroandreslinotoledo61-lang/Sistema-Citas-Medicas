# API de la version 1

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
