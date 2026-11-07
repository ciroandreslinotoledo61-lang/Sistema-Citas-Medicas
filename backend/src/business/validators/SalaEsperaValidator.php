<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Validators;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class SalaEsperaValidator
{
    public static function validar(array $d): array
    {
        ValidarCamposFormulario::exigirCamposPermitidos($d,['accion','version']);
        $accion=$d['accion']??null;$version=$d['version']??null;
        if(!is_string($accion)||!in_array($accion,['llegada','iniciar','finalizar'],true)||!is_int($version)||$version<0)throw new ErrorAplicacion('Acción o versión de sala inválida.',422);
        return compact('accion','version');
    }
}
