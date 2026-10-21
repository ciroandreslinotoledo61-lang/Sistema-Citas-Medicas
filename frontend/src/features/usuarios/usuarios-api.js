import { solicitarApi } from "../../api/cliente-api.js";

export function listarUsuarios(parametros) {
    return solicitarApi("/usuarios?" + parametros);
}
export function obtenerUsuarioPorId(idUsuario) {
    return solicitarApi("/usuarios/" + idUsuario);
}
export function registrarUsuarioAdministrativo(datosUsuario) {
    return solicitarApi("/usuarios", "POST", datosUsuario);
}
export function actualizarDatosUsuario(idUsuario, datosUsuario) {
    return solicitarApi("/usuarios/" + idUsuario, "PATCH", datosUsuario);
}
export function cambiarEstadoUsuario(idUsuario, usuarioActivo) {
    return solicitarApi("/usuarios/" + idUsuario + "/estado", "PATCH", { usuarioActivo });
}
