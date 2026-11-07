import { listarRecetas } from "../services/recetas-api.js";
import { configurarListaPaginada } from "../../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion } from "../../../components/formularios.js";
const cuerpo = document.querySelector("#filas-recetas");
const idConsulta = new URLSearchParams(window.location.search).get("idConsulta");
configurarListaPaginada(listarRecetas, filas => {
    for (const r of filas) {
        const tr = document.createElement("tr"), acciones = document.createElement("td");
        acciones.append(crearEnlaceAccion("Ver y descargar", "/recetas/" + r.idReceta));
        tr.append(...["#"+r.idReceta,"#"+r.idConsulta,r.fechaEmision,r.estado].map(crearCeldaTexto),acciones); cuerpo.append(tr);
    }
}, cuerpo, () => idConsulta ? { idConsulta } : {});
