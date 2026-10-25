-- Auditoria atomica de metadatos; nunca guardar el cuerpo del formulario.
CREATE TABLE bitacora_sistema (
 id_bitacora BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 id_usuario BIGINT,nombre_usuario VARCHAR(180) NOT NULL,codigo_rol VARCHAR(30) NOT NULL,
 modulo VARCHAR(40) NOT NULL,accion VARCHAR(40) NOT NULL,id_registro BIGINT,
 fecha_hora TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
 resultado VARCHAR(15) NOT NULL CHECK(resultado IN ('exito','rechazado','error')),descripcion VARCHAR(250) NOT NULL
);
COMMENT ON COLUMN bitacora_sistema.id_usuario IS 'Referencia historica sin FK: conservar evidencia aunque la cuenta desaparezca.';
CREATE INDEX ix_bitacora_fecha ON bitacora_sistema(fecha_hora DESC,id_bitacora DESC);
CREATE INDEX ix_bitacora_usuario_fecha ON bitacora_sistema(id_usuario,fecha_hora DESC);
CREATE INDEX ix_bitacora_modulo_accion ON bitacora_sistema(modulo,accion,fecha_hora DESC);
CREATE FUNCTION registrar_evento_bitacora(p_usuario BIGINT,p_modulo TEXT,p_accion TEXT,p_registro BIGINT,p_resultado TEXT,p_descripcion TEXT)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE nombre TEXT; rol TEXT;
BEGIN
 SELECT concat_ws(' ',u.nombres_usuario,u.apellidos_usuario),r.codigo_rol INTO nombre,rol
 FROM usuarios u JOIN roles r USING(id_rol) WHERE u.id_usuario=p_usuario;
 INSERT INTO bitacora_sistema(id_usuario,nombre_usuario,codigo_rol,modulo,accion,id_registro,resultado,descripcion)
 VALUES(p_usuario,COALESCE(nombre,'Sin sesion / sistema'),COALESCE(rol,'sistema'),p_modulo,p_accion,p_registro,p_resultado,p_descripcion);
END $$;
CREATE FUNCTION auditar_operacion_sistema() RETURNS trigger LANGUAGE plpgsql AS $$
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
END $$;
CREATE FUNCTION impedir_modificacion_bitacora() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'La bitacora no permite modificaciones.' USING ERRCODE='42501'; END $$;
CREATE TRIGGER tr_bitacora_inmutable BEFORE UPDATE OR DELETE OR TRUNCATE ON bitacora_sistema FOR EACH STATEMENT EXECUTE FUNCTION impedir_modificacion_bitacora();
REVOKE UPDATE,DELETE,TRUNCATE ON bitacora_sistema FROM PUBLIC;
CREATE TRIGGER tr_usuarios_auditoria AFTER INSERT OR UPDATE OR DELETE ON usuarios FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('usuarios','id_usuario');
CREATE TRIGGER tr_pacientes_auditoria AFTER INSERT OR UPDATE OR DELETE ON pacientes FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('pacientes','id_paciente');
CREATE TRIGGER tr_medicos_auditoria AFTER INSERT OR UPDATE OR DELETE ON medicos FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('medicos','id_medico');
CREATE TRIGGER tr_especialidades_auditoria AFTER INSERT OR UPDATE OR DELETE ON especialidades FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('especialidades','id_especialidad');
CREATE TRIGGER tr_horarios_medicos_auditoria AFTER INSERT OR UPDATE OR DELETE ON horarios_medicos FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('horarios','id_horario_medico');
CREATE TRIGGER tr_citas_auditoria AFTER INSERT OR UPDATE OR DELETE ON citas FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('citas','id_cita');
CREATE TRIGGER tr_consultas_auditoria AFTER INSERT OR UPDATE OR DELETE ON consultas FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('consultas','id_consulta');
CREATE TRIGGER tr_historias_clinicas_auditoria AFTER INSERT OR UPDATE OR DELETE ON historias_clinicas FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('historias_clinicas','id_historia_clinica');
CREATE TRIGGER tr_tratamientos_auditoria AFTER INSERT OR UPDATE OR DELETE ON tratamientos FOR EACH ROW EXECUTE FUNCTION auditar_operacion_sistema('tratamientos','id_tratamiento');
