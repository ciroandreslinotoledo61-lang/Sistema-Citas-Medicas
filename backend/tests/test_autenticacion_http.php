<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/SoporteProcesosPrueba.php';
require_once __DIR__ . '/http/ClienteHttpPruebas.php';

/** Crea PostgreSQL efimero; no carga .env ni utiliza conexiones del proyecto. */
function ejecutarPruebasHttpAisladas(array $argumentos): void
{
    $opciones = getopt('', ['pg-bin:', 'regresion', 'completo', 'navegador', 'solo-recuperacion', 'help']);
    if (isset($opciones['help']) || !isset($opciones['pg-bin'])) {
        echo "Uso: php backend/tests/ejecutar-pruebas.php --completo --pg-bin RUTA [--navegador]\n";
        if (!isset($opciones['help'])) { throw new RuntimeException('Indica la carpeta de binarios PostgreSQL locales.'); }
        return;
    }
    $proyecto = dirname(__DIR__, 2);
    $temporal = $proyecto . '/.tmp-auth/ejecucion-php-' . bin2hex(random_bytes(6));
    if (!mkdir($temporal, 0700, true)) { throw new RuntimeException('No se pudo preparar el directorio local.'); }
    $sufijo = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
    $pg = static fn(string $nombre): string => rtrim($opciones['pg-bin'], '/\\') . '/' . $nombre . $sufijo;
    $puertos = [];
    while (count($puertos) < 5) { $puertos[SoporteProcesosPrueba::puertoLibre()] = true; }
    [$puertoBd, $puertoApi, $puertoFront, $puertoCorreo, $puertoFrontCorreo] = array_keys($puertos);
    $base = "http://127.0.0.1:$puertoApi/api";
    $front = "http://127.0.0.1:$puertoFront";
    $baseCorreo = "http://127.0.0.1:$puertoCorreo/api";
    $frontCorreo = "http://127.0.0.1:$puertoFrontCorreo";
    $buzon = $temporal . '/buzon-simulado.json';
    $clave = 'PruebaAislada' . bin2hex(random_bytes(12));
    $entorno = array_replace(getenv(), [
        'DB_HOST' => '127.0.0.1', 'DB_PORT' => (string) $puertoBd, 'DB_NAME' => 'vidasalud_auth_pruebas',
        'DB_USER' => 'postgres', 'DB_PASS' => '', 'DB_SSLMODE' => 'disable', 'APP_ENV' => 'local',
        'APP_DEBUG' => 'false', 'SESSION_SECURE' => 'false', 'SESSION_COOKIE_NAME' => 'vidasalud_auth_pruebas',
        'SESSION_IDLE_SECONDS' => '1800', 'SEED_USERS_PASSWORD' => $clave, 'API_URL' => $base,
        'API_INTERNAL_URL' => $base, 'FRONTEND_URL' => $front, 'VIDASALUD_TEST_LOCAL' => '1',
        'CITA_DURACION_MINUTOS' => '30', 'MAIL_PROVIDER' => 'resend', 'MAIL_FROM' => '', 'RESEND_API_KEY' => '',
    ]);
    $entornoCorreo = array_replace($entorno, ['API_URL' => $baseCorreo, 'API_INTERNAL_URL' => $baseCorreo,
        'FRONTEND_URL' => $frontCorreo, 'RECUPERACION_BUZON_PRUEBAS' => $buzon]);
    $ejecutar = static function (array $args, ?array $env = null) use ($proyecto, $temporal, $entorno): string {
        $nombre = pathinfo($args[1] ?? $args[0], PATHINFO_FILENAME);
        if (str_starts_with($nombre, '-')) { $nombre = pathinfo($args[0], PATHINFO_FILENAME); }
        return SoporteProcesosPrueba::ejecutar($args, $proyecto, $env ?? $entorno, $temporal . '/' . $nombre . '.log');
    };
    $soporte = static fn(string ...$args): string => $ejecutar([PHP_BINARY, 'backend/tests/autenticacion_aislada.php', ...$args]);
    $soporteCorreo = static fn(string ...$args): string => $ejecutar([PHP_BINARY, 'backend/tests/recuperacion_aislada.php', ...$args], $entornoCorreo);
    $servidores = [];
    $iniciado = false;
    try {
        $ejecutar([$pg('initdb'), '-D', $temporal . '/pgdata', '-U', 'postgres', '--auth-local=trust',
            '--auth-host=trust', '--encoding=UTF8', '--no-locale']);
        $ejecutar([$pg('pg_ctl'), '-D', $temporal . '/pgdata', '-l', $temporal . '/postgres.log',
            '-o', "-h 127.0.0.1 -p $puertoBd", '-w', 'start']);
        $iniciado = true;
        echo "OK: cluster PostgreSQL nuevo local, sin conexion a Neon.\n";
        $cuentas = json_decode($soporte('preparar'), true, 512, JSON_THROW_ON_ERROR);
        echo "OK: migraciones 001-010 y datos sinteticos aplicados al cluster local.\n";
        foreach ([['backend', $puertoApi, 'src/server.php', $entorno, 'backend'],
            ['frontend', $puertoFront, 'src/server.php', $entorno, 'frontend'],
            ['backend', $puertoCorreo, 'tests/servidor_recuperacion_aislado.php', $entornoCorreo, 'backend-correo'],
            ['frontend', $puertoFrontCorreo, 'src/server.php', $entornoCorreo, 'frontend-correo']] as [$carpeta, $puerto, $router, $env, $nombre]) {
            $proceso = SoporteProcesosPrueba::iniciar([PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', 'public', $router],
                $proyecto . '/' . $carpeta, $env, $temporal . '/' . $nombre . '.log');
            $servidores[] = $proceso;
            SoporteProcesosPrueba::esperarServidor($proceso, $puerto);
        }
        $secretos = [$clave];
        if (!isset($opciones['solo-recuperacion'])) {
            require_once __DIR__ . '/test_privacidad_http.php';
            $fixture = json_decode($ejecutar([PHP_BINARY, 'backend/tests/privacidad_aislada.php']), true, 512, JSON_THROW_ON_ERROR);
            probarPrivacidadHttp($base, $front, $clave, $fixture);
            $soporte('reiniciar-limites');
            if (isset($opciones['navegador'])) {
                echo $ejecutar(['node', 'backend/tests/test_paciente_navegador.mjs']);
                $soporte('reiniciar-limites');
            }
        }
        require_once __DIR__ . '/test_recuperacion_http.php';
        $secretos = array_merge($secretos, probarRecuperacionHttp($baseCorreo, $frontCorreo, $clave, $soporteCorreo,
            static fn(): string => $soporte('reiniciar-limites'), $buzon));
        if (isset($opciones['navegador'])) {
            echo $ejecutar(['node', 'backend/tests/test_recuperacion_navegador.mjs'], $entornoCorreo);
            $soporte('reiniciar-limites');
        }
        if (!isset($opciones['solo-recuperacion'])) {
            require_once __DIR__ . '/http/escenario_autenticacion.php';
            $secretos = array_merge($secretos, probarAutenticacionHttp($base, $front, $clave, $cuentas, $soporte));
        }
        foreach (['backend', 'frontend', 'backend-correo', 'frontend-correo', 'postgres'] as $nombre) {
            $contenido = file_get_contents($temporal . '/' . $nombre . '.log');
            foreach ($secretos as $secreto) { comprobarHttp(!str_contains($contenido, $secreto), 'Un log contiene credenciales.'); }
        }
        if (isset($opciones['regresion']) || isset($opciones['completo'])) {
            $regresion = array_replace($entorno, ['DB_NAME' => 'vidasalud_regresion_pruebas']);
            $ejecutar([PHP_BINARY, 'backend/tests/autenticacion_aislada.php', 'preparar', 'solo-seeder'], $regresion);
            echo $ejecutar([PHP_BINARY, 'backend/tests/ejecutar-pruebas.php', '--integracion'], $regresion);
            $contrato = array_replace($entorno, ['DB_NAME' => 'vidasalud_http_pruebas']);
            $ejecutar([PHP_BINARY, 'backend/tests/autenticacion_aislada.php', 'preparar', 'solo-seeder'], $contrato);
            echo $ejecutar([PHP_BINARY, 'backend/tests/test_contrato_http.php'], $contrato);
            echo $ejecutar([PHP_BINARY, 'deploy/tests/test_configuracion.php']);
        }
        echo "OK: verificacion HTTP completada integramente con PHP.\n";
    } finally {
        foreach (array_reverse($servidores) as $proceso) { SoporteProcesosPrueba::detener($proceso); }
        if ($iniciado) { $ejecutar([$pg('pg_ctl'), '-D', $temporal . '/pgdata', '-m', 'fast', '-w', 'stop']); }
        echo 'Artefactos locales: ' . substr($temporal, strlen($proyecto) + 1) . PHP_EOL;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try { ejecutarPruebasHttpAisladas($argv); }
    catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . PHP_EOL); exit(1); }
}
