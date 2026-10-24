<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Medico;

final class MedicoRepository
{
    private const CONSULTA_MEDICO = 'SELECT medicos.id_medico, medicos.id_usuario, medicos.id_especialidad,
        medicos.telefono_medico, medicos.matricula_profesional, usuarios.nombres_usuario, usuarios.apellidos_usuario,
        usuarios.correo_usuario, usuarios.usuario_activo, especialidades.nombre_especialidad,
        especialidades.especialidad_activa FROM medicos
        INNER JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
        INNER JOIN especialidades ON especialidades.id_especialidad = medicos.id_especialidad';

    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function listarMedicosPorBusqueda(string $busqueda, int $pagina): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_MEDICO .
            " WHERE concat_ws(' ', usuarios.nombres_usuario, usuarios.apellidos_usuario, usuarios.correo_usuario,
                medicos.matricula_profesional, especialidades.nombre_especialidad) ILIKE :busqueda
              ORDER BY medicos.id_medico DESC LIMIT 21 OFFSET :desplazamiento");
        $consulta->bindValue(':busqueda', '%' . $busqueda . '%');
        $consulta->bindValue(':desplazamiento', ($pagina - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map([Medico::class, 'crearMedicoDesdeRegistro'], $consulta->fetchAll());
    }

    public function obtenerMedicoPorId(int $idMedico, bool $bloquearRegistro = false): ?Medico
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_MEDICO .
            ' WHERE medicos.id_medico = :idMedico' . ($bloquearRegistro ? ' FOR UPDATE OF medicos, usuarios' : ''));
        $consulta->execute(['idMedico' => $idMedico]);
        $registroMedico = $consulta->fetch();
        return $registroMedico ? Medico::crearMedicoDesdeRegistro($registroMedico) : null;
    }

    public function existeMedicoConMatricula(string $matriculaProfesional, int $idMedicoExcluido = 0): bool
    {
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT id_medico FROM medicos
             WHERE upper(regexp_replace(btrim(matricula_profesional), '[[:space:]]+', ' ', 'g')) = :matriculaProfesional
             AND id_medico <> :idMedicoExcluido LIMIT 1"
        );
        $consulta->execute(['matriculaProfesional' => $matriculaProfesional, 'idMedicoExcluido' => $idMedicoExcluido]);
        return $consulta->fetchColumn() !== false;
    }

    public function registrarPerfilMedico(int $idUsuario, array $datosMedico): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO medicos (id_usuario, id_especialidad, matricula_profesional, telefono_medico)
             VALUES (:idUsuario, :idEspecialidad, :matriculaProfesional, :telefonoMedico) RETURNING id_medico'
        );
        $consulta->execute([
            'idUsuario' => $idUsuario, 'idEspecialidad' => $datosMedico['idEspecialidad'],
            'matriculaProfesional' => $datosMedico['matriculaProfesional'], 'telefonoMedico' => $datosMedico['telefonoMedico'],
        ]);
        return (int) $consulta->fetchColumn();
    }

    public function actualizarPerfilMedico(int $idMedico, array $datosMedico): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE medicos SET telefono_medico = :telefonoMedico, id_especialidad = :idEspecialidad, matricula_profesional = :matriculaProfesional
             WHERE id_medico = :idMedico'
        );
        $consulta->execute([
            'idMedico' => $idMedico, 'idEspecialidad' => $datosMedico['idEspecialidad'],
            'matriculaProfesional' => $datosMedico['matriculaProfesional'], 'telefonoMedico' => $datosMedico['telefonoMedico'],
        ]);
    }

    public function listarMedicosDisponibles(): array
    {
        $consulta = $this->conexionPostgresql->query(
            "SELECT medicos.id_medico, concat_ws(' ', usuarios.nombres_usuario, usuarios.apellidos_usuario) AS nombre_medico,
                especialidades.id_especialidad, especialidades.nombre_especialidad
             FROM medicos JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
             JOIN especialidades ON especialidades.id_especialidad = medicos.id_especialidad
             WHERE usuarios.usuario_activo AND especialidades.especialidad_activa
             ORDER BY especialidades.nombre_especialidad, nombre_medico, medicos.id_medico"
        );
        return array_map(static fn (array $registro): array => [
            'idMedico' => (int) $registro['id_medico'], 'nombreMedico' => $registro['nombre_medico'],
            'idEspecialidad' => (int) $registro['id_especialidad'], 'nombreEspecialidad' => $registro['nombre_especialidad'],
        ], $consulta->fetchAll());
    }

    public function listarMedicosParaAgenda(?int $idUsuarioMedico = null): array
    {
        $consulta = $this->conexionPostgresql->prepare("SELECT m.id_medico, concat_ws(' ',u.nombres_usuario,u.apellidos_usuario) AS nombre_medico, u.usuario_activo
            FROM medicos m JOIN usuarios u USING(id_usuario)" . ($idUsuarioMedico !== null ? ' WHERE m.id_usuario=:idUsuario' : '') . ' ORDER BY nombre_medico,m.id_medico');
        $consulta->execute($idUsuarioMedico !== null ? ['idUsuario' => $idUsuarioMedico] : []);
        return array_map(static fn(array $fila): array => ['idMedico' => (int)$fila['id_medico'], 'nombreMedico' => $fila['nombre_medico'], 'medicoActivo' => in_array($fila['usuario_activo'], [true,1,'1','t','true'], true)], $consulta->fetchAll());
    }
}
