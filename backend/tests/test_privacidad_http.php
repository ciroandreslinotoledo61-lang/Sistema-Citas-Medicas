<?php
declare(strict_types=1);

require_once __DIR__ . '/http/ClienteHttpPruebas.php';

/** Escenarios equivalentes con dos pacientes y dos medicos del cluster aislado. */
function probarPrivacidadHttp(string $base, string $front, string $clave, array $cuentas): void
{
    $resultados = [];
    $secretos = [$clave, 'ClaveDePruebaAgenda123'];
    $cliente = static function () use ($base, &$resultados, &$secretos): ClienteHttpPruebas {
        return new ClienteHttpPruebas($base, $resultados, $secretos);
    };
    $anon = $cliente();
    $anon->request('GET', '/historias-clinicas/mi-historial', esperado: 401);
    $pacientes = $medicos = [];
    foreach ($cuentas as $cuenta) {
        $paciente = $cliente();
        $medico = $cliente();
        $paciente->login($cuenta['correoPaciente'], 'ClaveDePruebaAgenda123');
        $medico->login($cuenta['correoMedico'], 'ClaveDePruebaAgenda123');
        $pacientes[] = $paciente;
        $medicos[] = $medico;
    }
    foreach ($cuentas as $indice => $cuenta) {
        $paciente = $pacientes[$indice];
        $medico = $medicos[$indice];
        $otra = $cuentas[1 - $indice];
        $propio = $paciente->request('GET', '/historias-clinicas/mi-historial')['datos'];
        comprobarHttp($propio['historia']['idPaciente'] === $cuenta['idPaciente'], 'El historial no pertenece al paciente autenticado.');
        comprobarHttp($propio['puedeEditarHistoria'] === false, 'El paciente obtuvo edicion clinica.');
        comprobarHttp(array_column($propio['consultas']['elementos'], 'idConsulta') === [$cuenta['idConsulta']], 'El historial contiene consultas ajenas.');
        $paciente->request('GET', '/historias-clinicas/' . $cuenta['idPaciente']);
        $paciente->request('GET', '/historias-clinicas/' . $otra['idPaciente'], esperado: 404);
        $paciente->request('GET', '/historias-clinicas/mi-historial?idPaciente=' . $otra['idPaciente'], esperado: 422);
        $paciente->request('GET', '/historias-clinicas/mi-historial?busqueda=Segundo', esperado: 422);
        $paciente->request('GET', '/consultas', esperado: 403);
        $propia = $paciente->request('GET', '/consultas/' . $cuenta['idConsulta'])['datos'];
        comprobarHttp($propia['diagnostico'] === 'Diagnostico privado ' . $indice, 'Diagnostico propio incorrecto.');
        comprobarHttp($propia['tratamientos'][0]['medicamento'] === 'Tratamiento privado ' . $indice, 'Tratamiento propio incorrecto.');
        $paciente->request('GET', '/consultas/' . $otra['idConsulta'], esperado: 404);
        $paciente->request('PATCH', '/consultas/' . $cuenta['idConsulta'], [], 403);
        $paciente->request('PATCH', '/historias-clinicas/' . $cuenta['idPaciente'], [], 403);
        $medico->request('GET', '/historias-clinicas/' . $cuenta['idPaciente']);
        $medico->request('GET', '/consultas/' . $cuenta['idConsulta']);
        $medico->request('GET', '/historias-clinicas/' . $otra['idPaciente'], esperado: 404);
        $medico->request('GET', '/consultas/' . $otra['idConsulta'], esperado: 404);
        $medico->request('PATCH', '/historias-clinicas/' . $otra['idPaciente'], [], 404);
        $medico->request('POST', '/consultas', ['idCita' => $otra['idCitaProxima'],
            'motivoConsulta' => 'Ficticio', 'diagnostico' => 'Ficticio'], 404);
        foreach (['/pacientes/' . $otra['idPaciente'], '/pacientes?busqueda=Segundo'] as $ruta) {
            $paciente->request('GET', $ruta, esperado: 403);
        }
        $perfil = $paciente->request('GET', '/pacientes/mi-perfil?idPaciente=' . $otra['idPaciente'])['datos'];
        comprobarHttp($perfil['idPaciente'] === $cuenta['idPaciente'], 'Un identificador altero el perfil propio.');
        $citas = $paciente->request('GET', '/citas')['datos']['elementos'];
        comprobarHttp($citas !== [] && count(array_filter($citas,
            static fn(array $cita): bool => $cita['idPaciente'] === $cuenta['idPaciente'])) === count($citas),
            'El listado de citas contiene informacion ajena.');
        $paciente->request('GET', '/citas/' . $otra['idCitaProxima'], esperado: 404);
        $paciente->request('PATCH', '/citas/' . $otra['idCitaProxima'] . '/cancelacion', [], 404);
        $paciente->request('POST', '/citas', ['idTurno' => $cuenta['idTurnoDisponible'], 'idPaciente' => $otra['idPaciente']], 403);
        $paciente->request('PATCH', '/citas/' . $cuenta['idCitaAtendida'] . '/cancelacion', [], 409);
        $paciente->request('PATCH', '/citas/' . $cuenta['idCitaProxima'] . '/cancelacion', [], 419, csrf: false);
        $cancelada = $paciente->request('PATCH', '/citas/' . $cuenta['idCitaProxima'] . '/cancelacion', [])['datos'];
        comprobarHttp($cancelada['estadoCita'] === 'cancelada', 'La cita propia no fue cancelada.');
        $paciente->request('PATCH', '/citas/' . $cuenta['idCitaProxima'] . '/cancelacion', [], 409);
        [, $html] = $paciente->vista($front, '/historial-clinico');
        comprobarHttp(str_contains($html, 'Mi historial clínico') && !str_contains($html, 'Paciente o CI'), 'El historial presenta un buscador administrativo.');
        comprobarHttp(!str_contains($html, 'name="busqueda"') && !str_contains($html, 'id="formulario-historia"'), 'El paciente dispone de buscador o editor clinico.');
    }
    foreach (['administrador', 'recepcionista'] as $rol) {
        $actor = $cliente();
        $actor->login($rol . '@vidasalud.test', $clave);
        foreach ($cuentas as $cuenta) {
            $actor->request('GET', '/historias-clinicas/' . $cuenta['idPaciente'], esperado: 403);
            $actor->request('GET', '/consultas/' . $cuenta['idConsulta'], esperado: 403);
            $actor->request('POST', '/consultas', [], 403);
            $datos = json_encode($actor->request('GET', '/pacientes/' . $cuenta['idPaciente']), JSON_THROW_ON_ERROR);
            $datos .= json_encode($actor->request('GET', '/citas/' . $cuenta['idCitaAtendida']), JSON_THROW_ON_ERROR);
            foreach (['Diagnostico privado', 'Tratamiento privado', 'Alergia privada', 'Antecedente privado'] as $texto) {
                comprobarHttp(!str_contains($datos, $texto), 'Una respuesta administrativa contiene informacion clinica.');
            }
        }
        if ($rol === 'administrador') {
            $eventos = $actor->request('GET', '/bitacora?modulo=historias_clinicas&accion=consultar')['datos']['elementos'];
            comprobarHttp($eventos !== [] && !str_contains(json_encode($eventos, JSON_THROW_ON_ERROR), 'Diagnostico privado'), 'Falta auditoria o contiene diagnosticos.');
        } else {
            $actor->request('GET', '/bitacora', esperado: 403);
        }
    }
    comprobarHttp(count($resultados) === 111, 'Cambio inesperado en las 111 comprobaciones HTTP de privacidad.');
    echo 'OK: ' . count($resultados) . " comprobaciones HTTP de privacidad, dos pacientes, medicos autorizados y cancelacion.\n";
}
