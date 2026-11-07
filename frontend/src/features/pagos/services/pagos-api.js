import { solicitarApi } from "../../../api/cliente-api.js";
export const listarPagos = parametros => solicitarApi("/pagos-demo?"+parametros);
export const configuracionPagos = () => solicitarApi("/pagos-demo/configuracion");
export const crearPago = datos => solicitarApi("/pagos-demo","POST",datos);
export const finalizarPago = (id,accion) => solicitarApi("/pagos-demo/"+id,"PATCH",{accion,confirmarSimulacion:true});
export const reportePagos = parametros => solicitarApi("/pagos-demo/reporte?"+parametros);
export function montoDemo(centavos){const c=BigInt(centavos);return `Bs ${c/100n},${String(c%100n).padStart(2,"0")} · Simulado`;}
