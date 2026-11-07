import { configuracionPagos, crearPago, montoDemo } from "../services/pagos-api.js";
import { obtenerCita } from "../../citas/services/citas-api.js";
import { ejecutarEnvioFormulario, mostrarMensaje, mostrarErrorSolicitud } from "../../../components/formularios.js";
const form=document.querySelector("#formulario-pago"),idCita=Number(new URLSearchParams(window.location.search).get("idCita"));let preparado=false;
async function cargar(){try{
    const [cita,config]=await Promise.all([obtenerCita(idCita),configuracionPagos()]);
    if(!["programada","atendida"].includes(cita.datos.estadoCita))throw new Error("Esta cita no admite pagos.");
    if(!config.datos.habilitado)throw new Error("La tarifa aún no fue configurada por Administración.");
    document.querySelector("#resumen-pago").textContent=`Cita #${idCita} · ${cita.datos.nombreMedico} · ${montoDemo(config.datos.montoCentavos)}`;
    document.querySelector("#campos-pago").disabled=false;document.querySelector("#pagar-ahora").disabled=false;
}catch(e){mostrarErrorSolicitud(form.querySelector("#mensaje-formulario"),e);}}
cargar();
form.elements.metodo.addEventListener("change",()=>{document.querySelector("#vista-metodo").textContent=form.elements.metodo.value==="qr_demo"?"Método seleccionado: QR.":"Método seleccionado: PayPal.";});
form.addEventListener("submit",e=>{e.preventDefault();if(preparado)return;ejecutarEnvioFormulario(form,async d=>{
    const r=await crearPago({idCita,metodo:d.metodo});preparado=true;form.reset();document.querySelector("#campos-pago").disabled=true;document.querySelector("#pagar-ahora").hidden=true;
    mostrarMensaje(form.querySelector("#mensaje-formulario"),r.mensaje+" Referencia: "+r.datos.referencia);document.querySelector("#ver-pagos").hidden=false;
});});
