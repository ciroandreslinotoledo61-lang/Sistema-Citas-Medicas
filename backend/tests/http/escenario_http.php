<?php
declare(strict_types=1);

require_once __DIR__ . '/ClienteHttpPruebas.php';

/** Contrato anterior completo, sobre una base local con las cuatro cuentas del seeder. */
function ejecutarContratoHttp(string $base, string $front, string $clave): array
{
    $resultados = [];
    $secretos = [$clave];
    $crearCliente = static function () use ($base, &$resultados, &$secretos): ClienteHttpPruebas {
        // Este contrato no contabiliza las peticiones auxiliares del token CSRF.
        return new ClienteHttpPruebas($base, $resultados, $secretos, false);
    };
    $anon = $crearCliente();
    $anon->request('GET', '/estado');
    $anon->request('GET', '/autenticacion/token');
    $anon->request('GET', '/usuarios', esperado: 401);
    $anon->request('POST', '/autenticacion/iniciar-sesion', [], 419, false);
    $anon->request('POST', '/autenticacion/iniciar-sesion', esperado: 400, raw: '{');
    $anon->request('POST', '/autenticacion/iniciar-sesion', esperado: 400, raw: '[]');
    $anon->request('POST', '/autenticacion/iniciar-sesion', esperado: 415,
        headers: ['Content-Type' => 'text/plain'], raw: 'x');
    $anon->request('GET', '/usuarios', esperado: 403, headers: ['Origin' => 'https://otro.example']);
    $anon->request('GET', '/no-existe', esperado: 404);
    $anon->request('OPTIONS', '/citas', esperado: 204, csrf: false);
    $clientes = [];
    foreach (['administrador', 'recepcionista', 'medico', 'paciente'] as $rol) {
        $cliente = $crearCliente();
        $usuario = $cliente->login($rol . '@vidasalud.test', $clave)['datos'];
        comprobarHttp($usuario['codigoRol'] === $rol, 'El login altero el rol de la cuenta.');
        $cliente->request('GET', '/autenticacion/sesion');
        $clientes[$rol] = $cliente;
    }
    $admin = $clientes['administrador'];
    $recep = $clientes['recepcionista'];
    $paciente = $clientes['paciente'];
    $medico = $clientes['medico'];
    $matrices = [
        '/usuarios' => ['administrador'], '/roles' => ['administrador'],
        '/pacientes' => ['administrador', 'recepcionista'], '/especialidades' => ['administrador'],
        '/medicos' => ['administrador'], '/reportes' => ['administrador'],
        '/horarios' => ['administrador', 'medico'],
        '/agenda/medicos' => ['administrador', 'recepcionista', 'medico'],
        '/disponibilidad/medicos' => ['administrador', 'recepcionista', 'paciente'],
        '/disponibilidad/configuracion' => array_keys($clientes), '/pacientes/mi-perfil' => ['paciente'],
    ];
    foreach ($matrices as $ruta => $permitidos) {
        foreach ($clientes as $rol => $cliente) {
            $cliente->request('GET', $ruta, esperado: in_array($rol, $permitidos, true) ? 200 : 403);
        }
    }
    $admin->request('GET', '/usuarios/99999999999999999999999999999999999', esperado: 422);
    $admin->request('DELETE', '/usuarios', esperado: 404);
    $datos = ['nombresUsuario' => 'Contrato', 'apellidosUsuario' => 'HTTP',
        'correoUsuario' => 'contrato-recepcion@example.test', 'contrasenaUsuario' => $clave,
        'confirmacionContrasena' => $clave, 'codigoRol' => 'recepcionista'];
    $u = $admin->request('POST', '/usuarios', $datos, 201)['datos'];
    $uid = $u['idUsuario'];
    $admin->request('GET', "/usuarios/$uid");
    $admin->request('PATCH', "/usuarios/$uid", array_replace($datos, ['nombresUsuario' => 'Contrato editado']));
    $admin->request('PATCH', "/usuarios/$uid/estado", ['usuarioActivo' => false]);
    $especialidad = $admin->request('POST', '/especialidades', ['nombreEspecialidad' => 'Especialidad Contrato HTTP'], 201)['datos'];
    $eid = $especialidad['idEspecialidad'];
    $admin->request('GET', "/especialidades/$eid");
    $admin->request('GET', '/especialidades/activas');
    $admin->request('PATCH', "/especialidades/$eid", ['nombreEspecialidad' => 'Especialidad Contrato HTTP',
        'descripcionEspecialidad' => 'Prueba de arquitectura']);
    $admin->request('PATCH', "/especialidades/$eid/estado", ['especialidadActiva' => false]);
    $admin->request('PATCH', "/especialidades/$eid/estado", ['especialidadActiva' => true]);
    $dm = array_replace($datos, ['correoUsuario' => 'contrato-medico@example.test',
        'matriculaProfesional' => 'CONTRATO-HTTP', 'idEspecialidad' => $eid, 'telefonoMedico' => '70000002']);
    $m = $admin->request('POST', '/medicos', $dm, 201)['datos'];
    $mid = $m['idMedico'];
    $admin->request('GET', "/medicos/$mid");
    $admin->request('PATCH', "/medicos/$mid", $dm);
    $admin->request('PATCH', "/medicos/$mid/estado", ['medicoActivo' => false]);
    $admin->request('PATCH', "/medicos/$mid/estado", ['medicoActivo' => true]);
    $medicoNuevo = $crearCliente();
    $medicoNuevo->login($dm['correoUsuario'], $clave);
    $dp = array_replace($datos, ['correoUsuario' => 'contrato-paciente@example.test',
        'cedulaIdentidad' => 'HTTP-CONTRATO', 'fechaNacimiento' => '2000-01-01',
        'telefonoPaciente' => '70000003', 'direccionPaciente' => 'Santa Cruz',
        'nombreContactoEmergencia' => '', 'telefonoContactoEmergencia' => '']);
    $p = $anon->request('POST', '/autenticacion/registro-paciente', $dp, 201)['datos'];
    $pid = $p['idPaciente'];
    comprobarHttp($p['idHistoriaClinica'] > 0, 'El registro no creo historia clinica.');
    $nuevo = $crearCliente();
    $cuenta = $nuevo->login($dp['correoUsuario'], $clave);
    comprobarHttp($cuenta['datos']['codigoRol'] === 'paciente', 'El registro publico no asigno paciente.');
    $segundo = $recep->request('POST', '/pacientes', array_replace($dp,
        ['correoUsuario' => 'contrato-segundo@example.test', 'cedulaIdentidad' => 'HTTP-SEGUNDO']), 201)['datos'];
    $recep->request('GET', "/pacientes/$pid");
    $recep->request('PATCH', "/pacientes/$pid", $dp);
    $recep->request('PATCH', "/pacientes/$pid/contacto", array_intersect_key($dp, array_flip([
        'telefonoPaciente', 'direccionPaciente', 'nombreContactoEmergencia', 'telefonoContactoEmergencia',
    ])));
    $idSegundo = $segundo['idPaciente'];
    $recep->request('PATCH', "/pacientes/$idSegundo/estado", ['pacienteActivo' => false]);
    $recep->request('PATCH', "/pacientes/$idSegundo/estado", ['pacienteActivo' => true]);
    $anon->request('POST', '/autenticacion/registro-paciente', array_replace($dp,
        ['correoUsuario' => 'contrato-duplicado@example.test']), 409);
    comprobarHttp($admin->request('GET', '/usuarios?busqueda=contrato-duplicado')['datos']['elementos'] === [],
        'El registro duplicado dejo una cuenta parcial.');
    $dia = (new DateTimeImmutable('now', new DateTimeZone('America/La_Paz')))->modify('+7 days')->format('Y-m-d');
    $horario = ['idMedico' => $mid, 'fechaAtencion' => $dia, 'horaInicio' => '08:00', 'horaFin' => '10:00'];
    $creado = $admin->request('POST', '/horarios', $horario, 201)['datos'];
    comprobarHttp($creado['cantidadTurnos'] === 4 && $creado['duracionCitaMinutos'] === 30,
        'Se altero la duracion estandar o cantidad de turnos.');
    $periodo = ['idMedico' => $mid, 'fechaInicial' => $dia, 'fechaFinal' => $dia,
        'diasSemana' => range(1, 7), 'horaInicio' => '10:00', 'horaFin' => '11:00'];
    $admin->request('POST', '/horarios/programacion', $periodo);
    $admin->request('POST', '/horarios/programacion', $periodo);
    $turnos = $nuevo->request('GET', "/disponibilidad/turnos?idMedico=$mid&fecha=$dia")['datos'];
    comprobarHttp(count($turnos) === 6, 'La programacion repetida altero la disponibilidad.');
    $primera = $nuevo->request('POST', '/citas', ['idTurno' => $turnos[0]['idTurno'], 'motivoCita' => 'Control HTTP'], 201)['datos'];
    $cid = $primera['idCita'];
    $nuevo->request('GET', '/citas');
    $nuevo->request('GET', '/citas/activas');
    $nuevo->request('GET', "/citas/$cid");
    $nuevo->request('POST', '/citas', ['idTurno' => $turnos[1]['idTurno']], 409);
    $paciente->request('POST', '/citas', ['idTurno' => $turnos[0]['idTurno']], 409);
    $paciente->request('GET', "/citas/$cid", esperado: 404);
    $medico->request('GET', "/citas/$cid", esperado: 404);
    $medicoNuevo->request('GET', "/citas/$cid");
    $paciente->request('PATCH', "/citas/$cid/cancelacion", [], 404);
    $nuevo->request('PATCH', "/citas/$cid/reprogramacion", ['idTurno' => $turnos[1]['idTurno']], 403);
    $recep->request('PATCH', "/citas/$cid/asistencia", ['asistenciaRegistrada' => 'asistio'], 409);
    $medicoNuevo->request('PATCH', "/citas/$cid/atencion", [], 409);
    $medicoNuevo->request('PATCH', "/citas/$cid/inasistencia", [], 409);
    $recep->request('PATCH', "/citas/$cid/confirmacion", [], 404);
    $segunda = $recep->request('PATCH', "/citas/$cid/reprogramacion", ['idTurno' => $turnos[1]['idTurno']], 201)['datos'];
    $cid2 = $segunda['idCita'];
    comprobarHttp($segunda['idCitaOrigen'] === $cid, 'La reprogramacion perdio su cita de origen.');
    comprobarHttp($nuevo->request('GET', "/citas/$cid")['datos']['estadoCita'] === 'reprogramada',
        'La cita original no refleja la reprogramacion.');
    $medicoNuevo->request('PATCH', "/citas/$cid2/cancelacion", [], 422);
    $medicoNuevo->request('PATCH', "/citas/$cid2/cancelacion", ['motivoCancelacion' => 'Prueba HTTP completada']);
    comprobarHttp(count($nuevo->request('GET', "/disponibilidad/turnos?idMedico=$mid&fecha=$dia")['datos']) === 6,
        'La cancelacion no libero el turno.');
    $admin->request('GET', "/reportes?fechaDesde=$dia&fechaHasta=$dia");
    $vistas = ['/panel', '/usuarios', '/usuarios/nuevo', "/usuarios/$uid/editar", '/pacientes',
        '/pacientes/nuevo', "/pacientes/$pid", '/especialidades', '/especialidades/nueva',
        "/especialidades/$eid/editar", '/medicos', '/medicos/nuevo', "/medicos/$mid/editar", '/citas',
        '/citas/nueva', "/citas/$cid", "/citas/$cid2/reprogramar", '/horarios', '/horarios/nuevo', '/reportes'];
    foreach ($vistas as $vista) {
        $respuesta = $admin->obtenerRespuesta('GET', $front . $vista, seguir: true);
        $html = $respuesta['contenido'];
        comprobarHttp($respuesta['codigo'] === 200 && str_contains($html, 'data-api-url'),
            'Vista administrativa no disponible: ' . $vista);
        comprobarHttp(!str_contains($html, 'Fatal error') && !str_contains($html, 'Warning:'),
            'La vista administrativa expuso errores PHP: ' . $vista);
        $resultados[] = ['actor' => 'administrador', 'vista' => $vista, 'codigo' => $respuesta['codigo']];
    }
    $respuesta = $nuevo->obtenerRespuesta('GET', $front . '/mi-perfil', seguir: true);
    comprobarHttp($respuesta['codigo'] === 200, 'El paciente no pudo acceder a su perfil.');
    $respuesta = $paciente->obtenerRespuesta('GET', $front . '/usuarios', seguir: true);
    comprobarHttp($respuesta['codigo'] === 403, 'Vista administrativa abierta al paciente.');
    $nuevo->request('POST', '/autenticacion/cerrar-sesion');
    $nuevo->request('GET', '/autenticacion/sesion', esperado: 401);
    comprobarHttp(count($resultados) === 137, 'Se altero el conjunto de 137 comprobaciones del contrato anterior.');
    return $resultados;
}

/** Normalizador del contrato conservado para comparar respuestas con campos dinamicos. */
function normalizarContratoHttp(mixed $valor, string $clave = ''): mixed
{
    if (is_array($valor)) {
        $normalizado = [];
        foreach ($valor as $k => $v) {
            $normalizado[$k] = normalizarContratoHttp($v, is_string($k) ? $k : $clave);
        }
        return $normalizado;
    }
    if (in_array($clave, ['tokenCsrf', 'fechaReserva', 'fechaActualizacion', 'fechaRegistroCita',
        'fechaActualizacionCita', 'fecha'], true)) {
        return '<dinamico>';
    }
    if (is_string($valor) && preg_match('/^\d{4}-\d\d-\d\d[T ]\d\d:/', $valor)
        && !in_array($clave, ['fechaHoraInicio', 'fechaHoraFin'], true)) {
        return '<momento>';
    }
    return $valor;
}
