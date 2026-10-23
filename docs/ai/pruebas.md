# Pruebas de Clinica - version 1.3.0

## Validaciones y permisos sin base de datos

Desde la raiz del proyecto:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
php backend/tests/test_validaciones_agenda.php
php backend/tests/test_validaciones_actualizacion.php
```

La prueba de agenda realiza 85 comprobaciones de identificadores, fechas reales, anos bisiestos, tipos incorrectos, turnos de 30 minutos, limites temporales, roles desconocidos, cuentas inactivas y matriz de permisos.

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
php backend/scripts/actualizar-agenda-reportes.php
php backend/scripts/cargar-datos-iniciales.php
php backend/tests/test_registro_paciente.php
php backend/tests/test_gestion_especialidades_medicos.php
php backend/tests/test_gestion_citas.php
php backend/tests/test_reportes_perfiles.php
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

1. Solo Administrador crea bloques. Cruces rechazados, bloques contiguos admitidos y cuatro turnos por dos horas.
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
| Sintaxis | Archivos PHP analizados con TOKEN_PARSE; modulos JavaScript comprobados con node --check |
| Validaciones | Cinco scripts sin base aprobados; agenda: 85 comprobaciones; actualizacion: 15 rechazos de entradas invalidas |
| Servicios y SQL | Cuatro scripts de integracion aprobados con PHP 8.2 WebAssembly y adaptador PDO de QA sobre PGlite; agenda: 49 comprobaciones; reportes/perfiles: 30 |
| Migracion | 004 probada desde esquema 001-003 con reservas antiguas; mantiene horas e IDs, convierte Asistio, genera intervalos completos de 30 y no duplica al repetirse |
| Conflictos | SQL rechaza cruce con reserva anterior de 15; anulacion libera; CHECK rechaza duracion incoherente |
| Reportes | Totales sobre 22 citas y dos paginas, filtros, estados, registros al final del dia en Bolivia, rechazo a roles ajenos |
| Perfiles | Edicion completa, rollback por CI duplicada, conservacion de clave/rol/historia, desactivacion y rechazo de reservas nuevas |
| Navegador | Chromium con vistas PHP renderizadas y API PHP real mediante adaptador PDO de QA: reportes y paginacion, edicion/estado de pacientes, filtro de medico, reserva de 30, telefono medico y atencion |
| Seguridad HTTP | Sesiones de cuatro roles, CSRF 419 y permisos 403 en nuevas rutas comprobados |
| Responsive | Seis vistas a 1440, 768, 390 y 320 px: sin desbordamiento de pagina ni excepciones JavaScript; tablas con desplazamiento horizontal |

El adaptador PDO se usa solo en herramientas temporales de QA, no se entrega ni altera el runtime del proyecto. Las vistas se renderizaron con usuarios de prueba; la autenticacion de su API si se ejercito. No se probo el cliente cURL del frontend ni el despliegue Apache/XAMPP con PDO nativo en esta revision.

PGlite ejecuta PostgreSQL embebido, pero no demuestra concurrencia real entre conexiones independientes. Los bloqueos, las sesiones PHP nativas y la integracion completa de instalacion deben verificarse en tu equipo siguiendo el procedimiento siguiente. No se usaron datos reales.

## Concurrencia y entorno de instalacion: comprobacion pendiente

En PostgreSQL nativo, con la base de pruebas y dos sesiones independientes:

1. Crear un bloque futuro y preparar dos pacientes distintos con el mismo idTurno.
2. Enviar las dos reservas al mismo tiempo desde dos procesos/servidores PHP independientes. Debe haber una sola confirmada y un conflicto 409; comprobar tambien la tabla citas.
3. Repetir con un mismo paciente y dos turnos que se cruzan de medicos distintos. Debe confirmarse solo una.
4. Intentar crear dos bloques superpuestos del mismo medico simultaneamente. Debe guardarse solo uno con todos sus turnos.
5. Reprogramar mientras otra sesion reserva el nuevo turno. Solo una operacion debe ocuparlo; si falla reprogramar, la cita original debe seguir vigente.
6. Repetir reservas simultaneas con un intervalo nuevo de 30 minutos que cruce una reserva anterior de 15; debe permitirse solo una ocupacion.
7. Comprobar en Apache/XAMPP cookies, CSRF, permisos, fechas y ambos .env siguiendo el recorrido del README.

El servidor incorporado php -S con un unico proceso atiende solicitudes en serie: dos pestanas contra ese unico proceso no son una prueba suficiente de concurrencia entre conexiones.

## Antes del commit

Verifica la migracion 004 y el recorrido del README. Revisa git diff, conserva tus .env y confirma que no se incluyan credenciales ni bases de datos. Consulta ACTUALIZAR-ESTA-VERSION.md para una instalacion existente.
