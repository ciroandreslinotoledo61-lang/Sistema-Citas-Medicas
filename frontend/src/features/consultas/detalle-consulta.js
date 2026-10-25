import { obtenerConsulta } from "./consultas-api.js";
import { mostrarErrorSolicitud } from "../../components/formularios.js";
import { mostrarResumenCampos, formatearFecha } from "../citas/presentacion-citas.js";
function texto(t,v,c){const h=document.createElement("h3"),p=document.createElement("p");h.textContent=t;p.textContent=v||"Sin registrar";p.className="texto-clinico";c.append(h,p);}
function mostrarTratamientos(filas,c,historico=false){if(!filas.length){const p=document.createElement("p");p.textContent="Sin tratamientos registrados.";c.append(p);}for(const t of filas){const a=document.createElement("article");a.className="tratamiento-resumen";const v=k=>t[historico?k+"_tratamiento":k];texto(v("medicamento"),`${v("dosis")} | ${v("frecuencia")} | ${v("duracion")}`,a);texto("Indicaciones",v("indicaciones"),a);c.append(a);}}
async function cargar(){try{
 const d=(await obtenerConsulta(Number(document.body.dataset.idRegistro))).datos;
 mostrarResumenCampos(document.querySelector("#resumen-consulta"),[["Paciente",d.nombrePaciente],["Medico",d.nombreMedico],["Cita","#"+d.idCita],["Fecha de atencion",formatearFecha(d.fechaAtencion)+" "+d.fechaAtencion.slice(11,16)],["Version",d.versionConsulta]]);
 for(const [t,v] of [["Motivo",d.motivoConsulta],["Diagnostico",d.diagnostico],["Observaciones",d.observaciones]])texto(t,v,document.querySelector("#contenido-consulta"));
 mostrarTratamientos(d.tratamientos,document.querySelector("#tratamientos-consulta"));const editar=document.querySelector("#editar-consulta");editar.hidden=!d.puedeEditar;editar.href="/consultas/"+d.idConsulta+"/editar";document.querySelector("#ver-historial").href="/historial-clinico/"+d.idPaciente;
 for(const v of [...d.versionesAnteriores].reverse()){const b=document.createElement("details"),s=document.createElement("summary");s.textContent="Version "+v.version_consulta;b.append(s);texto("Motivo",v.motivo_consulta,b);texto("Diagnostico",v.diagnostico_consulta,b);texto("Observaciones",v.observaciones_consulta,b);mostrarTratamientos(v.tratamientos||[],b,true);document.querySelector("#lista-versiones").append(b);}
 document.querySelector("#versiones-consulta").hidden=!d.versionesAnteriores.length;document.querySelector("#detalle-consulta").hidden=false;
}catch(e){mostrarErrorSolicitud(document.querySelector("#mensaje-detalle"),e);}}cargar();
