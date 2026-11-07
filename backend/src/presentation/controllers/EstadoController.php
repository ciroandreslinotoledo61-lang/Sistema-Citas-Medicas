<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Shared\Utils\RespuestaJson;

final class EstadoController
{
    public function obtenerEstado(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => [
            'aplicacion' => 'Citas Médicas', 'version' => '1.4.0', 'estado' => 'disponible',
        ]]);
    }
}
