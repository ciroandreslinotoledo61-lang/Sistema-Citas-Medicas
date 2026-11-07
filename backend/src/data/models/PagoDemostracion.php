<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Models;
final class PagoDemostracion
{
    public function __construct(private readonly array $registro) {}
    public function obtenerDatos(): array
    {
        $r=$this->registro;return ['idPago'=>(int)$r['id_pago'],'idCita'=>(int)$r['id_cita'],'montoCentavos'=>(int)$r['monto_centavos'],
            'moneda'=>'BOB','metodo'=>$r['metodo'],'referencia'=>$r['referencia'],'estado'=>$r['estado'],
            'fechaCreacion'=>$r['fecha_creacion'],'fechaConfirmacion'=>$r['fecha_confirmacion'],'fechaCancelacion'=>$r['fecha_cancelacion'],'esSimulado'=>true];
    }
}
