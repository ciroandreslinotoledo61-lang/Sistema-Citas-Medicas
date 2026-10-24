<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Http;

/** null: publica; []: cualquier sesion; lista: roles permitidos. */
final class RutaHttp
{
    public function __construct(
        public readonly string $metodo,
        public readonly string $ruta,
        public readonly string $controlador,
        public readonly string $accion,
        public readonly ?array $roles = [],
        public readonly array $argumentos = []
    ) {
    }

    public function coincidir(string $ruta): ?array
    {
        $patron = preg_replace_callback(
            '/\\\\\{([a-zA-Z][a-zA-Z0-9]*)\\\\\}/',
            static fn (array $campo): string => '(?P<' . $campo[1] . '>[1-9][0-9]*)',
            preg_quote($this->ruta, '#')
        );
        return preg_match('#^' . $patron . '$#', $ruta, $coincidencias) === 1
            ? array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY)
            : null;
    }
}
