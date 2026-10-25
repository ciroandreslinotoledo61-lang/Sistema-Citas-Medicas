<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Http;

use CitasMedicas\Business\Services;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Presentation\Controllers;

/** Composicion de dependencias; solo abre PostgreSQL cuando la accion lo necesita. */
final class FabricaControladores
{
    private ?Services\AutenticacionService $autenticacionService = null;

    public function obtenerAutenticacionService(): Services\AutenticacionService
    {
        return $this->autenticacionService ??= new Services\AutenticacionService(
            new UsuarioRepository(ConexionPostgresql::obtenerConexionPostgresql()),$this->obtenerBitacoraService()
        );
    }

    public function obtenerBitacoraService():Services\BitacoraService {return new Services\BitacoraService($this->conexion());}

    public function obtener(string $clase): object
    {
        return match ($clase) {
            Controllers\ConsultaController::class => new Controllers\ConsultaController(new Services\ConsultaService($this->conexion()),new Services\HistoriaClinicaService($this->conexion())),
            Controllers\BitacoraController::class => new Controllers\BitacoraController($this->obtenerBitacoraService()),
            Controllers\EstadoController::class => new Controllers\EstadoController(),
            Controllers\TokenCsrfController::class => new Controllers\TokenCsrfController(),
            Controllers\AutenticacionController::class => new Controllers\AutenticacionController($this->obtenerAutenticacionService()),
            Controllers\UsuarioController::class => new Controllers\UsuarioController(new Services\UsuarioService($this->conexion())),
            Controllers\PacienteController::class => new Controllers\PacienteController(new Services\PacienteService($this->conexion())),
            Controllers\EspecialidadController::class => new Controllers\EspecialidadController(new Services\EspecialidadService($this->conexion())),
            Controllers\MedicoController::class => new Controllers\MedicoController(new Services\MedicoService($this->conexion())),
            Controllers\HorarioMedicoController::class => new Controllers\HorarioMedicoController(new Services\HorarioMedicoService($this->conexion())),
            Controllers\DisponibilidadController::class => new Controllers\DisponibilidadController(new Services\DisponibilidadService($this->conexion())),
            Controllers\CitaController::class => new Controllers\CitaController(new Services\CitaService($this->conexion())),
            Controllers\ReporteController::class => new Controllers\ReporteController(new Services\ReporteService($this->conexion())),
            default => throw new \LogicException('Controlador no registrado: ' . $clase),
        };
    }

    private function conexion(): \PDO
    {
        return ConexionPostgresql::obtenerConexionPostgresql();
    }
}
