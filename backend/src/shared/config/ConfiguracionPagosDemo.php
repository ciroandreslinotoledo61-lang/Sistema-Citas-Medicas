<?php
declare(strict_types=1);
namespace CitasMedicas\Shared\Config;
final class ConfiguracionPagosDemo
{
    public static function obtenerCentavos(): ?int
    {
        $valor=CargarVariablesEntorno::obtenerVariableEntorno('DEMO_TARIFA_BOB');
        if($valor==='')return null;
        if(!preg_match('/^([0-9]{1,7})(?:\.([0-9]{1,2}))?$/D',$valor,$m))throw new \RuntimeException('DEMO_TARIFA_BOB debe ser un monto de demostración positivo con hasta dos decimales.');
        $centavos=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
        if($centavos<1||$centavos>999999999)throw new \RuntimeException('La tarifa de demostración está fuera de rango.');
        return $centavos;
    }
}
