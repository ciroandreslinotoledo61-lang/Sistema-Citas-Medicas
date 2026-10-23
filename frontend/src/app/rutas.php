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
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$rutaActual = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$usuarioAutenticado = null;
$idRegistro = 0;
$paginaActual = '';
$tituloPagina = 'Citas Medicas';
$moduloJavascript = '';
$vista = '';

$rutasVistas = [
    '/' => ['inicio', 'Portal de pacientes', 'inicio/inicio.php', '', null],
    '/iniciar-sesion' => ['iniciar-sesion', 'Iniciar sesion', 'auth/iniciar-sesion.php', 'auth/iniciar-sesion.js', null],
    '/registro-paciente' => ['registro-paciente', 'Crear cuenta de paciente', 'pacientes/formulario-registro.php', 'pacientes/registrar-paciente.js', null],
    '/reportes' => ['reportes', 'Reportes administrativos', 'reportes/reportes.php', 'reportes/reportes.js', ['administrador']],
    '/panel' => ['panel', 'Panel principal', 'panel/panel.php', '', []],
    '/usuarios' => ['usuarios', 'Usuarios', 'usuarios/lista-usuarios.php', 'usuarios/listar-usuarios.js', ['administrador']],
    '/usuarios/nuevo' => ['usuario-nuevo', 'Nuevo usuario', 'usuarios/formulario-usuario.php', 'usuarios/formulario-usuario.js', ['administrador']],
    '/pacientes' => ['pacientes', 'Pacientes', 'pacientes/lista-pacientes.php', 'pacientes/listar-pacientes.js', ['administrador', 'recepcionista']],
    '/pacientes/nuevo' => ['paciente-nuevo', 'Registrar paciente', 'pacientes/formulario-registro.php', 'pacientes/registrar-paciente.js', ['administrador', 'recepcionista']],
    '/especialidades' => ['especialidades', 'Especialidades', 'especialidades/lista-especialidades.php', 'especialidades/listar-especialidades.js', ['administrador']],
    '/especialidades/nueva' => ['especialidad-nueva', 'Nueva especialidad', 'especialidades/formulario-especialidad.php', 'especialidades/formulario-especialidad.js', ['administrador']],
    '/medicos' => ['medicos', 'Medicos', 'medicos/lista-medicos.php', 'medicos/listar-medicos.js', ['administrador']],
    '/medicos/nuevo' => ['medico-nuevo', 'Registrar medico', 'medicos/formulario-medico.php', 'medicos/formulario-medico.js', ['administrador']],
    '/citas' => ['citas', 'Citas medicas', 'citas/lista-citas.php', 'citas/listar-citas.js', ['administrador', 'recepcionista', 'medico', 'paciente']],
    '/citas/nueva' => ['cita-nueva', 'Reservar cita', 'citas/formulario-cita.php', 'citas/formulario-cita.js', ['administrador', 'recepcionista', 'paciente']],
    '/horarios' => ['horarios', 'Horarios de atencion', 'horarios/lista-horarios.php', 'horarios/listar-horarios.js', ['administrador', 'medico']],
    '/horarios/nuevo' => ['horario-nuevo', 'Crear horario', 'horarios/formulario-horario.php', 'horarios/formulario-horario.js', ['administrador']],
    '/mi-perfil' => ['mi-perfil', 'Mi perfil', 'pacientes/detalle-paciente.php', 'pacientes/detalle-paciente.js', ['paciente']],
];

try {
    if (isset($rutasVistas[$rutaActual])) {
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] = $rutasVistas[$rutaActual];
    } elseif (preg_match('#^/citas/([1-9][0-9]*)(/reprogramar)?$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        $esReprogramacion = isset($coincidencia[2]);
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] = $esReprogramacion
            ? ['cita-reprogramar', 'Reprogramar cita', 'citas/formulario-cita.php', 'citas/formulario-cita.js', ['administrador', 'recepcionista']]
            : ['cita-detalle', 'Detalle de cita', 'citas/detalle-cita.php', 'citas/detalle-cita.js', ['administrador', 'recepcionista', 'medico', 'paciente']];
    } elseif (preg_match('#^/usuarios/([1-9][0-9]*)/editar$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['usuario-editar', 'Editar usuario', 'usuarios/formulario-usuario.php', 'usuarios/formulario-usuario.js', ['administrador']];
    } elseif (preg_match('#^/especialidades/([1-9][0-9]*)/editar$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['especialidad-editar', 'Editar especialidad', 'especialidades/formulario-especialidad.php', 'especialidades/formulario-especialidad.js', ['administrador']];
    } elseif (preg_match('#^/medicos/([1-9][0-9]*)/editar$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['medico-editar', 'Editar medico', 'medicos/formulario-medico.php', 'medicos/formulario-medico.js', ['administrador']];
    } elseif (preg_match('#^/pacientes/([1-9][0-9]*)$#', $rutaActual, $coincidencia)) {
        $idRegistro = (int) $coincidencia[1];
        [$paginaActual, $tituloPagina, $vista, $moduloJavascript, $rolesPermitidos] =
            ['paciente-detalle', 'Datos del paciente', 'pacientes/detalle-paciente.php', 'pacientes/detalle-paciente.js', ['administrador', 'recepcionista']];
    } else {
        http_response_code(404);
        throw new DomainException('La pagina que buscas no existe.');
    }
    if ($rolesPermitidos !== null) {
        $usuarioAutenticado = ProteccionVista::exigirSesionParaVista($rolesPermitidos);
    }
} catch (DomainException $error) {
    $tituloPagina = http_response_code() === 403 ? 'Acceso restringido' : 'Pagina no encontrada';
    $mensajeErrorPagina = $error->getMessage();
    $vista = 'inicio/error.php';
    $moduloJavascript = '';
} catch (Throwable) {
    http_response_code(503);
    $tituloPagina = 'Servicio no disponible';
    $mensajeErrorPagina = 'No podemos conectar con el servidor. Comprueba que el backend este iniciado y vuelve a intentar.';
    $vista = 'inicio/error.php';
    $moduloJavascript = '';
}

require dirname(__DIR__) . '/layouts/principal.php';
