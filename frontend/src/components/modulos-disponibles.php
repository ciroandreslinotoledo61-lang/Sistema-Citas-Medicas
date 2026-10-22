<?php
declare(strict_types=1);

/** Metadatos visuales. Las rutas y el backend siguen comprobando los permisos. */
function obtenerModulosDisponibles(string $codigoRol): array
{
    $modulos = [
        [
            'titulo' => 'Pacientes', 'ruta' => '/pacientes', 'prefijo' => 'paciente',
            'icono' => 'pacientes', 'descripcion' => 'Datos personales, contacto e historia clinica.',
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
