<?php
declare(strict_types=1);

// Las comprobaciones deben ejecutarse incluso con zend.assertions=-1.
function comprobarHttp(bool $condicion, string $mensaje): void
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
}

final class ClienteHttpPruebas
{
    private CurlHandle $conexion;
    private array $resultados;
    private array $secretos;

    public function __construct(private readonly string $base, array &$resultados, array &$secretos,
        private readonly bool $registrarCsrf = true)
    {
        comprobarHttp(parse_url($base, PHP_URL_HOST) === '127.0.0.1', 'Las pruebas HTTP solo admiten localhost aislado.');
        $this->resultados =& $resultados;
        $this->secretos =& $secretos;
        $this->conexion = curl_init();
        curl_setopt($this->conexion, CURLOPT_COOKIEFILE, '');
    }

    public function request(string $metodo, string $ruta, mixed $datos = null, int $esperado = 200,
        bool $csrf = true, ?string $token = null, array $headers = [], ?string $raw = null,
        bool $registrar = true): array
    {
        $cabeceras = ['Accept' => 'application/json'];
        $body = null;
        if (!in_array($metodo, ['GET', 'HEAD', 'OPTIONS'], true)) {
            if ($csrf) {
                $token ??= $this->request('GET', '/autenticacion/token', registrar: $this->registrarCsrf)['datos']['tokenCsrf'];
                $cabeceras['X-CSRF-Token'] = $token;
            }
            $cabeceras['Content-Type'] = 'application/json';
            $body = json_encode($datos === null || $datos === [] ? new stdClass() : $datos, JSON_THROW_ON_ERROR);
        }
        $respuesta = $this->obtenerRespuesta($metodo, $this->base . $ruta, array_replace($cabeceras, $headers), $raw ?? $body);
        comprobarHttp($respuesta['codigo'] === $esperado,
            "$metodo $ruta: esperado $esperado, obtenido " . $respuesta['codigo']);
        if ($registrar) { $this->resultados[] = ['metodo' => $metodo, 'ruta' => $ruta, 'codigo' => $respuesta['codigo']]; }
        if ($respuesta['contenido'] === '') { return []; }
        try { return json_decode($respuesta['contenido'], true, 512, JSON_THROW_ON_ERROR); }
        catch (JsonException) { throw new RuntimeException("$metodo $ruta: el servidor no devolvio JSON valido."); }
    }

    public function login(string $correo, string $clave, int $esperado = 200): array
    {
        return $this->request('POST', '/autenticacion/iniciar-sesion',
            ['correoUsuario' => $correo, 'contrasenaUsuario' => $clave], $esperado);
    }

    public function cookie(): array
    {
        $cookies = curl_getinfo($this->conexion, CURLINFO_COOKIELIST) ?: [];
        sort($cookies);
        return $cookies;
    }

    public function vista(string $front, string $ruta, int $esperado = 200): array
    {
        $respuesta = $this->obtenerRespuesta('GET', $front . $ruta, seguir: true);
        comprobarHttp($respuesta['codigo'] === $esperado, "Vista $ruta: codigo HTTP inesperado.");
        return [$respuesta['url'], $respuesta['contenido']];
    }

    public function obtenerRespuesta(string $metodo, string $url, array $headers = [], ?string $body = null,
        bool $seguir = false, bool $binario = false): array
    {
        comprobarHttp(parse_url($url, PHP_URL_HOST) === '127.0.0.1' && parse_url($url, PHP_URL_SCHEME) === 'http',
            'Se rechazo una URL fuera del entorno local.');
        $cabeceras = [];
        $lineas = [];
        foreach ($headers as $nombre => $valor) { $lineas[] = $nombre . ': ' . $valor; }
        curl_setopt_array($this->conexion, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_PROXY => '',
            CURLOPT_FOLLOWLOCATION => $seguir, CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP,
            CURLOPT_HTTPHEADER => $lineas, CURLOPT_POSTFIELDS => $body,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HEADERFUNCTION => static function ($conexion, string $linea) use (&$cabeceras): int {
                if (str_starts_with($linea, 'HTTP/')) { $cabeceras = []; }
                elseif (str_contains($linea, ':')) {
                    [$nombre, $valor] = explode(':', $linea, 2);
                    $cabeceras[strtolower(trim($nombre))] = trim($valor);
                }
                return strlen($linea);
            },
        ]);
        $contenido = curl_exec($this->conexion);
        comprobarHttp(is_string($contenido), 'No se pudo contactar el servidor local de pruebas.');
        if ($binario) {
            comprobarHttp(str_contains($cabeceras['content-type'] ?? '', 'application/pdf') && str_starts_with($contenido, '%PDF-'), 'La descarga no es PDF.');
        } else {
            comprobarHttp(mb_check_encoding($contenido, 'UTF-8'), 'Una respuesta HTTP contiene UTF-8 invalido.');
        }
        foreach ($this->secretos as $secreto) {
            comprobarHttp($secreto === '' || !str_contains($contenido, $secreto), 'Una respuesta expuso una credencial.');
        }
        return ['codigo' => (int) curl_getinfo($this->conexion, CURLINFO_RESPONSE_CODE), 'contenido' => $contenido,
            'url' => curl_getinfo($this->conexion, CURLINFO_EFFECTIVE_URL), 'headers' => $cabeceras];
    }
}
