// Ejecuta el helper y las páginas reales sin red, BD ni paquetes externos.
// node --experimental-vm-modules backend/tests/test_formularios.mjs
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import { createContext, SourceTextModule, SyntheticModule } from "node:vm";
const raiz=new URL("../../",import.meta.url);
const esperar=()=>new Promise(resolve=>setImmediate(resolve));
class Elemento{
    constructor(){this.value="1";this.children=[];this.listeners={};this.disabled=false;this.hidden=false;this.textContent="";}
    addEventListener(evento,callback){this.listeners[evento]=callback;}
    emitir(evento){this.listeners[evento]?.({preventDefault(){}});}
    append(...hijos){this.children.push(...hijos);}
    replaceChildren(...hijos){this.children=hijos;}
    setAttribute(){} removeAttribute(){}
}
let comprobaciones=0;
function comprobar(valor,mensaje){assert.ok(valor,mensaje);comprobaciones++;}
async function probarPagina(modulo,editar,error){
    const form=new Elemento(),boton=new Elemento(),mensaje=new Elemento(),selector=new Elemento();
    form.valores={nombresUsuario:"Ficticio",idEspecialidad:"1"};form.resets=0;form.eventos=[];
    form.elements={idEspecialidad:selector};form.reset=()=>{form.resets++;form.valores={};};
    form.dispatchEvent=e=>form.eventos.push(e.type);
    form.querySelector=id=>id.includes("submit")?boton:mensaje;
    const document={body:{dataset:{usuario:JSON.stringify({codigoRol:"administrador"}),idRegistro:editar?"1":"0"}},
        querySelector:id=>id.startsWith("#formulario-")?form:id==="#mensaje-formulario"?mensaje:new Elemento(),createElement:()=>new Elemento()};
    const contexto=createContext({document,console,FormData:class{constructor(f){this.f=f;}entries(){return Object.entries(this.f.valores);}},CustomEvent:class{constructor(type){this.type=type;}},HTMLInputElement:Elemento,HTMLSelectElement:Elemento});
    const helper=new SourceTextModule(await readFile(new URL("frontend/src/components/formularios.js",raiz),"utf8"),{context:contexto});await helper.link(()=>{throw new Error("Import no previsto en helper");});await helper.evaluate();
    let llamadas=0;
    const registro=async()=>{llamadas++;await esperar();if(error)throw new Error("Error simulado de validación");return {mensaje:"Creación correcta",datos:{idMedico:1,idUsuario:1,idPaciente:1,idEspecialidad:1}};};
    const obtener=async()=>({datos:{idMedico:1,idUsuario:1,idEspecialidad:1,especialidadActiva:true}});
    const sinteticos=new Map();
    const enlazar=async spec=>{
        if(spec.endsWith("formularios.js"))return helper;
        if(sinteticos.has(spec))return sinteticos.get(spec);
        let valores;
        if(spec.endsWith("contrasenas.js"))valores={configurarValidacionContrasenas:()=>()=>true,marcarErroresContrasena:()=>{}};
        else if(spec.endsWith("especialidades-api.js"))valores={listarEspecialidadesActivas:async()=>({datos:[{idEspecialidad:1,nombreEspecialidad:"Ficticia"}]}),obtenerEspecialidadPorId:obtener,registrarEspecialidad:registro,actualizarDatosEspecialidad:registro};
        else if(spec.endsWith("medicos-api.js"))valores={obtenerMedicoPorId:obtener,registrarMedicoConUsuario:registro,actualizarDatosMedico:registro};
        else if(spec.endsWith("usuarios-api.js"))valores={obtenerUsuarioPorId:obtener,registrarUsuarioAdministrativo:registro,actualizarDatosUsuario:registro};
        else if(spec.endsWith("pacientes-api.js"))valores={registrarPacienteDesdeRecepcion:registro};
        else if(spec.endsWith("autenticacion-api.js"))valores={registrarCuentaPaciente:registro};
        else throw new Error("Import no previsto: "+spec);
        const m=new SyntheticModule(Object.keys(valores),function(){for(const [k,v]of Object.entries(valores))this.setExport(k,v);},{context:contexto});sinteticos.set(spec,m);return m;
    };
    // Para cargar datos de edición se proporciona el contrato de namedItem.
    form.elements.namedItem=()=>new Elemento();
    const pagina=new SourceTextModule(await readFile(new URL(`frontend/src/features/${modulo}`,raiz),"utf8"),{context:contexto});await pagina.link(enlazar);await pagina.evaluate();await esperar();
    form.emitir("submit");form.emitir("submit");await esperar();await esperar();
    comprobar(llamadas===1,`${modulo}: doble envío bloqueado`);
    comprobar(!boton.disabled,`${modulo}: botón recuperado`);
    if(error){comprobar(form.resets===0&&form.valores.nombresUsuario==="Ficticio",`${modulo}: el error no borra datos`);comprobar(mensaje.textContent.includes("Error simulado"),`${modulo}: muestra error`);}
    else if(editar){comprobar(form.resets===0,`${modulo}: edición sin limpiar datos`);}
    else{comprobar(form.resets===1&&form.eventos.includes("registro-creado"),`${modulo}: creación limpia y notifica actualización`);comprobar(mensaje.textContent==="Creación correcta",`${modulo}: mensaje de éxito`);}
}
for(const pagina of ["medicos/pages/formulario-medico.js","usuarios/pages/formulario-usuario.js","especialidades/pages/formulario-especialidad.js","pacientes/pages/registrar-paciente.js"]){
    await probarPagina(pagina,false,false);await probarPagina(pagina,false,true);
    if(!pagina.startsWith("pacientes/"))await probarPagina(pagina,true,false);
}
console.log(`OK: ${comprobaciones} comprobaciones de formularios reales: creación, éxito, error, edición y envíos duplicados; sin red.`);
