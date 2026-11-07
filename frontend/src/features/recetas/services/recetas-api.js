import { solicitarApi, descargarArchivoApi } from "../../../api/cliente-api.js";
export const listarRecetas = parametros => solicitarApi("/recetas?" + parametros);
export const obtenerReceta = id => solicitarApi("/recetas/" + id);
export const emitirReceta = datos => solicitarApi("/recetas", "POST", datos);
export const anularReceta = (id, motivo) => solicitarApi("/recetas/" + id + "/anulacion", "PATCH", { motivo });
export const descargarReceta = id => descargarArchivoApi("/recetas/" + id + "/pdf", `receta-${id}.pdf`);
