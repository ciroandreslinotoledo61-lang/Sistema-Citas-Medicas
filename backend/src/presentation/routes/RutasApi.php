<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Business\Services\AutenticacionService;
use CitasMedicas\Business\Services\UsuarioService;
use CitasMedicas\Business\Services\PacienteService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Presentation\Controllers\AutenticacionController;
use CitasMedicas\Presentation\Controllers\UsuarioController;
use CitasMedicas\Presentation\Controllers\PacienteController;
use CitasMedicas\Presentation\Middlewares\VerificarRolUsuario;
use CitasMedicas\Presentation\Middlewares\VerificarSesionUsuario;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SesionUsuario;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class RutasApi
{
    public static function atenderSolicitudApi(): never
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ruta = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        if ($metodo === 'GET' && $ruta === '/api/estado') {
            RespuestaJson::enviarRespuestaJson(['datos' => ['aplicacion' => 'Citas Medicas', 'version' => '1.0.0', 'estado' => 'disponible']]);
        }
        if ($metodo === 'GET' && $ruta === '/api/autenticacion/token') {
            RespuestaJson::enviarRespuestaJson(['datos' => ['tokenCsrf' => SesionUsuario::obtenerTokenCsrf()]]);
        }
        $rutasConocidas = [
            '/api/autenticacion/iniciar-sesion', '/api/autenticacion/cerrar-sesion', '/api/autenticacion/sesion',
            '/api/autenticacion/registro-paciente', '/api/usuarios', '/api/roles', '/api/pacientes', '/api/pacientes/mi-perfil',
        ];
        if (!in_array($ruta, $rutasConocidas, true) && !preg_match('#^/api/(usuarios|pacientes)/[1-9][0-9]*(/estado|/contacto)?$#', $ruta)) {
            throw new ErrorAplicacion('La ruta solicitada no existe.', 404);
        }
        $conexion = ConexionPostgresql::obtenerConexionPostgresql();
        $autenticacionService = new AutenticacionService(new UsuarioRepository($conexion));
        $autenticacionController = new AutenticacionController($autenticacionService);
        $pacienteController = new PacienteController(new PacienteService($conexion));
        if ($metodo === 'POST' && $ruta === '/api/autenticacion/iniciar-sesion') {
            $autenticacionController->iniciarSesionUsuario();
        }
        if ($metodo === 'POST' && $ruta === '/api/autenticacion/registro-paciente') {
            $pacienteController->registrarPacienteConHistoriaClinica();
        }
        if ($metodo === 'POST' && $ruta === '/api/autenticacion/cerrar-sesion') {
            $autenticacionController->cerrarSesionUsuario();
        }
        $usuario = VerificarSesionUsuario::exigirUsuarioAutenticado($autenticacionService);
        if ($metodo === 'GET' && $ruta === '/api/autenticacion/sesion') {
            RespuestaJson::enviarRespuestaJson(['datos' => $usuario->obtenerDatosPublicosUsuario()]);
        }
        if ($ruta === '/api/roles' || str_starts_with($ruta, '/api/usuarios')) {
            VerificarRolUsuario::exigirRolPermitido($usuario, [RolesSistema::ADMINISTRADOR]);
            $usuarioController = new UsuarioController(new UsuarioService($conexion));
            if ($metodo === 'GET' && $ruta === '/api/roles') {
                $usuarioController->listarRolesSistema();
            }
            if ($ruta === '/api/usuarios') {
                if ($metodo === 'GET') { $usuarioController->listarUsuarios(); }
                if ($metodo === 'POST') { $usuarioController->registrarUsuarioAdministrativo(); }
            }
            if (preg_match('#^/api/usuarios/([1-9][0-9]*)$#', $ruta, $coincidencia)) {
                $idUsuario = SolicitudHttp::obtenerIdPositivo($coincidencia[1]);
                if ($metodo === 'GET') { $usuarioController->obtenerUsuarioPorId($idUsuario); }
                if ($metodo === 'PATCH') { $usuarioController->actualizarDatosUsuario($idUsuario); }
            }
            if ($metodo === 'PATCH' && preg_match('#^/api/usuarios/([1-9][0-9]*)/estado$#', $ruta, $coincidencia)) {
                $usuarioController->cambiarEstadoUsuario(SolicitudHttp::obtenerIdPositivo($coincidencia[1]), $usuario);
            }
        }
        if ($metodo === 'GET' && $ruta === '/api/pacientes/mi-perfil') {
            VerificarRolUsuario::exigirRolPermitido($usuario, [RolesSistema::PACIENTE]);
            $pacienteController->obtenerPerfilPaciente($usuario->idUsuario);
        }
        if (str_starts_with($ruta, '/api/pacientes')) {
            VerificarRolUsuario::exigirRolPermitido($usuario, RolesSistema::GESTION_PACIENTES);
            if ($ruta === '/api/pacientes') {
                if ($metodo === 'GET') { $pacienteController->listarPacientes(); }
                if ($metodo === 'POST') { $pacienteController->registrarPacienteConHistoriaClinica(); }
            }
            if ($metodo === 'GET' && preg_match('#^/api/pacientes/([1-9][0-9]*)$#', $ruta, $coincidencia)) {
                $pacienteController->obtenerPacientePorId(SolicitudHttp::obtenerIdPositivo($coincidencia[1]));
            }
            if ($metodo === 'PATCH' && preg_match('#^/api/pacientes/([1-9][0-9]*)/contacto$#', $ruta, $coincidencia)) {
                $pacienteController->actualizarContactoPaciente(SolicitudHttp::obtenerIdPositivo($coincidencia[1]));
            }
        }
        throw new ErrorAplicacion('La ruta o el metodo solicitado no esta disponible.', 404);
    }
}
