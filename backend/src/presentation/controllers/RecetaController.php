<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\RecetaService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\{RespuestaJson,SolicitudHttp,RecetaPdf};
final class RecetaController
{
    public function __construct(private readonly RecetaService $recetas) {}
    public function listar(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->recetas->listar($usuario,$_GET)]);}
    public function emitir(Usuario $usuario): never {RespuestaJson::enviarRespuestaJson(['mensaje'=>'Receta emitida. Su contenido no puede editarse.','datos'=>$this->recetas->emitir($usuario,SolicitudHttp::obtenerCuerpoJson())],201);}
    public function obtener(Usuario $usuario,int $idReceta): never {RespuestaJson::enviarRespuestaJson(['datos'=>$this->recetas->obtener($usuario,$idReceta)]);}
    public function anular(Usuario $usuario,int $idReceta): never {RespuestaJson::enviarRespuestaJson(['mensaje'=>'Receta anulada. Se conserva su contenido original.','datos'=>$this->recetas->anular($usuario,$idReceta,SolicitudHttp::obtenerCuerpoJson())]);}
    public function pdf(Usuario $usuario,int $idReceta): never
    {
        $pdf=RecetaPdf::generar($this->recetas->obtener($usuario,$idReceta));
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="receta-'.$idReceta.'.pdf"');
        header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');header('Content-Length: '.strlen($pdf));echo $pdf;exit;
    }
}
