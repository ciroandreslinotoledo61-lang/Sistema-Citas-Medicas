import { listarConsultas, obtenerHistorial, actualizarHistoria } from "./consultas-api.js";
import { configurarListaPaginada } from "../../components/lista-paginada.js";
import { crearCeldaTexto, crearEnlaceAccion, ejecutarEnvioFormulario, mostrarMensaje } from "../../components/formularios.js";
import { formatearFecha, mostrarResumenCampos } from "../citas/presentacion-citas.js";
const esHistorial=document.body.dataset.pagina==="historial-clinico",id=Number(document.body.dataset.idRegistro);
const cuerpo=document.querySelector("#filas-consultas"),formHistoria=document.querySelector("#formulario-historia");let historia;
function mostrarHistoria(h){historia=h;document.querySelector("#nombre-paciente").textContent=h.nombrePaciente;
 mostrarResumenCampos(document.querySelector("#antecedentes-paciente"),[["Tipo de sangre",h.tipoSangre],["Alergias",h.alergias],["Antecedentes",h.antecedentes]]);
 for(const c of ["tipoSangre","alergias","antecedentes"])formHistoria.elements[c].value=h[c]||"";document.querySelector("#historia-clinica").hidden=false;
}
configurarListaPaginada(async p=>{
 if(!esHistorial)return listarConsultas(p);const {datos}=await obtenerHistorial(id,p);mostrarHistoria(datos.historia);document.querySelector("#editar-antecedentes").hidden=!datos.puedeEditarHistoria;return {datos:datos.consultas};
},filas=>{for(const c of filas){const fila=document.createElement("tr"),acciones=document.createElement("td");
 acciones.append(crearEnlaceAccion("Ver consulta","/consultas/"+c.idConsulta));if(!esHistorial)acciones.append(document.createTextNode(" "),crearEnlaceAccion("Historial","/historial-clinico/"+c.idPaciente));
 fila.append(crearCeldaTexto(formatearFecha(c.fechaAtencion)+" "+c.fechaAtencion.slice(11,16)),crearCeldaTexto(c.nombrePaciente),crearCeldaTexto(c.nombreMedico),crearCeldaTexto("#"+c.idCita),acciones);cuerpo.append(fila);
}},cuerpo,f=>({fechaDesde:f.elements.fechaDesde.value,fechaHasta:f.elements.fechaHasta.value}));
formHistoria?.addEventListener("submit",e=>{e.preventDefault();ejecutarEnvioFormulario(formHistoria,async d=>{const r=await actualizarHistoria(historia.idPaciente,{...d,versionHistoria:historia.versionHistoria});mostrarHistoria(r.datos);mostrarMensaje(formHistoria.querySelector("#mensaje-formulario"),r.mensaje);});});
