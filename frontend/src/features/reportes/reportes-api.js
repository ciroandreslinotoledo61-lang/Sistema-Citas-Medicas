import { solicitarApi } from "../../api/cliente-api.js";
export const obtenerReporteAdministrativo = parametros => solicitarApi("/reportes?" + parametros);
