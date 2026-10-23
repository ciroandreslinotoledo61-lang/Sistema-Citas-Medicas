<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\PacienteService;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class PacienteController
{
    public function __construct(private readonly PacienteService $pacienteService) {}

    public function registrarPacienteConHistoriaClinica(): never
    {
        $paciente = $this->pacienteService->registrarPacienteConHistoriaClinica(SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Paciente registrado. Ya puede iniciar sesion.', 'datos' => $paciente->obtenerDatosPublicosPaciente()], 201);
    }

    public function listarPacientes(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->pacienteService->listarPacientes($_GET)]);
    }

    public function obtenerPacientePorId(int $idPaciente): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->pacienteService->obtenerPacientePorId($idPaciente)->obtenerDatosPublicosPaciente()]);
    }

    public function obtenerPerfilPaciente(int $idUsuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->pacienteService->obtenerPacientePorUsuario($idUsuario)->obtenerDatosPublicosPaciente()]);
    }

    public function actualizarContactoPaciente(int $idPaciente): never
    {
        $paciente = $this->pacienteService->actualizarContactoPaciente($idPaciente, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Contacto actualizado.', 'datos' => $paciente->obtenerDatosPublicosPaciente()]);
    }

    public function actualizarDatosPaciente(\CitasMedicas\Data\Models\Usuario $usuario, int $idPaciente): never
    {
        $paciente=$this->pacienteService->actualizarDatosPaciente($usuario,$idPaciente,SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje'=>'Datos del paciente actualizados.','datos'=>$paciente->obtenerDatosPublicosPaciente()]);
    }
    public function cambiarEstadoPaciente(\CitasMedicas\Data\Models\Usuario $usuario, int $idPaciente): never
    {
        $paciente=$this->pacienteService->cambiarEstadoPaciente($usuario,$idPaciente,SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje'=>'Estado actualizado. Sus citas e historia se conservan.','datos'=>$paciente->obtenerDatosPublicosPaciente()]);
    }

}
