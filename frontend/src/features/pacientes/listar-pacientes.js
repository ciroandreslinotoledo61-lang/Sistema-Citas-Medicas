import { listarPacientes } from "./pacientes-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion } from "../../components/formularios.js";

const cuerpoTabla = document.querySelector("#filas-pacientes");
configurarListaPaginada(listarPacientes, (pacientes) => {
    for (const paciente of pacientes) {
        const fila = document.createElement("tr");
        const acciones = document.createElement("td");
        acciones.append(crearEnlaceAccion("Ver paciente", "/pacientes/" + paciente.idPaciente));
        fila.append(
            crearCeldaTexto(paciente.nombresUsuario + " " + paciente.apellidosUsuario),
            crearCeldaTexto(paciente.cedulaIdentidad), crearCeldaTexto(paciente.telefonoPaciente),
            crearCeldaTexto("HC-" + paciente.idHistoriaClinica), crearCeldaTexto(paciente.pacienteActivo ? "Activo" : "Inactivo"), acciones
        );
        cuerpoTabla.append(fila);
    }
}, cuerpoTabla, formulario => ({ estado: formulario.elements.estado.value }));
