import { solicitarApi } from "../../api/cliente-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, mostrarErrorSolicitud } from "../../components/formularios.js";
const cuerpo=document.querySelector("#filas-bitacora"),form=document.querySelector("#formulario-busqueda");
solicitarApi("/bitacora/filtros").then(({datos})=>{
 for(const u of datos.usuarios)form.elements.idUsuario.add(new Option(`${u.nombreUsuario} (#${u.idUsuario})`,u.idUsuario));
 for(const [campo,valores] of [["modulo",datos.modulos],["accion",datos.acciones]])for(const v of valores)form.elements[campo].add(new Option(v.replaceAll("_"," "),v));
}).catch(e=>mostrarErrorSolicitud(document.querySelector("#mensaje-filtros"),e));
configurarListaPaginada(p=>solicitarApi("/bitacora?"+p),filas=>{for(const e of filas){
 const fila=document.createElement("tr");const fecha=new Intl.DateTimeFormat("es-BO",{timeZone:"America/La_Paz",dateStyle:"short",timeStyle:"medium"}).format(new Date(e.fechaHora));
 for(const valor of [fecha,e.nombreUsuario+" / "+e.codigoRol,e.modulo+(e.idRegistro?" #"+e.idRegistro:""),e.accion.replaceAll("_"," "),e.resultado,e.descripcion])fila.append(crearCeldaTexto(valor));cuerpo.append(fila);
}},cuerpo,f=>Object.fromEntries(["idUsuario","modulo","accion","fechaDesde","fechaHasta"].map(k=>[k,f.elements[k].value])));
