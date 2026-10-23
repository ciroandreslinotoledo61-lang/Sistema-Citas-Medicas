import { obtenerReporteAdministrativo } from "./reportes-api.js";
import { crearCeldaTexto, crearEnlaceAccion, mostrarErrorSolicitud } from "../../components/formularios.js";
import { formatearFecha, formatearTurno, crearEstadoCita, estadosCita } from "../citas/presentacion-citas.js";
const formulario = document.querySelector("#formulario-reportes");
const mensaje = document.querySelector("#mensaje-reporte");
const contenido = document.querySelector("#contenido-reporte");
let paginaCitas = 1, paginaPacientes = 1, numeroSolicitud = 0;
let catalogosCargados = false;
let filtrosAplicados = Object.fromEntries(new FormData(formulario));
const formatoNumero = new Intl.NumberFormat("es-BO");
function mostrarTablaReporte(selector, elementos, crearFila) {
    const cuerpo = document.querySelector(selector); cuerpo.replaceChildren();
    for (const elemento of elementos) cuerpo.append(crearFila(elemento));
    if (!elementos.length) {
        const fila = document.createElement("tr"); const celda = crearCeldaTexto("No hay registros para este periodo y filtros.");
        celda.colSpan = cuerpo.closest("table").querySelectorAll("thead th").length;
        celda.className = "sin-resultados"; fila.append(celda); cuerpo.append(fila);
    }
}
function mostrarReporteAdministrativo(datos) {
    if (!catalogosCargados) {
        for (const medico of datos.medicosDisponibles) formulario.elements.idMedico.add(new Option(medico.nombreMedico + (medico.medicoActivo ? "" : " (Inactivo)"), medico.idMedico));
        for (const especialidad of datos.especialidadesDisponibles) formulario.elements.idEspecialidad.add(new Option(especialidad.nombreEspecialidad, especialidad.idEspecialidad));
        catalogosCargados = true;
    }
    for (const [nombre, total] of Object.entries(datos.totales)) document.querySelector("#total-" + nombre).textContent = formatoNumero.format(total);
    document.querySelector("#periodo-reporte").textContent = "Periodo: " + formatearFecha(datos.filtros.fechaDesde) + " al " + formatearFecha(datos.filtros.fechaHasta) + ". Los totales de citas respetan los filtros seleccionados.";
    for (const [grupo, campo] of [["medico", "porMedico"], ["especialidad", "porEspecialidad"], ["estado", "porEstado"]]) {
        mostrarTablaReporte("#reporte-" + grupo, datos[campo], dato => {
            const fila = document.createElement("tr");
            fila.append(crearCeldaTexto(grupo === "estado" ? (estadosCita[dato.etiqueta] || dato.etiqueta) : dato.etiqueta), crearCeldaTexto(formatoNumero.format(dato.cantidad))); return fila;
        });
    }
    mostrarTablaReporte("#reporte-citas", datos.citas.elementos, cita => {
        const fila = document.createElement("tr"), codigo = document.createElement("td"), estado = document.createElement("td");
        codigo.append(crearEnlaceAccion("#" + cita.idCita, "/citas/" + cita.idCita)); estado.append(crearEstadoCita(cita));
        const medico = crearCeldaTexto(cita.nombreMedico), especialidad = document.createElement("small"); especialidad.className = "dato-secundario"; especialidad.textContent = cita.nombreEspecialidad; medico.append(especialidad);
        fila.append(codigo, crearCeldaTexto(formatearTurno(cita)), crearCeldaTexto(cita.nombrePaciente), medico, estado); return fila;
    });
    mostrarTablaReporte("#reporte-pacientes", datos.pacientes.elementos, paciente => {
        const fila = document.createElement("tr"), acciones = document.createElement("td"); acciones.append(crearEnlaceAccion("Ver paciente", "/pacientes/" + paciente.idPaciente));
        fila.append(crearCeldaTexto(paciente.nombrePaciente), crearCeldaTexto(paciente.cedulaIdentidad), crearCeldaTexto(formatearFecha(paciente.fechaRegistro)), crearCeldaTexto(paciente.pacienteActivo ? "Activo" : "Inactivo"), acciones); return fila;
    });
    for (const tabla of ["citas", "pacientes"]) {
        document.querySelector("#" + tabla + "-anterior").disabled = datos[tabla].pagina <= 1;
        document.querySelector("#" + tabla + "-siguiente").disabled = !datos[tabla].hayPaginaSiguiente;
        document.querySelector("#" + tabla + "-pagina").textContent = "Pagina " + datos[tabla].pagina;
    }
    contenido.hidden = false;
}
async function cargarReporteAdministrativo() {
    const solicitud = ++numeroSolicitud;
    mensaje.hidden = true; contenido.setAttribute("aria-busy", "true"); formulario.querySelector('[type="submit"]').disabled = true;
    contenido.querySelectorAll("button").forEach(boton => { boton.disabled = true; });
    try {
        const respuesta = await obtenerReporteAdministrativo(new URLSearchParams({ ...filtrosAplicados, paginaCitas, paginaPacientes }));
        if (solicitud === numeroSolicitud) mostrarReporteAdministrativo(respuesta.datos);
    } catch (error) {
        if (solicitud === numeroSolicitud) { contenido.hidden = true; mostrarErrorSolicitud(mensaje, error); }
    } finally {
        if (solicitud === numeroSolicitud) { formulario.querySelector('[type="submit"]').disabled = false; contenido.removeAttribute("aria-busy"); }
    }
}
formulario.addEventListener("input", () => formulario.elements.fechaHasta.setCustomValidity(""));
formulario.addEventListener("submit", evento => {
    evento.preventDefault();
    if (formulario.elements.fechaDesde.value > formulario.elements.fechaHasta.value) {
        formulario.elements.fechaHasta.setCustomValidity("La fecha final debe ser igual o posterior a la inicial."); formulario.elements.fechaHasta.reportValidity(); return;
    }
    filtrosAplicados = Object.fromEntries(new FormData(formulario)); paginaCitas = 1; paginaPacientes = 1; cargarReporteAdministrativo();
});
for (const [tabla, direccion, paso] of [["citas","anterior",-1],["citas","siguiente",1],["pacientes","anterior",-1],["pacientes","siguiente",1]]) {
    document.querySelector("#" + tabla + "-" + direccion).addEventListener("click", () => { if (tabla === "citas") paginaCitas += paso; else paginaPacientes += paso; cargarReporteAdministrativo(); });
}
cargarReporteAdministrativo();
