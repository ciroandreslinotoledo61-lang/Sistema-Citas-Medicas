const urlApi = document.body.dataset.apiUrl.replace(/\/$/, "");

export class ErrorSolicitudApi extends Error {
    constructor(mensaje, codigoHttp, erroresCampos = {}) {
        super(mensaje);
        this.codigoHttp = codigoHttp;
        this.erroresCampos = erroresCampos;
    }
}

async function enviarSolicitudHttp(rutaApi, opciones = {}) {
    let respuesta;
    try {
        respuesta = await fetch(urlApi + rutaApi, {
            credentials: "include",
            signal: AbortSignal.timeout(15000),
            ...opciones,
            headers: { Accept: "application/json", ...opciones.headers },
        });
    } catch {
        throw new ErrorSolicitudApi("No se pudo conectar. Comprueba que el backend esté iniciado.", 0);
    }
    let contenido;
    try {
        contenido = await respuesta.json();
    } catch {
        throw new ErrorSolicitudApi("El servidor devolvió una respuesta no válida.", respuesta.status);
    }
    if (!respuesta.ok) {
        if (respuesta.status === 403 && contenido.erroresCampos?.cambioContrasenaRequerido && window.location.pathname !== "/cambiar-contrasena") {
            window.location.assign("/cambiar-contrasena");
        }
        throw new ErrorSolicitudApi(contenido.mensaje || "No se pudo completar la solicitud.", respuesta.status, contenido.erroresCampos);
    }
    return contenido;
}

export async function solicitarApi(rutaApi, metodoHttp = "GET", datosSolicitud = undefined) {
    const opciones = { method: metodoHttp };
    if (metodoHttp !== "GET") {
        const respuestaToken = await enviarSolicitudHttp("/autenticacion/token");
        opciones.headers = {
            "Content-Type": "application/json",
            "X-CSRF-Token": respuestaToken.datos.tokenCsrf,
        };
        opciones.body = JSON.stringify(datosSolicitud || {});
    }
    return enviarSolicitudHttp(rutaApi, opciones);
}
