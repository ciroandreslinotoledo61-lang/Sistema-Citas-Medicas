<?php
declare(strict_types=1);

use CitasMedicas\Business\Services\CorreoTransaccionalService;

function exigirEntornoRecuperacionAislado(): void
{
    if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true) || getenv('VIDASALUD_TEST_LOCAL') !== '1'
        || getenv('DB_HOST') !== '127.0.0.1' || getenv('APP_ENV') !== 'local'
        || !preg_match('/^[a-z0-9_]+_pruebas$/D', getenv('DB_NAME') ?: '')) {
        throw new RuntimeException('El transporte simulado solo admite el cluster local aislado.');
    }
}

/** Captura mensajes como un buzon de pruebas; no utiliza red ni credenciales. */
final class CorreoPrueba extends CorreoTransaccionalService
{
    public array $mensajes = [];
    public bool $fallar = false;
    private ?string $archivo;

    public function __construct(?string $archivo = null)
    {
        exigirEntornoRecuperacionAislado();
        $this->archivo = $archivo;
        if ($archivo !== null) {
            $raiz = realpath(dirname(__DIR__, 2) . '/.tmp-auth');
            $carpeta = realpath(dirname($archivo));
            if (!$raiz || !$carpeta || !str_starts_with($carpeta . DIRECTORY_SEPARATOR, $raiz . DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('El buzon simulado debe quedar dentro de .tmp-auth.');
            }
        }
    }

    public function exigirConfiguracionValida(): void
    {
        exigirEntornoRecuperacionAislado();
        $this->obtenerUrlRecuperacion();
    }

    public function enviarRecuperacion(string $destinatario, string $enlace): void
    {
        $this->exigirConfiguracionValida();
        if ($this->fallar || ($this->archivo !== null && is_file($this->archivo . '.fallar'))) {
            throw new RuntimeException('Fallo deliberado del transporte simulado.');
        }
        $mensaje = compact('destinatario', 'enlace');
        $this->mensajes[] = $mensaje;
        if ($this->archivo !== null) {
            $handle = fopen($this->archivo, 'c+');
            if (!$handle || !flock($handle, LOCK_EX)) {
                throw new RuntimeException('No se pudo abrir el buzon aislado.');
            }
            try {
                $contenido = stream_get_contents($handle);
                $mensajes = $contenido === '' ? [] : json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
                $mensajes[] = $mensaje;
                rewind($handle);
                ftruncate($handle, 0);
                fwrite($handle, json_encode($mensajes, JSON_THROW_ON_ERROR));
                fflush($handle);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
    }
}
