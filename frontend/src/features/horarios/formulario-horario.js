import { registrarHorario } from "./horarios-api.js";
import { listarMedicosDisponibles } from "../citas/citas-api.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud } from "../../components/formularios.js";
const formulario = document.querySelector("#formulario-horario");
async function cargarMedicos() {
    try {
        const medicos = (await listarMedicosDisponibles()).datos;
        formulario.elements.idMedico.replaceChildren(new Option("Selecciona un medico", ""));
        for (const medico of medicos) formulario.elements.idMedico.add(new Option(medico.nombreMedico + " / " + medico.nombreEspecialidad, medico.idMedico));
        if (!medicos.length) throw new Error("Registra y activa un medico antes de configurar horarios.");
        document.querySelector("#campos-horario").disabled = false;
        document.querySelector("#guardar-horario").disabled = false;
    } catch (error) { mostrarErrorSolicitud(document.querySelector("#mensaje-formulario"), error); }
}
formulario.addEventListener("submit", evento => {
    evento.preventDefault();
    ejecutarEnvioFormulario(formulario, async datos => { await registrarHorario(datos); window.location.assign("/horarios"); });
});
cargarMedicos();
