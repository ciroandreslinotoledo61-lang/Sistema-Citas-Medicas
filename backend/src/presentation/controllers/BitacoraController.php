<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\BitacoraService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\RespuestaJson;
final class BitacoraController {
 public function __construct(private readonly BitacoraService $bitacoraService){}
 public function listarBitacora(Usuario $usuario):never{RespuestaJson::enviarRespuestaJson(['datos'=>$this->bitacoraService->listarBitacora($usuario,$_GET)]);}
 public function obtenerFiltrosBitacora(Usuario $usuario):never{RespuestaJson::enviarRespuestaJson(['datos'=>$this->bitacoraService->obtenerFiltrosBitacora($usuario)]);}
}
