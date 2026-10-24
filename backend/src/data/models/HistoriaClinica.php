<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class HistoriaClinica
{
    public function __construct(
        public readonly int $idHistoriaClinica,
        public readonly int $idPaciente
    ) {
    }
}
