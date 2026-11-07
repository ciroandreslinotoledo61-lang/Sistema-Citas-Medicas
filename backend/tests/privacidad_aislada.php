<?php
declare(strict_types=1);

// Solo el cluster efimero del runner HTTP. No admite la conexion privada del proyecto.
if (PHP_SAPI !== 'cli' || getenv('VIDASALUD_TEST_LOCAL') !== '1'
    || getenv('DB_HOST') !== '127.0.0.1'
    || !preg_match('/^[a-z0-9_]+_pruebas$/D', getenv('DB_NAME') ?: '')
    || !ctype_digit(getenv('DB_PORT') ?: '')) {
    throw new RuntimeException('Se requiere un cluster local aislado de pruebas.');
}
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';

use CitasMedicas\Business\Services\{CitaService,ConsultaService,HorarioMedicoService,HistoriaClinicaService};
use CitasMedicas\Data\Repositories\UsuarioRepository;

$conexion = new PDO('pgsql:host=127.0.0.1;port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME') . ';sslmode=disable',
    'postgres', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$conexion->exec("SET TIME ZONE 'America/La_Paz'");
$soporte = new SoportePruebasAgenda($conexion);
$soporte->crearDatos();
$admin = (new UsuarioRepository($conexion))->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
$fecha = (new DateTimeImmutable('+10 days'))->format('Y-m-d');
$antes = new DateTimeImmutable($fecha . ' 08:00');
$durante = new DateTimeImmutable($fecha . ' 09:00');
$horarios = new HorarioMedicoService($conexion, $antes);
$citas = new CitaService($conexion, $antes);
$consultas = new ConsultaService($conexion, $durante);
$historias = new HistoriaClinicaService($conexion);
$resultado = [];
foreach ([0, 1] as $indice) {
    $medico = $soporte->usuariosMedicos[$indice];
    $paciente = $soporte->usuariosPacientes[$indice];
    $horarios->registrarHorario($admin, ['idMedico' => $soporte->medicos[$indice]->idMedico,
        'fechaAtencion' => $fecha, 'horaInicio' => '09:00', 'horaFin' => '11:00']);
    $turnos = $horarios->listarTurnosDisponibles($paciente,
        ['idMedico' => $soporte->medicos[$indice]->idMedico, 'fecha' => $fecha]);
    $cita = $citas->reservarCita($paciente, ['idTurno' => $turnos[0]['idTurno']]);
    $consulta = $consultas->registrarConsulta($medico, ['idCita' => $cita['idCita'],
        'motivoConsulta' => 'Motivo ficticio', 'diagnostico' => 'Diagnostico privado ' . $indice,
        'observaciones' => 'Observacion privada ' . $indice,
        'tratamientos' => [['medicamento' => 'Tratamiento privado ' . $indice,
            'dosis' => 'Ficticia', 'frecuencia' => 'Ficticia', 'duracion' => 'Ficticia', 'indicaciones' => 'Solo prueba']]]);
    $historias->actualizarHistoria($medico, $cita['idPaciente'], ['tipoSangre' => 'O+',
        'alergias' => 'Alergia privada ' . $indice, 'antecedentes' => 'Antecedente privado ' . $indice, 'versionHistoria' => 1]);
    $proxima = $citas->reservarCita($paciente, ['idTurno' => $turnos[1]['idTurno']]);
    $resultado[] = ['correoPaciente' => $paciente->correoUsuario, 'correoMedico' => $medico->correoUsuario,
        'idPaciente' => $cita['idPaciente'], 'idConsulta' => $consulta['idConsulta'],
        'idCitaAtendida' => $cita['idCita'], 'idCitaProxima' => $proxima['idCita'],
        'idTurnoDisponible' => $turnos[2]['idTurno']];
}
echo json_encode($resultado, JSON_THROW_ON_ERROR);
