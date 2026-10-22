import { listarEspecialidadesActivas } from "../especialidades/especialidades-api.js";
import { obtenerMedicoPorId, registrarMedicoConUsuario, actualizarDatosMedico } from "./medicos-api.js";
import { ejecutarEnvioFormulario, completarCamposFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../components/formularios.js";

const formulario = document.querySelector("#formulario-medico");
const idMedico = Number(document.body.dataset.idRegistro);
const mensajeFormulario = document.querySelector("#mensaje-formulario");
const selectorEspecialidad = formulario.elements.idEspecialidad;
let formularioDisponible = false;

function agregarOpcionEspecialidad(idEspecialidad, nombreEspecialidad) {
    const opcionEspecialidad = document.createElement("option");
    opcionEspecialidad.value = String(idEspecialidad);
    opcionEspecialidad.textContent = nombreEspecialidad;
    selectorEspecialidad.append(opcionEspecialidad);
}

async function cargarFormularioMedico() {
    try {
        const [respuestaEspecialidades, respuestaMedico] = await Promise.all([
            listarEspecialidadesActivas(),
            idMedico ? obtenerMedicoPorId(idMedico) : Promise.resolve(null),
        ]);
        const especialidadesActivas = respuestaEspecialidades.datos;
        const medico = respuestaMedico?.datos;
        selectorEspecialidad.replaceChildren();
        agregarOpcionEspecialidad("", "Selecciona una especialidad");
        for (const especialidad of especialidadesActivas) {
            agregarOpcionEspecialidad(especialidad.idEspecialidad, especialidad.nombreEspecialidad);
        }
        if (medico && !medico.especialidadActiva) {
            agregarOpcionEspecialidad(medico.idEspecialidad, medico.nombreEspecialidad + " (Inactiva)");
            document.querySelector("#ayuda-especialidad").textContent =
                "La especialidad actual esta inactiva. Puedes conservarla si el medico sigue inactivo; para activarlo, reactiva la especialidad o asigna otra activa.";
        }
        if (especialidadesActivas.length === 0 && !medico) {
            mostrarMensaje(mensajeFormulario, "Primero registra o activa una especialidad desde Especialidades.", true);
            return;
        }
        if (medico) completarCamposFormulario(formulario, medico);
        document.querySelector("#campos-medico").disabled = false;
        document.querySelector("#guardar-medico").disabled = false;
        formularioDisponible = true;
    } catch (error) {
        mostrarErrorSolicitud(mensajeFormulario, error);
    }
}
cargarFormularioMedico();

formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    if (!formularioDisponible) return;
    ejecutarEnvioFormulario(formulario, async (datosMedico) => {
        datosMedico.idEspecialidad = Number(datosMedico.idEspecialidad);
        if (idMedico) {
            const respuesta = await actualizarDatosMedico(idMedico, datosMedico);
            completarCamposFormulario(formulario, respuesta.datos);
            mostrarMensaje(mensajeFormulario, respuesta.mensaje);
        } else {
            const respuesta = await registrarMedicoConUsuario(datosMedico);
            window.location.assign("/medicos/" + respuesta.datos.idMedico + "/editar");
        }
    });
});
