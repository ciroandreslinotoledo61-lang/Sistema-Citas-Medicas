import { listarCitas } from "../services/citas-api.js";
import { crearEnlaceAccion, mostrarErrorSolicitud } from "../../../components/formularios.js";
import { crearEstadoCita, formatearFecha } from "../utils/presentacion-citas.js";

export function iniciarCitasPaciente() {
    const tarjetas = document.querySelector("#tarjetas-citas");
    const panel = document.querySelector("#panel-citas");
    const filtros = document.querySelector("#filtros-historial");
    const pestanas = [...document.querySelectorAll(".pestanas-citas [role=tab]")];
    const mensaje = document.querySelector("#mensaje-listado");
    const vacias = document.querySelector("#citas-vacias");
    const cargando = document.querySelector("#cargando-citas");
    const anterior = document.querySelector("#pagina-anterior");
    const siguiente = document.querySelector("#pagina-siguiente");
    const paginacion = document.querySelector("#estado-paginacion");
    document.querySelector("#citas-paciente .nota-paginacion").textContent = "Solo se muestran las reservas de tu cuenta.";
    let vista = "proximas";
    let pagina = 1;
    let solicitud = 0;

    function crearTarjeta(cita) {
        const tarjeta = document.createElement("article");
        tarjeta.className = "tarjeta tarjeta-cita";
        const cabecera = document.createElement("div");
        cabecera.className = "cabecera-tarjeta-cita";
        const especialidad = document.createElement("h2");
        especialidad.textContent = cita.nombreEspecialidad;
        cabecera.append(especialidad, crearEstadoCita(cita));
        const medico = document.createElement("p");
        medico.className = "medico-tarjeta-cita";
        medico.textContent = "Médico: " + cita.nombreMedico;
        const datos = document.createElement("dl");
        datos.className = "fecha-tarjeta-cita";
        for (const [etiqueta, valor] of [["Fecha", formatearFecha(cita.fechaHoraInicio)],
            ["Hora", cita.fechaHoraInicio.slice(11, 16) + " – " + cita.fechaHoraFin.slice(11, 16)]]) {
            const grupo = document.createElement("div");
            const titulo = document.createElement("dt");
            titulo.textContent = etiqueta;
            const contenido = document.createElement("dd");
            contenido.textContent = valor;
            grupo.append(titulo, contenido);
            datos.append(grupo);
        }
        const acciones = document.createElement("div");
        acciones.className = "acciones acciones-tarjeta-cita";
        const detalles = crearEnlaceAccion("Ver detalles", "/citas/" + cita.idCita);
        detalles.className = "boton boton-secundario";
        detalles.setAttribute("aria-label", "Ver detalles de la cita de " + cita.nombreEspecialidad + " del " + formatearFecha(cita.fechaHoraInicio));
        acciones.append(detalles);
        if (cita.puedeCancelar) {
            const cancelar = crearEnlaceAccion("Cancelar cita", "/citas/" + cita.idCita + "?cancelar=1");
            cancelar.className = "boton boton-peligro cancelar-cita-tarjeta";
            acciones.append(cancelar);
        }
        tarjeta.append(cabecera, medico, datos, acciones);
        return tarjeta;
    }

    async function cargarCitas() {
        const actual = ++solicitud;
        tarjetas.setAttribute("aria-busy", "true");
        tarjetas.replaceChildren();
        vacias.hidden = true;
        mensaje.hidden = true;
        cargando.hidden = false;
        anterior.disabled = true;
        siguiente.disabled = true;
        try {
            const parametros = new URLSearchParams({ vista, pagina: String(pagina) });
            if (vista === "historial") {
                parametros.set("fecha", filtros.elements.fecha.value);
                parametros.set("busqueda", filtros.elements.busqueda.value.trim());
            }
            const { datos } = await listarCitas(parametros);
            if (actual !== solicitud) return;
            tarjetas.replaceChildren(...datos.elementos.map(crearTarjeta));
            vacias.hidden = datos.elementos.length !== 0;
            const hayFiltros = vista === "historial" && (filtros.elements.fecha.value || filtros.elements.busqueda.value.trim());
            document.querySelector("#titulo-citas-vacias").textContent = vista === "proximas" ? "No tienes próximas citas" : hayFiltros ? "No hay citas con estos filtros" : "Aún no tienes citas en tu historial";
            document.querySelector("#descripcion-citas-vacias").textContent = vista === "proximas" ? "Reserva una cita cuando necesites atención médica." : hayFiltros ? "Prueba otra fecha, especialidad o médico." : "Aquí encontrarás tus citas anteriores, canceladas y reprogramadas.";
            document.querySelector("#reservar-sin-citas").hidden = vista !== "proximas";
            paginacion.textContent = "Página " + pagina;
            anterior.disabled = pagina <= 1;
            siguiente.disabled = !datos.hayPaginaSiguiente;
        } catch (error) {
            if (actual !== solicitud) return;
            mostrarErrorSolicitud(mensaje, error);
            anterior.disabled = pagina <= 1;
        } finally {
            if (actual === solicitud) {
                tarjetas.removeAttribute("aria-busy");
                cargando.hidden = true;
            }
        }
    }

    function seleccionarPestana(pestana) {
        vista = pestana.dataset.vista;
        pagina = 1;
        filtros.hidden = vista !== "historial";
        panel.setAttribute("aria-labelledby", pestana.id);
        for (const boton of pestanas) {
            const seleccionado = boton === pestana;
            boton.setAttribute("aria-selected", String(seleccionado));
            boton.tabIndex = seleccionado ? 0 : -1;
        }
        cargarCitas();
    }
    for (const pestana of pestanas) {
        pestana.addEventListener("click", () => seleccionarPestana(pestana));
        pestana.addEventListener("keydown", evento => {
            if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(evento.key)) return;
            evento.preventDefault();
            const destino = evento.key === "Home" ? pestanas[0] : evento.key === "End" ? pestanas[1] : pestanas.find(boton => boton !== pestana);
            destino.focus();
            seleccionarPestana(destino);
        });
    }
    filtros.addEventListener("submit", evento => { evento.preventDefault(); pagina = 1; cargarCitas(); });
    filtros.addEventListener("reset", () => { pagina = 1; queueMicrotask(cargarCitas); });
    anterior.addEventListener("click", () => { pagina--; cargarCitas(); });
    siguiente.addEventListener("click", () => { pagina++; cargarCitas(); });
    cargarCitas();
}
