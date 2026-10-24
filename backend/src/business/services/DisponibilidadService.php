<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\TurnoRepository;
use CitasMedicas\Shared\Config\ConfiguracionAgenda;
use CitasMedicas\Shared\Constants\PermisosSistema;

final class DisponibilidadService
{
    private readonly MedicoRepository $medicoRepository;
    private readonly TurnoRepository $turnoRepository;

    public function __construct(\PDO $conexion, private readonly ?\DateTimeImmutable $momentoReferencia = null)
    {
        $this->medicoRepository = new MedicoRepository($conexion);
        $this->turnoRepository = new TurnoRepository($conexion);
    }

    public function obtenerConfiguracionAgenda(Usuario $usuario): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.consultar');
        return ConfiguracionAgenda::obtenerConfiguracionPublica();
    }

    public function listarMedicosDisponibles(Usuario $usuario): array
    {
        PermisosSistema::exigirPermiso($usuario, 'disponibilidad.consultar');
        return $this->medicoRepository->listarMedicosDisponibles();
    }

    public function listarTurnosDisponibles(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'disponibilidad.consultar');
        $idMedico = ValidarCamposFormulario::validarIdentificador($parametros, 'idMedico');
        $fecha = ValidarCamposFormulario::validarFecha($parametros['fecha'] ?? null);
        $ahora = $this->momentoReferencia ?? new \DateTimeImmutable();
        return $this->turnoRepository->listarTurnosDisponibles($idMedico, $fecha, $ahora->format('Y-m-d H:i:s'));
    }
}
