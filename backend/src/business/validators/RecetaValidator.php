<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Validators;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class RecetaValidator
{
    public static function validar(array $datos): array
    {
        ValidarCamposFormulario::exigirCamposPermitidos($datos, ['idConsulta','idRecetaAnterior','medicamentos']);
        $id = ValidarCamposFormulario::validarIdentificador($datos, 'idConsulta');
        $anterior = isset($datos['idRecetaAnterior']) ? ValidarCamposFormulario::validarIdentificador($datos, 'idRecetaAnterior') : null;
        $filas = $datos['medicamentos'] ?? null;
        if (!is_array($filas) || !array_is_list($filas) || count($filas)<1 || count($filas)>20) {
            throw new ErrorAplicacion('Registra entre 1 y 20 medicamentos.',422);
        }
        $medicamentos=[];
        foreach ($filas as $fila) {
            if (!is_array($fila)) { throw new ErrorAplicacion('Medicamento inválido.',422); }
            ValidarCamposFormulario::exigirCamposPermitidos($fila,['medicamento','dosis','frecuencia','duracion','indicaciones']);
            $registro=[];
            foreach (['medicamento'=>150,'dosis'=>150,'frecuencia'=>150,'duracion'=>150,'indicaciones'=>500] as $campo=>$limite) {
                $registro[$campo]=ValidarCamposFormulario::validarTexto($fila,$campo,ucfirst($campo),$limite,$campo!=='indicaciones');
            }
            $medicamentos[]=$registro;
        }
        return ['idConsulta'=>$id,'idRecetaAnterior'=>$anterior,'medicamentos'=>$medicamentos];
    }
}
