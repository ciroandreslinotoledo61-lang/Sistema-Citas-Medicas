import { obtenerUsuarioPorId, registrarUsuarioAdministrativo, actualizarDatosUsuario } from "./usuarios-api.js";
import { ejecutarEnvioFormulario, completarCamposFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../components/formularios.js";

const formulario = document.querySelector("#formulario-usuario");
const idUsuario = Number(document.body.dataset.idRegistro);
const mensaje = document.querySelector("#mensaje-formulario");

async function cargarDatosUsuario() {
    try {
        const respuesta = await obtenerUsuarioPorId(idUsuario);
        completarCamposFormulario(formulario, respuesta.datos);
        document.querySelector("#campos-usuario").disabled = false;
        document.querySelector("#guardar-usuario").disabled = false;
    } catch (error) {
        mostrarErrorSolicitud(mensaje, error);
    }
}
if (idUsuario) cargarDatosUsuario();

formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    ejecutarEnvioFormulario(formulario, async (datosUsuario) => {
        if (idUsuario) {
            const respuesta = await actualizarDatosUsuario(idUsuario, datosUsuario);
            mostrarMensaje(mensaje, respuesta.mensaje);
        } else {
            const respuesta = await registrarUsuarioAdministrativo(datosUsuario);
            window.location.assign("/usuarios/" + respuesta.datos.idUsuario + "/editar");
        }
    });
});
