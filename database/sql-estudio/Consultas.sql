-- PostgreSQL 18. Solo lectura. Los roles administrativos no autorizan contenido clinico.
BEGIN READ ONLY;

-- 01. Pacientes y cuentas
SELECT p.id_paciente,p.cedula_identidad,u.nombres_usuario,u.apellidos_usuario,u.usuario_activo FROM pacientes p JOIN usuarios u USING(id_usuario) ORDER BY p.id_paciente;

-- 02. Medicos y especialidades
SELECT m.id_medico,u.nombres_usuario,m.telefono_medico,e.nombre_especialidad FROM medicos m JOIN usuarios u USING(id_usuario) JOIN especialidades e USING(id_especialidad);

-- 03. Turnos futuros disponibles con anti-subconsulta
SELECT t.id_turno,h.id_medico,t.fecha_hora_inicio,t.fecha_hora_fin FROM turnos t JOIN horarios_medicos h USING(id_horario_medico) JOIN medicos m USING(id_medico) JOIN usuarios u USING(id_usuario) JOIN especialidades e USING(id_especialidad) WHERE t.turno_habilitado AND u.usuario_activo AND e.especialidad_activa AND t.fecha_hora_inicio>CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz' AND NOT EXISTS(SELECT 1 FROM citas c JOIN turnos ot USING(id_turno) JOIN horarios_medicos oh USING(id_horario_medico) WHERE oh.id_medico=h.id_medico AND c.estado_cita NOT IN ('cancelada','reprogramada') AND ot.fecha_hora_inicio<t.fecha_hora_fin AND ot.fecha_hora_fin>t.fecha_hora_inicio) ORDER BY t.fecha_hora_inicio;

-- 04. Citas programadas
SELECT id_cita,id_paciente,id_turno FROM citas WHERE estado_cita='programada';

-- 05. Cancelaciones
SELECT id_cita,motivo_cancelacion,fecha_actualizacion_cita FROM citas WHERE estado_cita='cancelada';

-- 06. Atenciones e inasistencias
SELECT estado_cita,count(*) cantidad FROM citas WHERE estado_cita IN ('atendida','no_asistio') GROUP BY estado_cita;

-- 07. Citas por medico, incluyendo cero
SELECT m.id_medico,count(c.id_cita) cantidad FROM medicos m LEFT JOIN horarios_medicos h USING(id_medico) LEFT JOIN turnos t USING(id_horario_medico) LEFT JOIN citas c USING(id_turno) GROUP BY m.id_medico;

-- 08. Especialidad historica de la reserva
SELECT e.nombre_especialidad,count(*) cantidad FROM citas c JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico) JOIN medicos m USING(id_medico) JOIN especialidades e ON e.id_especialidad=COALESCE(c.id_especialidad_reserva,m.id_especialidad) GROUP BY e.nombre_especialidad;

-- 09. Reporte del ultimo mes con FILTER
SELECT count(*) total,count(*) FILTER(WHERE estado_cita='atendida') atendidas,count(*) FILTER(WHERE estado_cita='cancelada') canceladas,count(*) FILTER(WHERE estado_cita='no_asistio') inasistencias FROM citas c JOIN turnos t USING(id_turno) WHERE t.fecha_hora_inicio>=(CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz')::date-30 AND t.fecha_hora_inicio<(CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz')::date+1;

-- 10. HAVING: pacientes con varias atenciones
SELECT id_paciente,count(*) atenciones FROM citas WHERE estado_cita='atendida' GROUP BY id_paciente HAVING count(*)>1;

-- 11. Historial de citas administrativo
SELECT c.id_paciente,c.id_cita,c.estado_cita,t.fecha_hora_inicio,h.id_medico,c.id_cita_origen FROM citas c JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico) ORDER BY c.id_paciente,t.fecha_hora_inicio DESC;

-- 12. Pacientes sin citas
SELECT p.id_paciente FROM pacientes p WHERE NOT EXISTS(SELECT 1 FROM citas c WHERE c.id_paciente=p.id_paciente);

-- 13. Historial clinico autorizado. Sustituir ID 0 solo por una identidad ya autenticada. El SQL no autentica al usuario.
WITH actor AS(SELECT u.id_usuario,r.codigo_rol FROM usuarios u JOIN roles r USING(id_rol) WHERE u.id_usuario=0 AND u.usuario_activo) SELECT co.id_consulta,c.id_paciente,co.diagnostico_consulta FROM consultas co JOIN citas c USING(id_cita) JOIN historias_clinicas hc ON hc.id_historia_clinica=co.id_historia_clinica AND hc.id_paciente=c.id_paciente JOIN pacientes p ON p.id_paciente=c.id_paciente CROSS JOIN actor a WHERE (a.codigo_rol='paciente' AND p.id_usuario=a.id_usuario) OR(a.codigo_rol='medico' AND EXISTS(SELECT 1 FROM citas ca JOIN turnos ta USING(id_turno) JOIN horarios_medicos ha USING(id_horario_medico) JOIN medicos ma USING(id_medico) WHERE ca.id_paciente=p.id_paciente AND ma.id_usuario=a.id_usuario AND ca.estado_cita IN ('programada','atendida'))) ORDER BY co.fecha_registro_consulta DESC;

-- 14. Tratamientos: utilizar solo un id_consulta autorizado por la regla anterior
SELECT medicamento_tratamiento,dosis_tratamiento,frecuencia_tratamiento,duracion_tratamiento FROM tratamientos WHERE id_consulta=0;

-- 15. Bitacora: reemplazar 0 por Administrador autenticado
SELECT b.id_bitacora,b.nombre_usuario,b.modulo,b.accion,b.resultado,b.fecha_hora AT TIME ZONE 'America/La_Paz' fecha_bolivia FROM bitacora_sistema b WHERE EXISTS(SELECT 1 FROM usuarios u JOIN roles r USING(id_rol) WHERE u.id_usuario=0 AND u.usuario_activo AND r.codigo_rol='administrador') ORDER BY b.fecha_hora DESC,b.id_bitacora DESC LIMIT 20;

-- 16. Cadena de reprogramaciones con proteccion contra ciclos
WITH RECURSIVE cadena AS(SELECT id_cita origen,id_cita,estado_cita,ARRAY[id_cita] camino FROM citas WHERE estado_cita='reprogramada' UNION ALL SELECT x.origen,c.id_cita,c.estado_cita,x.camino||c.id_cita FROM cadena x JOIN citas c ON c.id_cita_origen=x.id_cita WHERE NOT c.id_cita=ANY(x.camino)) SELECT DISTINCT ON(origen) origen,id_cita ultima_cita,estado_cita resultado FROM cadena ORDER BY origen,cardinality(camino) DESC,id_cita DESC;

-- 17. Excepciones historicas que necesitan resolverse
SELECT id_paciente,count(*) activas FROM citas WHERE estado_cita='programada' GROUP BY id_paciente HAVING count(*)>1;

-- 18. Verificar correspondencia de consultas antiguas: debe estar vacio
SELECT co.id_consulta FROM consultas co JOIN citas c USING(id_cita) JOIN historias_clinicas h USING(id_historia_clinica) WHERE h.id_paciente<>c.id_paciente;

-- 19. Funcion de ventana: acumulado por dia
SELECT fecha,cantidad,sum(cantidad) OVER(ORDER BY fecha) acumulado FROM(SELECT t.fecha_hora_inicio::date fecha,count(*) cantidad FROM citas c JOIN turnos t USING(id_turno) GROUP BY t.fecha_hora_inicio::date) dias ORDER BY fecha;
COMMIT;
