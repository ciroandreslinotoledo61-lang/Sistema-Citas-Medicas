<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Validators\PacienteValidator;
use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

function exigirResultadoPrueba(bool $condicion, string $mensaje): void
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
}

function exigirRechazoValidacion(callable $operacion, string $mensaje): void
{
    try { $operacion(); } catch (ErrorAplicacion $error) {
        exigirResultadoPrueba($error->codigoHttp === 422, $mensaje . ': codigo inesperado');
        return;
    }
    throw new RuntimeException($mensaje . ': se aceptaron datos invalidos');
}

$datosPaciente = [
    'nombresUsuario' => 'Ana', 'apellidosUsuario' => 'Prueba',
    'correoUsuario' => ' ANA@EXAMPLE.TEST ', 'contrasenaUsuario' => 'FraseParaPrueba123',
    'confirmacionContrasena' => 'FraseParaPrueba123', 'cedulaIdentidad' => 'ci-1234',
    'fechaNacimiento' => '2000-02-29', 'telefonoPaciente' => '+591 70000000',
    'direccionPaciente' => '', 'nombreContactoEmergencia' => '', 'telefonoContactoEmergencia' => '',
];
$datosValidados = PacienteValidator::validarDatosRegistroPaciente($datosPaciente);
exigirResultadoPrueba($datosValidados['correoUsuario'] === 'ana@example.test', 'Normalizacion de correo');
exigirResultadoPrueba($datosValidados['cedulaIdentidad'] === 'CI-1234', 'Normalizacion de CI');
exigirRechazoValidacion(fn() => PacienteValidator::validarDatosRegistroPaciente(array_replace($datosPaciente, ['fechaNacimiento' => '2001-02-29'])), 'Fecha inexistente');
exigirRechazoValidacion(fn() => PacienteValidator::validarDatosRegistroPaciente(array_replace($datosPaciente, ['fechaNacimiento' => '2999-01-01'])), 'Fecha futura');
exigirRechazoValidacion(fn() => PacienteValidator::validarDatosRegistroPaciente(array_replace($datosPaciente, ['correoUsuario' => ['correo']])), 'Tipo incorrecto');
exigirRechazoValidacion(fn() => PacienteValidator::validarDatosRegistroPaciente(array_replace($datosPaciente, ['confirmacionContrasena' => 'OtraFrase123456'])), 'Confirmacion distinta');
exigirRechazoValidacion(fn() => PacienteValidator::validarDatosRegistroPaciente(array_replace($datosPaciente, ['nombreContactoEmergencia' => 'Luis'])), 'Contacto incompleto');
exigirRechazoValidacion(fn() => UsuarioValidator::validarDatosRegistroUsuario($datosPaciente + ['codigoRol' => 'medico']), 'Rol no habilitado');
exigirRechazoValidacion(fn() => UsuarioValidator::validarEstadoUsuario(['usuarioActivo' => 'false']), 'Booleano falso como texto');
exigirResultadoPrueba(UsuarioValidator::validarEstadoUsuario(['usuarioActivo' => false]) === false, 'Booleano valido');
exigirRechazoValidacion(fn() => ValidarCamposFormulario::validarParametrosBusqueda(['pagina' => -1]), 'Pagina negativa');
exigirRechazoValidacion(fn() => ValidarCamposFormulario::validarContrasenaRegistro(['contrasenaUsuario' => str_repeat('a', 73), 'confirmacionContrasena' => str_repeat('a', 73)]), 'Limite bcrypt');
echo "OK: validaciones, normalizacion, roles, fechas y tipos.\n";
