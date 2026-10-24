<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Config;

final class Transaccion
{
    public const PREDETERMINADA = '';
    public const LECTURA_CONFIRMADA = 'READ COMMITTED';
    public const REPORTE_CONSISTENTE = 'REPEATABLE READ READ ONLY';

    public function __construct(private readonly \PDO $conexion)
    {
    }

    public function ejecutar(callable $operacion, string $aislamiento = self::PREDETERMINADA): mixed
    {
        if (!in_array($aislamiento, [self::PREDETERMINADA, self::LECTURA_CONFIRMADA, self::REPORTE_CONSISTENTE], true)) {
            throw new \InvalidArgumentException('Nivel de aislamiento no admitido.');
        }
        $this->conexion->beginTransaction();
        try {
            if ($aislamiento !== self::PREDETERMINADA) {
                $this->conexion->exec('SET TRANSACTION ISOLATION LEVEL ' . $aislamiento);
            }
            $resultado = $operacion();
            $this->conexion->commit();
            return $resultado;
        } catch (\Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }
}
