import { cambiarContrasena } from "../services/autenticacion-api.js";
import { mostrarMensaje, mostrarErrorSolicitud } from "../../../components/formularios.js";
import { configurarValidacionContrasenas, marcarErroresContrasena, limpiarContrasenas } from "../../../components/contrasenas.js";

const formulario = document.querySelector("#formulario-cambiar-contrasena");
const mensaje = formulario.querySelector("#mensaje-formulario");
const boton = formulario.querySelector('[type="submit"]');
const validar = configurarValidacionContrasenas(formulario, "nuevaContrasena", true);

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (boton.disabled || !validar()) return;
    const datos = Object.fromEntries(new FormData(formulario));
    boton.disabled = true;
    boton.textContent = "Guardando...";
    formulario.setAttribute("aria-busy", "true");
    mensaje.hidden = true;
    try {
        const respuesta = await cambiarContrasena(datos);
        limpiarContrasenas(formulario);
        document.body.dataset.usuario = JSON.stringify(respuesta.datos);
        mostrarMensaje(mensaje, respuesta.mensaje);
        mensaje.focus();
        document.querySelector("#campos-contrasena").disabled = true;
        boton.hidden = true;
        const continuar = document.querySelector("#continuar-panel");
        continuar.hidden = false;
        continuar.textContent = "Continuar al panel";
    } catch (error) {
        mostrarErrorSolicitud(mensaje, error);
        marcarErroresContrasena(formulario, error);
    } finally {
        boton.disabled = false;
        boton.textContent = "Guardar contraseña";
        formulario.removeAttribute("aria-busy");
    }
});

boton.disabled = false;
window.addEventListener("pagehide", () => {
    limpiarContrasenas(formulario);
    boton.disabled = true;
});
window.addEventListener("pageshow", (evento) => {
    if (evento.persisted) {
        limpiarContrasenas(formulario);
        window.location.reload();
    }
});
