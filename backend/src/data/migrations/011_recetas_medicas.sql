-- Incremental. No convierte ni modifica registros existentes.
CREATE TABLE recetas_medicas (
 id_receta BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 id_consulta BIGINT NOT NULL REFERENCES consultas(id_consulta),
 id_paciente BIGINT NOT NULL REFERENCES pacientes(id_paciente),
 id_medico BIGINT NOT NULL REFERENCES medicos(id_medico),
 id_receta_anterior BIGINT UNIQUE REFERENCES recetas_medicas(id_receta),
 datos_emision JSONB NOT NULL CHECK(jsonb_typeof(datos_emision)='object'),
 medicamentos JSONB NOT NULL CHECK(jsonb_typeof(medicamentos)='array' AND jsonb_array_length(medicamentos) BETWEEN 1 AND 20),
 fecha_emision TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
 estado VARCHAR(10) NOT NULL DEFAULT 'emitida' CHECK(estado IN ('emitida','anulada')),
 motivo_anulacion VARCHAR(500), anulada_por BIGINT REFERENCES usuarios(id_usuario), fecha_anulacion TIMESTAMPTZ,
 CHECK ((estado='emitida' AND motivo_anulacion IS NULL AND anulada_por IS NULL AND fecha_anulacion IS NULL)
     OR (estado='anulada' AND motivo_anulacion IS NOT NULL AND length(btrim(motivo_anulacion))>0 AND anulada_por IS NOT NULL AND fecha_anulacion IS NOT NULL))
);
CREATE INDEX idx_recetas_consulta ON recetas_medicas(id_consulta,id_receta);
CREATE INDEX idx_recetas_paciente ON recetas_medicas(id_paciente,id_receta);
CREATE FUNCTION proteger_receta_emitida() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE paciente BIGINT; medico BIGINT;
BEGIN
 IF TG_OP='DELETE' THEN RAISE EXCEPTION 'Las recetas se conservan; anule con motivo.' USING ERRCODE='P2011'; END IF;
 IF TG_OP='INSERT' THEN
  SELECT c.id_paciente,h.id_medico INTO paciente,medico FROM consultas co JOIN citas c USING(id_cita)
   JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico) WHERE co.id_consulta=NEW.id_consulta;
  IF paciente IS DISTINCT FROM NEW.id_paciente OR medico IS DISTINCT FROM NEW.id_medico OR NEW.estado<>'emitida' THEN
   RAISE EXCEPTION 'Relaciones de receta inválidas.' USING ERRCODE='P2011'; END IF;
  IF NEW.id_receta_anterior IS NOT NULL AND NOT EXISTS(SELECT 1 FROM recetas_medicas r WHERE r.id_receta=NEW.id_receta_anterior AND r.id_consulta=NEW.id_consulta AND r.estado='anulada') THEN
   RAISE EXCEPTION 'La corrección requiere receta anterior anulada de la misma consulta.' USING ERRCODE='P2011'; END IF;
 ELSE
  IF OLD.estado<>'emitida' OR NEW.estado<>'anulada' OR
    (to_jsonb(NEW)-ARRAY['estado','motivo_anulacion','anulada_por','fecha_anulacion']) IS DISTINCT FROM
    (to_jsonb(OLD)-ARRAY['estado','motivo_anulacion','anulada_por','fecha_anulacion']) THEN
   RAISE EXCEPTION 'No se permite modificar una receta emitida.' USING ERRCODE='P2011'; END IF;
  IF NOT EXISTS(SELECT 1 FROM medicos m WHERE m.id_medico=OLD.id_medico AND m.id_usuario=NEW.anulada_por) THEN
   RAISE EXCEPTION 'Solo el autor puede anular la receta.' USING ERRCODE='P2011'; END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_recetas_inmutables BEFORE INSERT OR UPDATE OR DELETE ON recetas_medicas FOR EACH ROW EXECUTE FUNCTION proteger_receta_emitida();
