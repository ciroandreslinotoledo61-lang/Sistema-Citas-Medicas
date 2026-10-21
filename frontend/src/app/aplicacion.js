import { solicitarApi } from "../api/cliente-api.js";
import { mostrarErrorSolicitud } from "../components/formularios.js";

const botonCerrarSesion = document.querySelector("#boton-cerrar-sesion");
botonCerrarSesion?.addEventListener("click", async () => {
    botonCerrarSesion.disabled = true;
    try {
        await solicitarApi("/autenticacion/cerrar-sesion", "POST");
        window.location.assign("/iniciar-sesion");
    } catch (error) {
        mostrarErrorSolicitud(document.querySelector("#mensaje-global"), error);
        botonCerrarSesion.disabled = false;
    }
});
