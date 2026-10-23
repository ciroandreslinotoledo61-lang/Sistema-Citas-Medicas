<?php
declare(strict_types=1);

/** Metadatos visuales. Las rutas y el backend siguen comprobando los permisos. */
function obtenerModulosDisponibles(string $codigoRol): array
{
    $modulos = [
        [
            'titulo' => $codigoRol === 'paciente' ? 'Mis citas' : ($codigoRol === 'medico' ? 'Mi agenda' : 'Citas medicas'),
            'ruta' => '/citas', 'prefijo' => 'cita', 'icono' => 'calendario',
            'descripcion' => match ($codigoRol) {
                'paciente' => 'Elige medico y horario. Consulta o anula tus reservas.',
                'medico' => 'Citas asignadas y datos basicos de tus pacientes.',
                default => 'Reservas, reprogramaciones y control de asistencia.',
            },
            'rutaRegistro' => $codigoRol === 'medico' ? '' : '/citas/nueva', 'accionRegistro' => 'Reservar cita',
            'roles' => ['administrador', 'recepcionista', 'medico', 'paciente'],
        ],
        [
            'titulo' => $codigoRol === 'medico' ? 'Mis horarios' : 'Horarios de atencion',
            'ruta' => '/horarios', 'prefijo' => 'horario', 'icono' => 'calendario',
            'descripcion' => $codigoRol === 'medico' ? 'Tus bloques de atencion asignados.' : 'Bloques de disponibilidad y turnos de 15 minutos.',
            'rutaRegistro' => $codigoRol === 'administrador' ? '/horarios/nuevo' : '', 'accionRegistro' => 'Crear horario',
            'roles' => ['administrador', 'medico'],
        ],
        [
            'titulo' => 'Pacientes', 'ruta' => '/pacientes', 'prefijo' => 'paciente',
            'icono' => 'pacientes', 'descripcion' => 'Datos personales, contacto y numero de historia.',
            'rutaRegistro' => '/pacientes/nuevo', 'accionRegistro' => 'Registrar paciente',
            'roles' => ['administrador', 'recepcionista'],
        ],
        [
            'titulo' => 'Medicos', 'ruta' => '/medicos', 'prefijo' => 'medico',
            'icono' => 'medicos', 'descripcion' => 'Perfiles profesionales y especialidad asignada.',
            'rutaRegistro' => '/medicos/nuevo', 'accionRegistro' => 'Registrar medico',
            'roles' => ['administrador'],
        ],
        [
            'titulo' => 'Especialidades', 'ruta' => '/especialidades', 'prefijo' => 'especialidad',
            'icono' => 'especialidades', 'descripcion' => 'Servicios medicos y estado de disponibilidad.',
            'rutaRegistro' => '/especialidades/nueva', 'accionRegistro' => 'Crear especialidad',
            'roles' => ['administrador'],
        ],
        [
            'titulo' => 'Usuarios', 'ruta' => '/usuarios', 'prefijo' => 'usuario',
            'icono' => 'usuarios', 'descripcion' => 'Cuentas del sistema, roles y acceso.',
            'rutaRegistro' => '/usuarios/nuevo', 'accionRegistro' => 'Crear usuario',
            'roles' => ['administrador'],
        ],
        [
            'titulo' => 'Mi perfil', 'ruta' => '/mi-perfil', 'prefijo' => 'mi-perfil',
            'icono' => 'perfil', 'descripcion' => 'Consulta tus datos personales y de contacto.',
            'rutaRegistro' => '', 'accionRegistro' => '', 'roles' => ['paciente'],
        ],
    ];
    return array_values(array_filter($modulos, static fn (array $modulo): bool =>
        in_array($codigoRol, $modulo['roles'], true)));
}
