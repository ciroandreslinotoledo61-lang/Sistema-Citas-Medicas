import { registrarHorario, programarHorariosPorPeriodo } from "../services/horarios-api.js";
import { listarMedicosDisponibles, obtenerConfiguracionAgenda } from "../../citas/services/citas-api.js";
import { formatearFecha, mostrarResumenCampos } from "../../citas/utils/presentacion-citas.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud, crearCeldaTexto } from "../../../components/formularios.js";
const formulario = document.querySelector("#formulario-horario");
const campos = document.querySelector("#campos-horario");
const resultado = document.querySelector("#resumen-programacion");
let configuracion;
let enviando = false;
function actualizarPrevision() {
    const porDia = formulario.elements.tipoProgramacion.value === "dia";
    document.querySelector("#campo-fecha-final").hidden = porDia;
    formulario.elements.fechaFinal.disabled = porDia;
    document.querySelector("#dias-programacion").hidden = porDia;
    document.querySelector("#dias-programacion").disabled = porDia;
    document.querySelector("#etiqueta-fecha-inicial").textContent = porDia ? "Fecha de atención" : "Fecha inicial";
    formulario.elements.fechaFinal.min = formulario.elements.fechaInicial.value || formulario.elements.fechaInicial.min;
    if (!configuracion) return;
    const minutos = hora => { const [h, m] = hora.split(":").map(Number); return h * 60 + m; };
    const duracion = minutos(formulario.elements.horaFin.value) - minutos(formulario.elements.horaInicio.value);
    document.querySelector("#prevision-turnos").textContent = duracion > 0 && duracion % configuracion.duracionCitaMinutos === 0
        ? `${duracion / configuracion.duracionCitaMinutos} turnos consecutivos por día seleccionado.`
        : "Elige un intervalo que contenga turnos completos y termine después del inicio.";
}
function mostrarResultado(resumen) {
    mostrarResumenCampos(document.querySelector("#contadores-programacion"), [
        ["Fechas procesadas", String(resumen.fechasProcesadas)], ["Fechas creadas", String(resumen.fechasCreadas)],
        ["Turnos creados", String(resumen.turnosCreados)], ["Ya existentes", String(resumen.fechasExistentes)],
        ["Conflictos", String(resumen.conflictosDetectados)], ["Inicio pasado", String(resumen.fechasPasadas)],
        ["Días no seleccionados", String(resumen.diasNoSeleccionados)],
    ]);
    const tabla = document.querySelector("#detalle-programacion"); tabla.replaceChildren();
    for (const dia of resumen.detalle) {
        const fila = document.createElement("tr");
        for (const texto of [formatearFecha(dia.fecha), dia.estado, dia.turnosCreados, dia.mensaje]) fila.append(crearCeldaTexto(texto));
        tabla.append(fila);
    }
    resultado.hidden = false; resultado.focus();
}
async function cargarMedicos() {
    try {
        const [respuestaMedicos, respuestaConfiguracion] = await Promise.all([listarMedicosDisponibles(), obtenerConfiguracionAgenda()]);
        configuracion = respuestaConfiguracion.datos;
        document.querySelector("#duracion-horario").textContent = `Atención de ${configuracion.horaApertura} a ${configuracion.horaCierre}. Citas de ${configuracion.duracionCitaMinutos} minutos. Minutos disponibles: 00 y 30.`;
        formulario.elements.idMedico.replaceChildren(new Option("Selecciona un médico", ""));
        for (const medico of respuestaMedicos.datos) formulario.elements.idMedico.add(new Option(`${medico.nombreMedico} / ${medico.nombreEspecialidad} (#${medico.idMedico})`, medico.idMedico));
        for (const [nombre, horas] of [["horaInicio", configuracion.horasPermitidas.slice(0, -1)], ["horaFin", configuracion.horasPermitidas.slice(1)]]) {
            formulario.elements[nombre].replaceChildren(...horas.map(hora => new Option(hora, hora)));
        }
        formulario.elements.horaFin.value = configuracion.horaCierre;
        if (!respuestaMedicos.datos.length) throw new Error("Registra y activa un médico antes de configurar horarios.");
        campos.disabled = false; document.querySelector("#guardar-horario").disabled = false; actualizarPrevision();
    } catch (error) { mostrarErrorSolicitud(document.querySelector("#mensaje-formulario"), error); }
}
formulario.addEventListener("change", actualizarPrevision);
formulario.addEventListener("submit", async evento => {
    evento.preventDefault(); if (enviando) return;
    enviando = true; resultado.hidden = true;
    await ejecutarEnvioFormulario(formulario, async datos => {
        const diasSemana = [...formulario.querySelectorAll('[name="diasSemana"]:checked')].map(campo => Number(campo.value));
        const porDia = datos.tipoProgramacion === "dia";
        if (!porDia && !diasSemana.length) throw new Error("Selecciona al menos un día de trabajo.");
        campos.disabled = true;
        try {
            let fechasCreadas = 1;
            if (porDia) {
                const creada = (await registrarHorario({ idMedico: datos.idMedico, fechaAtencion: datos.fechaInicial, horaInicio: datos.horaInicio, horaFin: datos.horaFin })).datos;
                mostrarResultado({ fechasProcesadas: 1, fechasCreadas: 1, turnosCreados: creada.cantidadTurnos, fechasExistentes: 0, conflictosDetectados: 0, fechasPasadas: 0, diasNoSeleccionados: 0,
                    detalle: [{ fecha: datos.fechaInicial, estado: "creada", turnosCreados: creada.cantidadTurnos, mensaje: "Turnos consecutivos generados." }] });
            } else {
                const resumen = (await programarHorariosPorPeriodo({ ...datos, diasSemana })).datos;
                fechasCreadas = resumen.fechasCreadas;
                mostrarResultado(resumen);
            }
            if (fechasCreadas > 0) {
                formulario.reset();
                formulario.elements.horaFin.value = configuracion.horaCierre;
                actualizarPrevision();
            }
        } finally { campos.disabled = false; }
    });
    enviando = false;
});
cargarMedicos();
