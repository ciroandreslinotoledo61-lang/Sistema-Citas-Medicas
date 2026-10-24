import { solicitarApi } from "../../api/cliente-api.js";
export const listarHorarios = parametros => solicitarApi("/horarios?" + parametros);
export const registrarHorario = datos => solicitarApi("/horarios", "POST", datos);
export const programarHorariosPorPeriodo = datos => solicitarApi("/horarios/programacion", "POST", datos);
