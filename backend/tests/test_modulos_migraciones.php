<?php
declare(strict_types=1);
if(getenv('VIDASALUD_TEST_LOCAL')!=='1'||getenv('DB_HOST')!=='127.0.0.1'||!ctype_digit(getenv('DB_PORT')?:''))throw new RuntimeException('Solo PostgreSQL aislado.');
require dirname(__DIR__).'/src/bootstrap.php';
require __DIR__.'/SoportePruebasAgenda.php';
use CitasMedicas\Data\Seeders\DatosInicialesSeeder;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Business\Services\{HorarioMedicoService,CitaService,ConsultaService,SalaEsperaService};
use CitasMedicas\Shared\Errors\ErrorAplicacion;
$port=getenv('DB_PORT');$base='modulos_'.bin2hex(random_bytes(5)).'_pruebas';
$opciones=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
$servidor=new PDO("pgsql:host=127.0.0.1;port=$port;dbname=postgres;sslmode=disable",'postgres','',$opciones);$servidor->exec('CREATE DATABASE '.$base);
$db=new PDO("pgsql:host=127.0.0.1;port=$port;dbname=$base;sslmode=disable",'postgres','',$opciones);
foreach(glob(dirname(__DIR__).'/src/data/migrations/*.sql') as $archivo){if((int)basename($archivo)<=10){$db->beginTransaction();$db->exec(file_get_contents($archivo));$db->commit();}}
DatosInicialesSeeder::cargarDatosIniciales($db,'Sintetica'.bin2hex(random_bytes(12)));
$datos=new SoportePruebasAgenda($db);$datos->crearDatos();
$admin=(new UsuarioRepository($db))->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
$fecha=(new DateTimeImmutable('+3 days'))->format('Y-m-d');$antes=new DateTimeImmutable($fecha.' 07:00');
$horarios=new HorarioMedicoService($db,$antes);$horarios->registrarHorario($admin,['idMedico'=>$datos->medicos[0]->idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>'08:00','horaFin'=>'09:00']);
$turnos=$horarios->listarTurnosDisponibles($datos->usuariosPacientes[0],['idMedico'=>$datos->medicos[0]->idMedico,'fecha'=>$fecha]);
$cita=(new CitaService($db,$antes))->reservarCita($datos->usuariosPacientes[0],['idTurno'=>$turnos[0]['idTurno']]);
(new ConsultaService($db,new DateTimeImmutable($fecha.' 08:00')))->registrarConsulta($datos->usuariosMedicos[0],['idCita'=>$cita['idCita'],'motivoConsulta'=>'Consulta sintética anterior a 011','diagnostico'=>'Contenido ficticio preservado','tratamientos'=>[['medicamento'=>'Ficticio','dosis'=>'Prueba','frecuencia'=>'Prueba','duracion'=>'Prueba','indicaciones'=>'Prueba']]]);
$snapshot=static function()use($db):array{$r=[];foreach(['usuarios','roles','medicos','pacientes','historias_clinicas','horarios_medicos','turnos','citas','consultas','tratamientos','bitacora_sistema'] as $tabla)$r[$tabla]=$db->query('SELECT * FROM '.$tabla)->fetchAll();return $r;};
$antes=$snapshot();
$ejecutar=static function(array $args,array $cambios=[])use($base):array{
    $env=array_replace(getenv(),['DB_NAME'=>$base,'APP_ENV'=>'local','DB_HOST'=>'127.0.0.1','DB_USER'=>'postgres','DB_PASS'=>'','DB_SSLMODE'=>'disable'],$cambios);
    $p=proc_open([PHP_BINARY,dirname(__DIR__).'/scripts/actualizar-recetas-sala-pagos.php',...$args],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2),$env);
    if(!is_resource($p))throw new RuntimeException('No se inició el actualizador.');fclose($pipes[0]);$salida=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$salida,$error];
};
[$codigo,$salida]=$ejecutar([],['APP_ENV'=>'production','DB_HOST'=>'prohibido.example.test']);
if($codigo!==0||!str_contains($salida,'Vista previa'))throw new RuntimeException('La vista previa abrió una conexión.');
foreach([['APP_ENV'=>'production'],['DB_HOST'=>'prohibido.example.test'],['DB_NAME'=>'sin_sufijo']] as $cambio){[$codigo]=$ejecutar(['--aplicar'],$cambio);if($codigo!==1)throw new RuntimeException('El actualizador admitió un destino no autorizado.');}
[$codigo,$salida,$error]=$ejecutar(['--aplicar']);if($codigo!==0)throw new RuntimeException('No se instaló 011–013 local: '.$salida.$error);
if($snapshot()!==$antes)throw new RuntimeException('Las migraciones cambiaron datos anteriores.');
foreach(['recetas_medicas','sala_espera','eventos_sala_espera','pagos_demostracion'] as $tabla){if((int)$db->query('SELECT count(*) FROM '.$tabla)->fetchColumn()!==0)throw new RuntimeException('Se crearon datos retroactivos.');}
try{
    $db->exec("INSERT INTO pagos_demostracion(id_cita,monto_centavos,metodo,referencia,creado_por,estado,fecha_confirmacion) VALUES(999,100,'qr_demo','DEMO-INVALIDO',1,'pagado',clock_timestamp())");
    throw new RuntimeException('El SQL permitió insertar un pago ya confirmado.');
}catch(PDOException $e){if((string)$e->getCode()!=='P2013')throw $e;}
[$codigo]=$ejecutar(['--aplicar']);if($codigo!==1||$snapshot()!==$antes)throw new RuntimeException('La repetición modificó registros.');
$recepcion=(new UsuarioRepository($db))->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
$nueva=(new CitaService($db,new DateTimeImmutable($fecha.' 07:00')))->reservarCita($datos->usuariosPacientes[0],['idTurno'=>$turnos[1]['idTurno']]);
$sala=new SalaEsperaService($db,new DateTimeImmutable($fecha.' 08:30'));
$sala->cambiar($recepcion,$nueva['idCita'],['accion'=>'llegada','version'=>0]);$sala->cambiar($datos->usuariosMedicos[0],$nueva['idCita'],['accion'=>'iniciar','version'=>1]);
try{
    (new CitaService($db,new DateTimeImmutable($fecha.' 09:00')))->marcarCitaInasistencia($datos->usuariosMedicos[0],$nueva['idCita']);
    throw new RuntimeException('Se registró ausencia mientras el paciente estaba en consulta.');
}catch(ErrorAplicacion $e){if($e->codigoHttp!==409)throw $e;}
echo "OK: actualizador 011–013 local/transaccional, rechazo de producción/remotos/repetición, conservación de datos anteriores y bloqueo de ausencia contradictoria durante consulta.\n";
