<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';

use CitasMedicas\Business\Services\{CitaService, HorarioMedicoService};
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\{MedicoRepository, UsuarioRepository};
use CitasMedicas\Shared\Errors\ErrorAplicacion;

// El ejecutor aislado fija host, puerto y base antes de llegar aquí.
if (getenv('VIDASALUD_TEST_LOCAL') !== '1' || getenv('DB_HOST') !== '127.0.0.1' || !str_ends_with(getenv('DB_NAME') ?: '', '_pruebas')) {
    throw new RuntimeException('Usa el ejecutor --completo con PostgreSQL aislado.');
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$datos = new SoportePruebasAgenda($conexion);
$comprobaciones = 0;
function comprobarCorreccion(bool $valor, string $mensaje): void {
    global $comprobaciones;
    if (!$valor) { throw new RuntimeException($mensaje); }
    $comprobaciones++;
}
function rechazarCorreccion(callable $operacion, int $codigo): void {
    try { $operacion(); } catch (ErrorAplicacion $error) { comprobarCorreccion($error->codigoHttp === $codigo, $error->getMessage()); return; }
    throw new RuntimeException('Se aceptó una operación restringida.');
}
try {
    $datos->crearDatos();
    $usuarios = new UsuarioRepository($conexion);
    $admin = $usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $recepcion = $usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
    [$medico, $otroMedico] = $datos->usuariosMedicos;
    [$paciente, $otroPaciente] = $datos->usuariosPacientes;
    [$perfil, $otroPerfil] = $datos->medicos;
    $ahora = new DateTimeImmutable('today 07:00');
    $fecha = $ahora->modify('+7 days')->format('Y-m-d');
    $horarios = new HorarioMedicoService($conexion, $ahora);
    $citas = new CitaService($conexion, $ahora);
    $bloque = ['idMedico'=>$perfil->idMedico, 'fechaAtencion'=>$fecha, 'horaInicio'=>'08:00', 'horaFin'=>'09:00'];
    $creado = $horarios->registrarHorario($admin, $bloque);
    $horarios->registrarHorario($admin, array_replace($bloque, ['horaInicio'=>'10:00', 'horaFin'=>'11:00']));
    $horarios->registrarHorario($admin, array_replace($bloque, ['idMedico'=>$otroPerfil->idMedico]));
    $turnos = $horarios->listarTurnosDisponibles($paciente, ['idMedico'=>$perfil->idMedico, 'fecha'=>$fecha]);
    comprobarCorreccion(count($turnos) === 4, 'Los bloques separados conservan el descanso.');
    foreach ($turnos as $turno) {
        comprobarCorreccion(strtotime($turno['fechaHoraFin']) - strtotime($turno['fechaHoraInicio']) === 1800, 'Turnos de 30 minutos.');
        comprobarCorreccion(substr($turno['fechaHoraInicio'], 11, 2) !== '09', 'No hay turnos durante el descanso.');
    }
    $propios = $horarios->listarHorarios($medico, ['fecha'=>$fecha])['elementos'];
    comprobarCorreccion(count($propios) === 2 && array_unique(array_column($propios, 'idMedico')) === [$perfil->idMedico], 'El médico solo recibe sus bloques.');
    comprobarCorreccion($horarios->listarHorarios($medico, ['fecha'=>$fecha, 'idMedico'=>$otroPerfil->idMedico])['elementos'] === [], 'Manipular idMedico no amplía el alcance de sesión.');
    foreach ([$paciente, $recepcion] as $actor) { rechazarCorreccion(fn()=>$horarios->listarHorarios($actor, []), 403); }
    rechazarCorreccion(fn()=>$horarios->registrarHorario($medico, $bloque), 403);
    $filtro = $horarios->listarHorarios($admin, ['fecha'=>$fecha, 'idMedico'=>$otroPerfil->idMedico]);
    comprobarCorreccion(count($filtro['elementos']) === 1 && $filtro['elementos'][0]['idMedico'] === $otroPerfil->idMedico, 'Filtro administrativo por ID y fecha.');
    for ($dia = 8; $dia <= 29; $dia++) {
        $horarios->registrarHorario($admin, array_replace($bloque, ['fechaAtencion'=>$ahora->modify('+' . $dia . ' days')->format('Y-m-d')]));
    }
    $primera = $horarios->listarHorarios($medico, []);
    $segunda = $horarios->listarHorarios($medico, ['pagina'=>'2']);
    comprobarCorreccion(count($primera['elementos']) === 20 && $primera['hayPaginaSiguiente'] && count($segunda['elementos']) === 4 && !$segunda['hayPaginaSiguiente'], 'Paginación de veinte bloques propios.');
    // Slot legado: se conserva, pero no se ofrece ni acepta para nuevas reservas.
    $consulta = $conexion->prepare('INSERT INTO horarios_medicos(id_medico,fecha_atencion,hora_inicio,hora_fin,duracion_cita_minutos) VALUES(:medico,:fecha,\'12:00\',\'13:00\',15) RETURNING id_horario_medico');
    $consulta->execute(['medico'=>$perfil->idMedico, 'fecha'=>$fecha]);
    $idLegado = (int) $consulta->fetchColumn();
    $consulta = $conexion->prepare("INSERT INTO turnos(id_horario_medico,fecha_hora_inicio,fecha_hora_fin,duracion_turno_minutos) VALUES(:horario,:inicio,:fin,15) RETURNING id_turno");
    $consulta->execute(['horario'=>$idLegado, 'inicio'=>$fecha.' 12:00', 'fin'=>$fecha.' 12:15']);
    $idTurnoLegado = (int) $consulta->fetchColumn();
    comprobarCorreccion(count($horarios->listarTurnosDisponibles($paciente, ['idMedico'=>$perfil->idMedico, 'fecha'=>$fecha])) === 4, 'No se ofrecen turnos legados de 15 minutos.');
    rechazarCorreccion(fn()=>$citas->reservarCita($otroPaciente, ['idTurno'=>$idTurnoLegado]), 409);
    $cita = $citas->reservarCita($paciente, ['idTurno'=>$turnos[0]['idTurno']]);
    rechazarCorreccion(fn()=>$citas->reprogramarCita($recepcion, $cita['idCita'], ['idTurno'=>$idTurnoLegado]), 409);
    comprobarCorreccion($citas->obtenerCita($paciente, $cita['idCita'])['estadoCita'] === 'programada', 'La reprogramación fallida conserva el origen.');
    comprobarCorreccion((int)$conexion->query('SELECT duracion_cita_minutos FROM horarios_medicos WHERE id_horario_medico='.$idLegado)->fetchColumn() === 15, 'No se modifican bloques legados.');
    // Datos sintéticos: un perfil médico con rol incorrecto no es disponibilidad válida.
    $consulta = $conexion->prepare("UPDATE usuarios SET id_rol=(SELECT id_rol FROM roles WHERE codigo_rol='paciente') WHERE id_usuario=:id");
    $consulta->execute(['id'=>$otroMedico->idUsuario]);
    $repositorio = new MedicoRepository($conexion);
    comprobarCorreccion(!in_array($otroPerfil->idMedico, array_column($repositorio->listarMedicosDisponibles(), 'idMedico'), true), 'El selector de disponibilidad exige rol médico.');
    comprobarCorreccion($horarios->listarTurnosDisponibles($paciente, ['idMedico'=>$otroPerfil->idMedico, 'fecha'=>$fecha]) === [], 'La disponibilidad de turnos exige rol médico.');
    rechazarCorreccion(fn()=>$horarios->registrarHorario($admin, array_replace($bloque, ['idMedico'=>$otroPerfil->idMedico, 'horaInicio'=>'14:00', 'horaFin'=>'15:00'])), 409);
    $turnoOtro = (int)$conexion->query('SELECT t.id_turno FROM turnos t JOIN horarios_medicos h USING(id_horario_medico) WHERE h.id_medico='.$otroPerfil->idMedico.' ORDER BY t.id_turno LIMIT 1')->fetchColumn();
    rechazarCorreccion(fn()=>$citas->reservarCita($otroPaciente, ['idTurno'=>$turnoOtro]), 409);
    $usuarios->actualizarDatosUsuario($otroMedico->idUsuario, ['nombresUsuario'=>$medico->nombresUsuario, 'apellidosUsuario'=>$medico->apellidosUsuario, 'correoUsuario'=>$otroMedico->correoUsuario]);
    $agenda = $citas->listarMedicosParaAgenda($admin);
    $homonimos = array_values(array_filter($agenda, fn($fila)=>in_array($fila['idMedico'], [$perfil->idMedico, $otroPerfil->idMedico], true)));
    comprobarCorreccion(count($homonimos) === 2 && $homonimos[0]['nombreMedico'] === $homonimos[1]['nombreMedico'] && $homonimos[0]['idMedico'] !== $homonimos[1]['idMedico'], 'Los homónimos conservan identificadores y perfiles separados.');
    comprobarCorreccion(isset($homonimos[0]['nombreEspecialidad']), 'El selector de agenda incluye especialidad.');
} finally { $datos->eliminarDatos(); }
echo "OK: $comprobaciones comprobaciones de alcance, filtros, paginación, descansos, rol y duración fija.\n";
