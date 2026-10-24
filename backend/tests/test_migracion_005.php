<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Seeders\DatosInicialesSeeder;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
    throw new RuntimeException('Usa una base de pruebas aislada, terminada en _pruebas.');
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$esquema = 'prueba_migracion_' . bin2hex(random_bytes(8));
$ruta = dirname(__DIR__) . '/src/data/migrations/';
$comprobaciones = 0;
function comprobarMigracion(bool $resultado, string $mensaje): void {
    global $comprobaciones;
    if (!$resultado) { throw new RuntimeException($mensaje); }
    $comprobaciones++;
}
function rechazarSqlMigracion(PDO $conexion, string $sql, array $codigos): void {
    try { $conexion->exec($sql); } catch (PDOException $error) { comprobarMigracion(in_array((string)$error->getCode(),$codigos,true),$error->getMessage()); return; }
    throw new RuntimeException('El SQL debio rechazar la operacion.');
}
$conexion->exec('CREATE SCHEMA '.$esquema);
try {
    $conexion->exec('SET search_path TO '.$esquema);
    $conexion->beginTransaction();
    foreach (['001_crear_tablas_sistema.sql','002_unicidad_especialidades_medicos.sql','003_responsabilidades_citas.sql','004_agenda_30_minutos_y_perfiles.sql'] as $archivo) {
        $conexion->exec(file_get_contents($ruta.$archivo));
    }
    $conexion->commit();
    DatosInicialesSeeder::cargarDatosIniciales($conexion,'ClaveDePruebaMigracion123');
    $idMedico=(int)$conexion->query('SELECT id_medico FROM medicos LIMIT 1')->fetchColumn();
    $idPaciente=(int)$conexion->query('SELECT id_paciente FROM pacientes LIMIT 1')->fetchColumn();
    $idUsuario=(int)$conexion->query("SELECT id_usuario FROM usuarios WHERE correo_usuario='recepcionista@vidasalud.test'")->fetchColumn();
    $fecha=(new DateTimeImmutable('+10 days'))->format('Y-m-d');
    // Datos legados: fuera del nuevo rango y con un turno de 15 minutos ya reservado.
    $idHorario=(int)$conexion->query("INSERT INTO horarios_medicos(id_medico,fecha_atencion,hora_inicio,hora_fin,duracion_cita_minutos)
        VALUES ($idMedico,'$fecha','20:00','21:30',30) RETURNING id_horario_medico")->fetchColumn();
    $turnos=[];
    foreach ([['20:00','20:15',15],['20:30','21:00',30],['21:00','21:30',30]] as [$inicio,$fin,$minutos]) {
        $turnos[]=(int)$conexion->query("INSERT INTO turnos(id_horario_medico,fecha_hora_inicio,fecha_hora_fin,duracion_turno_minutos)
            VALUES ($idHorario,'$fecha $inicio','$fecha $fin',$minutos) RETURNING id_turno")->fetchColumn();
    }
    $citas=[];
    foreach (array_slice($turnos,0,2) as $turno) {
        $citas[]=(int)$conexion->query("INSERT INTO citas(id_paciente,id_turno,id_usuario_registro) VALUES ($idPaciente,$turno,$idUsuario) RETURNING id_cita")->fetchColumn();
    }
    $horasAntes=$conexion->query('SELECT * FROM turnos ORDER BY id_turno')->fetchAll();
    $usuariosAntes=$conexion->query('SELECT * FROM usuarios ORDER BY id_usuario')->fetchAll();
    $estadosAntes=$conexion->query('SELECT id_cita,estado_cita FROM citas ORDER BY id_cita')->fetchAll();
    for ($i=0;$i<2;$i++) {
        $conexion->beginTransaction(); $conexion->exec(file_get_contents($ruta.'005_programacion_y_cita_activa.sql')); $conexion->commit();
    }
    comprobarMigracion($horasAntes===$conexion->query('SELECT * FROM turnos ORDER BY id_turno')->fetchAll(),'Conserva exactamente los turnos legados de 15 y 30 minutos.');
    comprobarMigracion($usuariosAntes===$conexion->query('SELECT * FROM usuarios ORDER BY id_usuario')->fetchAll(),'Conserva usuarios y contrasenas.');
    comprobarMigracion($estadosAntes===$conexion->query('SELECT id_cita,estado_cita FROM citas ORDER BY id_cita')->fetchAll(),'No resuelve duplicidades cancelando citas.');
    comprobarMigracion((int)$conexion->query('SELECT count(*) FROM citas WHERE excepcion_cita_activa_previa')->fetchColumn()===2,'Marca solamente duplicidades previas.');
    $nueva="INSERT INTO citas(id_paciente,id_turno,excepcion_cita_activa_previa) VALUES ($idPaciente,{$turnos[2]},TRUE)";
    rechazarSqlMigracion($conexion,$nueva,['P2001']);
    $conexion->exec("UPDATE citas SET estado_cita='confirmada',id_usuario_actualizacion=$idUsuario WHERE id_cita={$citas[0]}");
    comprobarMigracion((int)$conexion->query('SELECT count(*) FROM citas WHERE excepcion_cita_activa_previa')->fetchColumn()===2,'Confirmar una cita legada no pierde su excepcion.');
    $conexion->exec("UPDATE citas SET estado_cita='cancelada',id_usuario_actualizacion=$idUsuario WHERE id_cita={$citas[0]}");
    rechazarSqlMigracion($conexion,$nueva,['P2001']);
    $conexion->exec("UPDATE citas SET estado_cita='atendida',asistencia_registrada='asistio',id_usuario_actualizacion=$idUsuario WHERE id_cita={$citas[1]}");
    comprobarMigracion((int)$conexion->query('SELECT count(*) FROM citas WHERE excepcion_cita_activa_previa')->fetchColumn()===0,'Finalizar retira las excepciones de ambas citas.');
    $conexion->exec($nueva);
    comprobarMigracion((int)$conexion->query('SELECT count(*) FROM citas WHERE excepcion_cita_activa_previa')->fetchColumn()===0,'Una insercion no puede falsificar una excepcion.');
    $conexion->exec("UPDATE citas SET excepcion_cita_activa_previa=TRUE,historial_cambios='[]'::jsonb WHERE id_turno={$turnos[2]}");
    comprobarMigracion((int)$conexion->query('SELECT count(*) FROM citas WHERE excepcion_cita_activa_previa')->fetchColumn()===0,'Una actualizacion no puede falsificar la excepcion.');
    comprobarMigracion((int)$conexion->query("SELECT jsonb_array_length(historial_cambios) FROM citas WHERE id_turno={$turnos[2]}")->fetchColumn()===1,'No permite sobrescribir la auditoria.');
    comprobarMigracion((int)$conexion->query("SELECT jsonb_array_length(historial_cambios) FROM citas WHERE id_cita={$citas[0]}")->fetchColumn()===2,'Solo registra los cambios posteriores a 005.');
    rechazarSqlMigracion($conexion,"UPDATE citas SET estado_cita='confirmada',asistencia_registrada='pendiente' WHERE id_cita={$citas[1]}",['P2002']);
    rechazarSqlMigracion($conexion,"UPDATE citas SET estado_cita='atendida' WHERE id_turno={$turnos[2]}",['P2002']);
    comprobarMigracion((int)$conexion->query('SELECT count(*) FROM citas')->fetchColumn()===3,'Conserva todas las citas anteriores y la nueva.');
} finally {
    if ($conexion->inTransaction()) { $conexion->rollBack(); }
    $conexion->exec('SET search_path TO public');
    // Solo elimina el esquema aleatorio que esta prueba acaba de crear.
    $conexion->exec('DROP SCHEMA '.$esquema.' CASCADE');
}
echo "OK: $comprobaciones comprobaciones de migracion repetible, datos legados y proteccion SQL.\n";
