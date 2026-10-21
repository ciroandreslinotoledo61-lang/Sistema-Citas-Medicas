import { registrarCuentaPaciente } from "../auth/autenticacion-api.js";
import { registrarPacienteDesdeRecepcion } from "./pacientes-api.js";
import { ejecutarEnvioFormulario, mostrarMensaje } from "../../components/formularios.js";

const formulario = document.querySelector("#formulario-registro-paciente");
const registroPublico = document.body.dataset.usuario === "null";
let registroCompletado = false;

formulario.addEventListener("submit", (evento) => {
    evento.preventDefault();
    if (registroCompletado) return;
    ejecutarEnvioFormulario(formulario, async (datosPaciente) => {
        const respuesta = registroPublico
            ? await registrarCuentaPaciente(datosPaciente)
            : await registrarPacienteDesdeRecepcion(datosPaciente);
        registroCompletado = true;
        if (!registroPublico) {
            window.location.assign("/pacientes/" + respuesta.datos.idPaciente);
            return;
        }
        formulario.reset();
        formulario.querySelectorAll("fieldset").forEach((campo) => { campo.disabled = true; });
        const mensaje = document.querySelector("#mensaje-formulario");
        mostrarMensaje(mensaje, "Tu cuenta fue creada. Ya puedes iniciar sesion.");
        const enlace = document.createElement("a");
        enlace.href = "/iniciar-sesion";
        enlace.textContent = " Ir a iniciar sesion";
        mensaje.append(enlace);
        formulario.querySelector('[type="submit"]').hidden = true;
        mensaje.scrollIntoView({ block: "center", behavior: "smooth" });
    });
});
