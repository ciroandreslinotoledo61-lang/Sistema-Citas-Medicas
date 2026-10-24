<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
if (PHP_SAPI !== 'cli' || !str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
    throw new RuntimeException('Ejecuta con PHP CLI nativo y una base aislada terminada en _pruebas.');
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
if (($argv[1] ?? '') === '--trabajador') {
    $datos = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
    $usuario = (new UsuarioRepository($conexion))->obtenerUsuarioPorId((int) $datos['idUsuario']);
    file_put_contents($datos['barrera'] . '.' . $datos['numero'] . '.listo', 'listo');
    $limite = microtime(true) + 20;
    while (!is_file($datos['barrera'])) {
        if (microtime(true) > $limite) { throw new RuntimeException('No llego la barrera de inicio.'); }
        usleep(20000);
    }
    try {
        $reserva = (new CitaService($conexion, new DateTimeImmutable($datos['ahora'])))->reservarCita($usuario, ['idTurno'=>$datos['idTurno']]);
        echo json_encode(['codigo'=>201,'idCita'=>$reserva['idCita']], JSON_THROW_ON_ERROR);
    } catch (ErrorAplicacion $error) { echo json_encode(['codigo'=>$error->codigoHttp,'mensaje'=>$error->getMessage()], JSON_THROW_ON_ERROR); }
    exit;
}
if (!function_exists('proc_open')) { throw new RuntimeException('Habilita proc_open para iniciar dos conexiones independientes.'); }
/** Dos procesos PHP reales esperan la misma barrera. No simula concurrencia en una conexion. */
function ejecutarReservasConcurrentes(array $solicitudes, string $ahora): array
{
    $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'citas-concurrencia-' . bin2hex(random_bytes(8));
    if (!mkdir($directorio, 0700)) { throw new RuntimeException('No se pudo crear el directorio temporal.'); }
    $barrera = $directorio . DIRECTORY_SEPARATOR . 'iniciar';
    $procesos = []; $salidas = [];
    try {
        foreach ($solicitudes as $numero => $solicitud) {
            $argumento = json_encode($solicitud + compact('barrera', 'numero', 'ahora'), JSON_THROW_ON_ERROR);
            $proceso = proc_open([PHP_BINARY, __FILE__, '--trabajador', $argumento],
                [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $tuberias, dirname(__DIR__,2));
            if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar el trabajador.'); }
            fclose($tuberias[0]);
            $procesos[] = ['proceso'=>$proceso,'salida'=>$tuberias[1],'error'=>$tuberias[2]];
        }
        $limite = microtime(true) + 25;
        while (!is_file($barrera.'.0.listo') || !is_file($barrera.'.1.listo')) {
            if (microtime(true)>$limite) { throw new RuntimeException('Los trabajadores no quedaron listos.'); }
            usleep(20000);
        }
        file_put_contents($barrera,'iniciar');
        foreach ($procesos as $trabajador) {
            do {
                $estado = proc_get_status($trabajador['proceso']);
                if (!$estado['running']) { break; }
                if (microtime(true)>$limite) { throw new RuntimeException('Tiempo agotado esperando las reservas.'); }
                usleep(20000);
            } while (true);
            $texto = stream_get_contents($trabajador['salida']);
            $errores = stream_get_contents($trabajador['error']);
            if ($estado['exitcode'] !== 0 || $errores !== '') { throw new RuntimeException('Fallo de trabajador: '.$errores.$texto); }
            $salidas[] = json_decode($texto,true,512,JSON_THROW_ON_ERROR);
        }
        return $salidas;
    } finally {
        foreach ($procesos as $trabajador) {
            if (proc_get_status($trabajador['proceso'])['running']) { proc_terminate($trabajador['proceso']); }
            fclose($trabajador['salida']); fclose($trabajador['error']); proc_close($trabajador['proceso']);
        }
        foreach (glob($directorio . DIRECTORY_SEPARATOR . '*') ?: [] as $archivo) { unlink($archivo); }
        rmdir($directorio);
    }
}
$datosPrueba = new SoportePruebasAgenda($conexion);
try {
    $datosPrueba->crearDatos();
    $administrador = (new UsuarioRepository($conexion))->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $fecha = (new DateTimeImmutable('+7 days'))->format('Y-m-d');
    $ahora = $fecha . ' 07:00';
    $horarios = new HorarioMedicoService($conexion,new DateTimeImmutable($ahora));
    $turnos = [];
    foreach ($datosPrueba->medicos as $indice=>$medico) {
        $horarios->registrarHorario($administrador,['idMedico'=>$medico->idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>'08:00','horaFin'=>'12:00']);
        $turnos[$indice] = $horarios->listarTurnosDisponibles($administrador,['idMedico'=>$medico->idMedico,'fecha'=>$fecha]);
    }
    $casos = [
        [['idUsuario'=>$datosPrueba->usuariosPacientes[0]->idUsuario,'idTurno'=>$turnos[0][0]['idTurno']],
         ['idUsuario'=>$datosPrueba->usuariosPacientes[1]->idUsuario,'idTurno'=>$turnos[0][0]['idTurno']]],
        [['idUsuario'=>$datosPrueba->usuariosPacientes[0]->idUsuario,'idTurno'=>$turnos[0][1]['idTurno']],
         ['idUsuario'=>$datosPrueba->usuariosPacientes[0]->idUsuario,'idTurno'=>$turnos[1][6]['idTurno']]],
    ];
    foreach ($casos as $caso) {
        $resultados = ejecutarReservasConcurrentes($caso,$ahora);
        $codigos = array_column($resultados,'codigo'); sort($codigos);
        if ($codigos !== [201,409]) { throw new RuntimeException('Se esperaba una reserva y un rechazo: '.json_encode($resultados)); }
        $idGanadora = (int) ($resultados[0]['idCita'] ?? $resultados[1]['idCita']);
        (new CitaService($conexion,new DateTimeImmutable($ahora)))->cancelarCita($administrador,$idGanadora,['motivoCancelacion'=>'Fin de prueba concurrente']);
    }
} finally { $datosPrueba->eliminarDatos(); }
echo "OK: dos pacientes compiten por un turno y un paciente compite por dos medicos. Una sola reserva en ambos casos.\n";
