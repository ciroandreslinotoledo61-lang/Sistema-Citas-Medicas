<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Routes;
use CitasMedicas\Presentation\Controllers\SalaEsperaController;
use CitasMedicas\Presentation\Http\RutaHttp;
final class RutasSalaEspera
{
    public static function definir(): array
    {
        return [new RutaHttp('GET','/api/sala-espera',SalaEsperaController::class,'listar',[],['usuario']),
            new RutaHttp('PATCH','/api/sala-espera/{idCita}',SalaEsperaController::class,'cambiar',[],['usuario','idCita']),
            new RutaHttp('GET','/api/sala-espera/{idCita}/historial',SalaEsperaController::class,'historial',[],['usuario','idCita'])];
    }
}
