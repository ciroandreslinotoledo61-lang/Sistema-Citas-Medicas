<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
use CitasMedicas\Business\Validators\AgendaValidator;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Data\Models\Usuario;
$comprobaciones = 0;
function verificarAgenda(bool $resultado, string $mensaje): void {
    global $comprobaciones;
    if (!$resultado) { throw new RuntimeException($mensaje); }
    $comprobaciones++;
}
function rechazarAgenda(callable $operacion, int $codigo = 422): void {
    try { $operacion(); } catch (ErrorAplicacion $error) { verificarAgenda($error->codigoHttp === $codigo, $error->getMessage()); return; }
    throw new RuntimeException('La operacion debio rechazarse.');
}
$ahora = new DateTimeImmutable('2030-01-01 08:00:00');
$horario = ['idMedico' => '1', 'fechaAtencion' => '2030-01-02', 'horaInicio' => '08:00', 'horaFin' => '09:00'];
verificarAgenda(AgendaValidator::validarHorario($horario, $ahora)['idMedico'] === 1, 'Identificador normalizado.');
foreach ([0, -1, true, [], '1.5', '01', '1 OR 1=1', '9999999999999999999999999'] as $valor) {
    rechazarAgenda(fn() => AgendaValidator::validarIdentificador(['idMedico' => $valor], 'idMedico'));
}
foreach (['0000-01-01', '2030-02-30', '01/02/2030', '2030-13-01', [], null] as $fecha) {
    rechazarAgenda(fn() => AgendaValidator::validarFecha($fecha));
}
verificarAgenda(AgendaValidator::validarFecha('2032-02-29') === '2032-02-29', 'Fecha bisiesta.');
verificarAgenda(AgendaValidator::validarFecha('', true) === '', 'Fecha opcional.');
foreach ([['horaInicio' => '08:05'], ['horaFin' => '24:00'], ['horaInicio' => []], ['horaFin' => '08:00'],
    ['horaFin' => '07:45'], ['fechaAtencion' => '2029-12-31'], ['fechaAtencion' => '2031-02-01'],
    ['fechaAtencion' => '2030-01-01', 'horaInicio' => '08:00']] as $cambio) {
    rechazarAgenda(fn() => AgendaValidator::validarHorario(array_replace($horario, $cambio), $ahora));
}
rechazarAgenda(fn() => AgendaValidator::validarFiltrosCitas(['estado' => 'inventado']));
rechazarAgenda(fn() => AgendaValidator::validarFiltrosCitas(['estado' => []]));
rechazarAgenda(fn() => AgendaValidator::validarFiltrosCitas(['pagina' => '0']));
$esperado = [
    'administrador' => ['reportes.consultar', 'horarios.crear', 'horarios.consultar', 'disponibilidad.consultar', 'citas.consultar', 'citas.reservar', 'citas.cancelar', 'citas.reprogramar', 'citas.asistencia', 'citas.atender', 'agenda.filtrar', 'citas.inasistencia'],
    'recepcionista' => ['disponibilidad.consultar', 'citas.consultar', 'citas.reservar', 'citas.cancelar', 'citas.reprogramar', 'citas.asistencia', 'citas.atender', 'agenda.filtrar', 'citas.inasistencia'],
    'medico' => ['horarios.consultar', 'citas.consultar', 'citas.atender', 'agenda.filtrar', 'citas.cancelar', 'citas.inasistencia'],
    'paciente' => ['disponibilidad.consultar', 'citas.consultar', 'citas.reservar', 'citas.cancelar'],
    'desconocido' => [],
];
foreach ($esperado as $rol => $operaciones) {
    foreach ($esperado['administrador'] as $operacion) {
        verificarAgenda(PermisosSistema::tienePermiso($rol, $operacion) === in_array($operacion, $operaciones, true), "$rol / $operacion");
    }
}
verificarAgenda(!PermisosSistema::tienePermiso('administrador', 'operacion.inexistente'), 'Denegar permiso desconocido.');
$inactivo = new Usuario(1, 'Cuenta', 'Inactiva', 'inactiva@example.test', '', false, 1, 1, 'administrador', 'Administrador');
rechazarAgenda(fn() => PermisosSistema::exigirPermiso($inactivo, 'citas.consultar'), 403);
echo "OK: $comprobaciones comprobaciones de fechas, intervalos, entradas y matriz de permisos.\n";
