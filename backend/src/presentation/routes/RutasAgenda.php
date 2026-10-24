<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasAgenda
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return array_merge(
            RutasHorarios::definir(),
            RutasDisponibilidad::definir(),
            RutasCitas::definir(),
            RutasReportes::definir()
        );
    }
}
