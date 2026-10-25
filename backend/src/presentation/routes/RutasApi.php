<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasApi
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return array_merge(
            RutasSistema::definir(),
            RutasAutenticacion::definir(),
            RutasUsuarios::definir(),
            RutasPacientes::definir(),
            RutasEspecialidades::definir(),
            RutasMedicos::definir(),
            RutasAgenda::definir(),
            RutasBitacora::definir(),
            RutasConsultas::definir()
        );
    }
}
