import { obtenerReceta, anularReceta, descargarReceta } from "../services/recetas-api.js";
import { mostrarResumenCampos } from "../../citas/utils/presentacion-citas.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud } from "../../../components/formularios.js";
const id=Number(document.body.dataset.idRegistro), boton=document.querySelector("#descargar-receta"), form=document.querySelector("#formulario-anular-receta");
async function cargar(){try{
    const {datos:r}=await obtenerReceta(id), d=r.datosEmision;
    mostrarResumenCampos(document.querySelector("#resumen-receta"),[["Receta","#"+id],["Clínica",d.clinica],["Paciente",d.paciente],["Médico",d.medico],["Matrícula",d.matricula],["Emisión",r.fechaEmision],["Estado",r.estado],["Motivo de anulación",r.motivoAnulacion||"—"],["Sustituye receta",r.idRecetaAnterior?"#"+r.idRecetaAnterior:"—"]]);
    const contenedor=document.querySelector("#medicamentos-emitidos");contenedor.replaceChildren();
    for(const m of r.medicamentos){const articulo=document.createElement("article"),titulo=document.createElement("h3"),p=document.createElement("p");titulo.textContent=m.medicamento;p.textContent=`Dosis: ${m.dosis} | Frecuencia: ${m.frecuencia} | Duración: ${m.duracion} | Indicaciones: ${m.indicaciones}`;articulo.append(titulo,p);contenedor.append(articulo);}
    document.querySelector("#consulta-receta").href="/consultas/"+r.idConsulta;document.querySelector("#detalle-receta").hidden=false;boton.disabled=false;
    if(form){form.hidden=r.estado!=="emitida";const corregir=document.querySelector("#corregir-receta");corregir.hidden=r.estado!=="anulada";corregir.href=`/recetas/nueva?idConsulta=${r.idConsulta}&idRecetaAnterior=${id}`;}
}catch(e){mostrarErrorSolicitud(document.querySelector("#mensaje-detalle"),e);}}
cargar();
boton.addEventListener("click",async()=>{boton.disabled=true;try{await descargarReceta(id);}catch(e){mostrarErrorSolicitud(document.querySelector("#mensaje-detalle"),e);}finally{boton.disabled=false;}});
form?.addEventListener("submit",e=>{e.preventDefault();ejecutarEnvioFormulario(form,async d=>{await anularReceta(id,d.motivo);form.reset();await cargar();});});
