import { iniciarSesionUsuario } from "./autenticacion-api.js";
import { ejecutarEnvioFormulario } from "../../components/formularios.js";

const formulario = document.querySelector("#formulario-iniciar-sesion");
formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    ejecutarEnvioFormulario(formulario, async (datosUsuario) => {
        await iniciarSesionUsuario(datosUsuario);
        window.location.assign("/panel");
    });
});
