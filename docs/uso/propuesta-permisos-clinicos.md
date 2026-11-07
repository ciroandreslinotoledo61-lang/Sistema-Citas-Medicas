# Propuesta de autorizacion clinica administrativa

Estado: propuesta para revision; NO implementada ni habilitada.

## Politica actual conservada

- Paciente: consulta exclusivamente su historia y consultas, mediante identidad
  de sesion y filtros SQL. No puede buscar personas ni editar contenido clinico.
- Medico: consulta pacientes con relacion de atencion (cita Programada o
  Atendida); solo modifica consultas propias y antecedentes autorizados.
- Administrador: gestiona cuentas, citas, reportes y bitacora. NO tiene permiso
  `historial.consultar` ni `consultas.escribir` y no recibe diagnosticos,
  tratamientos o antecedentes por las rutas clinicas.
- Recepcionista: gestion administrativa de pacientes y reservas, sin contenido
  clinico confidencial ni facultad de autorizar su consulta.

El requisito inicial contemplaba acceso administrativo autorizado. El modelo
existente no define esa autorizacion y no se ha ampliado por pertenecer al rol
administrador. Las pruebas de rutas y HTTP aseguran que siga denegado.

## Modelo propuesto para una futura aprobacion

1. Crear un permiso independiente, por ejemplo `historial.consultar_autorizado`,
   denegado por defecto. No reutilizar `usuarios.gestionar` como permiso clinico.
2. Registrar concesiones individuales con administrador destinatario, paciente
   o ambito concreto, finalidad, autorizador, fecha de inicio, vencimiento y
   revocacion. Prohibir autoaprobacion. Definir con la clinica quien puede
   concederlas antes de habilitar una ruta de administracion de permisos.
3. Limitar inicialmente a lectura. La escritura y correccion de consultas
   permanece en el medico autorizado; no habilitarla con el permiso de lectura.
4. Resolver el permiso en backend y en el alcance SQL para cada solicitud,
   incluyendo listados, detalles y cualquier futura exportacion. Una concesion
   vencida o revocada debe dejar de servir en la siguiente solicitud.
5. Auditar concesion, revocacion, lecturas y denegaciones con actor, referencia
   del registro, permiso usado, fecha y resultado. Evitar diagnosticos,
   tratamientos, contrasenas y tokens en la bitacora y los logs.
6. Probar dos administradores con ambitos diferentes, acceso fuera de ambito,
   revocacion inmediata, vencimiento, autoaprobacion y sesion desactivada.
   Mantener pruebas negativas para recepcionista y otros pacientes.

## Decision pendiente

La clinica debe revisar finalidad, autorizadores, alcance, duracion y mecanismo
de revision de accesos. Esta propuesta no crea tablas, rutas, permisos efectivos
ni cambios de roles. Su implementacion requiere un encargo posterior explicito.
