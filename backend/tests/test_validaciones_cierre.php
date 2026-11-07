<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
use CitasMedicas\Shared\Config\ConfiguracionCitasVencidas;
use CitasMedicas\Shared\Constants\PermisosSistema;
$anterior=getenv('CITAS_TOLERANCIA_MINUTOS');$n=0;
try{
    putenv('CITAS_TOLERANCIA_MINUTOS');if(ConfiguracionCitasVencidas::obtenerToleranciaMinutos()!==30)throw new RuntimeException('El margen predeterminado debe ser 30.');$n++;
    foreach(['0'=>0,'45'=>45,'10080'=>10080] as $v=>$esperado){putenv('CITAS_TOLERANCIA_MINUTOS='.$v);if(ConfiguracionCitasVencidas::obtenerToleranciaMinutos()!==$esperado)throw new RuntimeException('Margen no normalizado.');$n++;}
    foreach(['','-1','1.5','abc','10081',str_repeat('9',40)] as $v){putenv('CITAS_TOLERANCIA_MINUTOS='.$v);try{ConfiguracionCitasVencidas::obtenerToleranciaMinutos();}catch(RuntimeException){$n++;continue;}throw new RuntimeException('Margen inválido admitido.');}
    foreach(['administrador'=>true,'recepcionista'=>true,'paciente'=>false,'medico'=>false] as $rol=>$esperado){if(PermisosSistema::tienePermiso($rol,'citas.incidencia_medico')!==$esperado)throw new RuntimeException('Permiso de incidencia médica incorrecto.');$n++;}
}finally{putenv($anterior===false?'CITAS_TOLERANCIA_MINUTOS':'CITAS_TOLERANCIA_MINUTOS='.$anterior);}
echo "OK: $n comprobaciones de tolerancia y permisos de incidencia médica.\n";
