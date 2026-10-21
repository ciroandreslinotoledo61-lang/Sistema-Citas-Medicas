# Arquitectura y nombres

## Responsabilidad de cada capa

| Capa | Hace | Ejemplo |
| --- | --- | --- |
| presentation/routes | Asocia metodo y URL con la accion; aplica permisos | RutasApi |
| presentation/middlewares | Verifica origen, sesion, rol y CSRF | VerificarRolUsuario |
| presentation/controllers | Lee la entrada y entrega una respuesta JSON | PacienteController |
| business/validators | Valida tipos, formato, longitudes y fechas | PacienteValidator |
| business/services | Coordina reglas y operaciones atomicas | PacienteService |
| data/repositories | Ejecuta SQL con parametros | PacienteRepository |
| data/models | Representa registros y define datos publicos | Usuario, Paciente |
| data/config | Configura una conexion PDO por proceso | ConexionPostgresql |
| shared | Agrupa errores, configuracion y utilidades comunes | ErrorAplicacion |
| frontend/src/api | Comunica las vistas con la API | cliente-api.js |
| frontend/src/routes | Impide servir vistas de otros roles | ProteccionVista |
| frontend/src/features | Agrupa pantallas y acciones por modulo | pacientes |
| frontend/src/components | Reutiliza campos, mensajes y paginacion | campos-contacto.php |

## Convenciones

| Elemento | Convencion | Ejemplo |
| --- | --- | --- |
| Clase | PascalCase; entidad y responsabilidad | HistoriaClinicaRepository |
| Metodo o funcion | camelCase; verbo y objeto completo | obtenerPacientePorIdUsuario |
| Variable | camelCase; contenido reconocible | usuarioAutenticado |
| Tabla PostgreSQL | snake_case en plural | historias_clinicas |
| Campo PostgreSQL | snake_case explicito | fecha_apertura_historia |
| Clave primaria y foranea | id mas entidad completa | id_historia_clinica |
| URL | minusculas y guiones | /api/autenticacion/iniciar-sesion |
| Archivo de clase PHP | mismo nombre que la clase | PacienteService.php |
| Archivo funcional JS/PHP | minusculas y guiones | registrar-paciente.js |

No se usan nombres como `getData`, `doIt`, `repo1`, `u` o `p` para representar operaciones o entidades principales. Los sufijos Controller, Service, Repository y Validator se mantienen por consistencia con CADEFAR.

## Ejemplo para explicar el registro

1. El formulario envia JSON a `POST /api/autenticacion/registro-paciente`.
2. Los middlewares comprueban origen y token CSRF.
3. PacienteController lee el JSON.
4. PacienteService llama a validarDatosRegistroPaciente.
5. El servicio calcula el hash y abre una transaccion.
6. UsuarioRepository inserta la cuenta con rol Paciente.
7. PacienteRepository inserta el perfil.
8. HistoriaClinicaRepository crea la historia vacia.
9. El servicio confirma la transaccion y el controlador devuelve 201.
10. Si falla una insercion, el servicio revierte toda la operacion.

## Sesion y permisos

El frontend renderiza las vistas privadas solo despues de consultar la sesion al backend. El backend vuelve a comprobar los permisos en cada solicitud. Ocultar un enlace no concede ni reemplaza autorizacion.

La sesion guarda el id del usuario y una version de credenciales. En cada acceso se comprueba en la base que la cuenta siga activa y que la version coincida. Desactivar o reactivar una cuenta incrementa esa version, por lo que las sesiones anteriores dejan de ser validas.

Las mutaciones requieren un token CSRF. Login y logout regeneran el identificador de sesion. Las respuestas privadas usan Cache-Control no-store.

## Limites de la V1

El limite de intentos de login es por sesion y sirve como control basico local; borrar la cookie permite iniciar otra ventana. Un despliegue publico necesita limitacion persistente por cuenta/origen, registro de eventos y monitoreo. El servidor PHP incorporado y las cuentas de prueba se usan solo en desarrollo.

La base por si sola no exige que todo paciente tenga historia: esa regla se garantiza en la transaccion del servicio. La FK y el UNIQUE garantizan que una historia apunte a un paciente valido y que no existan dos historias para el mismo paciente.
