-- Ejecutar con psql -X -v ON_ERROR_STOP=1 en la base sql de estudio. No es un endpoint publico.

-- 01. Reservar: medico -> paciente -> turno. Auditoria en la misma transaccion.
BEGIN ISOLATION LEVEL READ COMMITTED;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;
DO $$ DECLARE paciente BIGINT; medico BIGINT; turno BIGINT; actor BIGINT;
BEGIN
 SELECT id_usuario INTO STRICT actor FROM usuarios WHERE correo_usuario='recepcionista@ejemplo.test';PERFORM set_config('clinica.id_usuario',actor::text,true);
 SELECT id_medico INTO STRICT medico FROM medicos WHERE matricula_profesional='SQL-M001' FOR UPDATE;
 SELECT id_paciente INTO STRICT paciente FROM pacientes WHERE cedula_identidad='SQL-P001' FOR UPDATE;
 IF EXISTS(SELECT 1 FROM citas WHERE id_paciente=paciente AND estado_cita='programada') THEN RAISE EXCEPTION 'Cita activa existente.'; END IF;
 SELECT t.id_turno INTO STRICT turno FROM turnos t JOIN horarios_medicos h USING(id_horario_medico) WHERE h.id_medico=medico AND t.turno_habilitado AND t.fecha_hora_inicio>CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz' AND NOT EXISTS(SELECT 1 FROM citas c WHERE c.id_turno=t.id_turno AND c.estado_cita NOT IN ('cancelada','reprogramada')) ORDER BY t.fecha_hora_inicio LIMIT 1 FOR UPDATE OF t;
 INSERT INTO citas(id_paciente,id_turno,id_usuario_registro) VALUES(paciente,turno,actor);
END $$;
COMMIT;

-- 02. Cancelar antes del inicio, sin eliminar el historial.
BEGIN;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;
SELECT set_config('clinica.id_usuario',(SELECT id_usuario::text FROM usuarios WHERE correo_usuario='recepcionista@ejemplo.test'),true);
SELECT c.id_cita FROM citas c JOIN pacientes p USING(id_paciente) WHERE p.cedula_identidad='SQL-P001' AND c.estado_cita='programada' FOR UPDATE OF c;
UPDATE citas SET estado_cita='cancelada',motivo_cancelacion='Ejercicio SQL',id_usuario_actualizacion=(SELECT id_usuario FROM usuarios WHERE correo_usuario='recepcionista@ejemplo.test'),fecha_actualizacion_cita=clock_timestamp()
 WHERE id_paciente=(SELECT id_paciente FROM pacientes WHERE cedula_identidad='SQL-P001') AND estado_cita='programada' AND id_turno IN(SELECT id_turno FROM turnos WHERE fecha_hora_inicio>CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz');
COMMIT;

-- 03. Reprogramar: crear original de prueba, liberar y enlazar sucesora; ROLLBACK revierte todo.
BEGIN;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;
DO $$ DECLARE paciente BIGINT; medico BIGINT; turno BIGINT; actor BIGINT;
BEGIN
 SELECT id_usuario INTO STRICT actor FROM usuarios WHERE correo_usuario='recepcionista@ejemplo.test';PERFORM set_config('clinica.id_usuario',actor::text,true);
 SELECT id_medico INTO STRICT medico FROM medicos WHERE matricula_profesional='SQL-M001' FOR UPDATE;
 SELECT id_paciente INTO STRICT paciente FROM pacientes WHERE cedula_identidad='SQL-P001' FOR UPDATE;
 IF EXISTS(SELECT 1 FROM citas WHERE id_paciente=paciente AND estado_cita='programada') THEN RAISE EXCEPTION 'Cita activa existente.'; END IF;
 SELECT t.id_turno INTO STRICT turno FROM turnos t JOIN horarios_medicos h USING(id_horario_medico) WHERE h.id_medico=medico AND t.turno_habilitado AND t.fecha_hora_inicio>CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz' AND NOT EXISTS(SELECT 1 FROM citas c WHERE c.id_turno=t.id_turno AND c.estado_cita NOT IN ('cancelada','reprogramada')) ORDER BY t.fecha_hora_inicio LIMIT 1 FOR UPDATE OF t;
 INSERT INTO citas(id_paciente,id_turno,id_usuario_registro) VALUES(paciente,turno,actor);
END $$;
DO $$ DECLARE origen citas%ROWTYPE; nuevo_turno BIGINT;
BEGIN
 SELECT c.* INTO STRICT origen FROM citas c JOIN pacientes p USING(id_paciente) WHERE p.cedula_identidad='SQL-P001' AND c.estado_cita='programada' FOR UPDATE OF c;
 SELECT t.id_turno INTO STRICT nuevo_turno FROM turnos t WHERE t.id_turno<>origen.id_turno AND t.turno_habilitado AND t.fecha_hora_inicio>CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz' AND NOT EXISTS(SELECT 1 FROM citas c WHERE c.id_turno=t.id_turno AND c.estado_cita NOT IN ('cancelada','reprogramada')) ORDER BY t.fecha_hora_inicio LIMIT 1 FOR UPDATE;
 UPDATE citas SET estado_cita='reprogramada',id_usuario_actualizacion=origen.id_usuario_registro WHERE id_cita=origen.id_cita;
 INSERT INTO citas(id_paciente,id_turno,id_cita_origen,id_usuario_registro) VALUES(origen.id_paciente,nuevo_turno,origen.id_cita,origen.id_usuario_registro);
END $$;
SELECT id_cita,id_cita_origen,estado_cita FROM citas ORDER BY id_cita DESC LIMIT 2;
ROLLBACK;

-- 04. Documentar una cita atendida que todavia no tiene consulta.
BEGIN;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;
DO $$ DECLARE cita BIGINT; historia BIGINT; consulta BIGINT; actor BIGINT;
BEGIN
 SELECT id_usuario INTO STRICT actor FROM usuarios WHERE correo_usuario='medico@ejemplo.test';PERFORM set_config('clinica.id_usuario',actor::text,true);
 SELECT c.id_cita,hc.id_historia_clinica INTO STRICT cita,historia FROM citas c JOIN historias_clinicas hc USING(id_paciente) JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico) JOIN medicos m USING(id_medico)
 WHERE m.id_usuario=actor AND c.estado_cita='atendida' AND NOT EXISTS(SELECT 1 FROM consultas co WHERE co.id_cita=c.id_cita) ORDER BY c.id_cita LIMIT 1 FOR UPDATE OF c;
 INSERT INTO consultas(id_cita,id_historia_clinica,motivo_consulta,diagnostico_consulta,id_usuario_actualizacion) VALUES(cita,historia,'Ejercicio SQL','Diagnostico ficticio',actor) RETURNING id_consulta INTO consulta;
 INSERT INTO tratamientos(id_consulta,medicamento_tratamiento,dosis_tratamiento,frecuencia_tratamiento,duracion_tratamiento) VALUES(consulta,'Intervencion ficticia','Ejemplo','Ejemplo','Ejemplo');
END $$;
COMMIT;

-- 05. Evento administrativo explicito, sin cuerpo de formularios.
BEGIN;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;
SELECT registrar_evento_bitacora((SELECT id_usuario FROM usuarios WHERE correo_usuario='administrador@ejemplo.test'),'pruebas','verificar',NULL,'exito','Ejercicio terminado.');
COMMIT;

-- 06. Subtransaccion y manejo de un error de unicidad.
BEGIN;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;
DO $$ BEGIN
 BEGIN INSERT INTO especialidades(nombre_especialidad) VALUES('Medicina general');RAISE EXCEPTION 'Falto rechazar duplicado.';
 EXCEPTION WHEN unique_violation THEN RAISE NOTICE 'Duplicado rechazado; bloque interno revertido.';
 END;
END $$;
ROLLBACK;
-- Concurrencia multiproceso: backend/tests/test_reserva_concurrente.php con PHP CLI y PostgreSQL nativos.
-- El segundo proceso espera por la fila bloqueada y al continuar rechaza el turno ya ocupado.
