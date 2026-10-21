import { listarUsuarios, cambiarEstadoUsuario } from "./usuarios-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion, mostrarErrorSolicitud } from "../../components/formularios.js";

const cuerpoTabla = document.querySelector("#filas-usuarios");
const usuarioAutenticado = JSON.parse(document.body.dataset.usuario);
const cargarUsuarios = configurarListaPaginada(listarUsuarios, mostrarUsuariosEnTabla, cuerpoTabla);

function mostrarUsuariosEnTabla(usuarios) {
    for (const usuario of usuarios) {
        const fila = document.createElement("tr");
        const celdaEstado = document.createElement("td");
        const insignia = document.createElement("span");
        insignia.textContent = usuario.usuarioActivo ? "Activo" : "Inactivo";
        insignia.className = "insignia " + (usuario.usuarioActivo ? "activa" : "inactiva");
        celdaEstado.append(insignia);
        const celdaAcciones = document.createElement("td");
        celdaAcciones.className = "acciones-tabla";
        celdaAcciones.append(crearEnlaceAccion("Editar", "/usuarios/" + usuario.idUsuario + "/editar"));
        if (usuario.idUsuario !== usuarioAutenticado.idUsuario) {
            const botonEstado = document.createElement("button");
            botonEstado.type = "button";
            botonEstado.className = "enlace-accion";
            botonEstado.textContent = usuario.usuarioActivo ? "Desactivar" : "Activar";
            botonEstado.addEventListener("click", async () => {
                const accion = usuario.usuarioActivo ? "Desactivar" : "Activar";
                if (!window.confirm(accion + " la cuenta de " + usuario.nombresUsuario + "?")) return;
                botonEstado.disabled = true;
                try {
                    await cambiarEstadoUsuario(usuario.idUsuario, !usuario.usuarioActivo);
                    await cargarUsuarios();
                } catch (error) {
                    mostrarErrorSolicitud(document.querySelector("#mensaje-listado"), error);
                    botonEstado.disabled = false;
                }
            });
            celdaAcciones.append(botonEstado);
        }
        fila.append(
            crearCeldaTexto(usuario.nombresUsuario + " " + usuario.apellidosUsuario),
            crearCeldaTexto(usuario.correoUsuario), crearCeldaTexto(usuario.nombreRol),
            celdaEstado, celdaAcciones
        );
        cuerpoTabla.append(fila);
    }
}
