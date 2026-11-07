import { obtenerUsuarioPorId, registrarUsuarioAdministrativo, actualizarDatosUsuario } from "../services/usuarios-api.js";
import { ejecutarEnvioFormulario, completarCamposFormulario, mostrarErrorSolicitud, mostrarMensaje, confirmarCreacion } from "../../../components/formularios.js";
import { configurarValidacionContrasenas, marcarErroresContrasena } from "../../../components/contrasenas.js";

const formulario = document.querySelector("#formulario-usuario");
const idUsuario = Number(document.body.dataset.idRegistro);
const mensaje = document.querySelector("#mensaje-formulario");
const validarContrasenas = idUsuario ? null : configurarValidacionContrasenas(formulario, "contrasenaUsuario");

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
    if (validarContrasenas && !validarContrasenas()) return;
    ejecutarEnvioFormulario(formulario, async (datosUsuario) => {
        try {
            if (idUsuario) {
                const respuesta = await actualizarDatosUsuario(idUsuario, datosUsuario);
                mostrarMensaje(mensaje, respuesta.mensaje);
            } else {
                const respuesta = await registrarUsuarioAdministrativo(datosUsuario);
                confirmarCreacion(formulario, respuesta);
            }
        } catch (error) {
            marcarErroresContrasena(formulario, error);
            throw error;
        }
    });
});
