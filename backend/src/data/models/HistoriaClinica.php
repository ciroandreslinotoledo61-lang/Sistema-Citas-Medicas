<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class HistoriaClinica
{
    public function __construct(
        public readonly int $idHistoriaClinica,
        public readonly int $idPaciente,
        private readonly array $registro=[]
    ) {
    }
    public function obtenerDatosHistoria():array {
        $r=$this->registro;return ['idHistoriaClinica'=>$this->idHistoriaClinica,'idPaciente'=>$this->idPaciente,
        'nombrePaciente'=>$r['nombre_paciente']??'','tipoSangre'=>$r['tipo_sangre']??'','alergias'=>$r['alergias']??'',
        'antecedentes'=>$r['antecedentes_medicos']??'','versionHistoria'=>(int)($r['version_historia']??1)];
    }
}
