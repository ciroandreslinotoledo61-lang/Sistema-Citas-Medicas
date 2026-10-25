<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Presentation\Routes\RutasApi;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SolicitudHttp;

function comprobarArquitectura(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$rutas = RutasApi::definir();
comprobarArquitectura(count($rutas) === 56, 'Debe conservarse el contrato de las 56 rutas.');
$contratos = [];
foreach ($rutas as $ruta) {
    $clave = $ruta->metodo . ' ' . $ruta->ruta;
    comprobarArquitectura(!isset($contratos[$clave]), 'Ruta duplicada: ' . $clave);
    $contratos[$clave] = true;
    $metodo = new ReflectionMethod($ruta->controlador, $ruta->accion);
    comprobarArquitectura($metodo->isPublic(), 'La accion no es publica: ' . $clave);
    comprobarArquitectura(count($ruta->argumentos) === $metodo->getNumberOfParameters(), 'Firma incompatible: ' . $clave);
    $ejemplo = preg_replace('/\{[a-zA-Z][a-zA-Z0-9]*\}/', '123', $ruta->ruta);
    comprobarArquitectura($ruta->coincidir($ejemplo) !== null, 'No reconoce su ruta: ' . $clave);
    comprobarArquitectura($ruta->coincidir($ejemplo . '/otra') === null, 'Acepta segmentos extra: ' . $clave);
    if (str_contains($ruta->ruta, '{')) {
        $invalida = preg_replace('/\{[a-zA-Z][a-zA-Z0-9]*\}/', '0', $ruta->ruta);
        comprobarArquitectura($ruta->coincidir($invalida) === null, 'Acepta identificador cero.');
    }
}
try {
    SolicitudHttp::obtenerIdPositivo(str_repeat('9', 40));
    throw new RuntimeException('Se acepto un identificador fuera del rango entero.');
} catch (ErrorAplicacion $error) {
    comprobarArquitectura($error->codigoHttp === 422, 'Codigo incorrecto para identificador invalido.');
}

$fuente = dirname(__DIR__) . '/src';
foreach (glob($fuente . '/business/services/*.php') as $archivo) {
    $codigo = file_get_contents($archivo);
    comprobarArquitectura(!preg_match('/\$_(?:GET|POST|REQUEST|SERVER|SESSION)\b|->(?:query|prepare|exec)\s*\(/', $codigo), 'HTTP o SQL en servicio: ' . basename($archivo));
}
foreach (glob($fuente . '/presentation/controllers/*.php') as $archivo) {
    $codigo = file_get_contents($archivo);
    comprobarArquitectura(!str_contains($codigo, 'Data\\Repositories') && !preg_match('/->(?:query|prepare|exec|beginTransaction)\s*\(/', $codigo), 'Acceso a datos en controlador: ' . basename($archivo));
}
foreach (glob($fuente . '/presentation/routes/*.php') as $archivo) {
    $codigo = file_get_contents($archivo);
    comprobarArquitectura(!preg_match('/Business\\\\Services|Data\\\\|RespuestaJson|\$_/', $codigo), 'Responsabilidad ajena a rutas: ' . basename($archivo));
}
foreach (glob($fuente . '/data/repositories/*.php') as $archivo) {
    $codigo = file_get_contents($archivo);
    comprobarArquitectura(!preg_match('/Business\\\\|Presentation\\\\|->codigoRol|PermisosSistema|DateTimeImmutable/', $codigo), 'Reglas o permisos en repositorio: ' . basename($archivo));
}
echo "OK: 56 rutas unicas, firmas de controladores, parametros y dependencias entre capas.\n";
