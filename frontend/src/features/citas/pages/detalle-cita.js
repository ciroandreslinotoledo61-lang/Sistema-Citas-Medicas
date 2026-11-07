import { obtenerCita, cancelarCita, marcarCitaInasistencia, marcarCitaAtendida } from "../services/citas-api.js";
import { formatearTurno, crearEstadoCita, estadosAsistencia, mostrarResumenCampos, estadosCita } from "../utils/presentacion-citas.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../../components/formularios.js";
const estadosHistoricos = { ...estadosCita, pendiente: "Programada (anterior)", confirmada: "Confirmada (anterior)" };
const idCita = Number(document.body.dataset.idRegistro);
const mensaje = document.querySelector("#mensaje-detalle");
const formulario = document.querySelector("#formulario-anulacion");
const acciones = document.querySelector("#acciones-cita");
const detalle = document.querySelector("#detalle-cita");
let citaActual;
function mostrarCita(cita) {
    citaActual = cita;
    const pagar=document.querySelector("#pagar-cita");if(pagar){pagar.hidden=!["programada","atendida"].includes(cita.estadoCita);pagar.href="/pagos-demo/nuevo?idCita="+cita.idCita;}
    document.querySelector("#titulo-cita").textContent = "Reserva #" + cita.idCita;
    document.querySelector("#estado-cita").replaceChildren(crearEstadoCita(cita));
    mostrarResumenCampos(document.querySelector("#datos-cita"), [
        ["Paciente", cita.nombrePaciente], ["Cédula de identidad", cita.cedulaIdentidad],
        ["Fecha de nacimiento", cita.fechaNacimiento], ["Médico", cita.nombreMedico],
        ["Especialidad", cita.nombreEspecialidad], ["Fecha y hora", formatearTurno(cita)],
        ["Motivo de consulta", cita.motivoCita], ["Asistencia", estadosAsistencia[cita.asistenciaRegistrada]],
        ...(cita.motivoCancelacion ? [["Motivo de cancelación", cita.motivoCancelacion]] : []),
    ]);
    const resultado=document.querySelector("#resultado-reprogramacion");resultado.hidden=!cita.resultadoReprogramacion;
    if(cita.resultadoReprogramacion){const n=cita.resultadoReprogramacion;resultado.textContent=`Última reserva de esta reprogramación: #${n.idCita} | ${estadosCita[n.estadoCita]} | ${formatearTurno(n)}`;}
    document.querySelector("#acciones-clinicas").hidden=!(cita.puedeRegistrarConsulta||cita.idConsulta||cita.puedeVerHistoriaClinica);
    for(const [selector,visible,ruta] of [["#registrar-consulta",cita.puedeRegistrarConsulta,"/consultas/nueva?cita="+cita.idCita],["#ver-consulta",cita.idConsulta,"/consultas/"+cita.idConsulta],["#ver-historia",cita.puedeVerHistoriaClinica,"/historial-clinico/"+cita.idPaciente]]){const a=document.querySelector(selector);a.hidden=!visible;a.href=ruta;}
    const enlaceOrigen = document.querySelector("#enlace-cita-origen");
    enlaceOrigen.hidden = !cita.idCitaOrigen;
    if (cita.idCitaOrigen) { enlaceOrigen.textContent = "Reprogramada desde la reserva #" + cita.idCitaOrigen; }
    // Otro medico podria ser titular de la cita anterior. No ofrecer un enlace fuera de su alcance.
    if (cita.idCitaOrigen && JSON.parse(document.body.dataset.usuario).codigoRol !== "medico") {
        enlaceOrigen.href = "/citas/" + cita.idCitaOrigen;
    }
    const cancelar = document.querySelector("#mostrar-anulacion");
    cancelar.hidden = false; cancelar.disabled = !cita.puedeCancelar;
    cancelar.title = cita.motivoNoCancelacion || "Cancelar esta cita";
    const motivo = document.querySelector("#motivo-no-cancelacion");
    motivo.textContent = cita.motivoNoCancelacion || ""; motivo.hidden = !motivo.textContent;
    document.querySelector("#ausencia-medico").hidden = !cita.puedeCancelarPorAusenciaMedico;
    document.querySelector("#reprogramar-cita").hidden = !cita.puedeReprogramar;
    document.querySelector("#reprogramar-cita").href = "/citas/" + cita.idCita + "/reprogramar";
    document.querySelector("#registrar-asistencia").hidden = !cita.puedeMarcarAtendida;
    document.querySelector("#registrar-inasistencia").hidden = !cita.puedeRegistrarInasistencia;
    acciones.hidden = false;
    document.querySelector("#explicacion-acciones").textContent = cita.explicacionAcciones;
    formulario.elements.motivoCancelacion.required = cita.requiereMotivoCancelacion;
    document.querySelector("#etiqueta-motivo-cancelacion").textContent = "Motivo de cancelación " + (cita.requiereMotivoCancelacion ? "(obligatorio)" : "(opcional)");
    mostrarResumenCampos(document.querySelector("#resumen-cancelacion"), [
        ["Código", "#" + cita.idCita], ["Paciente", cita.nombrePaciente], ["Médico", cita.nombreMedico], ["Fecha y hora", formatearTurno(cita)],
    ]);
    const historial = document.querySelector("#historial-cambios"); historial.replaceChildren();
    for (const cambio of cita.historialCambios || []) {
        const item = document.createElement("li");
        const fecha = new Intl.DateTimeFormat("es-BO", { timeZone: "America/La_Paz", dateStyle: "short", timeStyle: "short" }).format(new Date(cambio.fechaHora));
        item.textContent = `${fecha} | ${cambio.nombreUsuario || "Registro del sistema"} | ${cambio.estadoAnterior ? (estadosHistoricos[cambio.estadoAnterior] || cambio.estadoAnterior) + " -> " : "Reserva: "}${estadosHistoricos[cambio.estadoNuevo] || cambio.estadoNuevo}${cambio.motivoCancelacion ? " | " + cambio.motivoCancelacion : ""}`;
        historial.append(item);
    }
    if (!historial.children.length) { const item = document.createElement("li"); item.textContent = "Registro anterior a esta actualización. No se reconstruyen cambios que no fueron registrados."; historial.append(item); }
    formulario.hidden = true;
    formulario.elements.tipoCancelacion.value = "ordinaria";
    detalle.removeAttribute("aria-busy");
}
async function cargarCita() {
    try {
        const cita = (await obtenerCita(idCita)).datos;
        mostrarCita(cita);
        if (cita.puedeCancelar && new URLSearchParams(window.location.search).get("cancelar") === "1") {
            formulario.hidden = false;
            formulario.elements.motivoCancelacion.focus();
        }
    }
    catch (error) { document.querySelector("#titulo-cita").textContent = "Cita no disponible"; mostrarErrorSolicitud(mensaje, error); detalle.removeAttribute("aria-busy"); acciones.hidden = true; }
}
cargarCita();
function abrirCancelacion(tipo = "ordinaria") {
    const ausencia = tipo === "medico_ausente";
    if (!citaActual || !(ausencia ? citaActual.puedeCancelarPorAusenciaMedico : citaActual.puedeCancelar)) return;
    formulario.elements.tipoCancelacion.value = tipo;
    formulario.elements.motivoCancelacion.required = ausencia || citaActual.requiereMotivoCancelacion;
    document.querySelector("#titulo-cancelacion").textContent = ausencia ? "Cancelar por ausencia del médico" : "Cancelar esta cita";
    document.querySelector("#ayuda-cancelacion").textContent = ausencia ? "Registra lo ocurrido. La cancelación quedará atribuida a la ausencia del médico, sin marcar inasistencia del paciente." : "El turno quedará libre. La cita cancelada se conserva en el historial.";
    document.querySelector("#etiqueta-motivo-cancelacion").textContent = "Motivo de cancelación " + (formulario.elements.motivoCancelacion.required ? "(obligatorio)" : "(opcional)");
    formulario.hidden = false; formulario.elements.motivoCancelacion.focus();
}
document.querySelector("#mostrar-anulacion").addEventListener("click", () => abrirCancelacion());
document.querySelector("#ausencia-medico").addEventListener("click", () => abrirCancelacion("medico_ausente"));
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
    const texto = asistencia === "asistio" ? "¿Marcar esta cita como atendida?" : "¿Confirmar que el paciente no asistió a esta cita?";
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
