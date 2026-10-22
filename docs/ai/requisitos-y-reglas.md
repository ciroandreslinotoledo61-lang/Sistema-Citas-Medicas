# Requisitos y reglas de negocio

## Requisitos funcionales

| ID | Requisito | Estado |
| --- | --- | --- |
| RF-01 | Iniciar y cerrar sesion de usuarios activos | V1 |
| RF-02 | Restringir las acciones por rol | V1 |
| RF-03 | Administrar datos basicos y estado de cuentas | V1 |
| RF-04 | Registrar paciente junto a usuario e historia | V1 |
| RF-05 | Buscar pacientes y actualizar su contacto | V1 |
| RF-06 | Permitir al paciente ver su propio perfil | V1 |
| RF-07 | Gestionar especialidades y medicos | Avance 2 |
| RF-08 | Crear horarios y generar turnos de 15 minutos | Pendiente |
| RF-09 | Reservar y cancelar citas sin duplicar turno | Pendiente |
| RF-10 | Registrar consulta y tratamientos | Pendiente |
| RF-11 | Consultar historial clinico autorizado | Pendiente |
| RF-12 | Generar reportes administrativos | Pendiente |

## Reglas implementadas

- El registro publico asigna siempre el rol Paciente, aunque se envie otro rol en el JSON.
- Correo normalizado en minusculas y CI normalizada en mayusculas son unicos.
- El registro completo se confirma o se revierte; no queda una cuenta suelta si la CI esta duplicada.
- La fecha de nacimiento debe existir, no ser futura y no superar 120 anos de antiguedad.
- El contacto de emergencia se completa con nombre y telefono, o ambos vacios.
- Solo Administrador crea cuentas administrativas, edita sus datos o cambia estados.
- Solo Administrador y Recepcionista listan pacientes y modifican sus contactos.
- El paciente consulta su perfil a partir del usuario de sesion, nunca de un id enviado por el cliente.
- Ninguna cuenta puede cambiar su propio estado; debe quedar al menos un administrador activo.
- El rol se mantiene fijo al editar una cuenta para evitar perfiles incompatibles.
- El seeder requiere una base sin usuarios y no restablece claves existentes.
- Los listados entregan 20 filas por pagina y permiten buscar.

## Requisitos no funcionales

| ID | Requisito | Implementacion o limite |
| --- | --- | --- |
| RNF-01 | Separacion de responsabilidades | Capas y nombres documentados |
| RNF-02 | Claves protegidas | password_hash y password_verify |
| RNF-03 | Consultas parametrizadas | PDO sin preparaciones emuladas |
| RNF-04 | Sesion protegida | HttpOnly, SameSite=Lax, expiracion por inactividad y regeneracion |
| RNF-05 | Formularios protegidos | Token CSRF en solicitudes de cambio |
| RNF-06 | Salida segura | Escape HTML, textContent y datos publicos sin hash |
| RNF-07 | Interfaz adaptable | CSS para celular y escritorio |
| RNF-08 | Configuracion externa | .env ignorados por Git |
| RNF-09 | Integridad | FK, UNIQUE, CHECK y transacciones |
| RNF-10 | Entrega explicable | README, diccionario, API, pruebas y plan Git |

## Reglas a completar antes de reservar citas

El avance actual no reserva citas. El indice parcial evita duplicar un turno en estados que lo ocupan, pero el siguiente avance tambien debe bloquear el turno en una transaccion, validar su fecha, disponibilidad y medico activo, y controlar conflictos de horarios.

Se debe definir y probar la politica de cancelacion, reprogramacion, no asistencia, acceso medico al historial y conservacion de datos antes de implementar sus pantallas.

## Reglas del avance 2

- Solo Administrador gestiona especialidades y medicos, tanto en las vistas como en la API.
- El nombre de especialidad es unico sin distinguir mayusculas y al normalizar espacios; tambien se consideran las inactivas.
- Las matriculas se guardan en mayusculas y son unicas al normalizar espacios.
- Registrar un medico crea cuenta y perfil en una sola transaccion, con rol Medico fijo.
- Crear o reasignar un medico requiere una especialidad activa.
- Un medico inactivo puede conservar su especialidad actual inactiva al corregir nombre, correo o matricula.
- No se puede desactivar una especialidad que tenga medicos activos.
- Activar un medico requiere que su especialidad este activa.
- El estado del medico se toma de usuarios.usuario_activo. El cambio invalida sesiones anteriores mediante version_credenciales.
- Usuarios dirige los cambios de estado de cuentas medicas al modulo Medicos, evitando saltarse la regla de especialidad.
- No hay borrado fisico de medicos ni especialidades desde la aplicacion.
- La desactivacion de especialidad y la asignacion/activacion de medicos bloquean la misma fila de especialidad dentro de sus transacciones.
