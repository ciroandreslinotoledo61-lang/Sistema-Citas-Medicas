import { listarEspecialidades, cambiarEstadoEspecialidad } from "./especialidades-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion, mostrarErrorSolicitud } from "../../components/formularios.js";

const cuerpoTabla = document.querySelector("#filas-especialidades");
const cargarEspecialidades = configurarListaPaginada(listarEspecialidades, mostrarEspecialidadesEnTabla, cuerpoTabla);

function mostrarEspecialidadesEnTabla(especialidades) {
    for (const especialidad of especialidades) {
        const fila = document.createElement("tr");
        const celdaEstado = document.createElement("td");
        const insigniaEstado = document.createElement("span");
        insigniaEstado.textContent = especialidad.especialidadActiva ? "Activa" : "Inactiva";
        insigniaEstado.className = "insignia " + (especialidad.especialidadActiva ? "activa" : "inactiva");
        celdaEstado.append(insigniaEstado);

        const celdaAcciones = document.createElement("td");
        celdaAcciones.className = "acciones-tabla";
        celdaAcciones.append(crearEnlaceAccion("Editar", "/especialidades/" + especialidad.idEspecialidad + "/editar"));
        const botonEstado = document.createElement("button");
        botonEstado.type = "button";
        botonEstado.className = "enlace-accion";
        botonEstado.textContent = especialidad.especialidadActiva ? "Desactivar" : "Activar";
        botonEstado.addEventListener("click", async () => {
            const accion = especialidad.especialidadActiva ? "Desactivar" : "Activar";
            if (!window.confirm(accion + " la especialidad " + especialidad.nombreEspecialidad + "?")) return;
            botonEstado.disabled = true;
            try {
                await cambiarEstadoEspecialidad(especialidad.idEspecialidad, !especialidad.especialidadActiva);
                await cargarEspecialidades();
            } catch (error) {
                mostrarErrorSolicitud(document.querySelector("#mensaje-listado"), error);
                botonEstado.disabled = false;
            }
        });
        celdaAcciones.append(botonEstado);
        fila.append(
            crearCeldaTexto(especialidad.nombreEspecialidad),
            crearCeldaTexto(especialidad.descripcionEspecialidad || "Sin descripcion"),
            crearCeldaTexto(especialidad.cantidadMedicosActivos), celdaEstado, celdaAcciones
        );
        cuerpoTabla.append(fila);
    }
}
