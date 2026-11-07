import { obtenerFiltrosBitacora, listarBitacora } from "../services/bitacora-api.js";
import { configurarListaPaginada } from "../../../components/lista-paginada.js";
import { crearCeldaTexto, mostrarErrorSolicitud } from "../../../components/formularios.js";

// Solo etiquetas de presentacion: filtros y datos almacenados conservan sus codigos.
const etiquetasRol = {
 administrador: "Administrador", recepcionista: "Recepcionista", medico: "Médico",
 paciente: "Paciente", sistema: "Sistema",
};
const etiquetasModulo = {
 autenticacion: "Autenticación", usuarios: "Usuarios", pacientes: "Pacientes",
 medicos: "Médicos", especialidades: "Especialidades", horarios: "Horarios",
 citas: "Citas", consultas: "Consultas", historias_clinicas: "Historias clínicas",
 tratamientos: "Tratamientos",
};
const etiquetasAccion = {
 crear: "Crear", editar: "Editar", eliminar: "Eliminar", reservar: "Reservar",
 cancelar: "Cancelar", reprogramar: "Reprogramar", atender: "Atender",
 inasistencia: "Inasistencia", cambiar_estado: "Cambiar estado", consultar: "Consultar",
 iniciar_sesion: "Iniciar sesión", cerrar_sesion: "Cerrar sesión",
 cambiar_contrasena: "Cambiar contraseña", restablecer_contrasena: "Restablecer contraseña",
 solicitar_recuperacion: "Solicitar recuperación", enviar_recuperacion: "Enviar recuperación",
 recuperar_contrasena: "Recuperar contraseña",
};
const etiquetasResultado = { exito: "Éxito", rechazado: "Rechazado", error: "Error" };
const descripcionesConocidas = {
 "Operacion de autenticacion.": "Operación de autenticación.",
 "Actualizacion de credenciales de acceso.": "Actualización de credenciales de acceso.",
 "Acceso autorizado al historial clinico.": "Acceso autorizado al historial clínico.",
};
function etiqueta(codigo, catalogo) {
 return Object.hasOwn(catalogo, codigo) ? catalogo[codigo] : String(codigo).replaceAll("_", " ");
}
function descripcionVisible(descripcion) {
 if (Object.hasOwn(descripcionesConocidas, descripcion)) return descripcionesConocidas[descripcion];
 // Reconocer solo la plantilla emitida por el trigger; conservar cualquier texto libre.
 const partes = /^Operacion ([a-z_]+) en ([a-z_]+)\.$/.exec(descripcion);
 if (partes && Object.hasOwn(etiquetasAccion, partes[1]) && Object.hasOwn(etiquetasModulo, partes[2])) {
  return `Operación ${etiquetasAccion[partes[1]].toLocaleLowerCase("es")} en ${etiquetasModulo[partes[2]].toLocaleLowerCase("es")}.`;
 }
 return descripcion;
}
const cuerpo=document.querySelector("#filas-bitacora"),form=document.querySelector("#formulario-busqueda");
obtenerFiltrosBitacora().then(({datos})=>{
 for(const u of datos.usuarios)form.elements.idUsuario.add(new Option(`${u.nombreUsuario} (#${u.idUsuario})`,u.idUsuario));
 for(const [campo,valores] of [["modulo",datos.modulos],["accion",datos.acciones]])for(const v of valores)form.elements[campo].add(new Option(etiqueta(v,campo === "modulo" ? etiquetasModulo : etiquetasAccion),v));
}).catch(e=>mostrarErrorSolicitud(document.querySelector("#mensaje-filtros"),e));
configurarListaPaginada(p=>listarBitacora(p),filas=>{for(const e of filas){
 const fila=document.createElement("tr");const fecha=new Intl.DateTimeFormat("es-BO",{timeZone:"America/La_Paz",dateStyle:"short",timeStyle:"medium"}).format(new Date(e.fechaHora));
 const nombre = e.idUsuario === null && e.nombreUsuario === "Sin sesion / sistema" ? "Sin sesión / sistema" : e.nombreUsuario;
 for(const valor of [fecha,nombre+" / "+etiqueta(e.codigoRol,etiquetasRol),etiqueta(e.modulo,etiquetasModulo)+(e.idRegistro?" #"+e.idRegistro:""),etiqueta(e.accion,etiquetasAccion),etiqueta(e.resultado,etiquetasResultado),descripcionVisible(e.descripcion)])fila.append(crearCeldaTexto(valor));cuerpo.append(fila);
}},cuerpo,f=>Object.fromEntries(["idUsuario","modulo","accion","fechaDesde","fechaHasta"].map(k=>[k,f.elements[k].value])));
