import { solicitarApi } from "../api/cliente-api.js";
import { mostrarErrorSolicitud } from "../components/formularios.js";
import { inicializarInterfaz } from "../components/interfaz.js";

inicializarInterfaz();

const botonCerrarSesion = document.querySelector("#boton-cerrar-sesion");
botonCerrarSesion?.addEventListener("click", async () => {
    botonCerrarSesion.disabled = true;
    try {
        await solicitarApi("/autenticacion/cerrar-sesion", "POST");
        window.location.assign("/iniciar-sesion");
    } catch (error) {
        mostrarErrorSolicitud(document.querySelector("#mensaje-global"), error);
        botonCerrarSesion.disabled = false;
        if (document.querySelector("#abrir-navegacion")?.getAttribute("aria-expanded") === "true") {
            document.querySelector("#cerrar-navegacion").click();
        }
    }
});
