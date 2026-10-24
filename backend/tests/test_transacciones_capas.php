<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';

use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;

if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
    throw new RuntimeException('Usa exclusivamente una base independiente terminada en _pruebas.');
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$datos = new SoportePruebasAgenda($conexion);
$transaccion = new Transaccion($conexion);
try {
    $datos->crearDatos();
    $idMedico = $datos->medicos[0]->idMedico;
    $administrador = (new UsuarioRepository($conexion))->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $fecha = (new DateTimeImmutable('+7 days'))->format('Y-m-d');
    // Fallo deliberado en el SEGUNDO turno: el primer INSERT ya se ejecuto.
    // El trigger solo afecta al medico de esta prueba y se elimina en finally.
    $conexion->exec("CREATE FUNCTION prueba_fallo_segundo_turno() RETURNS trigger LANGUAGE plpgsql AS \$\$
        BEGIN
            IF NEW.fecha_hora_inicio::time = '08:30:00'::time
                AND EXISTS (SELECT 1 FROM horarios_medicos WHERE id_horario_medico = NEW.id_horario_medico
                    AND id_medico = $idMedico) THEN
                RAISE EXCEPTION 'Fallo deliberado de la prueba' USING ERRCODE = 'P3001';
            END IF;
            RETURN NEW;
        END \$\$");
    $conexion->exec('CREATE TRIGGER prueba_fallo_segundo_turno BEFORE INSERT ON turnos
        FOR EACH ROW EXECUTE FUNCTION prueba_fallo_segundo_turno()');
    try {
        (new HorarioMedicoService($conexion))->registrarHorario($administrador, [
            'idMedico' => $idMedico, 'fechaAtencion' => $fecha, 'horaInicio' => '08:00', 'horaFin' => '09:00',
        ]);
        throw new RuntimeException('Debio fallar el segundo turno.');
    } catch (PDOException $error) {
        if ((string) $error->getCode() !== 'P3001') {
            throw $error;
        }
    }
    $consulta = $conexion->prepare('SELECT count(*) FROM horarios_medicos WHERE id_medico = :idMedico');
    $consulta->execute(['idMedico' => $idMedico]);
    if ((int) $consulta->fetchColumn() !== 0 || $conexion->inTransaction()) {
        throw new RuntimeException('Quedo un horario incompleto o una transaccion abierta.');
    }
    $conexion->exec('DROP TRIGGER prueba_fallo_segundo_turno ON turnos');
    $conexion->exec('DROP FUNCTION prueba_fallo_segundo_turno()');
    $resultado = (new HorarioMedicoService($conexion))->registrarHorario($administrador, [
        'idMedico' => $idMedico, 'fechaAtencion' => $fecha, 'horaInicio' => '08:00', 'horaFin' => '09:00',
    ]);
    if ($resultado['cantidadTurnos'] !== 2) {
        throw new RuntimeException('El nuevo intento no confirmo ambos turnos.');
    }
    $aislamiento = $transaccion->ejecutar(
        fn () => $conexion->query('SHOW transaction_isolation')->fetchColumn(),
        Transaccion::LECTURA_CONFIRMADA
    );
    if ($aislamiento !== 'read committed') {
        throw new RuntimeException('Se perdio el aislamiento de agenda.');
    }
    try {
        $transaccion->ejecutar(
            fn () => $conexion->exec('UPDATE usuarios SET nombres_usuario = nombres_usuario WHERE FALSE'),
            Transaccion::REPORTE_CONSISTENTE
        );
        throw new RuntimeException('El reporte permitio una escritura.');
    } catch (PDOException $error) {
        if ((string) $error->getCode() !== '25006' || $conexion->inTransaction()) {
            throw $error;
        }
    }
    echo "OK: fallo en segundo turno revierte horario completo; reintento confirma; aislamientos de agenda y reporte conservados.\n";
} finally {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $conexion->exec('DROP TRIGGER IF EXISTS prueba_fallo_segundo_turno ON turnos');
    $conexion->exec('DROP FUNCTION IF EXISTS prueba_fallo_segundo_turno()');
    $datos->eliminarDatos();
}
