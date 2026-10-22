import { solicitarApi } from "../../api/cliente-api.js";

export function listarEspecialidades(parametros) {
    return solicitarApi("/especialidades?" + parametros);
}
export function listarEspecialidadesActivas() {
    return solicitarApi("/especialidades/activas");
}
export function obtenerEspecialidadPorId(idEspecialidad) {
    return solicitarApi("/especialidades/" + idEspecialidad);
}
export function registrarEspecialidad(datosEspecialidad) {
    return solicitarApi("/especialidades", "POST", datosEspecialidad);
}
export function actualizarDatosEspecialidad(idEspecialidad, datosEspecialidad) {
    return solicitarApi("/especialidades/" + idEspecialidad, "PATCH", datosEspecialidad);
}
export function cambiarEstadoEspecialidad(idEspecialidad, especialidadActiva) {
    return solicitarApi("/especialidades/" + idEspecialidad + "/estado", "PATCH", { especialidadActiva });
}
