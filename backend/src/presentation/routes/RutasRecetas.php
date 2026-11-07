<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Routes;
use CitasMedicas\Presentation\Controllers\RecetaController;
use CitasMedicas\Presentation\Http\RutaHttp;
final class RutasRecetas
{
    public static function definir(): array
    {
        return [new RutaHttp('GET','/api/recetas',RecetaController::class,'listar',[],['usuario']),
            new RutaHttp('POST','/api/recetas',RecetaController::class,'emitir',[],['usuario']),
            new RutaHttp('GET','/api/recetas/{idReceta}',RecetaController::class,'obtener',[],['usuario','idReceta']),
            new RutaHttp('GET','/api/recetas/{idReceta}/pdf',RecetaController::class,'pdf',[],['usuario','idReceta']),
            new RutaHttp('PATCH','/api/recetas/{idReceta}/anulacion',RecetaController::class,'anular',[],['usuario','idReceta'])];
    }
}
