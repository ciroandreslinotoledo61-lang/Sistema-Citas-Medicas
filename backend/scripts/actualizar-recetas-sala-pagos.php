<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$migraciones=[
    'recetas_medicas'=>'011_recetas_medicas.sql',
    'sala_espera'=>'012_sala_espera.sql',
    'pagos_demostracion'=>'013_pagos_demostracion.sql',
];
if(!in_array('--aplicar',$argv,true)){
    echo "Vista previa sin conexión: aplicar después de 001–010, en este orden:\n".implode("\n",$migraciones)."\nNo se ejecutó SQL. --aplicar solo admite una base LOCAL _pruebas.\n";exit;
}
// Exige configuración explícita del proceso; no carga archivos .env.
if(!in_array(getenv('APP_ENV'),['local','test'],true)||!in_array(getenv('DB_HOST'),['127.0.0.1','::1','localhost'],true)
    ||!preg_match('/^[a-z][a-z0-9_]*_pruebas$/D',getenv('DB_NAME')?:'')||!ctype_digit(getenv('DB_PORT')?:'')){
    fwrite(STDERR,"Rechazado: configura por proceso un entorno local/test, host local, puerto y base _pruebas. Producción está prohibida.\n");exit(1);
}
putenv('VIDASALUD_TEST_LOCAL=1');
require dirname(__DIR__).'/src/bootstrap.php';
try{
    $conexion=\CitasMedicas\Data\Config\ConexionPostgresql::obtenerConexionPostgresql();
    $destino=$conexion->query('SELECT current_database() AS base,host(inet_server_addr()) AS host')->fetch();
    if($destino['base']!==getenv('DB_NAME')||!in_array($destino['host'],['127.0.0.1','::1'],true))throw new RuntimeException('La conexión no corresponde a la base local declarada.');
    $conexion->beginTransaction();
    $conexion->query('SELECT pg_advisory_xact_lock(2026110801)');
    foreach($migraciones as $tabla=>$archivo){
        $q=$conexion->prepare('SELECT to_regclass(:tabla)');$q->execute(['tabla'=>'public.'.$tabla]);
        if($q->fetchColumn())throw new RuntimeException('Ya existe '.$tabla.'. No se repiten migraciones; revisa el esquema antes de continuar.');
        $conexion->exec(file_get_contents(dirname(__DIR__).'/src/data/migrations/'.$archivo));
    }
    $conexion->commit();echo "011–013 instaladas en la base local de pruebas, en una transacción.\n";
}catch(Throwable $e){
    if(isset($conexion)&&$conexion->inTransaction())$conexion->rollBack();
    fwrite(STDERR,($e instanceof PDOException?'No se aplicaron las migraciones: esquema previo incompatible o incompleto.':$e->getMessage())."\n");exit(1);
}
