-- Solo simulaciones. No almacena cuentas bancarias ni información de tarjetas.
CREATE TABLE pagos_demostracion (
 id_pago BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 id_cita BIGINT NOT NULL REFERENCES citas(id_cita),
 monto_centavos BIGINT NOT NULL CHECK(monto_centavos BETWEEN 1 AND 999999999),
 moneda CHAR(3) NOT NULL DEFAULT 'BOB' CHECK(moneda='BOB'),
 metodo VARCHAR(20) NOT NULL CHECK(metodo IN ('qr_demo','paypal_demo')),
 referencia VARCHAR(50) NOT NULL UNIQUE CHECK(referencia LIKE 'DEMO-%'),
 estado VARCHAR(10) NOT NULL DEFAULT 'pendiente' CHECK(estado IN ('pendiente','pagado','cancelado')),
 es_simulado BOOLEAN NOT NULL DEFAULT TRUE CHECK(es_simulado),
 creado_por BIGINT NOT NULL REFERENCES usuarios(id_usuario),
 fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
 fecha_confirmacion TIMESTAMPTZ, fecha_cancelacion TIMESTAMPTZ,
 CHECK ((estado='pendiente' AND fecha_confirmacion IS NULL AND fecha_cancelacion IS NULL)
  OR (estado='pagado' AND fecha_confirmacion IS NOT NULL AND fecha_cancelacion IS NULL)
  OR (estado='cancelado' AND fecha_confirmacion IS NULL AND fecha_cancelacion IS NOT NULL))
);
CREATE UNIQUE INDEX uq_pago_demo_cita_activo ON pagos_demostracion(id_cita) WHERE estado IN ('pendiente','pagado');
CREATE INDEX idx_pago_demo_fecha ON pagos_demostracion(fecha_confirmacion) WHERE estado='pagado';
CREATE FUNCTION proteger_pago_demo() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF TG_OP='DELETE' THEN RAISE EXCEPTION 'Los pagos simulados se conservan.' USING ERRCODE='P2013'; END IF;
 IF TG_OP='INSERT' AND (NEW.estado<>'pendiente' OR NOT EXISTS(
   SELECT 1 FROM citas c JOIN pacientes p USING(id_paciente) WHERE c.id_cita=NEW.id_cita
    AND p.id_usuario=NEW.creado_por AND c.estado_cita IN ('programada','atendida'))) THEN
  RAISE EXCEPTION 'El pago simulado requiere una cita propia válida y estado pendiente.' USING ERRCODE='P2013'; END IF;
 IF TG_OP='UPDATE' AND (OLD.estado<>'pendiente' OR NEW.estado NOT IN ('pagado','cancelado') OR
   (to_jsonb(NEW)-ARRAY['estado','fecha_confirmacion','fecha_cancelacion']) IS DISTINCT FROM
   (to_jsonb(OLD)-ARRAY['estado','fecha_confirmacion','fecha_cancelacion'])) THEN
  RAISE EXCEPTION 'Pago finalizado o modificación inválida.' USING ERRCODE='P2013'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER tr_pagos_demo_inmutables BEFORE INSERT OR UPDATE OR DELETE ON pagos_demostracion FOR EACH ROW EXECUTE FUNCTION proteger_pago_demo();
