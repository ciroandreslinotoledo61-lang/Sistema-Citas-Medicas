import { emitirReceta } from "../services/recetas-api.js";
import { obtenerConsulta } from "../../consultas/services/consultas-api.js";
import { ejecutarEnvioFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../../components/formularios.js";
const form = document.querySelector("#formulario-receta"), filas = document.querySelector("#medicamentos-receta");
const parametros = new URLSearchParams(window.location.search), idConsulta = Number(parametros.get("idConsulta"));
const anterior = parametros.get("idRecetaAnterior");
let emitida = false;
function agregarMedicamento() {
    if (filas.children.length >= 20) return;
    const fila = document.createElement("fieldset"), leyenda = document.createElement("legend"); leyenda.textContent = "Medicamento"; fila.append(leyenda);
    for (const [nombre,titulo,max] of [["medicamento","Medicamento",150],["dosis","Dosis",150],["frecuencia","Frecuencia",150],["duracion","Duración",150],["indicaciones","Indicaciones",500]]) {
        const label=document.createElement("label"), input=document.createElement(nombre==="indicaciones"?"textarea":"input");
        label.textContent=titulo; input.dataset.campo=nombre; input.maxLength=max; input.required=nombre!=="indicaciones"; label.append(input); fila.append(label);
    }
    const quitar=document.createElement("button");quitar.type="button";quitar.className="boton boton-secundario";quitar.textContent="Quitar medicamento";
    quitar.addEventListener("click",()=>{if(filas.children.length>1)fila.remove();});fila.append(quitar);filas.append(fila);
}
agregarMedicamento();document.querySelector("#agregar-medicamento").addEventListener("click",agregarMedicamento);
async function cargar(){try{
    const {datos}=await obtenerConsulta(idConsulta);if(!datos.puedeEditar)throw new Error("Solo el médico de esta consulta puede emitir recetas.");
    document.querySelector("#contexto-receta").textContent=`Consulta #${datos.idConsulta} · ${datos.nombrePaciente} · ${datos.nombreMedico}`;
    document.querySelector("#campos-receta").disabled=false;document.querySelector("#emitir-receta").disabled=false;
}catch(e){mostrarErrorSolicitud(form.querySelector("#mensaje-formulario"),e);}}
cargar();
form.addEventListener("submit",e=>{e.preventDefault();if(emitida)return;ejecutarEnvioFormulario(form,async()=>{
    const medicamentos=[...filas.children].map(fila=>Object.fromEntries([...fila.querySelectorAll("[data-campo]")].map(input=>[input.dataset.campo,input.value])));
    const r=await emitirReceta({idConsulta,medicamentos,...(anterior?{idRecetaAnterior:Number(anterior)}:{})});
    emitida=true;form.reset();document.querySelector("#campos-receta").disabled=true;document.querySelector("#emitir-receta").hidden=true;
    mostrarMensaje(form.querySelector("#mensaje-formulario"),r.mensaje);
    const enlace=document.querySelector("#ver-receta-emitida");enlace.href="/recetas/"+r.datos.idReceta;enlace.hidden=false;
});});
