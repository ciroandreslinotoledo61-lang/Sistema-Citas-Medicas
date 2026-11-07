<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Data\Repositories\IntentoAutenticacionRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

/** Limites compartidos entre procesos y sesiones, sin almacenar la entrada original. */
final class IntentosAutenticacionService
{
    private readonly IntentoAutenticacionRepository $intentos;

    public function __construct(\PDO $conexion)
    {
        $this->intentos = new IntentoAutenticacionRepository($conexion);
    }

    public function exigirIntentoDisponible(string $operacion, string $identificador, int $limite = 5): void
    {
        // Limpieza acotada por el indice de ventana; evita crecimiento indefinido.
        if (random_int(1, 100) === 1) {
            $this->intentos->limpiarExpirados();
        }
        $clave = hash('sha256', $operacion . ':' . $identificador);
        if (!$this->intentos->consumirIntento($clave, $limite, 300)) {
            throw new ErrorAplicacion('Demasiados intentos. Espera cinco minutos antes de volver a intentar.', 429);
        }
    }
}
