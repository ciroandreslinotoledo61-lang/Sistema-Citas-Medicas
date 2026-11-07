// node --experimental-vm-modules backend/tests/test_cancelacion_pagos.mjs
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import { createContext, SourceTextModule, SyntheticModule } from "node:vm";
const raiz=new URL("../../",import.meta.url);let n=0;
const ok=(v,m)=>{assert.ok(v,m);n++;};
class Elemento{
    constructor(){this.hidden=true;this.disabled=false;this.value="";this.textContent="";this.children=[];this.listeners={};}
    addEventListener(t,f){this.listeners[t]=f;}click(){if(!this.disabled)this.listeners.click?.();}
    append(...h){this.children.push(...h);}replaceChildren(...h){this.children=h;}focus(){}removeAttribute(){}
}
async function probar(rol,datos){
    const nodos=new Map(),obtener=id=>{if(!nodos.has(id))nodos.set(id,new Elemento());return nodos.get(id);};
    const form=obtener("#formulario-anulacion");form.elements={motivoCancelacion:new Elemento(),tipoCancelacion:new Elemento()};
    const document={body:{dataset:{usuario:JSON.stringify({codigoRol:rol}),idRegistro:"1"}},querySelector:obtener,createElement:()=>new Elemento()};
    const contexto=createContext({document,window:{location:{search:""}},URLSearchParams,console});
    const base={idCita:1,nombrePaciente:"Paciente ficticio",nombreMedico:"Médico ficticio",estadoCita:"programada",asistenciaRegistrada:"pendiente",fechaHoraInicio:"2030-01-01 08:00",fechaHoraFin:"2030-01-01 08:30",historialCambios:[],motivoCancelacion:"",puedeCancelar:false,puedeReprogramar:false,puedeMarcarAtendida:false,puedeRegistrarInasistencia:false,puedeCancelarPorAusenciaMedico:false,motivoNoCancelacion:"Hora de inicio superada",explicacionAcciones:"Solicita revisión a Recepción",...datos};
    const cache=new Map();
    const linker=spec=>{
        if(cache.has(spec))return cache.get(spec);
        let valores;
        if(spec.endsWith("citas-api.js"))valores={obtenerCita:async()=>({datos:base}),cancelarCita:async()=>({datos:base}),marcarCitaInasistencia:async()=>{},marcarCitaAtendida:async()=>{}};
        else if(spec.endsWith("presentacion-citas.js"))valores={formatearTurno:()=>"Turno ficticio",crearEstadoCita:()=>new Elemento(),estadosAsistencia:{},mostrarResumenCampos:()=>{},estadosCita:{}};
        else if(spec.endsWith("formularios.js"))valores={ejecutarEnvioFormulario:()=>{},mostrarErrorSolicitud:()=>{},mostrarMensaje:()=>{}};
        else throw new Error("Import inesperado: "+spec);
        const modulo=new SyntheticModule(Object.keys(valores),function(){for(const[k,v]of Object.entries(valores))this.setExport(k,v);},{context:contexto});cache.set(spec,modulo);return modulo;
    };
    const pagina=new SourceTextModule(await readFile(new URL("frontend/src/features/citas/pages/detalle-cita.js",raiz),"utf8"),{context:contexto});await pagina.link(linker);await pagina.evaluate();await new Promise(r=>setImmediate(r));
    return {nodos,form};
}
const futura=await probar("paciente",{puedeCancelar:true,motivoNoCancelacion:""});
ok(!futura.nodos.get("#mostrar-anulacion").hidden&&!futura.nodos.get("#mostrar-anulacion").disabled,"Cancelar se conserva habilitado en cita futura autorizada");
futura.nodos.get("#mostrar-anulacion").click();ok(!futura.form.hidden&&futura.form.elements.tipoCancelacion.value==="ordinaria","Abre la confirmación ordinaria");
const pasada=await probar("paciente",{});
ok(!pasada.nodos.get("#mostrar-anulacion").hidden&&pasada.nodos.get("#mostrar-anulacion").disabled,"La hora pasada no hace desaparecer la acción: queda deshabilitada");
ok(!pasada.nodos.get("#motivo-no-cancelacion").hidden&&pasada.nodos.get("#explicacion-acciones").textContent.includes("Recepción"),"Motivo y resolución visibles");
pasada.nodos.get("#ausencia-medico").click();ok(pasada.form.hidden,"Paciente no habilita incidencia del médico");
const recepcion=await probar("recepcionista",{puedeCancelarPorAusenciaMedico:true});recepcion.nodos.get("#ausencia-medico").click();
ok(!recepcion.nodos.get("#ausencia-medico").hidden&&!recepcion.form.hidden&&recepcion.form.elements.tipoCancelacion.value==="medico_ausente"&&recepcion.form.elements.motivoCancelacion.required,"Incidencia con motivo obligatorio solo cuando backend la autoriza");
const final=await probar("medico",{estadoCita:"atendida",motivoNoCancelacion:"Resultado final"});
ok(final.nodos.get("#mostrar-anulacion").disabled&&!final.nodos.get("#motivo-no-cancelacion").hidden,"Atendida no se puede cancelar");
const contexto=createContext({});const servicio=new SourceTextModule(await readFile(new URL("frontend/src/features/pagos/services/pagos-api.js",raiz),"utf8"),{context:contexto});
await servicio.link(()=>new SyntheticModule(["solicitarApi"],function(){this.setExport("solicitarApi",()=>{});},{context:contexto}));await servicio.evaluate();
ok(servicio.namespace.montoDemo(1234)==="Bs 12,34","Importe sin etiqueta repetitiva");
ok(servicio.namespace.metodosPago.qr_demo==="QR"&&servicio.namespace.metodosPago.paypal_demo==="PayPal","Métodos profesionales sin cambiar códigos internos");
ok(Object.values(servicio.namespace.estadosPago).join(",")==="Pendiente,Pagado,Cancelado","Estados legibles");
const html=await readFile(new URL("frontend/src/features/pagos/pages/formulario-pago.php",raiz),"utf8");
ok(html.includes('id="aclaracion-prueba"')&&html.includes("no se realizarán cargos reales")&&!html.includes("Pagar ahora ·"),"Aclaración discreta en confirmación sin modificar seguridad financiera");
console.log(`OK: ${n} comprobaciones de cancelación visible/permisos y presentación profesional de pagos; sin red.`);
