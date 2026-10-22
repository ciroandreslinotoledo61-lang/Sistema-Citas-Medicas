<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Validators\EspecialidadValidator;
use CitasMedicas\Business\Validators\MedicoValidator;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Presentation\Middlewares\VerificarRolUsuario;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

function exigirCondicionValidacion(bool $condicion, string $mensaje): void
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
}

function exigirErrorValidacion(callable $operacion, int $codigoEsperado): void
{
    try { $operacion(); } catch (ErrorAplicacion $error) {
        exigirCondicionValidacion($error->codigoHttp === $codigoEsperado, 'Codigo de error inesperado.');
        return;
    }
    throw new RuntimeException('No se rechazo la entrada invalida.');
}

$especialidad = EspecialidadValidator::validarDatosEspecialidad(['nombreEspecialidad' => '  Medicina   general  ']);
exigirCondicionValidacion($especialidad['nombreEspecialidad'] === 'Medicina general', 'Normalizacion de espacios.');
exigirCondicionValidacion($especialidad['descripcionEspecialidad'] === '', 'Descripcion opcional.');
exigirErrorValidacion(fn() => EspecialidadValidator::validarDatosEspecialidad(['nombreEspecialidad' => 'ab']), 422);
exigirErrorValidacion(fn() => EspecialidadValidator::validarDatosEspecialidad(['nombreEspecialidad' => ['dato']]), 422);
exigirErrorValidacion(fn() => EspecialidadValidator::validarEstadoEspecialidad(['especialidadActiva' => 'false']), 422);
exigirCondicionValidacion(EspecialidadValidator::validarEstadoEspecialidad(['especialidadActiva' => false]) === false, 'Estado booleano.');

$datosMedico = [
    'nombresUsuario' => 'Ana', 'apellidosUsuario' => 'Prueba', 'correoUsuario' => ' ANA@EXAMPLE.TEST ',
    'matriculaProfesional' => ' sc-1234 ', 'idEspecialidad' => '1',
    'contrasenaUsuario' => 'FraseParaPrueba123', 'confirmacionContrasena' => 'FraseParaPrueba123',
    'codigoRol' => 'administrador', 'medicoActivo' => false,
];
$medico = MedicoValidator::validarDatosRegistroMedico($datosMedico);
exigirCondicionValidacion($medico['matriculaProfesional'] === 'SC-1234', 'Normalizacion de matricula.');
exigirCondicionValidacion($medico['idEspecialidad'] === 1 && $medico['correoUsuario'] === 'ana@example.test', 'Tipos normalizados.');
exigirCondicionValidacion(!array_key_exists('codigoRol', $medico) && !array_key_exists('medicoActivo', $medico), 'Campos no permitidos descartados.');
foreach ([true, false, 0, -1, 1.5, [], 'abc', '1.5'] as $idInvalido) {
    exigirErrorValidacion(fn() => MedicoValidator::validarDatosRegistroMedico(array_replace($datosMedico, ['idEspecialidad' => $idInvalido])), 422);
}
exigirErrorValidacion(fn() => MedicoValidator::validarDatosRegistroMedico(array_replace($datosMedico, ['matriculaProfesional' => '<script>'])), 422);
exigirErrorValidacion(fn() => MedicoValidator::validarDatosRegistroMedico(array_replace($datosMedico, ['confirmacionContrasena' => 'DistintaClave123'])), 422);
exigirErrorValidacion(fn() => MedicoValidator::validarEstadoMedico(['medicoActivo' => 'true']), 422);
exigirCondicionValidacion(MedicoValidator::validarEstadoMedico(['medicoActivo' => false]) === false, 'Desactivacion valida.');

foreach (['recepcionista', 'medico', 'paciente'] as $codigoRol) {
    $usuario = new Usuario(1, 'Prueba', 'Permiso', 'permiso@example.test', '', true, 1, 1, $codigoRol, $codigoRol);
    exigirErrorValidacion(fn() => VerificarRolUsuario::exigirRolPermitido($usuario, ['administrador']), 403);
}
echo "OK: validaciones de especialidades, medicos, normalizacion, tipos y permisos.\n";
