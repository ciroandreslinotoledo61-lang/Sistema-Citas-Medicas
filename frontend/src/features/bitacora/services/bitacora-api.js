import { solicitarApi } from "../../../api/cliente-api.js";
export const obtenerFiltrosBitacora = () => solicitarApi("/bitacora/filtros");
export const listarBitacora = parametros => solicitarApi("/bitacora?" + parametros);
