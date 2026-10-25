import { solicitarApi } from "../../api/cliente-api.js";
export const listarConsultas=p=>solicitarApi("/consultas?"+p);
export const obtenerConsulta=id=>solicitarApi("/consultas/"+id);
export const registrarConsulta=d=>solicitarApi("/consultas","POST",d);
export const actualizarConsulta=(id,d)=>solicitarApi("/consultas/"+id,"PATCH",d);
export const obtenerHistorial=(id,p)=>solicitarApi("/historias-clinicas/"+(id||"mi-historial")+"?"+p);
export const actualizarHistoria=(id,d)=>solicitarApi("/historias-clinicas/"+id,"PATCH",d);
