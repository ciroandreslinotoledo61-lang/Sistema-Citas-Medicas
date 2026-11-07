import { solicitarApi } from "../../../api/cliente-api.js";
export const listarSala = parametros => solicitarApi("/sala-espera?" + parametros);
export const cambiarSala = (id, accion, version) => solicitarApi("/sala-espera/"+id,"PATCH",{accion,version});
export const historialSala = id => solicitarApi("/sala-espera/"+id+"/historial");
