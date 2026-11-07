import { listarSala, cambiarSala, historialSala } from "../services/sala-api.js";
import { configurarListaPaginada } from "../../../components/lista-paginada.js";
import { crearCeldaTexto, mostrarErrorSolicitud } from "../../../components/formularios.js";
const rol=JSON.parse(document.body.dataset.usuario).codigoRol,cuerpo=document.querySelector("#filas-sala");
const estados={pendiente_llegada:"Pendiente de llegada",en_espera:"En espera",en_consulta:"En consulta",atendido:"Atendido"};
const cargar=configurarListaPaginada(listarSala,filas=>{for(const f of filas){
    const tr=document.createElement("tr"),acciones=document.createElement("td");
    const boton=(texto,operacion)=>{const b=document.createElement("button");b.type="button";b.className="boton boton-secundario boton-pequeno";b.textContent=texto;
        b.addEventListener("click",async()=>{if(b.disabled)return;b.disabled=true;try{await operacion();}catch(e){mostrarErrorSolicitud(document.querySelector("#mensaje-listado"),e);}finally{b.disabled=false;}});acciones.append(b);};
    if(f.estadoCita==="programada"){
        const accion=rol==="recepcionista"&&f.estado==="pendiente_llegada"?"llegada":rol==="medico"&&f.estado==="en_espera"?"iniciar":rol==="medico"&&f.estado==="en_consulta"?"finalizar":null;
        if(accion)boton({llegada:"Registrar llegada",iniciar:"Iniciar atención",finalizar:"Finalizar atención"}[accion],async()=>{await cambiarSala(f.idCita,accion,f.version);await cargar();});
    }
    boton("Historial",async()=>{const {datos}=await historialSala(f.idCita);const lista=document.querySelector("#eventos-sala");lista.replaceChildren();for(const evento of datos){const li=document.createElement("li");li.textContent=`${evento.fecha} · ${estados[evento.estado_anterior]||evento.estado_anterior} → ${estados[evento.estado_nuevo]||evento.estado_nuevo} · ${evento.evento} · Usuario #${evento.id_usuario||"sistema"}`;lista.append(li);}document.querySelector("#historial-sala").hidden=false;});
    tr.append(...["#"+f.idCita,f.nombrePaciente,f.nombreMedico,f.fechaHoraInicio,estados[f.estado],f.estadoCita].map(crearCeldaTexto),acciones);cuerpo.append(tr);
}},cuerpo,form=>({fecha:form.elements.fecha.value}));
