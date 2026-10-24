<?php

declare(strict_types=1);

spl_autoload_register(static function (string $nombreClase): void {
    $prefijo = 'CitasMedicas\\';
    if (!str_starts_with($nombreClase, $prefijo)) {
        return;
    }
    $partesNombre = explode('\\', substr($nombreClase, strlen($prefijo)));
    $archivoClase = array_pop($partesNombre) . '.php';
    $rutaClase = __DIR__ . '/' . implode('/', array_map('strtolower', $partesNombre)) . '/' . $archivoClase;
    if (is_file($rutaClase)) {
        require_once $rutaClase;
    }
});

\CitasMedicas\Shared\Config\CargarVariablesEntorno::cargarArchivoEntorno(dirname(__DIR__) . '/.env');
date_default_timezone_set(\CitasMedicas\Shared\Config\ConfiguracionAgenda::ZONA_HORARIA_BOLIVIA);
