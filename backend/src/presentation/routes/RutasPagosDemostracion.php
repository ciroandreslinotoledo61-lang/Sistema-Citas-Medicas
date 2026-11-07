<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Routes;
use CitasMedicas\Presentation\Controllers\PagoDemostracionController;
use CitasMedicas\Presentation\Http\RutaHttp;
final class RutasPagosDemostracion
{
    public static function definir(): array
    {
        return [new RutaHttp('GET','/api/pagos-demo',PagoDemostracionController::class,'listar',[],['usuario']),
            new RutaHttp('GET','/api/pagos-demo/configuracion',PagoDemostracionController::class,'configuracion',[],['usuario']),
            new RutaHttp('GET','/api/pagos-demo/reporte',PagoDemostracionController::class,'reporte',[],['usuario']),
            new RutaHttp('POST','/api/pagos-demo',PagoDemostracionController::class,'crear',[],['usuario']),
            new RutaHttp('GET','/api/pagos-demo/{idPago}',PagoDemostracionController::class,'obtener',[],['usuario','idPago']),
            new RutaHttp('PATCH','/api/pagos-demo/{idPago}',PagoDemostracionController::class,'finalizar',[],['usuario','idPago'])];
    }
}
