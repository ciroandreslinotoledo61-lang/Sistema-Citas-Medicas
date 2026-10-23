# Pruebas de Clinica - version 1.2.0

## Validaciones y permisos sin base de datos

Desde la raiz del proyecto:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
php backend/tests/test_validaciones_agenda.php
```

La prueba de agenda realiza 70 comprobaciones de identificadores, fechas reales, anos bisiestos, tipos incorrectos, intervalos de 15 minutos, limites temporales, roles desconocidos, cuentas inactivas y matriz de permisos.

## Integracion con una base separada

Crea una base exclusiva de pruebas, desde pgAdmin conectado a postgres:

```sql
CREATE DATABASE citas_medicas_pruebas WITH ENCODING 'UTF8';
```

En PowerShell, desde la raiz:

```powershell
$env:DB_NAME = "citas_medicas_pruebas"
php backend/scripts/migrar-base-datos.php
php backend/scripts/actualizar-especialidades-medicos.php
php backend/scripts/actualizar-roles-citas.php
php backend/scripts/cargar-datos-iniciales.php
php backend/tests/test_registro_paciente.php
php backend/tests/test_gestion_especialidades_medicos.php
php backend/tests/test_gestion_citas.php
Remove-Item Env:DB_NAME
```

DB_NAME del entorno tiene prioridad sobre backend/.env. Los demas datos de conexion y SEED_USERS_PASSWORD proceden del archivo, salvo que los definas tambien como variables del sistema. Si vaciaste SEED_USERS_PASSWORD despues de instalar, configura una clave de prueba antes de cargar la base nueva.

Si la base de pruebas ya existe, aplica solo las migraciones pendientes y ejecuta los tests; no repitas la migracion inicial ni el seeder. En Linux/macOS usa export DB_NAME=citas_medicas_pruebas y despues unset DB_NAME.

Las pruebas de integracion se rechazan si DB_NAME no termina en _pruebas. Crean sus propios registros con identificadores unicos y eliminan esos registros al terminar; no utilizan pacientes reales. No ejecutes otras pruebas a la vez: test_gestion_citas.php agrega temporalmente una restriccion ligada a su propia cita para forzar un fallo y comprobar el rollback, y la retira en finally.

## Cobertura de integracion

- test_registro_paciente.php: registro atomico de usuario/paciente/historia, rol fijo y rollback por CI duplicada.
- test_gestion_especialidades_medicos.php: altas, edicion, unicidad normalizada, especialidad activa, estado medico, rol fijo, busqueda e invalidacion de sesion.
- test_gestion_citas.php: 49 comprobaciones de horarios, citas, permisos y estados.

La prueba de citas comprueba:

1. Solo Administrador crea bloques. Cruces rechazados, bloques contiguos admitidos y ocho turnos por dos horas.
2. El Medico consulta solo sus bloques; idMedico en el filtro no evade ese alcance.
3. Reserva propia del Paciente e identificacion de paciente desde Recepcion.
4. Rechazo de reserva para otra persona, citas ajenas y acceso a la agenda de otro medico.
5. Rechazo de doble ocupacion desde el servicio y directamente por el indice unico SQL.
6. Rechazo de dos citas del mismo paciente con medicos diferentes a la misma hora.
7. Reprogramacion solo administrativa; trazabilidad mediante idCitaOrigen.
8. Conservacion de la reserva original si el turno nuevo esta ocupado.
9. Rollback completo ante un fallo SQL forzado al insertar la nueva cita, despues de modificar la anterior.
10. Anulacion propia, conservacion del registro y reutilizacion de su turno.
11. Rechazo de anulacion iniciada o repetida, reserva pasada y turno inhabilitado.
12. Asistencia desde el inicio e inasistencia desde el fin; no se duplican ni equivalen a registrar consulta.
13. Medico inactivo sin disponibilidad; desactivacion rechazada cuando quedan citas vigentes futuras.

## Revision ejecutada en esta entrega

| Comprobacion | Resultado y alcance |
| --- | --- |
| Sintaxis y archivos | 99 archivos PHP y 28 JavaScript sin errores de sintaxis; textos ASCII y enlaces locales del README validos |
| PHP | Pruebas ejecutadas con PHP 8.3.6 nativo, pdo_pgsql, mbstring, curl y ctype |
| Validaciones y permisos | Las cuatro pruebas sin base aprobaron; agenda: 70 comprobaciones |
| Integracion | Las tres pruebas aprobaron con los servicios reales y PDO nativo contra PostgreSQL embebido PGlite; agenda: 49 comprobaciones |
| Migraciones | 001, 002 y 003 aplicadas; 003 ejecutada dos veces sin borrar datos ni recrear tablas |
| API y navegador | 55 comprobaciones aprobadas con Chromium, backend PHP real, frontend PHP real y sesiones/cURL nativos |
| Roles | Login de los cuatro roles, menus distintos, vistas 403, API 403 y alcance propio con 404 ante citas ajenas |
| Reservas | Crear horario, elegir medico/fecha/turno, reservar, reprogramar desde Recepcion y anular desde Paciente |
| Asistencia | Registro en navegador desde Recepcion y rechazo de operaciones no autorizadas |
| CSRF | Escritura sin token rechazada con 419 |
| Salida HTML | Un motivo con etiquetas se muestra como texto, sin ejecutar scripts |
| Interfaz | Reserva a 390 px sin desbordamiento; revision visual de paneles y detalle; sin excepciones JavaScript ni violaciones CSP |
| Regresion | Listados de Usuarios, Pacientes, Medicos y Especialidades siguen cargando |
| Concurrencia real | Pendiente sobre un servidor PostgreSQL independiente con conexiones simultaneas |
| Apache/XAMPP | Pendiente de ejecutar en el equipo de instalacion; no se verifico un despliegue Apache |

PGlite usa el motor PostgreSQL embebido y un adaptador de protocolo de red. En esta revision no se reemplazaron la clase PDO, los servicios, el SQL, los middlewares ni el cliente cURL del proyecto. Su multiplexor no reproduce completamente las conexiones y bloqueos de un servidor PostgreSQL independiente: las pruebas de conflicto secuencial y rollback no demuestran por si solas la concurrencia real.

Las herramientas de revision no son dependencias de la aplicacion y no se incluyen en el paquete. No se usaron registros reales. Para revisar asistencia en navegador se movieron fechas exclusivamente dentro de la base efimera de pruebas.

## Concurrencia y entorno de instalacion: comprobacion pendiente

En PostgreSQL nativo, con la base de pruebas y dos sesiones independientes:

1. Crear un bloque futuro y preparar dos pacientes distintos con el mismo idTurno.
2. Enviar las dos reservas al mismo tiempo desde dos procesos/servidores PHP independientes. Debe haber una sola confirmada y un conflicto 409; comprobar tambien la tabla citas.
3. Repetir con un mismo paciente y dos turnos que se cruzan de medicos distintos. Debe confirmarse solo una.
4. Intentar crear dos bloques superpuestos del mismo medico simultaneamente. Debe guardarse solo uno con todos sus turnos.
5. Reprogramar mientras otra sesion reserva el nuevo turno. Solo una operacion debe ocuparlo; si falla reprogramar, la cita original debe seguir vigente.
6. Comprobar en Apache/XAMPP cookies, CSRF, permisos, fechas y ambos .env siguiendo el recorrido del README.

El servidor incorporado php -S con un unico proceso atiende solicitudes en serie: dos pestanas contra ese unico proceso no son una prueba suficiente de concurrencia entre conexiones.

## Antes del commit

Verifica la migracion 003 y el recorrido del README. Revisa git diff, conserva tus .env y confirma que no se incluyan credenciales ni bases de datos. Consulta ACTUALIZAR-ESTA-VERSION.md para una instalacion existente.
