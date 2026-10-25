-- Funciones reales de las migraciones. Solo ejecutar sobre una copia de pruebas actualizada.
BEGIN;
DO $$ BEGIN IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo bases aisladas terminadas en _pruebas.'; END IF; END $$;

-- 01. auditar_operacion_sistema: Registra solo identificador, accion y actor.
CREATE OR REPLACE FUNCTION public.auditar_operacion_sistema()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE registro JSONB; responsable BIGINT; actividad TEXT;
BEGIN
 registro:=CASE WHEN TG_OP='DELETE' THEN to_jsonb(OLD) ELSE to_jsonb(NEW) END;
 responsable:=NULLIF(current_setting('clinica.id_usuario',true),'')::BIGINT;
 IF responsable IS NULL AND TG_OP='INSERT' AND TG_TABLE_NAME IN ('usuarios','pacientes') THEN responsable:=(registro->>'id_usuario')::BIGINT; END IF;
 IF responsable IS NULL AND TG_TABLE_NAME='citas' THEN
  responsable:=CASE WHEN TG_OP='INSERT' THEN (registro->>'id_usuario_registro')::BIGINT ELSE (registro->>'id_usuario_actualizacion')::BIGINT END;
 END IF;
 actividad:=CASE TG_OP WHEN 'INSERT' THEN 'crear' WHEN 'DELETE' THEN 'eliminar' ELSE 'editar' END;
 IF TG_TABLE_NAME='citas' THEN
  IF TG_OP='INSERT' THEN actividad:='reservar';
  ELSIF TG_OP='UPDATE' AND NEW.estado_cita IS DISTINCT FROM OLD.estado_cita THEN
   actividad:=CASE NEW.estado_cita WHEN 'cancelada' THEN 'cancelar' WHEN 'reprogramada' THEN 'reprogramar' WHEN 'atendida' THEN 'atender' WHEN 'no_asistio' THEN 'inasistencia' ELSE 'cambiar_estado' END;
  END IF;
 END IF;
 PERFORM registrar_evento_bitacora(responsable,TG_ARGV[0],actividad,(registro->>TG_ARGV[1])::BIGINT,'exito','Operacion '||actividad||' en '||TG_ARGV[0]||'.');
 RETURN COALESCE(NEW,OLD);
END $function$
;

-- 02. conservar_version_historia: Conserva antecedentes anteriores e impide cambiar de paciente.
CREATE OR REPLACE FUNCTION public.conservar_version_historia()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
BEGIN
 IF NEW.id_paciente<>OLD.id_paciente THEN RAISE EXCEPTION 'No trasladar una historia.' USING ERRCODE='P2003'; END IF;
 NEW.version_historia:=OLD.version_historia+1;
 NEW.versiones_anteriores:=OLD.versiones_anteriores || jsonb_build_array((to_jsonb(OLD)-'versiones_anteriores') || jsonb_build_object('fechaCambio',clock_timestamp(),'idUsuario',NULLIF(current_setting('clinica.id_usuario',true),'')::BIGINT));
 RETURN NEW;
END $function$
;

-- 03. conservar_version_validar_consulta: Valida paciente/cita y conserva consulta y tratamientos anteriores.
CREATE OR REPLACE FUNCTION public.conservar_version_validar_consulta()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE paciente_cita BIGINT; paciente_historia BIGINT; estado TEXT; tratamientos_previos JSONB;
BEGIN
 SELECT id_paciente,estado_cita INTO paciente_cita,estado FROM citas WHERE id_cita=NEW.id_cita FOR UPDATE;
 SELECT id_paciente INTO paciente_historia FROM historias_clinicas WHERE id_historia_clinica=NEW.id_historia_clinica;
 IF paciente_cita IS DISTINCT FROM paciente_historia OR estado NOT IN ('programada','atendida') THEN
  RAISE EXCEPTION 'La consulta debe corresponder al paciente y a una cita valida.' USING ERRCODE='P2003';
 END IF;
 IF TG_OP='UPDATE' THEN
  IF NEW.id_cita<>OLD.id_cita OR NEW.id_historia_clinica<>OLD.id_historia_clinica THEN RAISE EXCEPTION 'No trasladar una consulta.' USING ERRCODE='P2003'; END IF;
  SELECT COALESCE(jsonb_agg(to_jsonb(t) ORDER BY id_tratamiento),'[]'::jsonb) INTO tratamientos_previos FROM tratamientos t WHERE id_consulta=OLD.id_consulta;
  NEW.versiones_anteriores:=OLD.versiones_anteriores || jsonb_build_array((to_jsonb(OLD)-'versiones_anteriores') || jsonb_build_object('tratamientos',tratamientos_previos));
  NEW.version_consulta:=OLD.version_consulta+1;NEW.fecha_registro_consulta:=OLD.fecha_registro_consulta;NEW.fecha_actualizacion_consulta:=clock_timestamp();
 ELSE NEW.version_consulta:=1;NEW.versiones_anteriores:='[]'::jsonb;
 END IF;
 RETURN NEW;
END $function$
;

-- 04. impedir_modificacion_bitacora: Rechaza UPDATE, DELETE y TRUNCATE.
CREATE OR REPLACE FUNCTION public.impedir_modificacion_bitacora()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
BEGIN RAISE EXCEPTION 'La bitacora no permite modificaciones.' USING ERRCODE='42501'; END $function$
;

-- 05. registrar_evento_bitacora: Guarda identidad historica, nunca contrasenas ni texto clinico.
CREATE OR REPLACE FUNCTION public.registrar_evento_bitacora(p_usuario bigint, p_modulo text, p_accion text, p_registro bigint, p_resultado text, p_descripcion text)
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE nombre TEXT; rol TEXT;
BEGIN
 SELECT concat_ws(' ',u.nombres_usuario,u.apellidos_usuario),r.codigo_rol INTO nombre,rol
 FROM usuarios u JOIN roles r USING(id_rol) WHERE u.id_usuario=p_usuario;
 INSERT INTO bitacora_sistema(id_usuario,nombre_usuario,codigo_rol,modulo,accion,id_registro,resultado,descripcion)
 VALUES(p_usuario,COALESCE(nombre,'Sin sesion / sistema'),COALESCE(rol,'sistema'),p_modulo,p_accion,p_registro,p_resultado,p_descripcion);
END $function$
;

-- 06. validar_cruces_reserva_cita: Bloqueos y rechazo de solapamientos (23P01).
CREATE OR REPLACE FUNCTION public.validar_cruces_reserva_cita()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE medico_reserva BIGINT; inicio_reserva TIMESTAMP; fin_reserva TIMESTAMP;
BEGIN
 IF NEW.estado_cita IN ('cancelada','reprogramada') THEN RETURN NEW; END IF;
 SELECT h.id_medico,t.fecha_hora_inicio,t.fecha_hora_fin INTO medico_reserva,inicio_reserva,fin_reserva
 FROM turnos t JOIN horarios_medicos h USING(id_horario_medico) WHERE t.id_turno=NEW.id_turno;
 PERFORM 1 FROM medicos WHERE id_medico=medico_reserva FOR UPDATE;
 PERFORM 1 FROM pacientes WHERE id_paciente=NEW.id_paciente FOR UPDATE;
 IF EXISTS(SELECT 1 FROM citas c JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico)
 WHERE c.id_cita<>NEW.id_cita AND c.id_turno<>NEW.id_turno AND c.estado_cita NOT IN ('cancelada','reprogramada')
 AND (h.id_medico=medico_reserva OR (c.id_paciente=NEW.id_paciente AND c.estado_cita IN ('programada')))
 AND t.fecha_hora_inicio<fin_reserva AND t.fecha_hora_fin>inicio_reserva) THEN
  RAISE EXCEPTION 'El medico o paciente ya tiene una cita que se cruza.' USING ERRCODE='23P01';
 END IF;
 RETURN NEW;
END $function$
;

-- 07. validar_politica_y_registrar_cambio_cita: Una cita activa, transiciones validas e historial de cambios.
CREATE OR REPLACE FUNCTION public.validar_politica_y_registrar_cambio_cita()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE nueva_activa BOOLEAN; validar_activa BOOLEAN; registrar_cambio BOOLEAN;
 responsable BIGINT; nombre_responsable TEXT; rol_responsable TEXT; estado_anterior TEXT;
BEGIN
 nueva_activa := NEW.estado_cita IN ('programada');
 IF TG_OP='INSERT' THEN
  NEW.excepcion_cita_activa_previa := FALSE;
  NEW.historial_cambios := '[]'::jsonb;
  validar_activa := nueva_activa;
  registrar_cambio := TRUE;
  responsable := NEW.id_usuario_registro;
  estado_anterior := NULL;
 ELSE
  -- No se permite convertir una nueva reserva en una excepcion de migracion.
  NEW.excepcion_cita_activa_previa := OLD.excepcion_cita_activa_previa
   AND nueva_activa AND OLD.estado_cita IN ('programada');
  NEW.historial_cambios := OLD.historial_cambios;
  IF NEW.id_paciente<>OLD.id_paciente OR NEW.id_turno<>OLD.id_turno THEN
   RAISE EXCEPTION 'Conserva la cita original; utiliza la reprogramacion.' USING ERRCODE='P2002';
  END IF;
  IF NEW.estado_cita<>OLD.estado_cita AND NOT (
   (OLD.estado_cita='programada' AND NEW.estado_cita IN ('atendida','cancelada','no_asistio','reprogramada'))) THEN
   RAISE EXCEPTION 'La transicion de estado no esta permitida.' USING ERRCODE='P2002';
  END IF;
  validar_activa := nueva_activa AND OLD.estado_cita NOT IN ('programada');
  registrar_cambio := NEW.estado_cita IS DISTINCT FROM OLD.estado_cita
   OR NEW.asistencia_registrada IS DISTINCT FROM OLD.asistencia_registrada
   OR NEW.motivo_cancelacion IS DISTINCT FROM OLD.motivo_cancelacion;
  IF OLD.estado_cita IN ('atendida','cancelada','no_asistio','reprogramada') AND registrar_cambio THEN
   RAISE EXCEPTION 'El resultado final no se puede modificar.' USING ERRCODE='P2002';
  END IF;
  responsable := NEW.id_usuario_actualizacion;
  estado_anterior := OLD.estado_cita;
 END IF;
 IF validar_activa THEN
  PERFORM 1 FROM pacientes WHERE id_paciente=NEW.id_paciente FOR UPDATE;
  IF EXISTS (SELECT 1 FROM citas WHERE id_paciente=NEW.id_paciente AND id_cita<>NEW.id_cita
             AND estado_cita IN ('programada')) THEN
   RAISE EXCEPTION 'El paciente ya tiene una cita activa.' USING ERRCODE='P2001';
  END IF;
 END IF;
 IF registrar_cambio THEN
  IF (NEW.estado_cita='atendida' AND NEW.asistencia_registrada<>'asistio') OR
     (NEW.estado_cita='no_asistio' AND NEW.asistencia_registrada<>'no_asistio') OR
     (NEW.estado_cita IN ('programada','cancelada','reprogramada') AND NEW.asistencia_registrada<>'pendiente') THEN
   RAISE EXCEPTION 'El estado y la asistencia no coinciden.' USING ERRCODE='P2002';
  END IF;
  SELECT concat_ws(' ',u.nombres_usuario,u.apellidos_usuario), r.codigo_rol
    INTO nombre_responsable,rol_responsable FROM usuarios u JOIN roles r USING(id_rol)
    WHERE u.id_usuario=responsable;
  NEW.historial_cambios := NEW.historial_cambios || jsonb_build_array(jsonb_build_object(
   'estadoAnterior',estado_anterior,'estadoNuevo',NEW.estado_cita,'asistencia',NEW.asistencia_registrada,
   'idUsuario',responsable,'nombreUsuario',nombre_responsable,'rolUsuario',rol_responsable,
   'fechaHora',clock_timestamp(),'motivoCancelacion',NEW.motivo_cancelacion));
 END IF;
 RETURN NEW;
END $function$
;

-- Los triggers ya existen: consultar las asociaciones sin duplicarlos.
SELECT tgname,pg_get_triggerdef(oid) FROM pg_trigger WHERE NOT tgisinternal AND tgrelid IN(SELECT oid FROM pg_class WHERE relnamespace='public'::regnamespace);
-- Procedimiento didactico de diagnostico. No escribe contenido clinico.
CREATE OR REPLACE PROCEDURE verificar_integridad_clinica_pruebas() LANGUAGE plpgsql AS $$
BEGIN
 IF current_database() NOT LIKE '%\_pruebas' ESCAPE '\' THEN RAISE EXCEPTION 'Solo pruebas.'; END IF;
 IF EXISTS(SELECT 1 FROM consultas co JOIN citas c USING(id_cita) JOIN historias_clinicas h USING(id_historia_clinica) WHERE c.id_paciente<>h.id_paciente) THEN RAISE EXCEPTION 'Historia incorrecta.'; END IF;
 RAISE NOTICE 'Integridad clinica verificada.';
END $$;
CALL verificar_integridad_clinica_pruebas();
COMMIT;
