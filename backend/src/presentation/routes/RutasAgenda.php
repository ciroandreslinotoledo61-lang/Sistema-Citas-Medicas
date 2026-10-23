<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Routes;
use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Presentation\Controllers\CitaController;
use CitasMedicas\Presentation\Controllers\HorarioMedicoController;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class RutasAgenda
{
    public static function atenderRutasAgenda(\PDO $conexion, Usuario $usuario, string $ruta, string $metodo): void
    {
        if ($metodo === 'GET' && $ruta === '/api/reportes') { (new \CitasMedicas\Presentation\Controllers\ReporteController(new \CitasMedicas\Business\Services\ReporteService($conexion)))->obtenerReporteAdministrativo($usuario); }
        $horarioController = new HorarioMedicoController(new HorarioMedicoService($conexion));
        if ($metodo === 'GET' && $ruta === '/api/disponibilidad/configuracion') { $horarioController->obtenerConfiguracionAgenda($usuario); }
        if ($metodo === 'GET' && $ruta === '/api/disponibilidad/medicos') { $horarioController->listarMedicosDisponibles($usuario); }
        if ($metodo === 'GET' && $ruta === '/api/disponibilidad/turnos') { $horarioController->listarTurnosDisponibles($usuario); }
        if ($ruta === '/api/horarios') {
            if ($metodo === 'GET') { $horarioController->listarHorarios($usuario); }
            if ($metodo === 'POST') { $horarioController->registrarHorario($usuario); }
        }
        $citaController = new CitaController(new CitaService($conexion));
        if ($metodo === 'GET' && $ruta === '/api/agenda/medicos') { $citaController->listarMedicosParaAgenda($usuario); }
        if ($ruta === '/api/citas') {
            if ($metodo === 'GET') { $citaController->listarCitas($usuario); }
            if ($metodo === 'POST') { $citaController->reservarCita($usuario); }
        }
        if (preg_match('#^/api/citas/([1-9][0-9]*)(?:/(cancelacion|reprogramacion|asistencia|atencion))?$#', $ruta, $coincidencia)) {
            $idCita = SolicitudHttp::obtenerIdPositivo($coincidencia[1]);
            $accion = $coincidencia[2] ?? '';
            if ($metodo === 'GET' && $accion === '') { $citaController->obtenerCita($usuario, $idCita); }
            if ($metodo === 'PATCH' && $accion === 'cancelacion') { $citaController->cancelarCita($usuario, $idCita); }
            if ($metodo === 'PATCH' && $accion === 'reprogramacion') { $citaController->reprogramarCita($usuario, $idCita); }
            if ($metodo === 'PATCH' && $accion === 'atencion') { $citaController->marcarCitaAtendida($usuario,$idCita); }
            if ($metodo === 'PATCH' && $accion === 'asistencia') { $citaController->registrarAsistencia($usuario, $idCita); }
        }
    }
}
