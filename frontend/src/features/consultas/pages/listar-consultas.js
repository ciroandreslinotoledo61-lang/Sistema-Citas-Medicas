import { listarConsultas, obtenerHistorial, actualizarHistoria } from "../services/consultas-api.js";
import { configurarListaPaginada } from "../../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion, ejecutarEnvioFormulario, mostrarMensaje } from "../../../components/formularios.js";
import { formatearFecha, mostrarResumenCampos } from "../../citas/utils/presentacion-citas.js";
const esHistorial=document.body.dataset.pagina==="historial-clinico";
const esPaciente=JSON.parse(document.body.dataset.usuario||"null")?.codigoRol==="paciente";
const id=esPaciente?0:Number(document.body.dataset.idRegistro);
const cuerpo=document.querySelector("#filas-consultas"),formHistoria=document.querySelector("#formulario-historia");let historia;
function mostrarHistoria(h){historia=h;document.querySelector("#nombre-paciente").textContent=h.nombrePaciente;
 mostrarResumenCampos(document.querySelector("#antecedentes-paciente"),[["Tipo de sangre",h.tipoSangre],["Alergias",h.alergias],["Antecedentes",h.antecedentes]]);
 if(formHistoria)for(const c of ["tipoSangre","alergias","antecedentes"])formHistoria.elements[c].value=h[c]||"";document.querySelector("#historia-clinica").hidden=false;
}
configurarListaPaginada(async p=>{
 if(!esHistorial)return listarConsultas(p);const {datos}=await obtenerHistorial(id,p);mostrarHistoria(datos.historia);const editor=document.querySelector("#editar-antecedentes");if(editor)editor.hidden=!datos.puedeEditarHistoria;return {datos:datos.consultas};
},filas=>{for(const c of filas){const fila=document.createElement("tr"),acciones=document.createElement("td");
 acciones.append(crearEnlaceAccion("Ver consulta","/consultas/"+c.idConsulta));if(!esHistorial)acciones.append(document.createTextNode(" "),crearEnlaceAccion("Historial","/historial-clinico/"+c.idPaciente));
 fila.append(crearCeldaTexto(formatearFecha(c.fechaAtencion)+" "+c.fechaAtencion.slice(11,16)));if(!esPaciente)fila.append(crearCeldaTexto(c.nombrePaciente));fila.append(crearCeldaTexto(c.nombreMedico));if(!esPaciente)fila.append(crearCeldaTexto("#"+c.idCita));fila.append(acciones);cuerpo.append(fila);
}},cuerpo,f=>({fechaDesde:f.elements.fechaDesde.value,fechaHasta:f.elements.fechaHasta.value}));
formHistoria?.addEventListener("submit",e=>{e.preventDefault();ejecutarEnvioFormulario(formHistoria,async d=>{const r=await actualizarHistoria(historia.idPaciente,{...d,versionHistoria:historia.versionHistoria});mostrarHistoria(r.datos);mostrarMensaje(formHistoria.querySelector("#mensaje-formulario"),r.mensaje);});});
