import { listarCitas, listarMedicosParaAgenda } from "./citas-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion } from "../../components/formularios.js";
import { formatearTurno, crearEstadoCita, estadosAsistencia } from "./presentacion-citas.js";
const cuerpoTabla = document.querySelector("#filas-citas");
const formularioFiltros = document.querySelector("#formulario-busqueda");
const iniciales = new URLSearchParams(window.location.search);
for (const campo of ["fecha", "estado"]) if (iniciales.has(campo)) formularioFiltros.elements[campo].value = iniciales.get(campo);
if (/^[1-9][0-9]*$/.test(iniciales.get("idPaciente") || "")) {
    formularioFiltros.elements.idPaciente.value = iniciales.get("idPaciente");
    const aviso = document.querySelector("#filtro-paciente-activo"); aviso.hidden = false; aviso.textContent = "Historial de citas del paciente #" + iniciales.get("idPaciente") + ". ";
    const enlace = document.createElement("a"); enlace.href = "/citas"; enlace.textContent = "Ver todas las citas"; aviso.append(enlace);
}
const selectorMedico = document.querySelector("#filtro-medico-agenda");
if (selectorMedico) listarMedicosParaAgenda().then(respuesta => {
    for (const medico of respuesta.datos) selectorMedico.add(new Option(medico.nombreMedico + (medico.medicoActivo ? "" : " (Inactivo)"), medico.idMedico));
}).catch(error => { const mensaje = document.querySelector("#mensaje-listado"); mensaje.hidden = false; mensaje.textContent = error.message; });
configurarListaPaginada(listarCitas, citas => {
    for (const cita of citas) {
        const fila = document.createElement("tr");
        const medico = crearCeldaTexto(cita.nombreMedico);
        const especialidad = document.createElement("small");
        especialidad.className = "dato-secundario";
        especialidad.textContent = cita.nombreEspecialidad;
        medico.append(especialidad);
        const estado = document.createElement("td");
        estado.append(crearEstadoCita(cita));
        if (cita.asistenciaRegistrada !== "pendiente") {
            const asistencia = document.createElement("small");
            asistencia.className = "dato-secundario";
            asistencia.textContent = "Asistencia: " + estadosAsistencia[cita.asistenciaRegistrada];
            estado.append(asistencia);
        }
        const acciones = document.createElement("td");
        acciones.append(crearEnlaceAccion("Ver cita", "/citas/" + cita.idCita));
        const paciente = crearCeldaTexto(cita.nombrePaciente);
        const motivo = document.createElement("small"); motivo.className = "dato-secundario motivo-agenda";
        motivo.textContent = cita.motivoCita ? cita.motivoCita.slice(0, 100) + (cita.motivoCita.length > 100 ? "..." : "") : "Sin motivo registrado";
        paciente.append(motivo);
        fila.append(crearCeldaTexto("#" + cita.idCita), crearCeldaTexto(formatearTurno(cita)), paciente, medico, estado, acciones);
        cuerpoTabla.append(fila);
    }
}, cuerpoTabla, formulario => ({ fecha: formulario.elements.fecha.value, estado: formulario.elements.estado.value, idMedico: formulario.elements.idMedico?.value || "", idPaciente: formulario.elements.idPaciente.value }));
