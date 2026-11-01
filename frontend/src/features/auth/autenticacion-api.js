import { solicitarApi } from "../../api/cliente-api.js";

export function iniciarSesionUsuario(datosUsuario) {
    return solicitarApi("/autenticacion/iniciar-sesion", "POST", datosUsuario);
}

export function registrarCuentaPaciente(datosPaciente) {
    return solicitarApi("/autenticacion/registro-paciente", "POST", datosPaciente);
}

export function cambiarContrasena(datos) {
    return solicitarApi("/autenticacion/cambiar-contrasena", "POST", datos);
}
