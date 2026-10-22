import { solicitarApi } from "../../api/cliente-api.js";

export function listarMedicos(parametros) {
    return solicitarApi("/medicos?" + parametros);
}
export function obtenerMedicoPorId(idMedico) {
    return solicitarApi("/medicos/" + idMedico);
}
export function registrarMedicoConUsuario(datosMedico) {
    return solicitarApi("/medicos", "POST", datosMedico);
}
export function actualizarDatosMedico(idMedico, datosMedico) {
    return solicitarApi("/medicos/" + idMedico, "PATCH", datosMedico);
}
export function cambiarEstadoMedico(idMedico, medicoActivo) {
    return solicitarApi("/medicos/" + idMedico + "/estado", "PATCH", { medicoActivo });
}
