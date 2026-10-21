export function mostrarMensaje(elemento, mensaje, esError = false) {
    elemento.textContent = mensaje;
    elemento.className = "mensaje " + (esError ? "error" : "exito");
    elemento.hidden = false;
}

export function mostrarErrorSolicitud(elemento, error) {
    const primerErrorCampo = Object.values(error.erroresCampos || {})[0];
    mostrarMensaje(elemento, primerErrorCampo || error.message || "Ocurrio un error.", true);
    if (error.codigoHttp === 401 && document.body.dataset.usuario !== "null") {
        const enlace = document.createElement("a");
        enlace.href = "/iniciar-sesion";
        enlace.textContent = " Iniciar sesion";
        elemento.append(enlace);
    }
}

export function obtenerDatosFormulario(formulario) {
    return Object.fromEntries(new FormData(formulario).entries());
}

export async function ejecutarEnvioFormulario(formulario, operacion) {
    const botonEnvio = formulario.querySelector('[type="submit"]');
    const mensajeFormulario = formulario.querySelector("#mensaje-formulario");
    botonEnvio.disabled = true;
    formulario.setAttribute("aria-busy", "true");
    mensajeFormulario.hidden = true;
    try {
        await operacion(obtenerDatosFormulario(formulario));
    } catch (error) {
        mostrarErrorSolicitud(mensajeFormulario, error);
    } finally {
        botonEnvio.disabled = false;
        formulario.removeAttribute("aria-busy");
    }
}

export function completarCamposFormulario(formulario, datos) {
    for (const [nombreCampo, valorCampo] of Object.entries(datos)) {
        const campo = formulario.elements.namedItem(nombreCampo);
        if (campo instanceof HTMLInputElement || campo instanceof HTMLSelectElement) {
            campo.value = String(valorCampo ?? "");
        }
    }
}

export function crearCeldaTexto(texto) {
    const celda = document.createElement("td");
    celda.textContent = String(texto);
    return celda;
}

export function crearEnlaceAccion(texto, direccion) {
    const enlace = document.createElement("a");
    enlace.textContent = texto;
    enlace.href = direccion;
    enlace.className = "enlace-accion";
    return enlace;
}
