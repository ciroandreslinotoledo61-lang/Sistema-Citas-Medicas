<?php
declare(strict_types=1);

require __DIR__ . '/configuracion.php';
require dirname(__DIR__) . '/api/ClienteApiServidor.php';
require dirname(__DIR__) . '/routes/ProteccionVista.php';

$origenApi = parse_url(obtenerConfiguracionFrontend('API_URL', 'http://127.0.0.1:8000/api'));
$origenConexion = ($origenApi['scheme'] ?? 'http') . '://' . ($origenApi['host'] ?? '127.0.0.1') .
    (isset($origenApi['port']) ? ':' . (int) $origenApi['port'] : '');
if (!preg_match('#^https?://[a-zA-Z0-9.:-]+$#', $origenConexion)) {
    http_response_code(500);
    exit('Revisa API_URL en frontend/.env.');
}
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self' {$origenConexion}; img-src 'self' data:; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Content-Type: text/html; charset=utf-8');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$rutaActual = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
if (str_starts_with($rutaActual, '/recuperar-contrasena')) {
    header('Referrer-Policy: no-referrer');
}
$usuarioAutenticado = null;
$idRegistro = 0;
$paginaActual = '';
$tituloPagina = 'Citas Médicas';
$moduloJavascript = '';
$vista = '';

$rutasVistas = [
    '/recuperar-contrasena' => ['recuperar-contrasena', 'Recuperación de contraseña', 'auth/pages/recuperar-contrasena.php', 'auth/pages/recuperar-contrasena.js', null],
    '/recuperar-contrasena/confirmar' => ['confirmar-recuperacion', 'Nueva contraseña', 'auth/pages/confirmar-recuperacion.php', 'auth/pages/confirmar-recuperacion.js', null],
    '/' => ['inicio', 'Portal de pacientes', 'inicio/inicio.php', '', null],
    '/iniciar-sesion' => ['iniciar-sesion', 'Iniciar sesión', 'auth/pages/iniciar-sesion.php', 'auth/pages/iniciar-sesion.js', null],
    '/registro-paciente' => ['registro-paciente', 'Crear cuenta de paciente', 'pacientes/pages/formulario-registro.php', 'pacientes/pages/registrar-paciente.js', null],
    '/bitacora'=>['bitacora','Bitácora del sistema','bitacora/pages/bitacora.php','bitacora/pages/bitacora.js',['administrador']],
    '/consultas'=>['consultas','Mis consultas','consultas/pages/lista-consultas.php','consultas/pages/listar-consultas.js',['medico']],
    '/consultas/nueva'=>['consulta-nueva','Registrar consulta','consultas/pages/formulario-consulta.php','consultas/pages/formulario-consulta.js',['medico']],
    '/historial-clinico'=>['historial-clinico','Mi historial clínico','consultas/pages/historial-clinico.php','consultas/pages/listar-consultas.js',['paciente']],
    '/reportes' => ['reportes', 'Reportes administrativos', 'reportes/pages/reportes.php', 'reportes/pages/reportes.js', ['administrador']],
    '/panel' => ['panel', 'Panel principal', 'panel/panel.php', '', []],
    '/cambiar-contrasena' => ['cambiar-contrasena', 'Cambiar contraseña', 'auth/pages/cambiar-contrasena.php', 'auth/pages/cambiar-contrasena.js', []],
    '/usuarios' => ['usuarios', 'Usuarios', 'usuarios/pages/lista-usuarios.php', 'usuarios/pages/listar-usuarios.js', ['administrador']],
    '/usuarios/nuevo' => ['usuario-nuevo', 'Nuevo usuario', 'usuarios/pages/formulario-usuario.php', 'usuarios/pages/formulario-usuario.js', ['administrador']],
    '/pacientes' => ['pacientes', 'Pacientes', 'pacientes/pages/lista-pacientes.php', 'pacientes/pages/listar-pacientes.js', ['administrador', 'recepcionista']],
    '/pacientes/nuevo' => ['paciente-nuevo', 'Registrar paciente', 'pacientes/pages/formulario-registro.php', 'pacientes/pages/registrar-paciente.js', ['administrador', 'recepcionista']],
    '/especialidades' => ['especialidades', 'Especialidades', 'especialidades/pages/lista-especialidades.php', 'especialidades/pages/listar-especialidades.js', ['administrador']],
    '/especialidades/nueva' => ['especialidad-nueva', 'Nueva especialidad', 'especialidades/pages/formulario-especialidad.php', 'especialidades/pages/formulario-especialidad.js', ['administrador']],
    '/medicos' => ['medicos', 'Médicos', 'medicos/pages/lista-medicos.php', 'medicos/pages/listar-medicos.js', ['administrador']],
    '/medicos/nuevo' => ['medico-nuevo', 'Registrar médico', 'medicos/pages/formulario-medico.php', 'medicos/pages/formulario-medico.js', ['administrador']],
    '/citas' => ['citas', 'Citas médicas', 'citas/pages/lista-citas.php', 'citas/pages/listar-citas.js', ['administrador', 'recepcionista', 'medico', 'paciente']],
    '/citas/nueva' => ['cita-nueva', 'Reservar cita', 'citas/pages/formulario-cita.php', 'citas/pages/formulario-cita.js', ['administrador', 'recepcionista', 'paciente']],
    '/horarios' => ['horarios', 'Horarios de atención', 'horarios/pages/lista-horarios.php', 'horarios/pages/listar-horarios.js', ['administrador', 'medico']],
    '/horarios/nuevo' => ['horario-nuevo', 'Programar horarios', 'horarios/pages/formulario-horario.php', 'horarios/pages/formulario-horario.js', ['administrador']],
    '/mi-perfil' => ['mi-perfil', 'Mi perfil', 'pacientes/pages/detalle-paciente.php', 'pacientes/pages/detalle-paciente.js', ['paciente']],
];

try {
    if (isset($rutasVistas[$rutaActual])) {
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] = $rutasVistas[$rutaActual];
    } elseif(preg_match('#^/consultas/([1-9][0-9]*)(/editar)?$#',$rutaActual,$coincidencia)){
        $idRegistro=(int)$coincidencia[1];
        [$paginaActual,$tituloPagina,$vista,$moduloJavascript,$rolesPermitidos]=isset($coincidencia[2])
            ?['consulta-editar','Editar consulta','consultas/pages/formulario-consulta.php','consultas/pages/formulario-consulta.js',['medico']]
            :['consulta-detalle','Detalle de consulta','consultas/pages/detalle-consulta.php','consultas/pages/detalle-consulta.js',['medico','paciente']];
    } elseif(preg_match('#^/historial-clinico/([1-9][0-9]*)$#',$rutaActual,$coincidencia)){
        $idRegistro=(int)$coincidencia[1];
        [$paginaActual,$tituloPagina,$vista,$moduloJavascript,$rolesPermitidos]=['historial-clinico','Historial clínico','consultas/pages/historial-clinico.php','consultas/pages/listar-consultas.js',['medico','paciente']];
    } elseif (preg_match('#^/citas/([1-9][0-9]*)(/reprogramar)?$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        $esReprogramacion = isset($coincidencia[2]);
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] = $esReprogramacion
            ? ['cita-reprogramar', 'Reprogramar cita', 'citas/pages/formulario-cita.php', 'citas/pages/formulario-cita.js', ['administrador', 'recepcionista']]
            : ['cita-detalle', 'Detalle de cita', 'citas/pages/detalle-cita.php', 'citas/pages/detalle-cita.js', ['administrador', 'recepcionista', 'medico', 'paciente']];
    } elseif (preg_match('#^/usuarios/([1-9][0-9]*)/restablecer-contrasena$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['usuario-restablecer-contrasena', 'Restablecer contraseña', 'usuarios/pages/restablecer-contrasena.php', 'usuarios/pages/restablecer-contrasena.js', ['administrador']];
    } elseif (preg_match('#^/usuarios/([1-9][0-9]*)/editar$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['usuario-editar', 'Editar usuario', 'usuarios/pages/formulario-usuario.php', 'usuarios/pages/formulario-usuario.js', ['administrador']];
    } elseif (preg_match('#^/especialidades/([1-9][0-9]*)/editar$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['especialidad-editar', 'Editar especialidad', 'especialidades/pages/formulario-especialidad.php', 'especialidades/pages/formulario-especialidad.js', ['administrador']];
    } elseif (preg_match('#^/medicos/([1-9][0-9]*)/editar$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['medico-editar', 'Editar médico', 'medicos/pages/formulario-medico.php', 'medicos/pages/formulario-medico.js', ['administrador']];
    } elseif (preg_match('#^/pacientes/([1-9][0-9]*)$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['paciente-detalle', 'Datos del paciente', 'pacientes/pages/detalle-paciente.php', 'pacientes/pages/detalle-paciente.js', ['administrador', 'recepcionista']];
    } else {
        http_response_code(404);
        throw new DomainException('La página que buscas no existe.');
    }
    if ($rolesPermitidos !== null) {
        $usuarioAutenticado = ProteccionVista::exigirSesionParaVista($rolesPermitidos);
    }
} catch (DomainException $error) {
    $tituloPagina = http_response_code() === 403 ? 'Acceso restringido' : 'Página no encontrada';
    $mensajeErrorPagina = $error->getMessage();
    $vista = 'inicio/error.php';
    $moduloJavascript = '';
} catch (Throwable) {
    http_response_code(503);
    $tituloPagina = 'Servicio no disponible';
    $mensajeErrorPagina = 'No podemos conectar con el servidor. Comprueba que el backend esté iniciado y vuelve a intentar.';
    $vista = 'inicio/error.php';
    $moduloJavascript = '';
}

require dirname(__DIR__) . '/layouts/principal.php';
