import { solicitarRecuperacion } from "../services/autenticacion-api.js";
import { mostrarMensaje, mostrarErrorSolicitud } from "../../../components/formularios.js";

const formulario = document.querySelector("#formulario-recuperar-contrasena");
const mensaje = formulario.querySelector("#mensaje-formulario");
const boton = formulario.querySelector('[type="submit"]');

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (boton.disabled || !formulario.reportValidity()) return;
    boton.disabled = true;
    boton.textContent = "Procesando solicitud...";
    formulario.setAttribute("aria-busy", "true");
    mensaje.hidden = true;
    try {
        const respuesta = await solicitarRecuperacion(Object.fromEntries(new FormData(formulario)));
        mostrarMensaje(mensaje, respuesta.mensaje);
        formulario.reset();
    } catch (error) {
        mostrarErrorSolicitud(mensaje, error);
    } finally {
        boton.disabled = false;
        boton.textContent = "Enviar enlace de recuperación";
        formulario.removeAttribute("aria-busy");
        mensaje.focus();
    }
});
boton.disabled = false;
