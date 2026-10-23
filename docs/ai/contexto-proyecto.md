# Contexto del proyecto

Version 1.2.0 de Clinica. Stack: PHP 8.2 o posterior, PostgreSQL, PDO pdo_pgsql, HTML, CSS y JavaScript sin framework ni compilacion.

## Implementado

- Autenticacion, sesiones, CSRF y permisos de cuatro roles.
- Usuarios administrativos, pacientes e historia vacia automatica.
- Especialidades y medicos; cuenta y perfil atomicos.
- Horarios futuros sin cruces y turnos de 15 minutos.
- Reserva, consulta, anulacion, reprogramacion y asistencia.
- Panel y navegacion por rol; controles de propiedad en el backend.
- README con matriz de responsabilidades, 30 casos implementados y 12 previstos, prioridades y flujos principales.

Paciente significa cliente: reserva y anula solo sus citas; elige medico y turno. Recepcion registra pacientes, gestiona citas y asistencia. Medico consulta solo sus bloques y citas, con datos basicos de esos pacientes. Administrador gestiona recursos y operaciones administrativas; no escribe informacion clinica por ser administrador.

## Pendiente

Edicion o retiro de bloques, consulta clinica, historial completo, tratamientos, reportes, cambio/recuperacion de clave, cambio de rol existente y visor general de bitacora. Consultorios y laboratorio proceden del menu previo y requieren requisitos. No simular estos modulos.

## Reglas para continuar

- Mantener frontend, negocio y datos separados; SQL preparado en repositories.
- Texto ASCII, UTF-8 sin BOM y nombres completos descriptivos.
- No copiar el stack Node/React del proyecto de referencia CADEFAR.
- Conservar .env, credenciales y datos; no ejecutar seeder en una base con usuarios.
- Extender contratos reales y documentar cambios; no inventar endpoints en las pantallas.
- Fechas de turnos expresadas en la zona local de la clinica (America/La_Paz); auditoria con TIMESTAMPTZ.
- La gestion por rol y la propiedad de registros se comprueban en el servidor.

## Actualizar

Leer ACTUALIZAR-ESTA-VERSION.md. Mantener migraciones 001 y 002 y aplicar 003 mediante backend/scripts/actualizar-roles-citas.php. Las 11 tablas se conservan. La migracion nueva agrega asistencia y referencias de auditoria/reprogramacion a citas.

Las pruebas nativas de PHP/PDO con PostgreSQL embebido, los recorridos de navegador y sus limites se describen en docs/ai/pruebas.md. La configuracion Apache/XAMPP y concurrencia sobre un servidor PostgreSQL independiente deben verificarse en el entorno de instalacion.
