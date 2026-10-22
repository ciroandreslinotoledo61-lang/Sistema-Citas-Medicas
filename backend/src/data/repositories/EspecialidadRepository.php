<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Especialidad;

final class EspecialidadRepository
{
    private const CONSULTA_ESPECIALIDAD = 'SELECT especialidades.*,
        (SELECT count(*) FROM medicos INNER JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
         WHERE medicos.id_especialidad = especialidades.id_especialidad AND usuarios.usuario_activo = TRUE)
         AS cantidad_medicos_activos FROM especialidades';

    public function __construct(private readonly \PDO $conexionPostgresql) {}

    public function listarEspecialidadesPorBusqueda(string $busqueda, int $pagina): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_ESPECIALIDAD .
            " WHERE concat_ws(' ', nombre_especialidad, descripcion_especialidad) ILIKE :busqueda
              ORDER BY nombre_especialidad, id_especialidad LIMIT 21 OFFSET :desplazamiento");
        $consulta->bindValue(':busqueda', '%' . $busqueda . '%');
        $consulta->bindValue(':desplazamiento', ($pagina - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map([Especialidad::class, 'crearEspecialidadDesdeRegistro'], $consulta->fetchAll());
    }

    public function listarEspecialidadesActivas(): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_ESPECIALIDAD .
            ' WHERE especialidad_activa = TRUE ORDER BY nombre_especialidad, id_especialidad');
        $consulta->execute();
        return array_map([Especialidad::class, 'crearEspecialidadDesdeRegistro'], $consulta->fetchAll());
    }

    public function obtenerEspecialidadPorId(int $idEspecialidad, bool $bloquearRegistro = false): ?Especialidad
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_ESPECIALIDAD .
            ' WHERE especialidades.id_especialidad = :idEspecialidad' . ($bloquearRegistro ? ' FOR UPDATE OF especialidades' : ''));
        $consulta->execute(['idEspecialidad' => $idEspecialidad]);
        $registroEspecialidad = $consulta->fetch();
        return $registroEspecialidad ? Especialidad::crearEspecialidadDesdeRegistro($registroEspecialidad) : null;
    }

    public function existeEspecialidadConNombre(string $nombreEspecialidad, int $idEspecialidadExcluida = 0): bool
    {
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT id_especialidad FROM especialidades
             WHERE lower(regexp_replace(btrim(nombre_especialidad), '[[:space:]]+', ' ', 'g')) = lower(:nombreEspecialidad)
             AND id_especialidad <> :idEspecialidadExcluida LIMIT 1"
        );
        $consulta->execute(['nombreEspecialidad' => $nombreEspecialidad, 'idEspecialidadExcluida' => $idEspecialidadExcluida]);
        return $consulta->fetchColumn() !== false;
    }

    public function registrarEspecialidad(array $datosEspecialidad): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO especialidades (nombre_especialidad, descripcion_especialidad)
             VALUES (:nombreEspecialidad, :descripcionEspecialidad) RETURNING id_especialidad'
        );
        $consulta->execute($datosEspecialidad);
        return (int) $consulta->fetchColumn();
    }

    public function actualizarDatosEspecialidad(int $idEspecialidad, array $datosEspecialidad): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE especialidades SET nombre_especialidad = :nombreEspecialidad,
             descripcion_especialidad = :descripcionEspecialidad WHERE id_especialidad = :idEspecialidad'
        );
        $consulta->execute(['idEspecialidad' => $idEspecialidad] + $datosEspecialidad);
    }

    public function cambiarEstadoEspecialidad(int $idEspecialidad, bool $especialidadActiva): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE especialidades SET especialidad_activa = :especialidadActiva WHERE id_especialidad = :idEspecialidad'
        );
        $consulta->bindValue(':especialidadActiva', $especialidadActiva, \PDO::PARAM_BOOL);
        $consulta->bindValue(':idEspecialidad', $idEspecialidad, \PDO::PARAM_INT);
        $consulta->execute();
    }

    public function contarMedicosActivosPorEspecialidad(int $idEspecialidad): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT count(*) FROM medicos INNER JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
             WHERE medicos.id_especialidad = :idEspecialidad AND usuarios.usuario_activo = TRUE'
        );
        $consulta->execute(['idEspecialidad' => $idEspecialidad]);
        return (int) $consulta->fetchColumn();
    }
}
