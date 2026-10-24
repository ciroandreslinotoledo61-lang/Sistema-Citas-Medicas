<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class ValidarCamposFormulario
{
    public static function validarTexto(array $datos, string $nombreCampo, string $etiquetaCampo, int $longitudMaxima, bool $obligatorio = true): string
    {
        $valorCampo = $datos[$nombreCampo] ?? '';
        if (!is_string($valorCampo)) {
            throw new ErrorAplicacion('Revisa los datos del formulario.', 422, [$nombreCampo => $etiquetaCampo . ' debe ser texto.']);
        }
        $valorCampo = trim($valorCampo);
        if (($obligatorio && $valorCampo === '') || mb_strlen($valorCampo) > $longitudMaxima) {
            throw new ErrorAplicacion('Revisa los datos del formulario.', 422, [
                $nombreCampo => $etiquetaCampo . ($obligatorio ? ' es obligatorio y' : '') . ' permite hasta ' . $longitudMaxima . ' caracteres.',
            ]);
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $valorCampo)) {
            throw new ErrorAplicacion('Revisa los datos del formulario.', 422, [$nombreCampo => $etiquetaCampo . ' contiene caracteres no permitidos.']);
        }
        return $valorCampo;
    }

    public static function validarCorreoUsuario(array $datos): string
    {
        $correoUsuario = strtolower(self::validarTexto($datos, 'correoUsuario', 'El correo', 160));
        if (!filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {
            throw new ErrorAplicacion('Revisa el correo del usuario.', 422, ['correoUsuario' => 'Escribe un correo valido.']);
        }
        return $correoUsuario;
    }

    public static function validarContrasenaRegistro(array $datos): string
    {
        $contrasenaUsuario = $datos['contrasenaUsuario'] ?? null;
        if (
            !is_string($contrasenaUsuario) || strlen($contrasenaUsuario) < 12 ||
            strlen($contrasenaUsuario) > 72 || str_contains($contrasenaUsuario, "\0")
        ) {
            throw new ErrorAplicacion('Revisa la clave de acceso.', 422, [
                'contrasenaUsuario' => 'Usa entre 12 y 72 bytes. Para texto ASCII, cada caracter ocupa un byte.',
            ]);
        }
        if (($datos['confirmacionContrasena'] ?? null) !== $contrasenaUsuario) {
            throw new ErrorAplicacion('Las claves de acceso no coinciden.', 422, ['confirmacionContrasena' => 'Repite la misma clave.']);
        }
        return $contrasenaUsuario;
    }

    public static function validarTelefono(array $datos, string $nombreCampo, bool $obligatorio): string
    {
        $telefono = self::validarTexto($datos, $nombreCampo, 'El telefono', 25, $obligatorio);
        if ($telefono !== '' && !preg_match('/^\+?[0-9 ()-]{6,25}$/', $telefono)) {
            throw new ErrorAplicacion('Revisa el numero de telefono.', 422, [$nombreCampo => 'Usa de 6 a 25 caracteres: numeros, espacios, +, - o parentesis.']);
        }
        return $telefono;
    }

    public static function validarParametrosBusqueda(array $parametros): array
    {
        $busqueda = self::validarTexto($parametros, 'busqueda', 'La busqueda', 80, false);
        $pagina = filter_var($parametros['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
        if ($pagina === false) {
            throw new ErrorAplicacion('La pagina solicitada no es valida.', 422);
        }
        return ['busqueda' => $busqueda, 'pagina' => $pagina];
    }

    public static function validarIdentificador(array $datos, string $campo): int
    {
        $valor = $datos[$campo] ?? null;
        if (
            (!is_int($valor) && !is_string($valor)) ||
            !preg_match('/^[1-9][0-9]*$/', (string) $valor) ||
            filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
        ) {
            throw new ErrorAplicacion('Selecciona un registro valido.', 422, [$campo => 'Selecciona una opcion valida.']);
        }
        return (int) $valor;
    }

    public static function validarFecha(mixed $valor, bool $opcional = false): string
    {
        if ($opcional && ($valor === '' || $valor === null)) {
            return '';
        }
        if (!is_string($valor) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $valor)) {
            throw new ErrorAplicacion('La fecha no es valida. Usa AAAA-MM-DD.', 422);
        }
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if (!$fecha || str_starts_with($valor, '0000-') || $fecha->format('Y-m-d') !== $valor) {
            throw new ErrorAplicacion('La fecha no existe.', 422);
        }
        return $valor;
    }
}
