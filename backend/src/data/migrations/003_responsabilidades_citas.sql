-- Agrega trazabilidad y asistencia; conserva las 11 tablas y los registros.
ALTER TABLE citas ADD COLUMN IF NOT EXISTS asistencia_registrada VARCHAR(12) NOT NULL DEFAULT 'pendiente';
ALTER TABLE citas ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(300) NOT NULL DEFAULT '';
ALTER TABLE citas ADD COLUMN IF NOT EXISTS id_usuario_registro BIGINT REFERENCES usuarios(id_usuario);
ALTER TABLE citas ADD COLUMN IF NOT EXISTS id_usuario_actualizacion BIGINT REFERENCES usuarios(id_usuario);
ALTER TABLE citas ADD COLUMN IF NOT EXISTS fecha_actualizacion_cita TIMESTAMPTZ;
ALTER TABLE citas ADD COLUMN IF NOT EXISTS id_especialidad_reserva BIGINT REFERENCES especialidades(id_especialidad);
ALTER TABLE citas ADD COLUMN IF NOT EXISTS id_cita_origen BIGINT REFERENCES citas(id_cita);
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_citas_asistencia' AND conrelid = 'citas'::regclass) THEN
        ALTER TABLE citas ADD CONSTRAINT ck_citas_asistencia
            CHECK (asistencia_registrada IN ('pendiente', 'asistio', 'no_asistio'));
    END IF;
END $$;
CREATE INDEX IF NOT EXISTS idx_horarios_medico_fecha ON horarios_medicos(id_medico, fecha_atencion);
CREATE INDEX IF NOT EXISTS idx_turnos_inicio ON turnos(fecha_hora_inicio);
CREATE INDEX IF NOT EXISTS idx_citas_origen ON citas(id_cita_origen);
