<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class HorarioMedicoController
{
    public function __construct(private readonly HorarioMedicoService $horarioService) {}
    public function listarHorarios(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->horarioService->listarHorarios($usuario, $_GET)]);
    }
    public function registrarHorario(Usuario $usuario): never
    {
        $resultado = $this->horarioService->registrarHorario($usuario, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Horario registrado. Se generaron turnos de ' . $resultado['duracionCitaMinutos'] . ' minutos.', 'datos' => $resultado], 201);
    }
    public function listarMedicosDisponibles(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->horarioService->listarMedicosDisponibles($usuario)]);
    }
    public function listarTurnosDisponibles(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->horarioService->listarTurnosDisponibles($usuario, $_GET)]);
    }

    public function obtenerConfiguracionAgenda(Usuario $usuario): never
    {
        \CitasMedicas\Shared\Constants\PermisosSistema::exigirPermiso($usuario,'citas.consultar');
        RespuestaJson::enviarRespuestaJson(['datos'=>\CitasMedicas\Shared\Config\ConfiguracionAgenda::obtenerConfiguracionPublica()]);
    }

    public function programarHorariosPorPeriodo(Usuario $usuario): never
    {
        $resultado = $this->horarioService->programarHorariosPorPeriodo($usuario, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Programacion procesada. Revisa el resumen de cada fecha.', 'datos' => $resultado]);
    }

}
