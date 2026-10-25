<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Routes;
use CitasMedicas\Presentation\Controllers\BitacoraController;
use CitasMedicas\Presentation\Http\RutaHttp;
final class RutasBitacora { public static function definir():array {return [
 new RutaHttp('GET','/api/bitacora',BitacoraController::class,'listarBitacora',['administrador'],['usuario']),
 new RutaHttp('GET','/api/bitacora/filtros',BitacoraController::class,'obtenerFiltrosBitacora',['administrador'],['usuario'])];}}
