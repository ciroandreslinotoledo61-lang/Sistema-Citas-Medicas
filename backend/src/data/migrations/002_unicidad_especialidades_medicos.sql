-- Ejecutar mediante backend/scripts/actualizar-especialidades-medicos.php.
-- Conserva las tablas y los datos de la V1. Se puede ejecutar nuevamente.
-- Si ya existen duplicados equivalentes, PostgreSQL rechaza el indice y se revierte la actualizacion.
CREATE UNIQUE INDEX IF NOT EXISTS uq_especialidades_nombre_normalizado
    ON especialidades (lower(regexp_replace(btrim(nombre_especialidad), '[[:space:]]+', ' ', 'g')));

CREATE UNIQUE INDEX IF NOT EXISTS uq_medicos_matricula_normalizada
    ON medicos (upper(regexp_replace(btrim(matricula_profesional), '[[:space:]]+', ' ', 'g')));
