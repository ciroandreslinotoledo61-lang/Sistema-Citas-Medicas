-- Migracion incremental. Aplicar primero en una copia de pruebas con 001-008.
-- No modifica contrasenas existentes ni elimina usuarios o sesiones historicas.
ALTER TABLE usuarios
    ADD COLUMN requiere_cambio_contrasena BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN contrasena_temporal_expira TIMESTAMPTZ,
    ADD COLUMN contrasena_temporal_usada BOOLEAN NOT NULL DEFAULT FALSE,
    ADD CONSTRAINT ck_usuarios_contrasena_temporal CHECK (
        (requiere_cambio_contrasena = TRUE AND contrasena_temporal_expira IS NOT NULL)
        OR (requiere_cambio_contrasena = FALSE AND contrasena_temporal_expira IS NULL AND contrasena_temporal_usada = FALSE)
    );

-- La clave es SHA-256 de un identificador y la operacion: no contiene secretos.
CREATE TABLE intentos_autenticacion (
    clave VARCHAR(64) PRIMARY KEY CHECK (clave ~ '^[a-f0-9]{64}$'),
    intentos INTEGER NOT NULL CHECK (intentos > 0),
    inicio_ventana TIMESTAMPTZ NOT NULL
);
CREATE INDEX ix_intentos_autenticacion_ventana ON intentos_autenticacion(inicio_ventana);
