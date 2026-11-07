<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Repositories\RecuperacionContrasenaRepository;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

final class RecuperacionContrasenaService
{
    private readonly UsuarioRepository $usuarios;
    private readonly RecuperacionContrasenaRepository $recuperaciones;
    private readonly IntentosAutenticacionService $intentos;
    private readonly BitacoraService $bitacora;
    private readonly Transaccion $transaccion;
    private readonly CorreoTransaccionalService $correo;

    public function __construct(\PDO $conexion, ?CorreoTransaccionalService $correo = null)
    {
        $this->usuarios = new UsuarioRepository($conexion);
        $this->recuperaciones = new RecuperacionContrasenaRepository($conexion);
        $this->intentos = new IntentosAutenticacionService($conexion);
        $this->bitacora = new BitacoraService($conexion);
        $this->transaccion = new Transaccion($conexion);
        $this->correo = $correo ?? new CorreoTransaccionalService();
    }

    public function solicitarRecuperacion(array $datos, string $direccionCliente = 'local'): void
    {
        $inicio = microtime(true);
        $this->intentos->exigirIntentoDisponible('recuperar-contrasena-ip', $direccionCliente, 20);
        $direccion = UsuarioValidator::validarSolicitudRecuperacion($datos);
        $this->intentos->exigirIntentoDisponible('recuperar-contrasena-cuenta', $direccion, 3);
        // Si falta configuracion se responde igual antes de consultar si existe la cuenta.
        $this->correo->exigirConfiguracionValida();
        try {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $destinatario = $this->transaccion->ejecutar(function () use ($direccion, $hash): ?string {
                $usuario = $this->usuarios->obtenerUsuarioPorCorreo($direccion);
                $actual = $usuario ? $this->usuarios->obtenerUsuarioPorId($usuario->idUsuario, true) : null;
                $this->bitacora->establecerUsuarioResponsable(null);
                $this->bitacora->registrarEventoAutenticacion(null, 'solicitar_recuperacion', 'exito');
                if (!$actual || !$actual->usuarioActivo || $actual->correoUsuario !== $direccion) {
                    return null;
                }
                $this->recuperaciones->guardarToken($actual->idUsuario, $hash, $actual->versionCredenciales,
                    hash('sha256', $actual->correoUsuario));
                return $actual->correoUsuario;
            });
            if ($destinatario !== null) {
                try {
                    // Fuera de la transaccion: la red nunca mantiene bloqueada una cuenta.
                    $this->correo->enviarRecuperacion($destinatario, $this->correo->obtenerUrlRecuperacion() . '#token=' . $token);
                    $this->bitacora->registrarEventoAutenticacion(null, 'enviar_recuperacion', 'exito');
                } catch (\RuntimeException $error) {
                    $this->recuperaciones->invalidarToken($hash);
                    $this->bitacora->registrarEventoAutenticacion(null, 'enviar_recuperacion', 'rechazado');
                    error_log('Correo de recuperacion no aceptado por el proveedor.');
                    // Mismo codigo y mensaje que una cuenta desconocida: no enumerar cuentas.
                }
            }
        } finally {
            // El timeout de correo es 2 s. Igualar tambien la duracion de cuentas inexistentes.
            $restante = 2.5 - (microtime(true) - $inicio);
            if ($restante > 0) { usleep((int) ($restante * 1000000)); }
        }
    }

    public function confirmarRecuperacion(array $datos, string $direccionCliente = 'local'): void
    {
        $this->intentos->exigirIntentoDisponible('confirmar-recuperacion-ip', $direccionCliente, 30);
        $validados = UsuarioValidator::validarRecuperacionContrasena($datos);
        $hash = hash('sha256', $validados['token']);
        $this->intentos->exigirIntentoDisponible('confirmar-recuperacion-token', $hash);
        $this->transaccion->ejecutar(function () use ($hash, $validados): void {
            $idUsuario = $this->recuperaciones->obtenerIdUsuarioPorToken($hash);
            $actual = $idUsuario ? $this->usuarios->obtenerUsuarioPorId($idUsuario, true) : null;
            if (!$actual || !$actual->usuarioActivo || !$this->recuperaciones->consumirToken(
                $actual->idUsuario, $hash, $actual->versionCredenciales, hash('sha256', $actual->correoUsuario))) {
                throw new ErrorAplicacion('El enlace es inválido o ha vencido. Solicita uno nuevo.', 422);
            }
            if ($actual->verificarContrasenaUsuario($validados['nuevaContrasena'])) {
                throw new ErrorAplicacion('Elige una contraseña diferente de la actual.', 422,
                    ['nuevaContrasena' => 'La nueva contraseña debe ser diferente.']);
            }
            $this->bitacora->establecerUsuarioResponsable($actual);
            $this->usuarios->actualizarContrasena($actual->idUsuario,
                password_hash($validados['nuevaContrasena'], PASSWORD_DEFAULT));
            $this->bitacora->registrarEventoAutenticacion($actual, 'recuperar_contrasena', 'exito');
        });
        // Todas las sesiones anteriores quedan revocadas por version; no se inicia otra automaticamente.
        SesionUsuario::limpiarSesionUsuario();
    }
}
