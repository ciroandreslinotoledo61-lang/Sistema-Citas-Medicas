import { obtenerConsulta, registrarConsulta, actualizarConsulta } from "./consultas-api.js";
import { obtenerCita } from "../citas/citas-api.js";
import { mostrarResumenCampos, formatearFecha } from "../citas/presentacion-citas.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud } from "../../components/formularios.js";
const id=Number(document.body.dataset.idRegistro),idCita=Number(new URLSearchParams(location.search).get("cita"));
const form=document.querySelector("#formulario-consulta"),tratamientos=document.querySelector("#tratamientos"),agregar=document.querySelector("#agregar-tratamiento");let version=0;
function agregarTratamiento(d={}){
 if(tratamientos.children.length>=20)return;const b=document.querySelector("#plantilla-tratamiento").content.firstElementChild.cloneNode(true);
 for(const c of b.querySelectorAll("[data-campo]"))c.value=d[c.dataset.campo]||"";
 b.querySelector(".quitar-tratamiento").addEventListener("click",()=>{b.remove();agregar.disabled=false;agregar.focus();});tratamientos.append(b);agregar.disabled=tratamientos.children.length>=20;
}
agregar.addEventListener("click",()=>{agregarTratamiento();tratamientos.lastElementChild.querySelector("input").focus();});
async function cargar(){try{
 let d;if(id){d=(await obtenerConsulta(id)).datos;if(!d.puedeEditar)throw new Error("Consulta no disponible para edicion.");version=d.versionConsulta;for(const c of ["motivoConsulta","diagnostico","observaciones"])form.elements[c].value=d[c];for(const t of d.tratamientos)agregarTratamiento(t);}
 else{if(!Number.isSafeInteger(idCita)||idCita<1)throw new Error("Abre una cita desde tu agenda.");d=(await obtenerCita(idCita)).datos;if(!d.puedeRegistrarConsulta)throw new Error(d.idConsulta?"La cita ya tiene consulta. Abre el registro existente.":"Esta cita todavia no permite registrar una consulta.");form.elements.motivoConsulta.value=d.motivoCita;d.fechaAtencion=d.fechaHoraInicio;}
 mostrarResumenCampos(document.querySelector("#resumen-consulta"),[["Paciente",d.nombrePaciente],["Medico",d.nombreMedico],["Cita","#"+d.idCita],["Fecha de atencion",formatearFecha(d.fechaAtencion)+" "+d.fechaAtencion.slice(11,16)]]);form.hidden=false;
}catch(e){mostrarErrorSolicitud(document.querySelector("#mensaje-carga"),e);}}
form.addEventListener("submit",e=>{e.preventDefault();ejecutarEnvioFormulario(form,async d=>{
 d.tratamientos=[...tratamientos.children].map(b=>Object.fromEntries([...b.querySelectorAll("[data-campo]")].map(c=>[c.dataset.campo,c.value])));
 if(id)d.versionConsulta=version;else d.idCita=idCita;const r=await(id?actualizarConsulta(id,d):registrarConsulta(d));location.assign("/consultas/"+r.datos.idConsulta);
});});cargar();
