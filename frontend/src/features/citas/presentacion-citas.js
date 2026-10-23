// Las fechas de la API corresponden a la zona horaria configurada de la clinica.
// No convertirlas a la zona del navegador: el turno debe conservar su hora local.
export function formatearFecha(fecha) {
    const [anio, mes, dia] = fecha.slice(0, 10).split("-");
    return `${dia}/${mes}/${anio}`;
}
export function formatearTurno(cita) {
    return `${formatearFecha(cita.fechaHoraInicio)} | ${cita.fechaHoraInicio.slice(11, 16)} - ${cita.fechaHoraFin.slice(11, 16)}`;
}
export const estadosCita = { pendiente: "Pendiente", confirmada: "Confirmada", cancelada: "Anulada", reprogramada: "Reprogramada", no_asistio: "No asistio", atendida: "Atendida" };
export const estadosAsistencia = { pendiente: "Sin registrar", asistio: "Asistio", no_asistio: "No asistio" };
export function crearEstadoCita(cita) {
    const insignia = document.createElement("span");
    insignia.className = "insignia " + (["confirmada", "atendida"].includes(cita.estadoCita) ? "activa" : "inactiva");
    insignia.textContent = estadosCita[cita.estadoCita] || cita.estadoCita;
    return insignia;
}
export function mostrarResumenCampos(contenedor, campos) {
    contenedor.replaceChildren();
    for (const [etiqueta, valor] of campos) {
        const grupo = document.createElement("div");
        const titulo = document.createElement("dt");
        const contenido = document.createElement("dd");
        titulo.textContent = etiqueta;
        contenido.textContent = valor || "Sin registrar";
        grupo.append(titulo, contenido);
        contenedor.append(grupo);
    }
}
