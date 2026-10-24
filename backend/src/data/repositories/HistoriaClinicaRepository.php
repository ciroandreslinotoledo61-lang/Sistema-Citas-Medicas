<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\HistoriaClinica;

final class HistoriaClinicaRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function crearHistoriaClinicaParaPaciente(int $idPaciente): HistoriaClinica
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO historias_clinicas (id_paciente) VALUES (:idPaciente) RETURNING id_historia_clinica'
        );
        $consulta->execute(['idPaciente' => $idPaciente]);
        return new HistoriaClinica((int) $consulta->fetchColumn(), $idPaciente);
    }
}
