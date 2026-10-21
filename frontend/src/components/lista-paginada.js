import { mostrarErrorSolicitud } from "./formularios.js";

export function configurarListaPaginada(obtenerListado, mostrarElementos, cuerpoTabla) {
    const formularioBusqueda = document.querySelector("#formulario-busqueda");
    const mensajeListado = document.querySelector("#mensaje-listado");
    const botonAnterior = document.querySelector("#pagina-anterior");
    const botonSiguiente = document.querySelector("#pagina-siguiente");
    const estadoPaginacion = document.querySelector("#estado-paginacion");
    let paginaActual = 1;
    let numeroSolicitud = 0;

    async function cargarPaginaActual() {
        const solicitudActual = ++numeroSolicitud;
        botonAnterior.disabled = true;
        botonSiguiente.disabled = true;
        mensajeListado.hidden = true;
        cuerpoTabla.setAttribute("aria-busy", "true");
        try {
            const parametros = new URLSearchParams({
                busqueda: formularioBusqueda.elements.busqueda.value.trim(),
                pagina: String(paginaActual),
            });
            const respuesta = await obtenerListado(parametros);
            if (solicitudActual !== numeroSolicitud) return;
            cuerpoTabla.replaceChildren();
            mostrarElementos(respuesta.datos.elementos);
            if (respuesta.datos.elementos.length === 0) {
                const fila = document.createElement("tr");
                const celda = document.createElement("td");
                celda.colSpan = 5;
                celda.className = "sin-resultados";
                celda.textContent = "No se encontraron registros para esta busqueda.";
                fila.append(celda);
                cuerpoTabla.append(fila);
            }
            estadoPaginacion.textContent = "Pagina " + paginaActual;
            botonAnterior.disabled = paginaActual <= 1;
            botonSiguiente.disabled = !respuesta.datos.hayPaginaSiguiente;
        } catch (error) {
            if (solicitudActual !== numeroSolicitud) return;
            cuerpoTabla.replaceChildren();
            mostrarErrorSolicitud(mensajeListado, error);
            botonAnterior.disabled = paginaActual <= 1;
        } finally {
            if (solicitudActual === numeroSolicitud) cuerpoTabla.removeAttribute("aria-busy");
        }
    }

    formularioBusqueda.addEventListener("submit", (evento) => {
        evento.preventDefault();
        paginaActual = 1;
        cargarPaginaActual();
    });
    botonAnterior.addEventListener("click", () => { paginaActual--; cargarPaginaActual(); });
    botonSiguiente.addEventListener("click", () => { paginaActual++; cargarPaginaActual(); });
    cargarPaginaActual();
    return cargarPaginaActual;
}
