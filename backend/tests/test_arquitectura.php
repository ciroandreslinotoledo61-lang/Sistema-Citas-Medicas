<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Presentation\Routes\RutasApi;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SolicitudHttp;

function comprobarArquitectura(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$rutas = RutasApi::definir();
// Contrato revisado: 60 rutas anteriores más 14 privadas de recetas/sala/pagos.
// No se regenera automáticamente: un cambio exige revisar permisos.
$esperadas = json_decode(file_get_contents(__DIR__ . '/contratos/rutas.json'), true, 512, JSON_THROW_ON_ERROR);
$porContrato = [];
foreach ($esperadas as $esperada) {
    $clave = $esperada['metodo'] . ' ' . $esperada['ruta'];
    comprobarArquitectura(!isset($porContrato[$clave]), 'Contrato esperado duplicado: ' . $clave);
    $porContrato[$clave] = $esperada;
}
comprobarArquitectura(count($rutas) === count($porContrato), 'Se agregaron o eliminaron rutas del contrato revisado.');
$contratos = [];
foreach ($rutas as $ruta) {
    $clave = $ruta->metodo . ' ' . $ruta->ruta;
    comprobarArquitectura(!isset($contratos[$clave]), 'Ruta duplicada: ' . $clave);
    comprobarArquitectura(isset($porContrato[$clave]), 'Metodo o ruta fuera del contrato: ' . $clave);
    comprobarArquitectura(get_object_vars($ruta) === $porContrato[$clave],
        'Cambio de controlador, accion, permisos o argumentos: ' . $clave);
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
// null permite anonimos; [] exige sesion. No son equivalentes. Estas operaciones
// siempre pasan por CSRF global (se comprueba tambien por HTTP en la suite aislada).
$publicas = array_values(array_map(static fn($ruta) => $ruta->metodo . ' ' . $ruta->ruta,
    array_filter($rutas, static fn($ruta) => $ruta->roles === null)));
$publicasEsperadas = ['GET /api/estado', 'GET /api/autenticacion/token',
    'POST /api/autenticacion/iniciar-sesion', 'POST /api/autenticacion/cerrar-sesion',
    'POST /api/autenticacion/registro-paciente', 'POST /api/autenticacion/recuperar-contrasena',
    'POST /api/autenticacion/confirmar-recuperacion'];
sort($publicas); sort($publicasEsperadas);
comprobarArquitectura($publicas === $publicasEsperadas, 'Se altero la superficie publica de autenticacion.');
foreach (['administrador', 'recepcionista'] as $rol) {
    comprobarArquitectura(!PermisosSistema::tienePermiso($rol, 'historial.consultar'), 'Acceso clinico ampliado sin autorizacion expresa.');
    comprobarArquitectura(!PermisosSistema::tienePermiso($rol, 'consultas.escribir'), 'Escritura clinica ampliada.');
}
comprobarArquitectura(PermisosSistema::tienePermiso('administrador', 'usuarios.gestionar'), 'Falta gestion administrativa.');
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
echo 'OK: ' . count($rutas) . " contratos HTTP, permisos, superficie publica, firmas y dependencias entre capas.\n";
