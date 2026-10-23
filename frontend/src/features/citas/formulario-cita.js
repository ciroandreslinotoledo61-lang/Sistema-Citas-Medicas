import { listarMedicosDisponibles, listarTurnosDisponibles, reservarCita, obtenerCita, reprogramarCita } from "./citas-api.js";
import { listarPacientes } from "../pacientes/pacientes-api.js";
import { formatearTurno } from "./presentacion-citas.js";
import { mostrarErrorSolicitud, mostrarMensaje, ejecutarEnvioFormulario } from "../../components/formularios.js";
const formulario = document.querySelector("#formulario-cita");
const campos = document.querySelector("#campos-cita");
const selectorMedico = formulario.elements.idMedico;
const fecha = formulario.elements.fecha;
const especialidad = document.querySelector("#filtro-especialidad");
const contenedorTurnos = document.querySelector("#turnos-disponibles");
const mensajeTurnos = document.querySelector("#mensaje-turnos");
const guardar = document.querySelector("#guardar-cita");
const idCita = Number(document.body.dataset.idRegistro);
let medicos = [];
let numeroSolicitudTurnos = 0;
let enviando = false;
let reservaConfirmada = false;
function actualizarBotonReserva() { guardar.disabled = enviando || reservaConfirmada || !formulario.querySelector('input[name="idTurno"]:checked') || (formulario.elements.idPaciente && !formulario.elements.idPaciente.value); }
function mostrarMedicos() {
    selectorMedico.replaceChildren(new Option("Selecciona un medico", ""));
    for (const medico of medicos.filter(registro => !especialidad.value || String(registro.idEspecialidad) === especialidad.value)) {
        selectorMedico.add(new Option(medico.nombreMedico + " / " + medico.nombreEspecialidad, medico.idMedico));
    }
}
async function cargarTurnos() {
    const solicitud = ++numeroSolicitudTurnos;
    contenedorTurnos.replaceChildren(); contenedorTurnos.removeAttribute("aria-busy"); actualizarBotonReserva();
    mensajeTurnos.className = "ayuda";
    if (!selectorMedico.value || !fecha.value || !fecha.checkValidity()) {
        mensajeTurnos.textContent = "Selecciona un medico y una fecha valida para consultar horarios.";
        return;
    }
    mensajeTurnos.textContent = "Consultando disponibilidad...";
    contenedorTurnos.setAttribute("aria-busy", "true");
    try {
        const respuesta = await listarTurnosDisponibles(selectorMedico.value, fecha.value);
        if (solicitud !== numeroSolicitudTurnos) return;
        mensajeTurnos.textContent = respuesta.datos.length ? "Selecciona la hora de tu cita." : "No hay turnos disponibles para esta fecha. Prueba otro dia o medico.";
        for (const turno of respuesta.datos) {
            const etiqueta = document.createElement("label");
            etiqueta.className = "opcion-turno";
            const opcion = document.createElement("input");
            opcion.type = "radio"; opcion.name = "idTurno"; opcion.value = turno.idTurno; opcion.required = true;
            const texto = document.createElement("span");
            texto.textContent = turno.fechaHoraInicio.slice(11, 16);
            opcion.setAttribute("aria-label", texto.textContent + " a " + turno.fechaHoraFin.slice(11, 16));
            etiqueta.append(opcion, texto); contenedorTurnos.append(etiqueta);
        }
    } catch (error) { if (solicitud === numeroSolicitudTurnos) mostrarErrorSolicitud(mensajeTurnos, error); }
    finally { if (solicitud === numeroSolicitudTurnos) { contenedorTurnos.removeAttribute("aria-busy"); actualizarBotonReserva(); } }
}
async function cargarFormulario() {
    try {
        medicos = (await listarMedicosDisponibles()).datos;
        const especialidades = new Map(medicos.map(medico => [medico.idEspecialidad, medico.nombreEspecialidad]));
        for (const [identificador, nombre] of especialidades) especialidad.add(new Option(nombre, identificador));
        mostrarMedicos();
        if (idCita) {
            const cita = (await obtenerCita(idCita)).datos;
            if (!cita.puedeReprogramar) throw new Error("Esta cita no admite reprogramacion.");
            document.querySelector("#resumen-cita-anterior").textContent = cita.nombrePaciente + " / Reserva actual: " + formatearTurno(cita);
            selectorMedico.value = cita.idMedico; fecha.value = cita.fechaHoraInicio.slice(0, 10);
        }
        campos.disabled = false;
        if (!medicos.length) mostrarMensaje(document.querySelector("#mensaje-formulario"), "No hay medicos disponibles para reservar. Comunicate con recepcion.", true);
        await cargarTurnos();
    } catch (error) { mostrarErrorSolicitud(document.querySelector("#mensaje-formulario"), error); }
}
especialidad.addEventListener("change", () => { mostrarMedicos(); cargarTurnos(); });
selectorMedico.addEventListener("change", cargarTurnos);
fecha.addEventListener("change", cargarTurnos);
formulario.addEventListener("change", actualizarBotonReserva);
document.querySelector("#actualizar-turnos").addEventListener("click", cargarTurnos);
const busquedaPaciente = document.querySelector("#busqueda-paciente-reserva");
let numeroSolicitudPaciente = 0;
busquedaPaciente?.addEventListener("submit", async evento => {
    evento.preventDefault();
    const solicitud = ++numeroSolicitudPaciente;
    const mensaje = document.querySelector("#mensaje-busqueda-paciente");
    const selector = formulario.elements.idPaciente;
    selector.replaceChildren(new Option("Selecciona un paciente", "")); actualizarBotonReserva();
    mensaje.hidden = true;
    try {
        const respuesta = await listarPacientes(new URLSearchParams({ busqueda: busquedaPaciente.elements.busqueda.value.trim(), pagina: "1" }));
        if (solicitud !== numeroSolicitudPaciente) return;
        for (const paciente of respuesta.datos.elementos) selector.add(new Option(paciente.nombresUsuario + " " + paciente.apellidosUsuario + " / " + paciente.cedulaIdentidad, paciente.idPaciente));
        if (!respuesta.datos.elementos.length) mostrarMensaje(mensaje, "No se encontraron pacientes. Revisa el nombre o la cedula.", true);
        else selector.focus();
    } catch (error) { if (solicitud === numeroSolicitudPaciente) mostrarErrorSolicitud(mensaje, error); }
});
formulario.addEventListener("submit", async evento => {
    evento.preventDefault();
    if (enviando || reservaConfirmada || !formulario.querySelector('input[name="idTurno"]:checked')) return;
    enviando = true;
    await ejecutarEnvioFormulario(formulario, async datos => {
        // Capturar los datos antes de bloquear los campos impide cambios durante el envio.
        campos.disabled = true;
        try {
            const respuesta = idCita ? await reprogramarCita(idCita, { idTurno: datos.idTurno }) : await reservarCita(datos);
            reservaConfirmada = true;
            window.location.assign("/citas/" + respuesta.datos.idCita);
        } catch (error) {
            if (error.codigoHttp === 409) await cargarTurnos();
            throw error;
        } finally { campos.disabled = false; }
    });
    enviando = false; actualizarBotonReserva();
});
cargarFormulario();
