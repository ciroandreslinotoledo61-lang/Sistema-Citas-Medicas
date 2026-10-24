<?php
declare(strict_types=1);

final class ClienteApiServidor
{
    public static function obtenerSesionDesdeBackend(): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('Activa la extension curl en php.ini.');
        }
        $urlApi = rtrim(obtenerConfiguracionFrontend('API_INTERNAL_URL', obtenerConfiguracionFrontend('API_URL', 'http://127.0.0.1:8000/api')), '/');
        $nombreCookie = obtenerConfiguracionFrontend('SESSION_COOKIE_NAME', 'citas_medicas_sesion');
        $valorCookie = $_COOKIE[$nombreCookie] ?? '';
        if (!is_string($valorCookie) || !preg_match('/^[a-zA-Z0-9,-]{16,256}$/', $valorCookie)) {
            return ['codigoHttp' => 401, 'datos' => null];
        }
        $peticion = curl_init($urlApi . '/autenticacion/sesion');
        curl_setopt_array($peticion, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_COOKIE => $nombreCookie . '=' . $valorCookie,
        ]);
        $contenido = curl_exec($peticion);
        $codigoHttp = (int) curl_getinfo($peticion, CURLINFO_HTTP_CODE);
        // curl_close($peticion);
        if ($contenido === false || $codigoHttp >= 500 || $codigoHttp === 0) {
            throw new RuntimeException('El backend no esta disponible.');
        }
        $respuesta = json_decode($contenido, true, 32, JSON_THROW_ON_ERROR);
        return ['codigoHttp' => $codigoHttp, 'datos' => $respuesta['datos'] ?? null];
    }
}
