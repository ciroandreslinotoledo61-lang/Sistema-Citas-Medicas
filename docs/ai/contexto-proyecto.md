# Contexto del proyecto

Version 1.4.0 de Clinica. Stack: PHP 8.2 o posterior, PostgreSQL, PDO pdo_pgsql, HTML, CSS y JavaScript sin framework ni compilacion.

## Implementado

- Autenticacion, sesiones, CSRF y permisos de cuatro roles.
- Usuarios administrativos, pacientes e historia vacia automatica.
- Especialidades y medicos; cuenta y perfil atomicos.
- Horarios futuros sin cruces y turnos de 30 minutos por defecto.
- Reserva, consulta, anulacion, reprogramacion y asistencia.
- Panel y navegacion por rol; controles de propiedad en el backend.
- README con matriz de responsabilidades, casos implementados y pendientes, prioridades y flujos principales.

Paciente significa cliente: reserva y anula solo sus citas; elige medico y turno. Recepcion registra pacientes, gestiona citas y asistencia. Medico consulta solo sus bloques y citas; registra Atendida/Inasistencia y cancela futuras con motivo obligatorio. Administrador gestiona recursos y operaciones administrativas; no escribe informacion clinica por ser administrador.

## Pendiente

Edicion o retiro de bloques, consulta clinica, historial completo, tratamientos, cambio/recuperacion de clave, cambio de rol existente y visor general de bitacora. Consultorios y laboratorio proceden del menu previo y requieren requisitos. No simular estos modulos.

## Reglas para continuar

- Mantener frontend, negocio y datos separados; SQL preparado en repositories.
- Texto ASCII, UTF-8 sin BOM y nombres completos descriptivos.
- No copiar el stack Node/React del proyecto de referencia CADEFAR.
- Conservar .env, credenciales y datos; no ejecutar seeder en una base con usuarios.
- Extender contratos reales y documentar cambios; no inventar endpoints en las pantallas.
- Fechas de turnos expresadas en la zona local de la clinica (America/La_Paz); auditoria con TIMESTAMPTZ.
- La gestion por rol y la propiedad de registros se comprueban en el servidor.

## Actualizar

Leer ACTUALIZAR-ESTA-VERSION.md. Con migraciones 001 a 004 ya instaladas, aplicar solo 005 mediante backend/scripts/actualizar-programacion-mensual.php. No repetir migraciones anteriores ni seeder. Las 11 tablas se conservan. La migracion 004 agrega duracion por bloque/turno, sexo opcional y telefono medico; conserva las horas de las reservas anteriores y ajusta las asistencias a Atendida.

Las pruebas con PHP WebAssembly y un adaptador PDO de QA sobre PostgreSQL embebido, los recorridos de navegador y sus limites se describen en docs/ai/pruebas.md. La configuracion Apache/XAMPP y concurrencia sobre un servidor PostgreSQL independiente deben verificarse en el entorno de instalacion.

Version 1.3 agrega reportes administrativos, edicion completa y estado de pacientes, telefono medico, duracion configurable y filtro de medico. No mostrar zona horaria en pantallas. Ver actualizacion-agenda-reportes.md.

Version 1.4 agrega programacion por periodos, jornada 08:00-17:00, minutos 00/30, una cita activa por paciente y trazabilidad del resultado medico. Conservar todas las reservas previas, incluidas las duplicidades; no habilitar nuevas reservas hasta resolverlas. Consultar programacion-mensual.md.
