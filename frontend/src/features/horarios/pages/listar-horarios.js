import { listarHorarios } from "../services/horarios-api.js";
import { configurarListaPaginada } from "../../../components/lista-paginada.js";
import { crearCeldaTexto } from "../../../components/formularios.js";
import { formatearFecha } from "../../citas/utils/presentacion-citas.js";
import { listarMedicosParaAgenda } from "../../citas/services/citas-api.js";
import { mostrarErrorSolicitud } from "../../../components/formularios.js";
const cuerpoTabla = document.querySelector("#filas-horarios");
const esMedico = JSON.parse(document.body.dataset.usuario || "null")?.codigoRol === "medico";
const selector = document.querySelector('[name="idMedico"]');
async function cargarMedicos() {
    if (!selector) return;
    try {
        const { datos } = await listarMedicosParaAgenda();
        const opciones = datos.map(medico => ({
            id: medico.idMedico,
            texto: `${medico.nombreMedico} / ${medico.nombreEspecialidad} (#${medico.idMedico})${medico.medicoActivo ? "" : " — Inactivo"}`,
        }));
        const filtro = document.querySelector("#filtro-opciones-medicos");
        const dibujarOpciones = () => {
            const elegido = selector.value;
            const texto = filtro.querySelector("input").value.trim().toLocaleLowerCase();
            selector.replaceChildren(new Option("Todos los médicos", ""));
            for (const opcion of opciones) {
                if (String(opcion.id) === elegido || opcion.texto.toLocaleLowerCase().includes(texto)) {
                    selector.add(new Option(opcion.texto, opcion.id));
                }
            }
            selector.value = elegido;
        };
        dibujarOpciones();
        filtro.hidden = opciones.length <= 15;
        filtro.querySelector("input").addEventListener("input", dibujarOpciones);
        selector.disabled = false;
    } catch (error) {
        selector.replaceChildren(new Option("No se pudieron cargar los médicos", ""));
        mostrarErrorSolicitud(document.querySelector("#mensaje-listado"), error);
    }
}
configurarListaPaginada(listarHorarios, horarios => {
    for (const horario of horarios) {
        const fila = document.createElement("tr");
        const celdas = [formatearFecha(horario.fechaAtencion), horario.horaInicio, horario.horaFin, horario.cantidadTurnos + " de " + horario.duracionCitaMinutos + " min"];
        if (!esMedico) celdas.unshift(horario.nombreMedico);
        fila.append(...celdas.map(crearCeldaTexto));
        cuerpoTabla.append(fila);
    }
}, cuerpoTabla, formulario => ({ fecha: formulario.elements.fecha.value, ...(!esMedico ? { idMedico: formulario.elements.idMedico.value } : {}) }));
cargarMedicos();
