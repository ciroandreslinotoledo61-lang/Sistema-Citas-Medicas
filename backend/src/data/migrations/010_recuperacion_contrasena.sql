-- Migracion incremental posterior a 009. No cambia cuentas ni claves existentes.
-- Unicamente almacena hashes; un nuevo enlace reemplaza al anterior de la cuenta.
CREATE TABLE recuperaciones_contrasena (
    id_usuario BIGINT PRIMARY KEY REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL UNIQUE CHECK (token_hash ~ '^[a-f0-9]{64}$'),
    correo_hash VARCHAR(64) NOT NULL CHECK (correo_hash ~ '^[a-f0-9]{64}$'),
    version_credenciales INTEGER NOT NULL CHECK (version_credenciales > 0),
    creado_en TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    expira_en TIMESTAMPTZ NOT NULL,
    usado_en TIMESTAMPTZ,
    CONSTRAINT ck_recuperacion_vigencia CHECK (expira_en > creado_en)
);
