import { iniciarSesionUsuario } from "../services/autenticacion-api.js";
import { ejecutarEnvioFormulario } from "../../../components/formularios.js";

const formulario = document.querySelector("#formulario-iniciar-sesion");
formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    ejecutarEnvioFormulario(formulario, async (datosUsuario) => {
        const respuesta = await iniciarSesionUsuario(datosUsuario);
        formulario.reset();
        window.location.assign(respuesta.datos.requiereCambioContrasena ? "/cambiar-contrasena" : "/panel");
    });
});
