<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

/** Transporte real. Los dobles de prueba se inyectan solo desde las suites CLI. */
class CorreoTransaccionalService
{
    public function exigirConfiguracionValida(): void
    {
        $proveedor = CargarVariablesEntorno::obtenerVariableEntorno('MAIL_PROVIDER');
        $clave = CargarVariablesEntorno::obtenerVariableEntorno('RESEND_API_KEY');
        $remitente = CargarVariablesEntorno::obtenerVariableEntorno('MAIL_FROM');
        if ($proveedor !== 'resend' || $clave === '' || strpbrk($clave, "\r\n") !== false
            || !filter_var($remitente, FILTER_VALIDATE_EMAIL) || !extension_loaded('curl')) {
            throw new ErrorAplicacion('La recuperación por correo no está disponible. Inténtalo más tarde o comunícate con recepción.', 503);
        }
        $this->obtenerUrlRecuperacion();
    }

    public function obtenerUrlRecuperacion(): string
    {
        $origen = rtrim(CargarVariablesEntorno::obtenerVariableEntorno('FRONTEND_URL'), '/');
        $partes = parse_url($origen);
        $local = CargarVariablesEntorno::obtenerVariableEntorno('APP_ENV', 'production') === 'local';
        $httpLocal = $local && ($partes['scheme'] ?? '') === 'http'
            && in_array($partes['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true);
        if (!filter_var($origen, FILTER_VALIDATE_URL) || !$partes || isset($partes['user'], $partes['pass'])
            || isset($partes['user']) || isset($partes['pass']) || isset($partes['query']) || isset($partes['fragment'])
            || ($partes['path'] ?? '') !== '' || (($partes['scheme'] ?? '') !== 'https' && !$httpLocal)) {
            throw new ErrorAplicacion('La recuperación por correo no está disponible. Inténtalo más tarde o comunícate con recepción.', 503);
        }
        // Nunca se construye el destino desde Host ni datos de una solicitud.
        return $origen . '/recuperar-contrasena/confirmar';
    }

    public function enviarRecuperacion(string $destinatario, string $enlace): void
    {
        $this->exigirConfiguracionValida();
        $cuerpo = json_encode([
            'from' => CargarVariablesEntorno::obtenerVariableEntorno('MAIL_FROM'),
            'to' => [$destinatario],
            'subject' => 'Recuperación de contraseña — Clínica Vida Salud',
            'text' => "Solicitaste recuperar el acceso a Clínica Vida Salud.\n\n"
                . "Abre este enlace para elegir una nueva contraseña:\n" . $enlace
                . "\n\nEl enlace vence en 30 minutos y solo puede usarse una vez."
                . "\nSi no solicitaste este cambio, ignora el mensaje. Tu contraseña no ha cambiado.",
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $peticion = curl_init('https://api.resend.com/emails');
        curl_setopt_array($peticion, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . CargarVariablesEntorno::obtenerVariableEntorno('RESEND_API_KEY'),
                'Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => 1000,
            CURLOPT_TIMEOUT_MS => 2000,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $respuesta = curl_exec($peticion);
        $codigo = (int) curl_getinfo($peticion, CURLINFO_RESPONSE_CODE);
        $datos = is_string($respuesta) ? json_decode($respuesta, true) : null;
        // No registrar cuerpos, URL, claves, correos ni respuestas del proveedor.
        if ($respuesta === false || $codigo < 200 || $codigo >= 300 || !is_string($datos['id'] ?? null)) {
            throw new \RuntimeException('El proveedor no confirmó la aceptación del correo de recuperación.');
        }
    }
}
