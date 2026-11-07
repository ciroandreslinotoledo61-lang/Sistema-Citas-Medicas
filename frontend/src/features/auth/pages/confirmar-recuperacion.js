import { confirmarRecuperacion } from "../services/autenticacion-api.js";
import { mostrarMensaje, mostrarErrorSolicitud } from "../../../components/formularios.js";
import { configurarValidacionContrasenas, marcarErroresContrasena, limpiarContrasenas } from "../../../components/contrasenas.js";

// El fragmento no se envia al servidor ni al Referer. Se retira del historial al cargar.
let token = new URLSearchParams(window.location.hash.slice(1)).get("token") || "";
window.history.replaceState(null, "", window.location.pathname);
const formulario = document.querySelector("#formulario-confirmar-recuperacion");
const mensaje = formulario.querySelector("#mensaje-formulario");
const boton = formulario.querySelector('[type="submit"]');
const campos = formulario.querySelector("#campos-recuperacion");
const validar = configurarValidacionContrasenas(formulario, "nuevaContrasena", true);

if (/^[a-f0-9]{64}$/.test(token)) {
    boton.disabled = false;
} else {
    token = "";
    campos.disabled = true;
    mostrarMensaje(mensaje, "El enlace es inválido o está incompleto. Solicita uno nuevo.", true);
}

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (boton.disabled || !token || !validar()) return;
    boton.disabled = true;
    formulario.setAttribute("aria-busy", "true");
    boton.textContent = "Guardando...";
    mensaje.hidden = true;
    try {
        const respuesta = await confirmarRecuperacion({ ...Object.fromEntries(new FormData(formulario)), token });
        token = "";
        limpiarContrasenas(formulario);
        campos.disabled = true;
        boton.hidden = true;
        document.querySelector("#nuevo-enlace-recuperacion").hidden = true;
        document.querySelector("#continuar-iniciar-sesion").hidden = false;
        mostrarMensaje(mensaje, respuesta.mensaje);
        mensaje.focus();
    } catch (error) {
        mostrarErrorSolicitud(mensaje, error);
        marcarErroresContrasena(formulario, error);
    } finally {
        boton.disabled = !token;
        boton.textContent = "Guardar nueva contraseña";
        formulario.removeAttribute("aria-busy");
    }
});
window.addEventListener("pagehide", () => {
    token = "";
    limpiarContrasenas(formulario);
    boton.disabled = true;
});
window.addEventListener("pageshow", (evento) => {
    if (evento.persisted) window.location.reload();
});
