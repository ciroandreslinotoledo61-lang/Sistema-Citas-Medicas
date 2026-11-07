<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\SalaEsperaService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\{RespuestaJson,SolicitudHttp};
final class SalaEsperaController
{
    public function __construct(private readonly SalaEsperaService $sala) {}
    public function listar(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->sala->listar($usuario,$_GET)]);}
    public function cambiar(Usuario $usuario,int $idCita): never {RespuestaJson::enviarRespuestaJson(['mensaje'=>'Sala actualizada.','datos'=>$this->sala->cambiar($usuario,$idCita,SolicitudHttp::obtenerCuerpoJson())]);}
    public function historial(Usuario $usuario,int $idCita): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->sala->historial($usuario,$idCita)]);}
}
