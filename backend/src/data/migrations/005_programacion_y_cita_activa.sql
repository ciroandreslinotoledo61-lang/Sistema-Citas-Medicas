-- Aplicar una sola transaccion, despues de 004, con ambos servidores detenidos.
-- No modifica horarios, contrasenas ni estados de citas existentes.
LOCK TABLE pacientes, citas IN SHARE ROW EXCLUSIVE MODE;
DO $$
BEGIN
 IF NOT EXISTS (SELECT 1 FROM pg_attribute WHERE attrelid='citas'::regclass
                AND attname='excepcion_cita_activa_previa' AND NOT attisdropped) THEN
  ALTER TABLE citas ADD COLUMN excepcion_cita_activa_previa BOOLEAN NOT NULL DEFAULT FALSE;
  -- Solo las duplicidades anteriores a 005 quedan exceptuadas del indice.
  UPDATE citas SET excepcion_cita_activa_previa=TRUE
   WHERE estado_cita IN ('pendiente','confirmada') AND id_paciente IN (
    SELECT id_paciente FROM citas WHERE estado_cita IN ('pendiente','confirmada')
    GROUP BY id_paciente HAVING count(*)>1);
 END IF;
END $$;
ALTER TABLE citas ADD COLUMN IF NOT EXISTS historial_cambios JSONB NOT NULL DEFAULT '[]'::jsonb;
CREATE UNIQUE INDEX IF NOT EXISTS uq_citas_paciente_activa ON citas(id_paciente)
 WHERE estado_cita IN ('pendiente','confirmada') AND NOT excepcion_cita_activa_previa;

CREATE OR REPLACE FUNCTION validar_politica_y_registrar_cambio_cita() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE nueva_activa BOOLEAN; validar_activa BOOLEAN; registrar_cambio BOOLEAN;
 responsable BIGINT; nombre_responsable TEXT; rol_responsable TEXT; estado_anterior TEXT;
BEGIN
 nueva_activa := NEW.estado_cita IN ('pendiente','confirmada');
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
   AND nueva_activa AND OLD.estado_cita IN ('pendiente','confirmada');
  NEW.historial_cambios := OLD.historial_cambios;
  IF NEW.id_paciente<>OLD.id_paciente OR NEW.id_turno<>OLD.id_turno THEN
   RAISE EXCEPTION 'Conserva la cita original; utiliza la reprogramacion.' USING ERRCODE='P2002';
  END IF;
  IF NEW.estado_cita<>OLD.estado_cita AND NOT (
   (OLD.estado_cita='pendiente' AND NEW.estado_cita IN ('confirmada','atendida','cancelada','no_asistio','reprogramada')) OR
   (OLD.estado_cita='confirmada' AND NEW.estado_cita IN ('atendida','cancelada','no_asistio','reprogramada'))) THEN
   RAISE EXCEPTION 'La transicion de estado no esta permitida.' USING ERRCODE='P2002';
  END IF;
  validar_activa := nueva_activa AND OLD.estado_cita NOT IN ('pendiente','confirmada');
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
             AND estado_cita IN ('pendiente','confirmada')) THEN
   RAISE EXCEPTION 'El paciente ya tiene una cita activa.' USING ERRCODE='P2001';
  END IF;
 END IF;
 IF registrar_cambio THEN
  IF (NEW.estado_cita='atendida' AND NEW.asistencia_registrada<>'asistio') OR
     (NEW.estado_cita='no_asistio' AND NEW.asistencia_registrada<>'no_asistio') OR
     (NEW.estado_cita IN ('pendiente','confirmada','cancelada','reprogramada') AND NEW.asistencia_registrada<>'pendiente') THEN
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
END $$;
DROP TRIGGER IF EXISTS tr_citas_politica_y_trazabilidad ON citas;
CREATE TRIGGER tr_citas_politica_y_trazabilidad BEFORE INSERT OR UPDATE ON citas
 FOR EACH ROW EXECUTE FUNCTION validar_politica_y_registrar_cambio_cita();

-- Una cita final conserva la ocupacion del medico, pero ya no bloquea al paciente.
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
 AND (h.id_medico=medico_reserva OR (c.id_paciente=NEW.id_paciente AND c.estado_cita IN ('pendiente','confirmada')))
 AND t.fecha_hora_inicio<fin_reserva AND t.fecha_hora_fin>inicio_reserva) THEN
  RAISE EXCEPTION 'El medico o paciente ya tiene una cita que se cruza.' USING ERRCODE='23P01';
 END IF;
 RETURN NEW;
END $$;
