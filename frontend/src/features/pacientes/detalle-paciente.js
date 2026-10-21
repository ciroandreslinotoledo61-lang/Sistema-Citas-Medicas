import { obtenerPacientePorId, obtenerPerfilPacienteAutenticado, actualizarContactoPaciente } from "./pacientes-api.js";
import { ejecutarEnvioFormulario, completarCamposFormulario, mostrarErrorSolicitud, mostrarMensaje } from "../../components/formularios.js";

const esPerfilPropio = document.body.dataset.pagina === "mi-perfil";
const idPaciente = Number(document.body.dataset.idRegistro);
const formularioContacto = document.querySelector("#formulario-contacto-paciente");

function mostrarDatosPaciente(paciente) {
    document.querySelector("#nombre-paciente").textContent = paciente.nombresUsuario + " " + paciente.apellidosUsuario;
    const datosPaciente = document.querySelector("#datos-paciente");
    datosPaciente.replaceChildren();
    const campos = [
        ["Cedula de identidad", paciente.cedulaIdentidad], ["Correo electronico", paciente.correoUsuario],
        ["Fecha de nacimiento", paciente.fechaNacimiento], ["Historia clinica", "HC-" + paciente.idHistoriaClinica],
        ["Telefono", paciente.telefonoPaciente], ["Direccion", paciente.direccionPaciente],
        ["Contacto de emergencia", paciente.nombreContactoEmergencia], ["Telefono de emergencia", paciente.telefonoContactoEmergencia],
    ];
    for (const [etiqueta, valor] of campos) {
        const contenedor = document.createElement("div");
        const titulo = document.createElement("dt");
        const contenido = document.createElement("dd");
        titulo.textContent = etiqueta;
        contenido.textContent = valor || "Sin registrar";
        contenedor.append(titulo, contenido);
        datosPaciente.append(contenedor);
    }
    if (formularioContacto) {
        completarCamposFormulario(formularioContacto, paciente);
        document.querySelector("#campos-contacto").disabled = false;
        document.querySelector("#guardar-contacto").disabled = false;
    }
}

async function cargarDatosPaciente() {
    try {
        const respuesta = esPerfilPropio ? await obtenerPerfilPacienteAutenticado() : await obtenerPacientePorId(idPaciente);
        mostrarDatosPaciente(respuesta.datos);
    } catch (error) {
        document.querySelector("#nombre-paciente").textContent = "No se pudo cargar el perfil";
        mostrarErrorSolicitud(document.querySelector("#mensaje-detalle"), error);
    }
}
cargarDatosPaciente();

formularioContacto?.addEventListener("submit", (evento) => {
    evento.preventDefault();
    ejecutarEnvioFormulario(formularioContacto, async (datosContacto) => {
        const respuesta = await actualizarContactoPaciente(idPaciente, datosContacto);
        mostrarDatosPaciente(respuesta.datos);
        mostrarMensaje(document.querySelector("#mensaje-formulario"), respuesta.mensaje);
    });
});
