import { listarCitas } from "./citas-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion } from "../../components/formularios.js";
import { formatearTurno, crearEstadoCita, estadosAsistencia } from "./presentacion-citas.js";
const cuerpoTabla = document.querySelector("#filas-citas");
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
        fila.append(crearCeldaTexto(formatearTurno(cita)), crearCeldaTexto(cita.nombrePaciente), medico, estado, acciones);
        cuerpoTabla.append(fila);
    }
}, cuerpoTabla, formulario => ({ fecha: formulario.elements.fecha.value, estado: formulario.elements.estado.value }));
