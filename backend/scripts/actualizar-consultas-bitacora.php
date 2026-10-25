<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/src/bootstrap.php';
use CitasMedicas\Data\Config\ConexionPostgresql;
$conexion=null;
try{
 $conexion=ConexionPostgresql::obtenerConexionPostgresql();$conexion->beginTransaction();$conexion->exec('SELECT pg_advisory_xact_lock(2026102406)');
 $conexion->query('SELECT excepcion_cita_activa_previa,historial_cambios FROM citas LIMIT 0');
 $estado=(string)$conexion->query("SELECT pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid='citas'::regclass AND conname='ck_citas_estado'")->fetchColumn();
 $bitacora=$conexion->query("SELECT to_regclass('public.bitacora_sistema')")->fetchColumn();
 $clinica=$conexion->query("SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='consultas' AND column_name='version_consulta'")->fetchColumn();
 $aplicadas=[];
 foreach(['006_estado_programada.sql'=>str_contains($estado,"'programada'"),'007_bitacora_sistema.sql'=>(bool)$bitacora,'008_consultas_historial_clinico.sql'=>(bool)$clinica] as $archivo=>$instalada){
  if($instalada)continue;$sql=file_get_contents(dirname(__DIR__).'/src/data/migrations/'.$archivo);if($sql===false)throw new RuntimeException('Archivo ausente.');$conexion->exec($sql);$aplicadas[]=$archivo;
 }
 $conexion->commit();echo $aplicadas?'Actualizacion completada: '.implode(', ',$aplicadas).".\n":"006-008 ya instaladas. No se cambiaron datos.\n";
}catch(Throwable $error){if($conexion?->inTransaction())$conexion->rollBack();fwrite(STDERR,"Actualizacion revertida. Verifica conexion, permisos y que 005 este instalada. No recrees la base.\n");exit(1);}
