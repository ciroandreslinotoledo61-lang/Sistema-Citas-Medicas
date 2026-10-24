import { obtenerCita, cancelarCita, marcarCitaInasistencia, confirmarCita, marcarCitaAtendida } from "./citas-api.js";
import { formatearTurno, crearEstadoCita, estadosAsistencia, mostrarResumenCampos, estadosCita } from "./presentacion-citas.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../components/formularios.js";
const idCita = Number(document.body.dataset.idRegistro);
const mensaje = document.querySelector("#mensaje-detalle");
const formulario = document.querySelector("#formulario-anulacion");
const acciones = document.querySelector("#acciones-cita");
const detalle = document.querySelector("#detalle-cita");
function mostrarCita(cita) {
    document.querySelector("#titulo-cita").textContent = "Reserva #" + cita.idCita;
    document.querySelector("#estado-cita").replaceChildren(crearEstadoCita(cita));
    mostrarResumenCampos(document.querySelector("#datos-cita"), [
        ["Paciente", cita.nombrePaciente], ["Cedula de identidad", cita.cedulaIdentidad],
        ["Fecha de nacimiento", cita.fechaNacimiento], ["Medico", cita.nombreMedico],
        ["Especialidad", cita.nombreEspecialidad], ["Fecha y hora", formatearTurno(cita)],
        ["Motivo de consulta", cita.motivoCita], ["Asistencia", estadosAsistencia[cita.asistenciaRegistrada]],
        ...(cita.motivoCancelacion ? [["Motivo de cancelacion", cita.motivoCancelacion]] : []),
    ]);
    const enlaceOrigen = document.querySelector("#enlace-cita-origen");
    enlaceOrigen.hidden = !cita.idCitaOrigen;
    if (cita.idCitaOrigen) { enlaceOrigen.textContent = "Reprogramada desde la reserva #" + cita.idCitaOrigen; }
    // Otro medico podria ser titular de la cita anterior. No ofrecer un enlace fuera de su alcance.
    if (cita.idCitaOrigen && JSON.parse(document.body.dataset.usuario).codigoRol !== "medico") {
        enlaceOrigen.href = "/citas/" + cita.idCitaOrigen;
    }
    document.querySelector("#mostrar-anulacion").hidden = !cita.puedeCancelar;
    document.querySelector("#reprogramar-cita").hidden = !cita.puedeReprogramar;
    document.querySelector("#reprogramar-cita").href = "/citas/" + cita.idCita + "/reprogramar";
    document.querySelector("#registrar-asistencia").hidden = !cita.puedeMarcarAtendida;
    document.querySelector("#registrar-inasistencia").hidden = !cita.puedeRegistrarInasistencia;
    acciones.hidden = !(cita.puedeCancelar || cita.puedeReprogramar || cita.puedeMarcarAtendida || cita.puedeRegistrarInasistencia);
    document.querySelector("#confirmar-cita").hidden = !cita.puedeConfirmar;
    if (cita.puedeConfirmar) acciones.hidden = false;
    document.querySelector("#explicacion-acciones").textContent = cita.explicacionAcciones;
    formulario.elements.motivoCancelacion.required = cita.requiereMotivoCancelacion;
    document.querySelector("#etiqueta-motivo-cancelacion").textContent = "Motivo de cancelacion " + (cita.requiereMotivoCancelacion ? "(obligatorio)" : "(opcional)");
    mostrarResumenCampos(document.querySelector("#resumen-cancelacion"), [
        ["Codigo", "#" + cita.idCita], ["Paciente", cita.nombrePaciente], ["Medico", cita.nombreMedico], ["Fecha y hora", formatearTurno(cita)],
    ]);
    const historial = document.querySelector("#historial-cambios"); historial.replaceChildren();
    for (const cambio of cita.historialCambios || []) {
        const item = document.createElement("li");
        const fecha = new Intl.DateTimeFormat("es-BO", { timeZone: "America/La_Paz", dateStyle: "short", timeStyle: "short" }).format(new Date(cambio.fechaHora));
        item.textContent = `${fecha} | ${cambio.nombreUsuario || "Registro del sistema"} | ${cambio.estadoAnterior ? (estadosCita[cambio.estadoAnterior] || cambio.estadoAnterior) + " -> " : "Reserva: "}${estadosCita[cambio.estadoNuevo] || cambio.estadoNuevo}${cambio.motivoCancelacion ? " | " + cambio.motivoCancelacion : ""}`;
        historial.append(item);
    }
    if (!historial.children.length) { const item = document.createElement("li"); item.textContent = "Registro anterior a esta actualizacion. No se reconstruyen cambios que no fueron registrados."; historial.append(item); }
    formulario.hidden = true;
    detalle.removeAttribute("aria-busy");
}
async function cargarCita() {
    try { mostrarCita((await obtenerCita(idCita)).datos); }
    catch (error) { document.querySelector("#titulo-cita").textContent = "Cita no disponible"; mostrarErrorSolicitud(mensaje, error); detalle.removeAttribute("aria-busy"); acciones.hidden = true; }
}
cargarCita();
document.querySelector("#mostrar-anulacion").addEventListener("click", () => { formulario.hidden = false; formulario.elements.motivoCancelacion.focus(); });
document.querySelector("#mantener-cita").addEventListener("click", () => { formulario.hidden = true; document.querySelector("#mostrar-anulacion").focus(); });
formulario.addEventListener("submit", evento => {
    evento.preventDefault();
    ejecutarEnvioFormulario(formulario, async datos => {
        const respuesta = await cancelarCita(idCita, datos);
        mostrarCita(respuesta.datos);
        mostrarMensaje(mensaje, respuesta.mensaje);
        mensaje.tabIndex = -1; mensaje.focus();
    });
});
async function guardarAsistencia(asistencia) {
    const texto = asistencia === "asistio" ? "Marcar esta cita como atendida?" : "Confirmar que el paciente no asistio a esta cita?";
    if (!window.confirm(texto)) return;
    const botones = acciones.querySelectorAll("button");
    botones.forEach(boton => { boton.disabled = true; });
    try {
        const respuesta = await (asistencia === "asistio" ? marcarCitaAtendida(idCita) : marcarCitaInasistencia(idCita));
        mostrarCita(respuesta.datos); mostrarMensaje(mensaje, respuesta.mensaje);
    } catch (error) { mostrarErrorSolicitud(mensaje, error); }
    finally { botones.forEach(boton => { boton.disabled = false; }); }
}
document.querySelector("#registrar-asistencia").addEventListener("click", () => guardarAsistencia("asistio"));
document.querySelector("#registrar-inasistencia").addEventListener("click", () => guardarAsistencia("no_asistio"));
document.querySelector("#confirmar-cita").addEventListener("click", async evento => {
    const boton = evento.currentTarget; boton.disabled = true;
    try { const respuesta = await confirmarCita(idCita); mostrarCita(respuesta.datos); mostrarMensaje(mensaje, respuesta.mensaje); }
    catch (error) { mostrarErrorSolicitud(mensaje, error); }
    finally { boton.disabled = false; }
});
