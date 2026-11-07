<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

final class CredencialService
{
    private readonly UsuarioRepository $usuarios;
    private readonly AutenticacionService $autenticacion;
    private readonly BitacoraService $bitacora;
    private readonly IntentosAutenticacionService $intentos;
    private readonly Transaccion $transaccion;

    public function __construct(\PDO $conexion)
    {
        $this->usuarios = new UsuarioRepository($conexion);
        $this->bitacora = new BitacoraService($conexion);
        $this->autenticacion = new AutenticacionService($this->usuarios, $this->bitacora, $conexion);
        $this->intentos = new IntentosAutenticacionService($conexion);
        $this->transaccion = new Transaccion($conexion);
    }

    public function cambiarContrasena(array $datos): Usuario
    {
        // La identidad procede exclusivamente de la sesion, nunca del formulario.
        $actor = $this->autenticacion->obtenerUsuarioAutenticado();
        $this->intentos->exigirIntentoDisponible('cambiar-contrasena', (string) $actor->idUsuario);
        $validados = UsuarioValidator::validarCambioContrasena($datos);
        $usuario = $this->transaccion->ejecutar(function () use ($actor, $validados): Usuario {
            $actual = $this->exigirActorVigente($actor);
            if (!$actual->verificarContrasenaUsuario($validados['contrasenaActual'])) {
                throw new ErrorAplicacion('La contraseña actual es incorrecta.', 422,
                    ['contrasenaActual' => 'Introduce tu contraseña actual.']);
            }
            if ($actual->verificarContrasenaUsuario($validados['nuevaContrasena'])) {
                throw new ErrorAplicacion('Elige una contraseña diferente de la actual.', 422,
                    ['nuevaContrasena' => 'La nueva contraseña debe ser diferente.']);
            }
            $this->bitacora->establecerUsuarioResponsable($actual);
            $this->usuarios->actualizarContrasena($actual->idUsuario,
                password_hash($validados['nuevaContrasena'], PASSWORD_DEFAULT));
            $this->bitacora->registrarCambioCredenciales($actual, $actual->idUsuario, 'cambiar_contrasena');
            return $this->usuarios->obtenerUsuarioPorId($actual->idUsuario);
        });
        // Solo esta sesion conserva acceso; las demas tienen una version anterior.
        SesionUsuario::establecerUsuarioAutenticado($usuario->idUsuario, $usuario->versionCredenciales);
        return $usuario;
    }

    public function restablecerContrasena(int $idUsuario, array $datos): void
    {
        $actor = $this->autenticacion->obtenerUsuarioAutenticado();
        $this->exigirAdministrador($actor);
        $this->intentos->exigirIntentoDisponible('restablecer-contrasena', (string) $actor->idUsuario);
        $temporal = UsuarioValidator::validarRestablecimientoContrasena($datos);
        if ($idUsuario === $actor->idUsuario) {
            throw new ErrorAplicacion('Utiliza Cambiar contraseña para tu propia cuenta.', 409);
        }
        $this->transaccion->ejecutar(function () use ($actor, $idUsuario, $temporal): void {
            // Orden fijo para evitar interbloqueos entre administradores.
            $ids = [$actor->idUsuario, $idUsuario];
            sort($ids, SORT_NUMERIC);
            $cuentas = [];
            foreach ($ids as $id) {
                $cuentas[$id] = $this->usuarios->obtenerUsuarioPorId($id, true);
            }
            $actual = $this->exigirActorVigente($actor, $cuentas[$actor->idUsuario]);
            $this->exigirAdministrador($actual);
            $destino = $cuentas[$idUsuario];
            if (!$destino || !$destino->usuarioActivo) {
                throw new ErrorAplicacion('No se puede restablecer esta cuenta.', 409);
            }
            // El permiso usuarios.gestionar comprende todas las cuentas de esta clinica.
            $this->bitacora->establecerUsuarioResponsable($actual);
            $this->usuarios->actualizarContrasena($idUsuario, password_hash($temporal, PASSWORD_DEFAULT), true);
            $this->bitacora->registrarCambioCredenciales($actual, $idUsuario, 'restablecer_contrasena');
        });
    }

    private function exigirAdministrador(Usuario $usuario): void
    {
        PermisosSistema::exigirPermiso($usuario, 'usuarios.gestionar');
        if ($usuario->requiereCambioContrasena) {
            throw new ErrorAplicacion('Establece tu contraseña personal antes de administrar cuentas.', 403);
        }
    }

    private function exigirActorVigente(Usuario $actor, ?Usuario $actual = null): Usuario
    {
        $actual ??= $this->usuarios->obtenerUsuarioPorId($actor->idUsuario, true);
        if (!$actual || !$actual->usuarioActivo || $actual->versionCredenciales !== $actor->versionCredenciales ||
            ($actual->requiereCambioContrasena &&
                (!$actual->contrasenaTemporalExpira || strtotime($actual->contrasenaTemporalExpira) <= time()))) {
            throw new ErrorAplicacion('Tu sesión ya no está disponible. Inicia sesión otra vez.', 401);
        }
        return $actual;
    }
}
