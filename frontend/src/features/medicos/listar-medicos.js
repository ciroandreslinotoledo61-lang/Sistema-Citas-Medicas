import { listarMedicos, cambiarEstadoMedico } from "./medicos-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion, mostrarErrorSolicitud } from "../../components/formularios.js";

const cuerpoTabla = document.querySelector("#filas-medicos");
const cargarMedicos = configurarListaPaginada(listarMedicos, mostrarMedicosEnTabla, cuerpoTabla);

function mostrarMedicosEnTabla(medicos) {
    for (const medico of medicos) {
        const fila = document.createElement("tr");
        const celdaMedico = crearCeldaTexto(medico.nombresUsuario + " " + medico.apellidosUsuario);
        const correoMedico = document.createElement("small");
        correoMedico.className = "detalle-celda";
        correoMedico.textContent = medico.correoUsuario;
        celdaMedico.append(correoMedico);

        const celdaEspecialidad = crearCeldaTexto(medico.nombreEspecialidad);
        if (!medico.especialidadActiva) {
            const avisoEspecialidad = document.createElement("small");
            avisoEspecialidad.className = "detalle-celda";
            avisoEspecialidad.textContent = "Especialidad inactiva";
            celdaEspecialidad.append(avisoEspecialidad);
        }
        const celdaEstado = document.createElement("td");
        const insigniaEstado = document.createElement("span");
        insigniaEstado.textContent = medico.medicoActivo ? "Activo" : "Inactivo";
        insigniaEstado.className = "insignia " + (medico.medicoActivo ? "activa" : "inactiva");
        celdaEstado.append(insigniaEstado);

        const celdaAcciones = document.createElement("td");
        celdaAcciones.className = "acciones-tabla";
        celdaAcciones.append(crearEnlaceAccion("Editar", "/medicos/" + medico.idMedico + "/editar"));
        const botonEstado = document.createElement("button");
        botonEstado.type = "button";
        botonEstado.className = "enlace-accion";
        botonEstado.textContent = medico.medicoActivo ? "Desactivar" : "Activar";
        botonEstado.addEventListener("click", async () => {
            const accion = medico.medicoActivo ? "Desactivar" : "Activar";
            if (!window.confirm(accion + " la cuenta del medico " + medico.nombresUsuario + " " + medico.apellidosUsuario + "?")) return;
            botonEstado.disabled = true;
            try {
                await cambiarEstadoMedico(medico.idMedico, !medico.medicoActivo);
                await cargarMedicos();
            } catch (error) {
                mostrarErrorSolicitud(document.querySelector("#mensaje-listado"), error);
                botonEstado.disabled = false;
            }
        });
        celdaAcciones.append(botonEstado);
        fila.append(celdaMedico, crearCeldaTexto(medico.matriculaProfesional), celdaEspecialidad, celdaEstado, celdaAcciones);
        cuerpoTabla.append(fila);
    }
}
