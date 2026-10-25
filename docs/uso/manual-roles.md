# Manual por rol y demostracion

Usa cuentas y pacientes ficticios. La clave de las cuatro cuentas del seeder es la elegida en tu .env, no una clave publica fija.

## Administrador

Gestiona Usuarios, Especialidades, Medicos y Pacientes. Programa manana 09:00-10:00: deben aparecer dos turnos de 30 minutos. Repite un periodo y revisa que conserva bloques iguales. Desactivar conserva los registros. En Citas reserva/reprograma/cancela; en Reportes filtra periodo, medico, especialidad y estado. En Bitacora filtra usuario, fechas, modulo, accion y texto. No hay boton de editar evento ni contenido clinico.

## Recepcionista

Registra/busca pacientes y corrige datos personales. Reserva para pacientes activos, reprograma o cancela antes del inicio y registra asistencia desde inicio o inasistencia desde fin. No gestiona catalogos/horarios, reportes o bitacora ni contenido clinico. Datos personales e historia clinica son accesos distintos.

## Medico

Mi agenda muestra solo citas asignadas. Antes del inicio puede cancelar con motivo; desde inicio marcar Atendida; desde fin Inasistencia. Una cita Programada/Atendida iniciada permite Registrar consulta: motivo y diagnostico obligatorios, observaciones opcionales y hasta 20 tratamientos. Cada tratamiento indica medicamento, dosis, frecuencia y duracion; indicaciones son opcionales. Se permite cero tratamientos. La aplicacion registra la decision del medico, no recomienda prescripciones.

Guardar confirma consulta/tratamientos y Atendida juntos. Mis consultas permite abrir o corregir las propias; la version anterior queda disponible. Ante 409, recarga y revisa antes de guardar otra vez. Historial muestra antecedentes y atenciones anteriores del paciente si tienes una cita Programada o Atendida con el; consultas de otros medicos son solo lectura. Actualizar antecedentes requiere versionHistoria.

Para demostrar atencion debe haber llegado el inicio. Prepara la cita ficticia con anticipacion o usa la base de pruebas. No cambies fechas de datos reales.

## Paciente

Revisa tu perfil, elige especialidad/medico/fecha y reserva un turno. La identidad se toma de la sesion. Mientras haya otra Programada no se permite reservar, aunque no coincida en fecha. Mis citas permite ver detalle y cancelar la propia antes del inicio. Para reprogramar solicita a Recepcion. Mi historial permite leer antecedentes, consultas y tratamientos propios, sin editar ni acceder a otra persona por URL.

## Comprobacion manual

Admin/Recepcion no deben abrir consultas clinicas. Paciente ajeno recibe 404. Medico no edita la consulta de otro medico. Dos ventanas con la misma version: la segunda que guarde debe recibir 409. Tras reprogramar y atender el destino, la original sigue Reprogramada y muestra el desenlace. Recargar una consulta no crea duplicados. Revisa el menu y tablas a 320 px.
