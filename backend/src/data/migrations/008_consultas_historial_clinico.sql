-- Reutiliza consultas, historias_clinicas y tratamientos. No elimina datos.
ALTER TABLE consultas ADD COLUMN motivo_consulta TEXT NOT NULL DEFAULT '';
ALTER TABLE consultas ADD COLUMN version_consulta INTEGER NOT NULL DEFAULT 1 CHECK(version_consulta>0);
ALTER TABLE consultas ADD COLUMN fecha_actualizacion_consulta TIMESTAMPTZ;
ALTER TABLE consultas ADD COLUMN id_usuario_actualizacion BIGINT REFERENCES usuarios(id_usuario);
ALTER TABLE consultas ADD COLUMN versiones_anteriores JSONB NOT NULL DEFAULT '[]'::jsonb;
ALTER TABLE historias_clinicas ADD COLUMN version_historia INTEGER NOT NULL DEFAULT 1 CHECK(version_historia>0);
ALTER TABLE historias_clinicas ADD COLUMN versiones_anteriores JSONB NOT NULL DEFAULT '[]'::jsonb;
CREATE INDEX ix_consultas_historia_fecha ON consultas(id_historia_clinica,fecha_registro_consulta DESC,id_consulta DESC);
CREATE FUNCTION conservar_version_validar_consulta() RETURNS trigger LANGUAGE plpgsql AS $$
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
END $$;
CREATE TRIGGER tr_consultas_integridad_version BEFORE INSERT OR UPDATE ON consultas FOR EACH ROW EXECUTE FUNCTION conservar_version_validar_consulta();
CREATE FUNCTION conservar_version_historia() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF NEW.id_paciente<>OLD.id_paciente THEN RAISE EXCEPTION 'No trasladar una historia.' USING ERRCODE='P2003'; END IF;
 NEW.version_historia:=OLD.version_historia+1;
 NEW.versiones_anteriores:=OLD.versiones_anteriores || jsonb_build_array((to_jsonb(OLD)-'versiones_anteriores') || jsonb_build_object('fechaCambio',clock_timestamp(),'idUsuario',NULLIF(current_setting('clinica.id_usuario',true),'')::BIGINT));
 RETURN NEW;
END $$;
CREATE TRIGGER tr_historias_version BEFORE UPDATE ON historias_clinicas FOR EACH ROW EXECUTE FUNCTION conservar_version_historia();
