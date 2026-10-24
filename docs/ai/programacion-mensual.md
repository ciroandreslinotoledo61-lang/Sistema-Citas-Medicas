# Version 1.4: programacion, reservas y gestion medica

## Punto de partida

Se trabajo sobre Sistema-Citas-Medicas-main(1).zip, version 1.3 con migraciones 001 a 004. Ya existian usuarios, pacientes, medicos, especialidades, agenda diaria, reserva/cancelacion/reprogramacion, Atendida, asistencia administrativa y reportes. Se ampliaron esas capas; no se reconstruyo el frontend.

## Resultado

| Solicitud | Estado | Implementacion |
| --- | --- | --- |
| Programacion mensual o de cualquier periodo | Implementada | CU-20: fechas inicial/final, dias 1-7, medico y horas; resumen por fecha |
| Turnos consecutivos | Implementada | Duracion actual 30; 08:00-17:00 genera 18 y 08:00-12:00 genera 8 |
| Jornada general configurable | Implementada | ConfiguracionAgenda, 08:00-17:00 por defecto; selects con minutos 00/30 |
| Repetir programacion | Implementada | Bloqueo del medico, deteccion de equivalentes/cruces; no regenera ni sobrescribe |
| Una sola cita activa | Implementada | CU-23/CU-24: bloqueo del paciente, comprobacion PHP, trigger y UNIQUE parcial |
| Atencion e inasistencia medica | Implementada | CU-28/CU-29: medico asignado, inicio/fin y estado validados |
| Cancelacion medica | Implementada | CU-26: cita propia futura, motivo obligatorio, actor y tiempo |
| Operaciones de Recepcion | Conservadas | Reserva, reprogramacion, cancelacion, asistencia e inasistencia |
| Confirmacion de Programada | Implementada | CU-43: Administrador/Recepcionista antes del inicio |
| Mi agenda e historial de cambios | Implementada | CU-25/CU-30; datos propios y acciones calculadas en servidor |
| Bitacora general CU-40 | Parcial | Historial por cita desde 005; falta un visor de todas las operaciones |
| Correccion administrativa de resultado final | Pendiente | No hay operacion para revertir Atendida/Inasistencia/Cancelada |
| Consulta clinica, historia completa y tratamientos | Pendientes | CU-32 a CU-35; marcar Atendida no crea diagnostico ni tratamiento |

## Reglas de programacion

Los periodos pueden cruzar meses. El maximo es 366 dias y la fecha final debe estar dentro del siguiente ano, conservando el limite existente de agenda. DiasSemana usa enteros: lunes=1, domingo=7. Se descartan los dias no seleccionados. Si una fecha ya comenzo, se omite el bloque completo; no se crean intervalos pasados ni jornadas parciales inesperadas.

Para cada fecha seleccionada: equivalente existente se conserva, cruce con otro bloque se informa, fecha valida sin cruce se crea. El resumen distingue fechas procesadas, creadas, existentes, pasadas, dias no seleccionados y conflictos. No se rellenan ni sustituyen turnos de bloques legados. Si ocurre un fallo SQL inesperado, se revierte el lote; repetir es seguro.

La regla se aplica a altas nuevas. Horarios previos fuera de 08:00-17:00 y reservas de otras duraciones se conservan. No se modifica su fecha para hacerlos encajar. La duracion configurada se guarda por bloque/turno; cambiarla solo afecta futuras altas. Actualmente la seleccion de extremos sigue siendo 00/30, incluso si en una etapa posterior se cambia la duracion.

## Estados, permisos y tiempo

| Estado actual | Transicion | Quien | Momento |
| --- | --- | --- | --- |
| Programada | Confirmada | Administrador / Recepcionista | Antes del inicio |
| Programada / Confirmada | Atendida | Medico asignado / Administrador / Recepcionista | Desde el inicio |
| Programada / Confirmada | Inasistencia | Medico asignado / Administrador / Recepcionista | Desde el fin |
| Programada / Confirmada | Cancelada | Medico asignado / Paciente propietario / Administrador / Recepcionista | Antes del inicio |
| Programada / Confirmada | Reprogramada + nueva Confirmada | Administrador / Recepcionista | Antes del inicio, nueva reserva valida |
| Atendida / Inasistencia / Cancelada / Reprogramada | Ninguna | Ninguno | Estados finales |

La nueva reserva sigue naciendo Confirmada, como en 1.3. Programada corresponde al valor interno pendiente; Inasistencia corresponde a no_asistio. Reprogramada se conserva como estado historico para no perder la funcion existente. El reloj no cambia el estado automaticamente.

Cancelar exige motivo no vacio para Medico. Para los demas roles sigue siendo opcional y admite hasta 300 caracteres. El turno cancelado vuelve a estar disponible si aun es futuro y cumple las reglas. Atendida e Inasistencia liberan la regla de cita activa del paciente; conservan la ocupacion historica del turno del medico.

## Duplicidades anteriores y migracion

005 no cancela, fusiona ni elimina citas activas anteriores. Solo marca excepcion_cita_activa_previa en las reservas pendientes/confirmadas de pacientes que ya tienen varias. Ese campo no se ofrece en API ni formulario y el trigger impide falsificarlo. El indice UNIQUE parcial protege nuevas reservas; el trigger tambien comprueba las exceptuadas.

Mientras quede una cita activa, no se permite una nueva. Si quedan varias, deben resolverse con acciones validas: cancelar antes del inicio, atender desde el inicio o marcar inasistencia desde el fin. No marcar un resultado que no haya ocurrido. Reprogramar una cita legada tambien se bloquea si hay otra activa. La confirmacion de una Programada legada se admite porque no aumenta el numero de reservas.

El script de instalacion informa cuantos pacientes conservan varias activas. Para localizarlos, un administrador de base puede ejecutar esta consulta de solo lectura:

```sql
SELECT id_paciente, count(*) AS citas_activas,
       array_agg(id_cita ORDER BY id_cita) AS codigos_citas
FROM citas
WHERE estado_cita IN ('pendiente', 'confirmada')
GROUP BY id_paciente
HAVING count(*) > 1;
```

Desde Citas se puede abrir el historial con /citas?idPaciente=ID. No se deben borrar registros ni deshabilitar el indice para reservar otra cita. Cuando un caso historico no se pueda resolver con informacion fiable, requiere una decision administrativa; no se inventa asistencia.

005 agrega historial_cambios JSONB. Guarda estado previo/nuevo, asistencia, motivo, usuario, nombre/rol y hora del servidor. El arreglo previo se conserva en cada UPDATE y solo se agregan eventos de cambio. Los registros anteriores comienzan sin eventos; sus responsables previos siguen en las columnas de 003. No es una bitacora contra administradores de base con privilegios para desactivar triggers.

## Instalacion y comprobacion

Ver [ACTUALIZAR-ESTA-VERSION.md](../../ACTUALIZAR-ESTA-VERSION.md). En la base actual con 004 solo ejecutar actualizar-programacion-mensual.php. El paquete no incluye .env, contrasenas, .git ni copias de la base. No ejecuta commits ni push.

Los resultados ejecutados y sus limites estan en [pruebas.md](pruebas.md). La concurrencia entre procesos PHP/PDO nativos queda pendiente de ejecutar en Windows con test_reserva_concurrente.php. El esquema y las reglas SQL si se ejercitaron en PGlite; eso no reemplaza esa prueba.
