<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Seeders\DatosInicialesSeeder;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
if(!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'),'_pruebas'))throw new RuntimeException('Usa una base _pruebas.');
$c=ConexionPostgresql::obtenerConexionPostgresql();$esquema='prueba006_'.bin2hex(random_bytes(6));$ruta=dirname(__DIR__).'/src/data/migrations/';
function verificar006(bool $ok,string $mensaje):void{if(!$ok)throw new RuntimeException($mensaje);}
function datos006(mixed $v):array{return is_array($v)?$v:json_decode($v,true,512,JSON_THROW_ON_ERROR);}
$c->exec('CREATE SCHEMA '.$esquema);
try{
 $c->exec('SET search_path TO '.$esquema);$c->beginTransaction();foreach(glob($ruta.'00[1-5]_*.sql') as $f)$c->exec(file_get_contents($f));$c->commit();
 DatosInicialesSeeder::cargarDatosIniciales($c,'ClavePruebaMigracion123');
 $paciente=(int)$c->query('SELECT id_paciente FROM pacientes LIMIT 1')->fetchColumn();$medico=(int)$c->query('SELECT id_medico FROM medicos LIMIT 1')->fetchColumn();
 $h=(int)$c->query("INSERT INTO horarios_medicos(id_medico,fecha_atencion,hora_inicio,hora_fin,duracion_cita_minutos) VALUES($medico,'2030-01-10','09:00','10:00',30) RETURNING id_horario_medico")->fetchColumn();
 $t=(int)$c->query("INSERT INTO turnos(id_horario_medico,fecha_hora_inicio,fecha_hora_fin,duracion_turno_minutos) VALUES($h,'2030-01-10 09:00','2030-01-10 09:30',30) RETURNING id_turno")->fetchColumn();
 $id=(int)$c->query("INSERT INTO citas(id_paciente,id_turno,estado_cita) VALUES($paciente,$t,'confirmada') RETURNING id_cita")->fetchColumn();
 $antes=datos006($c->query('SELECT historial_cambios FROM citas WHERE id_cita='.$id)->fetchColumn());
 $c->beginTransaction();$c->exec(file_get_contents($ruta.'006_estado_programada.sql'));$c->commit();
 $fila=$c->query('SELECT * FROM citas WHERE id_cita='.$id)->fetch();$hist=datos006($fila['historial_cambios']);
 verificar006($fila['estado_cita']==='programada','Migrar valor real.');verificar006($hist[0]===$antes[0]&&count($hist)===2,'Preservar evento anterior.');
 $c->beginTransaction();$c->exec(file_get_contents($ruta.'006_estado_programada.sql'));$c->commit();
 verificar006(datos006($c->query('SELECT historial_cambios FROM citas WHERE id_cita='.$id)->fetchColumn())===$hist,'Repeticion sin duplicar eventos.');
 try{$c->exec("UPDATE citas SET estado_cita='confirmada' WHERE id_cita=$id");throw new RuntimeException('Estado retirado permitido');}catch(PDOException $e){verificar006(in_array((string)$e->getCode(),['P2002','23514'],true),'Estado retirado rechazado.');}
 verificar006($c->query("SELECT tgenabled FROM pg_trigger WHERE tgrelid='citas'::regclass AND tgname='tr_citas_evitar_cruces'")->fetchColumn()==='O','Cruces reactivados.');
 echo "OK: 5 comprobaciones de migracion 006 y preservacion.\n";
}finally{if($c->inTransaction())$c->rollBack();$c->exec('SET search_path TO public');$c->exec('DROP SCHEMA '.$esquema.' CASCADE');}
