<?php
declare(strict_types=1);

use CitasMedicas\Presentation\Middlewares\VerificarOrigenSolicitud;
use CitasMedicas\Presentation\Middlewares\VerificarTokenCsrf;
use CitasMedicas\Presentation\Routes\RutasApi;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SesionUsuario;

try {
    require __DIR__ . '/bootstrap.php';
    VerificarOrigenSolicitud::aplicarPoliticaOrigen();
    SesionUsuario::iniciarSesionSegura();
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
        VerificarTokenCsrf::exigirTokenCsrfValido();
    }
    RutasApi::atenderSolicitudApi();
} catch (ErrorAplicacion $error) {
    RespuestaJson::enviarRespuestaJson(['mensaje' => $error->getMessage(), 'erroresCampos' => $error->erroresCampos], $error->codigoHttp);
} catch (PDOException $error) {
    if ((string) $error->getCode() === '23505') {
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'El correo o la cedula de identidad ya estan registrados.'], 409);
    }
    // Nunca registrar valores de formularios ni consultas con datos personales.
    error_log('Fallo de base de datos. SQLSTATE: ' . $error->getCode());
    RespuestaJson::enviarRespuestaJson(['mensaje' => 'No se pudo completar la operacion. Revisa la conexion y las migraciones.'], 503);
} catch (Throwable $error) {
    error_log('Fallo interno: ' . get_class($error));
    RespuestaJson::enviarRespuestaJson(['mensaje' => 'No se pudo completar la operacion. Revisa la configuracion del servidor.'], 500);
}
