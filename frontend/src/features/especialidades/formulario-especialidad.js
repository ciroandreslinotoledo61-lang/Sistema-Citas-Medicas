import { obtenerEspecialidadPorId, registrarEspecialidad, actualizarDatosEspecialidad } from "./especialidades-api.js";
import { ejecutarEnvioFormulario, completarCamposFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../components/formularios.js";

const formulario = document.querySelector("#formulario-especialidad");
const idEspecialidad = Number(document.body.dataset.idRegistro);
const mensajeFormulario = document.querySelector("#mensaje-formulario");
let formularioDisponible = idEspecialidad === 0;

async function cargarDatosEspecialidad() {
    try {
        const respuesta = await obtenerEspecialidadPorId(idEspecialidad);
        completarCamposFormulario(formulario, respuesta.datos);
        document.querySelector("#campos-especialidad").disabled = false;
        document.querySelector("#guardar-especialidad").disabled = false;
        formularioDisponible = true;
    } catch (error) {
        mostrarErrorSolicitud(mensajeFormulario, error);
    }
}
if (idEspecialidad) cargarDatosEspecialidad();

formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    if (!formularioDisponible) return;
    ejecutarEnvioFormulario(formulario, async (datosEspecialidad) => {
        if (idEspecialidad) {
            const respuesta = await actualizarDatosEspecialidad(idEspecialidad, datosEspecialidad);
            completarCamposFormulario(formulario, respuesta.datos);
            mostrarMensaje(mensajeFormulario, respuesta.mensaje);
        } else {
            const respuesta = await registrarEspecialidad(datosEspecialidad);
            window.location.assign("/especialidades/" + respuesta.datos.idEspecialidad + "/editar");
        }
    });
});
