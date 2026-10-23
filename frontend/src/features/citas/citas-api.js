import { solicitarApi } from "../../api/cliente-api.js";
export const listarCitas = parametros => solicitarApi("/citas?" + parametros);
export const obtenerCita = identificador => solicitarApi("/citas/" + identificador);
export const reservarCita = datos => solicitarApi("/citas", "POST", datos);
export const cancelarCita = (identificador, datos) => solicitarApi("/citas/" + identificador + "/cancelacion", "PATCH", datos);
export const reprogramarCita = (identificador, datos) => solicitarApi("/citas/" + identificador + "/reprogramacion", "PATCH", datos);
export const registrarAsistencia = (identificador, asistenciaRegistrada) => solicitarApi("/citas/" + identificador + "/asistencia", "PATCH", { asistenciaRegistrada });
export const listarMedicosDisponibles = () => solicitarApi("/disponibilidad/medicos");
export const listarTurnosDisponibles = (idMedico, fecha) => solicitarApi("/disponibilidad/turnos?" + new URLSearchParams({ idMedico, fecha }));

export const listarMedicosParaAgenda = () => solicitarApi("/agenda/medicos");
export const obtenerConfiguracionAgenda = () => solicitarApi("/disponibilidad/configuracion");
export const marcarCitaAtendida = idCita => solicitarApi("/citas/" + idCita + "/atencion", "PATCH");
