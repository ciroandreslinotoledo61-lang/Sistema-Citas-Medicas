<?php
declare(strict_types=1);

namespace CitasMedicas\Shared\Errors;

final class ErrorAplicacion extends \RuntimeException
{
    public function __construct(
        string $mensaje,
        public readonly int $codigoHttp = 400,
        public readonly array $erroresCampos = []
    ) {
        parent::__construct($mensaje);
    }
}
