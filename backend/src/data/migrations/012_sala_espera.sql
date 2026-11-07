CREATE TABLE sala_espera (
 id_cita BIGINT PRIMARY KEY REFERENCES citas(id_cita),
 estado VARCHAR(20) NOT NULL CHECK(estado IN ('en_espera','en_consulta','atendido')),
 version_espera INTEGER NOT NULL DEFAULT 1 CHECK(version_espera>0),
 id_usuario_actualizacion BIGINT NOT NULL REFERENCES usuarios(id_usuario),
 fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);
CREATE TABLE eventos_sala_espera (
 id_evento BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 id_cita BIGINT NOT NULL REFERENCES citas(id_cita), estado_anterior VARCHAR(20) NOT NULL,
 estado_nuevo VARCHAR(20) NOT NULL, evento VARCHAR(40) NOT NULL,
 id_usuario BIGINT REFERENCES usuarios(id_usuario), fecha TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);
CREATE INDEX idx_eventos_sala_cita ON eventos_sala_espera(id_cita,id_evento);
CREATE FUNCTION proteger_sala_espera() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF TG_OP='DELETE' THEN RAISE EXCEPTION 'La sala conserva su historial.' USING ERRCODE='P2012'; END IF;
 IF TG_OP='INSERT' AND NEW.estado<>'en_espera' THEN RAISE EXCEPTION 'Registre primero la llegada.' USING ERRCODE='P2012'; END IF;
 IF TG_OP='UPDATE' AND (NEW.id_cita<>OLD.id_cita OR NEW.version_espera<>OLD.version_espera+1 OR NOT
  ((OLD.estado='en_espera' AND NEW.estado IN ('en_consulta','atendido')) OR (OLD.estado='en_consulta' AND NEW.estado='atendido'))) THEN
  RAISE EXCEPTION 'Transición de sala inválida.' USING ERRCODE='P2012'; END IF;
 IF TG_OP='INSERT' OR NEW.estado<>OLD.estado THEN
  IF NOT EXISTS(SELECT 1 FROM citas WHERE id_cita=NEW.id_cita AND estado_cita=CASE WHEN NEW.estado='atendido' THEN 'atendida' ELSE 'programada' END) THEN
   RAISE EXCEPTION 'El estado de cita no permite esta transición.' USING ERRCODE='P2012'; END IF;
 END IF;
 NEW.fecha_actualizacion=clock_timestamp();
 RETURN NEW;
END $$;
CREATE TRIGGER tr_sala_transiciones BEFORE INSERT OR UPDATE OR DELETE ON sala_espera FOR EACH ROW EXECUTE FUNCTION proteger_sala_espera();
CREATE FUNCTION registrar_evento_sala() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 INSERT INTO eventos_sala_espera(id_cita,estado_anterior,estado_nuevo,evento,id_usuario)
 VALUES(NEW.id_cita,CASE WHEN TG_OP='INSERT' THEN 'pendiente_llegada' ELSE OLD.estado END,NEW.estado,'cambio_estado',NEW.id_usuario_actualizacion);
 RETURN NEW;
END $$;
CREATE TRIGGER tr_sala_eventos AFTER INSERT OR UPDATE ON sala_espera FOR EACH ROW EXECUTE FUNCTION registrar_evento_sala();
CREATE FUNCTION sincronizar_sala_desde_cita() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF NEW.estado_cita IS DISTINCT FROM OLD.estado_cita THEN
  IF NEW.estado_cita='atendida' THEN
   UPDATE sala_espera SET estado='atendido',version_espera=version_espera+1,
    id_usuario_actualizacion=coalesce(NEW.id_usuario_actualizacion,id_usuario_actualizacion)
    WHERE id_cita=NEW.id_cita AND estado<>'atendido';
  ELSIF NEW.estado_cita IN ('cancelada','reprogramada','no_asistio') AND EXISTS(SELECT 1 FROM sala_espera WHERE id_cita=NEW.id_cita) THEN
   INSERT INTO eventos_sala_espera(id_cita,estado_anterior,estado_nuevo,evento,id_usuario)
    SELECT NEW.id_cita,estado,estado,'cita_'||NEW.estado_cita,NEW.id_usuario_actualizacion FROM sala_espera WHERE id_cita=NEW.id_cita;
  END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_citas_sala AFTER UPDATE OF estado_cita ON citas FOR EACH ROW EXECUTE FUNCTION sincronizar_sala_desde_cita();
CREATE FUNCTION validar_cita_en_consulta() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF NEW.estado_cita IN ('cancelada','reprogramada','no_asistio') AND EXISTS(
  SELECT 1 FROM sala_espera WHERE id_cita=NEW.id_cita AND estado='en_consulta') THEN
  RAISE EXCEPTION 'La atención ya está en consulta; no puede registrarse como ausencia o cancelación.' USING ERRCODE='P2002';
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_citas_sala_coherencia BEFORE UPDATE OF estado_cita ON citas FOR EACH ROW EXECUTE FUNCTION validar_cita_en_consulta();
CREATE FUNCTION conservar_eventos_sala() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'Historial de sala inmutable.' USING ERRCODE='P2012'; END $$;
CREATE TRIGGER tr_eventos_sala_inmutables BEFORE UPDATE OR DELETE ON eventos_sala_espera FOR EACH ROW EXECUTE FUNCTION conservar_eventos_sala();
