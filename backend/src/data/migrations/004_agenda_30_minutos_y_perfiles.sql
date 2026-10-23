-- Ejecutar dentro de una transaccion y con los servidores detenidos.
LOCK TABLE horarios_medicos, turnos, citas IN SHARE ROW EXCLUSIVE MODE;
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS sexo_paciente VARCHAR(20) NOT NULL DEFAULT 'no_especificado';
ALTER TABLE medicos ADD COLUMN IF NOT EXISTS telefono_medico VARCHAR(25) NOT NULL DEFAULT '';
ALTER TABLE horarios_medicos ADD COLUMN IF NOT EXISTS duracion_cita_minutos SMALLINT;
ALTER TABLE turnos ADD COLUMN IF NOT EXISTS duracion_turno_minutos SMALLINT;
-- Las citas existentes conservan sus horas. Se convierte solo la oferta futura.
UPDATE horarios_medicos SET duracion_cita_minutos = CASE
 WHEN fecha_atencion + hora_fin > CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz' THEN 30 ELSE 15 END
 WHERE duracion_cita_minutos IS NULL;
UPDATE turnos SET duracion_turno_minutos = (EXTRACT(EPOCH FROM (fecha_hora_fin-fecha_hora_inicio))/60)::smallint WHERE duracion_turno_minutos IS NULL;
ALTER TABLE horarios_medicos ALTER COLUMN duracion_cita_minutos SET DEFAULT 30;
ALTER TABLE horarios_medicos ALTER COLUMN duracion_cita_minutos SET NOT NULL;
ALTER TABLE turnos ALTER COLUMN duracion_turno_minutos SET DEFAULT 30;
ALTER TABLE turnos ALTER COLUMN duracion_turno_minutos SET NOT NULL;
ALTER TABLE turnos DROP CONSTRAINT IF EXISTS ck_turnos_duracion;
ALTER TABLE turnos ADD CONSTRAINT ck_turnos_duracion CHECK(fecha_hora_fin = fecha_hora_inicio + duracion_turno_minutos * INTERVAL '1 minute');
ALTER TABLE turnos DROP CONSTRAINT IF EXISTS uq_turnos_horario_inicio;
CREATE UNIQUE INDEX IF NOT EXISTS uq_turnos_horario_intervalo ON turnos(id_horario_medico,fecha_hora_inicio,fecha_hora_fin);
DO $$ BEGIN
 IF NOT EXISTS(SELECT 1 FROM pg_constraint WHERE conname='ck_pacientes_sexo' AND conrelid='pacientes'::regclass) THEN
  ALTER TABLE pacientes ADD CONSTRAINT ck_pacientes_sexo CHECK(sexo_paciente IN ('no_especificado','femenino','masculino','otro'));
 END IF;
 IF NOT EXISTS(SELECT 1 FROM pg_constraint WHERE conname='ck_horarios_duracion' AND conrelid='horarios_medicos'::regclass) THEN
  ALTER TABLE horarios_medicos ADD CONSTRAINT ck_horarios_duracion CHECK(duracion_cita_minutos BETWEEN 5 AND 120 AND duracion_cita_minutos % 5 = 0);
 END IF;
 IF NOT EXISTS(SELECT 1 FROM pg_constraint WHERE conname='ck_turnos_minutos' AND conrelid='turnos'::regclass) THEN
  ALTER TABLE turnos ADD CONSTRAINT ck_turnos_minutos CHECK(duracion_turno_minutos BETWEEN 5 AND 120 AND duracion_turno_minutos % 5 = 0);
 END IF;
END $$;
UPDATE turnos SET turno_habilitado=FALSE FROM horarios_medicos h WHERE h.id_horario_medico=turnos.id_horario_medico
 AND turnos.duracion_turno_minutos <> h.duracion_cita_minutos AND turnos.fecha_hora_fin > CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz';
INSERT INTO turnos(id_horario_medico,fecha_hora_inicio,fecha_hora_fin,duracion_turno_minutos)
 SELECT h.id_horario_medico,inicio,inicio+h.duracion_cita_minutos*INTERVAL '1 minute',h.duracion_cita_minutos
 FROM horarios_medicos h CROSS JOIN LATERAL generate_series(h.fecha_atencion+h.hora_inicio,
 h.fecha_atencion+h.hora_fin-h.duracion_cita_minutos*INTERVAL '1 minute',h.duracion_cita_minutos*INTERVAL '1 minute') serie(inicio)
 WHERE inicio > CURRENT_TIMESTAMP AT TIME ZONE 'America/La_Paz'
 ON CONFLICT(id_horario_medico,fecha_hora_inicio,fecha_hora_fin) DO NOTHING;
UPDATE citas SET estado_cita='atendida' WHERE asistencia_registrada='asistio' AND estado_cita IN ('pendiente','confirmada');
CREATE INDEX IF NOT EXISTS idx_usuarios_fecha_registro ON usuarios(fecha_registro_usuario);
CREATE INDEX IF NOT EXISTS idx_citas_estado ON citas(estado_cita);
-- Defensa adicional para reservas con intervalos de distinta duracion.
CREATE OR REPLACE FUNCTION validar_cruces_reserva_cita() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE medico_reserva BIGINT; inicio_reserva TIMESTAMP; fin_reserva TIMESTAMP;
BEGIN
 IF NEW.estado_cita IN ('cancelada','reprogramada') THEN RETURN NEW; END IF;
 SELECT h.id_medico,t.fecha_hora_inicio,t.fecha_hora_fin INTO medico_reserva,inicio_reserva,fin_reserva
 FROM turnos t JOIN horarios_medicos h USING(id_horario_medico) WHERE t.id_turno=NEW.id_turno;
 PERFORM 1 FROM medicos WHERE id_medico=medico_reserva FOR UPDATE;
 PERFORM 1 FROM pacientes WHERE id_paciente=NEW.id_paciente FOR UPDATE;
 IF EXISTS(SELECT 1 FROM citas c JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico)
 WHERE c.id_cita<>NEW.id_cita AND c.id_turno<>NEW.id_turno AND c.estado_cita NOT IN ('cancelada','reprogramada')
 AND (h.id_medico=medico_reserva OR c.id_paciente=NEW.id_paciente)
 AND t.fecha_hora_inicio<fin_reserva AND t.fecha_hora_fin>inicio_reserva) THEN
  RAISE EXCEPTION 'El medico o paciente ya tiene una cita que se cruza.' USING ERRCODE='23P01';
 END IF;
 RETURN NEW;
END $$;
DROP TRIGGER IF EXISTS tr_citas_evitar_cruces ON citas;
CREATE TRIGGER tr_citas_evitar_cruces BEFORE INSERT OR UPDATE OF id_turno,id_paciente,estado_cita ON citas
 FOR EACH ROW EXECUTE FUNCTION validar_cruces_reserva_cita();
