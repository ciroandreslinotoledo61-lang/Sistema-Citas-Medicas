<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
use CitasMedicas\Business\Validators\PacienteValidator;
use CitasMedicas\Business\Validators\ReporteValidator;
use CitasMedicas\Shared\Config\ConfiguracionAgenda;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
$cantidad=0;
function rechazarFiltro(callable $operacion): void {
    global $cantidad;
    try { $operacion(); } catch (ErrorAplicacion $error) { if ($error->codigoHttp!==422) { throw $error; } $cantidad++; return; }
    throw new RuntimeException('Se acepto una entrada invalida.');
}
foreach ([null,'false',0,[],''] as $valor) { rechazarFiltro(fn()=>PacienteValidator::validarEstadoPaciente(['pacienteActivo'=>$valor])); }
if (PacienteValidator::validarEstadoPaciente(['pacienteActivo'=>false])!==false) { throw new RuntimeException('Estado falso perdido.'); }
foreach ([['fechaDesde'=>'2026-03-02','fechaHasta'=>'2026-03-01'],['fechaHasta'=>'2026-02-30'],['idMedico'=>'-1'],['idEspecialidad'=>[]],['estado'=>'incorrecto'],['paginaCitas'=>0]] as $datos) { rechazarFiltro(fn()=>ReporteValidator::validarFiltrosReporte($datos)); }
$original=getenv('CITA_DURACION_MINUTOS');
try {
    putenv('CITA_DURACION_MINUTOS');
    if (ConfiguracionAgenda::obtenerDuracionCitaMinutos()!==30) { throw new RuntimeException('El valor predeterminado debe ser 30.'); }
    foreach (['0','31','abc','125'] as $valor) {
        putenv('CITA_DURACION_MINUTOS='.$valor);
        try { ConfiguracionAgenda::obtenerDuracionCitaMinutos(); } catch (RuntimeException) { $cantidad++; continue; }
        throw new RuntimeException('Se acepto configuracion invalida.');
    }
} finally { putenv($original===false ? 'CITA_DURACION_MINUTOS' : 'CITA_DURACION_MINUTOS='.$original); }
echo "OK: $cantidad rechazos de entradas invalidas, estado booleano y duracion predeterminada.\n";
