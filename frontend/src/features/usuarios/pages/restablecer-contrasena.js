import { obtenerUsuarioPorId, restablecerContrasena } from "../services/usuarios-api.js";
import { mostrarMensaje, mostrarErrorSolicitud } from "../../../components/formularios.js";
import { limpiarContrasenas } from "../../../components/contrasenas.js";

const formulario = document.querySelector("#formulario-restablecer-contrasena");
const mensaje = formulario.querySelector("#mensaje-formulario");
const campos = formulario.querySelector("#campos-restablecimiento");
const boton = formulario.querySelector('[type="submit"]');
const temporal = formulario.querySelector("#contrasena-temporal");
const confirmar = formulario.querySelector("#confirmar-restablecimiento");
const idUsuario = Number(document.body.dataset.idRegistro);
const usuarioActual = JSON.parse(document.body.dataset.usuario);
let cuentaDisponible = false;

async function cargarCuenta() {
    try {
        const { datos: cuenta } = await obtenerUsuarioPorId(idUsuario);
        document.querySelector("#nombre-cuenta").textContent = cuenta.nombresUsuario + " " + cuenta.apellidosUsuario;
        document.querySelector("#correo-cuenta").textContent = cuenta.correoUsuario;
        document.querySelector("#rol-cuenta").textContent = cuenta.nombreRol;
        document.querySelector("#estado-usuario").textContent = cuenta.usuarioActivo ? "Activo" : "Inactivo";
        document.querySelector("#datos-cuenta").hidden = false;
        document.querySelector("#estado-cuenta").hidden = true;
        if (cuenta.idUsuario === usuarioActual.idUsuario) {
            throw new Error("Para tu propia cuenta utiliza Cambiar contraseña en el menú.");
        }
        if (!cuenta.usuarioActivo) throw new Error("Esta cuenta está inactiva. Activa la cuenta desde su módulo antes de restablecerla.");
        cuentaDisponible = true;
        campos.disabled = false;
    } catch (error) {
        document.querySelector("#estado-cuenta").hidden = true;
        mostrarErrorSolicitud(mensaje, error);
    }
}

document.querySelector("#generar-temporal").addEventListener("click", () => {
    if (!cuentaDisponible) return;
    try {
        let claveGenerada = "";
        for (let intento = 0; intento < 10; intento++) {
            const bytes = crypto.getRandomValues(new Uint8Array(12));
            const propuesta = Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0")).join("");
            if (new Set(propuesta).size >= 6 && !/^(.{1,12})\1+$/.test(propuesta)) {
                claveGenerada = propuesta;
                break;
            }
        }
        if (!claveGenerada) throw new Error("Generación no disponible.");
        temporal.value = claveGenerada;
        confirmar.checked = false;
        document.querySelector("#area-temporal").hidden = false;
        boton.disabled = false;
        mensaje.hidden = true;
        temporal.focus();
    } catch {
        mostrarMensaje(mensaje, "No se pudo generar una contraseña segura. Actualiza tu navegador e inténtalo otra vez.", true);
    }
});

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (boton.disabled || !cuentaDisponible || !confirmar.checked || !/^[0-9a-f]{24}$/.test(temporal.value)) return;
    const datos = { contrasenaTemporal: temporal.value, confirmarRestablecimiento: true };
    boton.disabled = true;
    campos.disabled = true;
    boton.textContent = "Restableciendo...";
    formulario.setAttribute("aria-busy", "true");
    mensaje.hidden = true;
    try {
        const respuesta = await restablecerContrasena(idUsuario, datos);
        cuentaDisponible = false;
        mostrarMensaje(mensaje, respuesta.mensaje + " Entrega la contraseña guardada de forma privada a la persona.");
        boton.hidden = true;
    } catch (error) {
        mostrarErrorSolicitud(mensaje, error);
        if (error.codigoHttp === 0 || error.codigoHttp >= 500) {
            mensaje.append(" Si no pudiste confirmar el resultado, genera otra contraseña y restablece de nuevo antes de entregarla.");
        }
    } finally {
        limpiarContrasenas(formulario);
        confirmar.checked = false;
        document.querySelector("#area-temporal").hidden = true;
        campos.disabled = !cuentaDisponible;
        boton.textContent = "Restablecer contraseña";
        formulario.removeAttribute("aria-busy");
        mensaje.focus();
    }
});

window.addEventListener("pagehide", () => {
    limpiarContrasenas(formulario);
    confirmar.checked = false;
    boton.disabled = true;
    campos.disabled = true;
    document.querySelector("#area-temporal").hidden = true;
});
window.addEventListener("pageshow", (evento) => {
    if (evento.persisted) {
        limpiarContrasenas(formulario);
        window.location.reload();
    }
});
cargarCuenta();
