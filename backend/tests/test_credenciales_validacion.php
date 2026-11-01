<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

$cantidad = 0;
$rechazar = static function (callable $operacion) use (&$cantidad): void {
    try {
        $operacion();
    } catch (ErrorAplicacion $error) {
        if ($error->codigoHttp !== 422) {
            throw new RuntimeException('Codigo incorrecto para validacion de credenciales.');
        }
        $cantidad++;
        return;
    }
    throw new RuntimeException('Se aceptaron credenciales invalidas.');
};
$cambio = ['contrasenaActual' => 'ClaveActualPrueba123', 'nuevaContrasena' => 'FrasePersonalNueva456',
    'confirmacionContrasena' => 'FrasePersonalNueva456'];
UsuarioValidator::validarCambioContrasena($cambio);
$cantidad++;
foreach (['corta1', str_repeat('x', 73), str_repeat('a', 12), '1234567890123456', 'Abc123Abc123', "Clave\0Personal123", [], null] as $clave) {
    $rechazar(fn() => UsuarioValidator::validarCambioContrasena(array_replace($cambio, ['nuevaContrasena' => $clave, 'confirmacionContrasena' => $clave])));
}
$rechazar(fn() => UsuarioValidator::validarCambioContrasena(array_replace($cambio, ['confirmacionContrasena' => 'OtraFrasePersonal789'])));
$rechazar(fn() => UsuarioValidator::validarCambioContrasena($cambio + ['idUsuario' => 999]));
$rechazar(fn() => UsuarioValidator::validarCambioContrasena(array_replace($cambio, ['contrasenaActual' => []])));
$rechazar(fn() => UsuarioValidator::validarCambioContrasena(array_replace($cambio, ['contrasenaActual' => ''])));
$temporal = ['contrasenaTemporal' => bin2hex(random_bytes(12)), 'confirmarRestablecimiento' => true];
UsuarioValidator::validarRestablecimientoContrasena($temporal);
$cantidad++;
$rechazar(fn() => UsuarioValidator::validarRestablecimientoContrasena(array_replace($temporal, ['confirmarRestablecimiento' => false])));
$rechazar(fn() => UsuarioValidator::validarRestablecimientoContrasena(array_replace($temporal, ['confirmarRestablecimiento' => 'true'])));
$rechazar(fn() => UsuarioValidator::validarRestablecimientoContrasena(array_replace($temporal, ['contrasenaTemporal' => 'TemporalDebil123'])));
$rechazar(fn() => UsuarioValidator::validarRestablecimientoContrasena($temporal + ['codigoRol' => 'administrador']));
echo "OK: $cantidad comprobaciones de validacion de credenciales.\n";
