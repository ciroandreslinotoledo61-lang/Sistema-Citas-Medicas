<?php
declare(strict_types=1);

namespace CitasMedicas\Shared\Constants;

final class RolesSistema
{
    public const ADMINISTRADOR = 'administrador';
    public const RECEPCIONISTA = 'recepcionista';
    public const MEDICO = 'medico';
    public const PACIENTE = 'paciente';

    public const GESTION_PACIENTES = [self::ADMINISTRADOR, self::RECEPCIONISTA];
    public const CUENTAS_ADMINISTRATIVAS = [self::ADMINISTRADOR, self::RECEPCIONISTA];
}
