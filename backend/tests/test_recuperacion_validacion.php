<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Services\CorreoTransaccionalService;
use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

$cantidad = 0;
$rechazar = static function (callable $operacion, int $codigo = 422) use (&$cantidad): void {
    try { $operacion(); } catch (ErrorAplicacion $error) {
        if ($error->codigoHttp !== $codigo) { throw new RuntimeException('Codigo inesperado.'); }
        $cantidad++;
        return;
    }
    throw new RuntimeException('Se acepto una recuperacion insegura.');
};
$datos = ['token' => bin2hex(random_bytes(32)), 'nuevaContrasena' => 'FrasePersonalSegura456',
    'confirmacionContrasena' => 'FrasePersonalSegura456'];
UsuarioValidator::validarRecuperacionContrasena($datos);
$cantidad++;
foreach (['', '123', str_repeat('g', 64), str_repeat('a', 65), [], null] as $token) {
    $rechazar(fn() => UsuarioValidator::validarRecuperacionContrasena(array_replace($datos, ['token' => $token])));
}
foreach (['corta1', '1234567890123456', str_repeat('a', 20), 'Abc123Abc123', str_repeat('x', 73)] as $clave) {
    $rechazar(fn() => UsuarioValidator::validarRecuperacionContrasena(array_replace($datos,
        ['nuevaContrasena' => $clave, 'confirmacionContrasena' => $clave])));
}
$rechazar(fn() => UsuarioValidator::validarRecuperacionContrasena($datos + ['idUsuario' => 42]));
$rechazar(fn() => UsuarioValidator::validarRecuperacionContrasena(array_replace($datos, ['confirmacionContrasena' => 'OtraFraseSegura789'])));
$correo = UsuarioValidator::validarSolicitudRecuperacion(['correoUsuario' => ' PACIENTE@EXAMPLE.TEST ']);
if ($correo !== 'paciente@example.test') { throw new RuntimeException('No se normalizo el correo.'); }
$cantidad++;
foreach (['correo-invalido', '', [], str_repeat('a', 161) . '@example.test'] as $correo) {
    $rechazar(fn() => UsuarioValidator::validarSolicitudRecuperacion(['correoUsuario' => $correo]));
}
$rechazar(fn() => UsuarioValidator::validarSolicitudRecuperacion(['correoUsuario' => 'paciente@example.test', 'idUsuario' => 42]));

// Configuracion sintetica en este proceso; nunca modifica los archivos .env.
putenv('MAIL_PROVIDER=resend');
putenv('MAIL_FROM=clinica@example.test');
putenv('RESEND_API_KEY=prueba-sintetica-no-es-credencial');
putenv('APP_ENV=production');
foreach (['http://clinica.example.test', 'https://usuario:clave@clinica.example.test',
    'https://clinica.example.test/?destino=otra', 'https://clinica.example.test/#otra',
    'https://clinica.example.test/ruta', 'javascript:alert(1)'] as $url) {
    putenv('FRONTEND_URL=' . $url);
    $rechazar(fn() => (new CorreoTransaccionalService())->obtenerUrlRecuperacion(), 503);
}
putenv('FRONTEND_URL=https://clinica.example.test');
$correo = new CorreoTransaccionalService();
$correo->exigirConfiguracionValida();
if ($correo->obtenerUrlRecuperacion() !== 'https://clinica.example.test/recuperar-contrasena/confirmar') {
    throw new RuntimeException('Destino de recuperacion incorrecto.');
}
$cantidad++;
putenv('RESEND_API_KEY=');
$rechazar(fn() => $correo->exigirConfiguracionValida(), 503);
putenv('APP_ENV=local');
putenv('FRONTEND_URL=http://127.0.0.1:8080');
$correo->obtenerUrlRecuperacion();
$cantidad++;
echo "OK: $cantidad comprobaciones de recuperacion, campos permitidos y enlaces seguros.\n";
