<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\RolRepository;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class UsuarioService
{
    private readonly UsuarioRepository $usuarioRepository;
    private readonly RolRepository $rolRepository;

    public function __construct(private readonly \PDO $conexionPostgresql)
    {
        $this->usuarioRepository = new UsuarioRepository($conexionPostgresql);
        $this->rolRepository = new RolRepository($conexionPostgresql);
    }

    public function listarUsuarios(array $parametros): array
    {
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $usuarios = $this->usuarioRepository->listarUsuariosPorBusqueda($filtros['busqueda'], $filtros['pagina']);
        return [
            'elementos' => array_map(static fn(Usuario $usuario): array => $usuario->obtenerDatosPublicosUsuario(), array_slice($usuarios, 0, 20)),
            'pagina' => $filtros['pagina'], 'hayPaginaSiguiente' => count($usuarios) > 20,
        ];
    }

    public function obtenerUsuarioPorId(int $idUsuario): Usuario
    {
        return $this->usuarioRepository->obtenerUsuarioPorId($idUsuario)
            ?? throw new ErrorAplicacion('El usuario no existe.', 404);
    }

    public function registrarUsuarioAdministrativo(array $datos): Usuario
    {
        $datosValidados = UsuarioValidator::validarDatosRegistroUsuario($datos);
        $rol = $this->rolRepository->obtenerRolPorCodigo($datosValidados['codigoRol'])
            ?? throw new ErrorAplicacion('El rol no esta configurado.', 409);
        $idUsuario = $this->usuarioRepository->registrarUsuario(
            $datosValidados, $rol->idRol, password_hash($datosValidados['contrasenaUsuario'], PASSWORD_DEFAULT)
        );
        return $this->obtenerUsuarioPorId($idUsuario);
    }

    public function actualizarDatosUsuario(int $idUsuario, array $datos): Usuario
    {
        $this->obtenerUsuarioPorId($idUsuario);
        $datosValidados = UsuarioValidator::validarDatosActualizacionUsuario($datos);
        $this->usuarioRepository->actualizarDatosUsuario($idUsuario, $datosValidados);
        return $this->obtenerUsuarioPorId($idUsuario);
    }

    public function cambiarEstadoUsuario(int $idUsuario, array $datos, Usuario $usuarioAutenticado): Usuario
    {
        $usuarioActivo = UsuarioValidator::validarEstadoUsuario($datos);
        if ($idUsuario === $usuarioAutenticado->idUsuario) {
            throw new ErrorAplicacion('No puedes cambiar el estado de tu propia cuenta.', 409);
        }
        $this->conexionPostgresql->beginTransaction();
        try {
            // El bloqueo del rol serializa los cambios del ultimo administrador.
            $this->rolRepository->obtenerRolPorCodigo(RolesSistema::ADMINISTRADOR, true);
            $usuario = $this->usuarioRepository->obtenerUsuarioPorId($idUsuario, true)
                ?? throw new ErrorAplicacion('El usuario no existe.', 404);
            if ($usuario->codigoRol === RolesSistema::MEDICO) {
                throw new ErrorAplicacion('Cambia el estado de esta cuenta desde Medicos para validar su especialidad.', 409);
            }
            if (!$usuarioActivo && $usuario->usuarioActivo && $usuario->codigoRol === RolesSistema::ADMINISTRADOR &&
                $this->usuarioRepository->contarAdministradoresActivos() <= 1) {
                throw new ErrorAplicacion('Debe quedar al menos un administrador activo.', 409);
            }
            if ($usuario->usuarioActivo !== $usuarioActivo) {
                $this->usuarioRepository->cambiarEstadoUsuario($idUsuario, $usuarioActivo);
            }
            $usuarioActualizado = $this->obtenerUsuarioPorId($idUsuario);
            $this->conexionPostgresql->commit();
            return $usuarioActualizado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) {
                $this->conexionPostgresql->rollBack();
            }
            throw $error;
        }
    }

    public function listarRolesSistema(): array
    {
        return array_map(static fn($rol): array => $rol->obtenerDatosPublicosRol(), $this->rolRepository->listarRolesSistema());
    }
}
