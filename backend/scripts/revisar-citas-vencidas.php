<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(!in_array('--aplicar',$argv,true)){
    echo "Tarea de citas vencidas (sin conexión en este modo).\n";
    echo "Uso: php backend/scripts/revisar-citas-vencidas.php --aplicar\n";
    echo "Requiere CITAS_AUTOMATICAS_HABILITADAS=true. Tolerancia: CITAS_TOLERANCIA_MINUTOS (30 por defecto).\n";
    echo "Programar externamente cada minuto; no depende de visitas web. No ejecuta migraciones.\n";exit;
}
// Configuración explícita del proceso: ni el job ni su vista previa leen .env.
if(getenv('CITAS_AUTOMATICAS_HABILITADAS')!=='true'||!in_array(getenv('APP_ENV'),['production','local','test'],true)){
    fwrite(STDERR,"Tarea deshabilitada: define APP_ENV y CITAS_AUTOMATICAS_HABILITADAS=true explícitamente en el proceso.\n");exit(1);
}
if(getenv('APP_ENV')!=='production'&&(!in_array(getenv('DB_HOST'),['127.0.0.1','::1','localhost'],true)||!preg_match('/^[a-z][a-z0-9_]*_pruebas$/D',getenv('DB_NAME')?:''))){
    fwrite(STDERR,"En local/test solo se admite una base local independiente terminada en _pruebas.\n");exit(1);
}
if(!getenv('DB_HOST')||!getenv('DB_NAME')||!ctype_digit(getenv('DB_PORT')?:'')||!getenv('DB_USER')){
    fwrite(STDERR,"Falta la configuración explícita de conexión para la tarea.\n");exit(1);
}
define('VIDASALUD_ENTORNO_PROCESO',true); // Solo CLI: bootstrap sin .env ni indicadores de prueba en producción.
require dirname(__DIR__).'/src/bootstrap.php';
try{
    $resultado=(new \CitasMedicas\Business\Services\CitasVencidasService(\CitasMedicas\Data\Config\ConexionPostgresql::obtenerConexionPostgresql()))->revisar();
    echo json_encode($resultado,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){
    // No imprimir DSN, datos de pacientes, trazas o credenciales en logs del cron.
    $mensaje=$e instanceof PDOException?'Falló la operación transaccional; el lote se revirtió. Revisa disponibilidad y permisos de la base.':$e->getMessage();
    fwrite(STDERR,'Revisión de citas detenida: '.$mensaje.PHP_EOL);exit(1);
}
