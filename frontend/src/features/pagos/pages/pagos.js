import { listarPagos, finalizarPago, reportePagos, montoDemo, metodosPago, estadosPago } from "../services/pagos-api.js";
import { configurarListaPaginada } from "../../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion, ejecutarEnvioFormulario, mostrarErrorSolicitud } from "../../../components/formularios.js";
const paciente=JSON.parse(document.body.dataset.usuario).codigoRol==="paciente",cuerpo=document.querySelector("#filas-pagos");
const cargar=configurarListaPaginada(listarPagos,filas=>{for(const p of filas){
    const tr=document.createElement("tr"),acciones=document.createElement("td");
    if(paciente&&p.estado==="pendiente"){
        for(const accion of ["confirmar","cancelar"]){const b=document.createElement("button");b.type="button";b.className="boton boton-secundario boton-pequeno";b.textContent=accion==="confirmar"?"Confirmar pago":"Cancelar pago";
            b.addEventListener("click",async()=>{const texto=accion==="confirmar"?"Prueba académica: no se realizarán cargos reales. ¿Confirmar el pago?":"¿Cancelar este pago pendiente?";if(b.disabled||!window.confirm(texto))return;b.disabled=true;try{await finalizarPago(p.idPago,accion);await cargar();}catch(e){mostrarErrorSolicitud(document.querySelector("#mensaje-listado"),e);}finally{b.disabled=false;}});acciones.append(b);}
    }
    acciones.append(crearEnlaceAccion("Ver cita","/citas/"+p.idCita));
    tr.append(...["#"+p.idCita,montoDemo(p.montoCentavos),metodosPago[p.metodo]||p.metodo,p.referencia,p.fechaConfirmacion||p.fechaCancelacion||p.fechaCreacion,estadosPago[p.estado]||p.estado].map(crearCeldaTexto),acciones);cuerpo.append(tr);
}},cuerpo);
const form=document.querySelector("#formulario-reporte-demo");
form?.addEventListener("submit",e=>{e.preventDefault();ejecutarEnvioFormulario(form,async d=>{const {datos}=await reportePagos(new URLSearchParams(d));const cont=document.querySelector("#reporte-demo");cont.replaceChildren();
    if(!datos.elementos.length)cont.textContent="Sin pagos confirmados en este período.";
    for(const fila of datos.elementos){const p=document.createElement("p");p.textContent=`${metodosPago[fila.metodo]||fila.metodo}: ${fila.cantidad} operaciones · ${montoDemo(fila.total_centavos)}`;cont.append(p);}
});});
