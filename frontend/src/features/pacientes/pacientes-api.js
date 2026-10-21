import { solicitarApi } from "../../api/cliente-api.js";

export function listarPacientes(parametros) {
    return solicitarApi("/pacientes?" + parametros);
}
export function registrarPacienteDesdeRecepcion(datosPaciente) {
    return solicitarApi("/pacientes", "POST", datosPaciente);
}
export function obtenerPacientePorId(idPaciente) {
    return solicitarApi("/pacientes/" + idPaciente);
}
export function obtenerPerfilPacienteAutenticado() {
    return solicitarApi("/pacientes/mi-perfil");
}
export function actualizarContactoPaciente(idPaciente, datosContacto) {
    return solicitarApi("/pacientes/" + idPaciente + "/contacto", "PATCH", datosContacto);
}
