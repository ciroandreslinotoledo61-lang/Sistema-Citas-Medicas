import { listarHorarios } from "./horarios-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto } from "../../components/formularios.js";
import { formatearFecha } from "../citas/presentacion-citas.js";
const cuerpoTabla = document.querySelector("#filas-horarios");
configurarListaPaginada(listarHorarios, horarios => {
    for (const horario of horarios) {
        const fila = document.createElement("tr");
        fila.append(...[horario.nombreMedico, formatearFecha(horario.fechaAtencion), horario.horaInicio, horario.horaFin, horario.cantidadTurnos].map(crearCeldaTexto));
        cuerpoTabla.append(fila);
    }
}, cuerpoTabla, formulario => ({ fecha: formulario.elements.fecha.value }));
