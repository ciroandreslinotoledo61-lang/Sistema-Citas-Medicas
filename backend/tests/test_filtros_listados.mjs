// Sin red ni paquetes externos: ejecuta los módulos reales con DOM/API controlados.
// node --experimental-vm-modules backend/tests/test_filtros_listados.mjs
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import { SourceTextModule, SyntheticModule, createContext } from "node:vm";

class Elemento {
    constructor() { this.children = []; this.listeners = {}; this.value = ""; this.disabled = false; this.hidden = false; }
    addEventListener(evento, callback) { this.listeners[evento] = callback; }
    emitir(evento) { this.listeners[evento]?.({ preventDefault() {} }); }
    append(...hijos) { this.children.push(...hijos); }
    replaceChildren(...hijos) { this.children = hijos; if (this.esSelector) this.value = hijos[0]?.value || ""; }
    add(opcion) { this.children.push(opcion); }
    setAttribute() {}
    removeAttribute() {}
}
class Opcion {
    constructor(texto, valor) { this.text = texto; this.value = String(valor); }
}
const esperar = () => new Promise(resolve => setImmediate(resolve));
const raiz = new URL("../../", import.meta.url);
let comprobaciones = 0;
function comprobar(condicion, mensaje) { assert.ok(condicion, mensaje); comprobaciones++; }

async function escenario(tipo, rol = "administrador", falloMedicos = false) {
    const nodos = new Map();
    for (const id of ["formulario-busqueda", "mensaje-listado", "pagina-anterior", "pagina-siguiente", "estado-paginacion", "filas-horarios", "filas-consultas", "filtro-opciones-medicos"]) nodos.set("#" + id, new Elemento());
    const form = nodos.get("#formulario-busqueda");
    form.elements = { fecha: { value: "2030-01-02" }, fechaDesde: { value: "2030-01-01" }, fechaHasta: { value: "2030-01-31" } };
    const medico = tipo === "horarios" && rol === "medico";
    const selector = new Elemento(); selector.esSelector = true;
    if (tipo === "horarios" && !medico) { form.elements.idMedico = selector; nodos.set('[name="idMedico"]', selector); }
    if (tipo === "consultas") form.elements.busqueda = { value: "  Paciente ficticio  " };
    const entradaOpciones = new Elemento();
    nodos.get("#filtro-opciones-medicos").querySelector = () => entradaOpciones;
    const tabla = { querySelectorAll: () => Array(medico ? 4 : 5).fill({}) };
    nodos.get("#filas-horarios").closest = () => tabla;
    nodos.get("#filas-consultas").closest = () => tabla;
    const document = {
        body: { dataset: { pagina: tipo, usuario: JSON.stringify({ codigoRol: rol }) } },
        querySelector: id => nodos.get(id) || null,
        createElement: () => new Elemento(), createTextNode: texto => texto,
    };
    const llamadas = [];
    let llamadasMedicos = 0;
    const listar = async parametros => {
        llamadas.push(new URLSearchParams(parametros));
        return { datos: { elementos: tipo === "horarios" ? [{ nombreMedico: "Médico ficticio", fechaAtencion: "2030-01-02", horaInicio: "08:00", horaFin: "09:00", cantidadTurnos: 2, duracionCitaMinutos: 30 }] : [], hayPaginaSiguiente: parametros.get("pagina") === "1" } };
    };
    const formularios = {
        crearCeldaTexto: texto => ({ texto }), crearEnlaceAccion: () => new Elemento(),
        ejecutarEnvioFormulario: () => {}, mostrarMensaje: () => {},
        mostrarErrorSolicitud: (elemento, error) => { elemento.hidden = false; elemento.textContent = error.message; },
    };
    const contexto = createContext({ document, Option: Opcion, URLSearchParams, console });
    const cache = new Map();
    const sintetico = (id, valores) => {
        if (!cache.has(id)) cache.set(id, new SyntheticModule(Object.keys(valores), function () {
            for (const [nombre, valor] of Object.entries(valores)) this.setExport(nombre, valor);
        }, { context: contexto }));
        return cache.get(id);
    };
    async function enlazar(specifier) {
        if (specifier.endsWith("lista-paginada.js")) {
            if (!cache.has("lista")) {
                const modulo = new SourceTextModule(await readFile(new URL("frontend/src/components/lista-paginada.js", raiz), "utf8"), { context: contexto });
                cache.set("lista", modulo); await modulo.link(enlazar);
            }
            return cache.get("lista");
        }
        if (specifier.endsWith("formularios.js")) return sintetico("formularios", formularios);
        if (specifier.endsWith("presentacion-citas.js")) return sintetico("presentacion", { formatearFecha: valor => valor, mostrarResumenCampos: () => {} });
        if (specifier.endsWith("horarios-api.js")) return sintetico("horarios", { listarHorarios: listar });
        if (specifier.endsWith("consultas-api.js")) return sintetico("consultas", { listarConsultas: listar, obtenerHistorial: () => {}, actualizarHistoria: () => {} });
        if (specifier.endsWith("citas-api.js")) return sintetico("citas", { listarMedicosParaAgenda: async () => {
            llamadasMedicos++;
            if (falloMedicos) throw new Error("Error simulado al cargar médicos");
            return { datos: Array.from({ length: 18 }, (_, i) => ({ idMedico: i + 1, nombreMedico: i < 2 ? "Homónimo ficticio" : `Profesional ${i}`, nombreEspecialidad: i % 2 ? "Pediatría" : "Dermatología", medicoActivo: i !== 17 })) };
        } });
        throw new Error("Import no previsto: " + specifier);
    }
    const pagina = tipo === "horarios" ? "horarios/pages/listar-horarios.js" : "consultas/pages/listar-consultas.js";
    const modulo = new SourceTextModule(await readFile(new URL("frontend/src/features/" + pagina, raiz), "utf8"), { context: contexto });
    await modulo.link(enlazar); await modulo.evaluate(); await esperar();
    return { nodos, form, selector, entradaOpciones, llamadas, llamadasMedicos };
}

const consultas = await escenario("consultas", "medico");
comprobar(consultas.llamadas[0].get("busqueda") === "Paciente ficticio", "La búsqueda de consultas se transmite recortada desde el componente compartido");
comprobar(consultas.llamadas[0].get("fechaDesde") === "2030-01-01" && consultas.llamadas[0].get("fechaHasta") === "2030-01-31", "Se conservan las fechas");
consultas.nodos.get("#pagina-siguiente").emitir("click"); await esperar();
comprobar(consultas.llamadas.at(-1).get("pagina") === "2" && consultas.llamadas.at(-1).get("busqueda") === "Paciente ficticio", "La segunda página conserva la búsqueda");
consultas.form.elements.busqueda.value = "CI-FICTICIA";
consultas.form.emitir("submit"); await esperar();
comprobar(consultas.llamadas.at(-1).get("pagina") === "1" && consultas.llamadas.at(-1).get("busqueda") === "CI-FICTICIA", "Una nueva búsqueda por CI reinicia la paginación");

const admin = await escenario("horarios");
comprobar(admin.selector.children[0].text === "Todos los médicos" && admin.selector.children.length === 19 && !admin.selector.disabled, "Selector cargado desde backend");
comprobar(admin.selector.children[1].text.includes("Dermatología (#1)") && admin.selector.children[2].text.includes("Pediatría (#2)"), "Especialidad e ID distinguen homónimos");
comprobar(!admin.nodos.get("#filtro-opciones-medicos").hidden, "Muchos médicos habilitan filtrado local de opciones");
admin.selector.value = "2";
admin.form.emitir("submit"); await esperar();
comprobar(admin.llamadas.at(-1).get("idMedico") === "2" && admin.llamadas.at(-1).get("fecha") === "2030-01-02", "Se filtra por ID y fecha");
admin.nodos.get("#pagina-siguiente").emitir("click"); await esperar();
comprobar(admin.llamadas.at(-1).get("pagina") === "2" && admin.llamadas.at(-1).get("idMedico") === "2", "La paginación conserva el médico elegido");
admin.entradaOpciones.value = "Dermatología";
admin.entradaOpciones.emitir("input");
comprobar(admin.selector.value === "2" && admin.selector.children.length === 11, "Filtrar opciones conserva la selección y no consulta por texto libre");
comprobar(admin.nodos.get("#filas-horarios").children[0].children.length === 5, "Administración muestra la columna de médico");

const propios = await escenario("horarios", "medico");
comprobar(propios.llamadasMedicos === 0 && !propios.llamadas[0].has("idMedico"), "El médico no carga otros profesionales ni envía identidad elegida");
comprobar(propios.nodos.get("#filas-horarios").children[0].children.length === 4, "Los bloques propios ocultan la columna redundante");
comprobar(propios.llamadas[0].get("fecha") === "2030-01-02", "El médico conserva el filtro por fecha");
const fallo = await escenario("horarios", "administrador", true);
comprobar(fallo.selector.children[0].text.includes("No se pudieron cargar") && fallo.nodos.get("#mensaje-listado").textContent.includes("Error simulado"), "El fallo del selector se informa sin inventar profesionales");
console.log(`OK: ${comprobaciones} comprobaciones JavaScript de filtros, IDs, homónimos, roles, fechas, errores y paginación; sin red.`);
