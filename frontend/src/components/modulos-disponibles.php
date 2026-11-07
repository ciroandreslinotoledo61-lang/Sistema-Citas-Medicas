<?php
declare(strict_types=1);

/** Metadatos visuales. Las rutas y el backend siguen comprobando los permisos. */
function obtenerModulosDisponibles(string $codigoRol): array
{
    $modulos = [
        ['titulo'=>'Mi historial clínico','ruta'=>'/historial-clinico','prefijo'=>'historial','icono'=>'informacion','descripcion'=>'Mis atenciones, diagnósticos y tratamientos.','rutaRegistro'=>'','accionRegistro'=>'','roles'=>['paciente']],
        ['titulo'=>'Consultas e historial','ruta'=>'/consultas','prefijo'=>'consulta','icono'=>'medicos','descripcion'=>'Atenciones registradas y continuidad clínica.','rutaRegistro'=>'','accionRegistro'=>'','roles'=>['medico']],
        ['titulo'=>'Bitácora','ruta'=>'/bitacora','prefijo'=>'bitacora','icono'=>'informacion','descripcion'=>'Actividades, responsables y resultados.','rutaRegistro'=>'','accionRegistro'=>'','roles'=>['administrador']],
        ['titulo'=>'Reportes', 'ruta'=>'/reportes', 'prefijo'=>'reportes', 'icono'=>'panel', 'descripcion'=>'Citas por fecha, médico, especialidad y estado. Pacientes registrados.', 'rutaRegistro'=>'', 'accionRegistro'=>'', 'roles'=>['administrador']],
        [
            'titulo' => $codigoRol === 'paciente' ? 'Mis citas' : ($codigoRol === 'medico' ? 'Mi agenda' : 'Citas médicas'),
            'ruta' => '/citas', 'prefijo' => 'cita', 'icono' => 'calendario',
            'descripcion' => match ($codigoRol) {
                'paciente' => 'Elige médico y horario. Consulta o anula tus reservas.',
                'medico' => 'Citas asignadas y datos básicos de tus pacientes.',
                default => 'Reservas, reprogramaciones y control de asistencia.',
            },
            'rutaRegistro' => $codigoRol === 'medico' ? '' : '/citas/nueva', 'accionRegistro' => 'Reservar cita',
            'roles' => ['administrador', 'recepcionista', 'medico', 'paciente'],
        ],
        [
            'titulo' => $codigoRol === 'medico' ? 'Mis horarios' : 'Horarios de atención',
            'ruta' => '/horarios', 'prefijo' => 'horario', 'icono' => 'calendario',
            'descripcion' => $codigoRol === 'medico' ? 'Tus bloques de atención asignados.' : 'Bloques de disponibilidad y generación automática de turnos.',
            'rutaRegistro' => $codigoRol === 'administrador' ? '/horarios/nuevo' : '', 'accionRegistro' => 'Programar horarios',
            'roles' => ['administrador', 'medico'],
        ],
        [
            'titulo' => 'Pacientes', 'ruta' => '/pacientes', 'prefijo' => 'paciente',
            'icono' => 'pacientes', 'descripcion' => 'Datos personales, contacto y número de historia.',
            'rutaRegistro' => '/pacientes/nuevo', 'accionRegistro' => 'Registrar paciente',
            'roles' => ['administrador', 'recepcionista'],
        ],
        [
            'titulo' => 'Médicos', 'ruta' => '/medicos', 'prefijo' => 'medico',
            'icono' => 'medicos', 'descripcion' => 'Perfiles profesionales y especialidad asignada.',
            'rutaRegistro' => '/medicos/nuevo', 'accionRegistro' => 'Registrar médico',
            'roles' => ['administrador'],
        ],
        [
            'titulo' => 'Especialidades', 'ruta' => '/especialidades', 'prefijo' => 'especialidad',
            'icono' => 'especialidades', 'descripcion' => 'Servicios médicos y estado de disponibilidad.',
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
