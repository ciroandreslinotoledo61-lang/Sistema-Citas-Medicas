<?php

declare(strict_types=1);

use CitasMedicas\Presentation\Middlewares\VerificarOrigenSolicitud;
use CitasMedicas\Presentation\Middlewares\VerificarTokenCsrf;
use CitasMedicas\Presentation\Http\EnrutadorApi;
use CitasMedicas\Presentation\Http\FabricaControladores;
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
    // El punto de entrada normal usa dependencias reales; otros puntos de entrada
    // PHP pueden proporcionar explicitamente su composicion, nunca mediante HTTP.
    (new EnrutadorApi($fabricaControladores ?? new FabricaControladores()))->atenderSolicitud();
} catch (ErrorAplicacion $error) {
    RespuestaJson::enviarRespuestaJson(['mensaje' => $error->getMessage(), 'erroresCampos' => $error->erroresCampos], $error->codigoHttp);
} catch (PDOException $error) {
    if ((string) $error->getCode() === '23505') {
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'El correo o la cédula de identidad ya están registrados.'], 409);
    }
    // Nunca registrar valores de formularios ni consultas con datos personales.
    error_log('Fallo de base de datos. SQLSTATE: ' . $error->getCode());
    RespuestaJson::enviarRespuestaJson(['mensaje' => 'No se pudo completar la operación. Revisa la conexión y las migraciones.'], 503);
} catch (Throwable $error) {
    error_log('Fallo interno: ' . get_class($error));
    RespuestaJson::enviarRespuestaJson(['mensaje' => 'No se pudo completar la operación. Revisa la configuración del servidor.'], 500);
}
