<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\PagoDemostracionService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\{RespuestaJson,SolicitudHttp};
final class PagoDemostracionController
{
    public function __construct(private readonly PagoDemostracionService $pagos) {}
    public function listar(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->pagos->listar($usuario,$_GET)]);}
    public function configuracion(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->pagos->configuracion($usuario)]);}
    public function crear(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['mensaje'=>'Pago pendiente registrado.','datos'=>$this->pagos->crear($usuario,SolicitudHttp::obtenerCuerpoJson())],201);}
    public function obtener(Usuario $usuario,int $idPago): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->pagos->obtener($usuario,$idPago)]);}
    public function finalizar(Usuario $usuario,int $idPago): never {RespuestaJson::enviarRespuestaJson(['mensaje'=>'Estado del pago actualizado.','datos'=>$this->pagos->finalizar($usuario,$idPago,SolicitudHttp::obtenerCuerpoJson())]);}
    public function reporte(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->pagos->reporte($usuario,$_GET)]);}
}
