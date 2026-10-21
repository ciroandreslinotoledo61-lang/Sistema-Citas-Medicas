<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Presentation\Middlewares\VerificarRolUsuario;
use CitasMedicas\Presentation\Middlewares\VerificarTokenCsrf;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

$usuarioPaciente = new Usuario(1, 'Paciente', 'Prueba', 'paciente@example.test',
    password_hash('FraseParaPrueba123', PASSWORD_DEFAULT), true, 1, 4, 'paciente', 'Paciente');

VerificarRolUsuario::exigirRolPermitido($usuarioPaciente, ['paciente']);
try {
    VerificarRolUsuario::exigirRolPermitido($usuarioPaciente, ['administrador', 'recepcionista']);
    throw new RuntimeException('Un paciente obtuvo permisos de gestion.');
} catch (ErrorAplicacion $error) {
    if ($error->codigoHttp !== 403) { throw new RuntimeException('Se esperaba 403.'); }
}
$datosPublicos = $usuarioPaciente->obtenerDatosPublicosUsuario();
if (array_key_exists('contrasenaHash', $datosPublicos) || array_key_exists('versionCredenciales', $datosPublicos)) {
    throw new RuntimeException('El modelo expone informacion privada.');
}
if (!$usuarioPaciente->verificarContrasenaUsuario('FraseParaPrueba123') ||
    $usuarioPaciente->verificarContrasenaUsuario('ClaveIncorrecta')) {
    throw new RuntimeException('Verificacion de clave incorrecta.');
}
$_SESSION = [];
$tokenCsrf = SesionUsuario::obtenerTokenCsrf();
if (strlen($tokenCsrf) !== 64) { throw new RuntimeException('Token incompleto.'); }
$_SERVER['HTTP_X_CSRF_TOKEN'] = $tokenCsrf;
VerificarTokenCsrf::exigirTokenCsrfValido();
$_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('x', 64);
try {
    VerificarTokenCsrf::exigirTokenCsrfValido();
    throw new RuntimeException('Se acepto un token falso.');
} catch (ErrorAplicacion $error) {
    if ($error->codigoHttp !== 419) { throw new RuntimeException('Se esperaba 419.'); }
}
echo "OK: permisos, datos publicos, hash de clave y token CSRF.\n";
