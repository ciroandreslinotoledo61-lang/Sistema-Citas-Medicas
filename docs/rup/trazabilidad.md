# Trazabilidad

Clases en backend/src; pruebas en backend/tests. La [API](../ai/api.md) y [catalogo](../ai/catalogo-metodos.md) enlazan archivos exactos.

| RF | CU | Servicio/control | Tablas | Pantalla | Evidencia |
| --- | --- | --- | --- | --- | --- |
| 01 | 01,02 | AutenticacionService, PermisosSistema | roles, usuarios | auth/panel | test_permisos, escenario_http |
| 02 | 03-07 | PacienteService | usuarios, pacientes, historias_clinicas | pacientes/registro | test_registro_paciente, test_reportes_perfiles |
| 03 | 16-19 | MedicoService | usuarios, medicos, especialidades | medicos | test_gestion_especialidades_medicos |
| 04 | 12-15 | EspecialidadService | especialidades, medicos | especialidades | test_validaciones_especialidades_medicos |
| 05 | 20,21 | HorarioMedicoService | horarios_medicos, turnos | horarios | test_programacion_periodos, test_transacciones_capas |
| 06 | 22-24 | DisponibilidadService, CitaService | pacientes, turnos, citas | reserva | test_gestion_citas |
| 07 | 23,24 | prepararReserva, triggers, UNIQUE | citas, pacientes, turnos | reserva | test_gestion_citas; concurrente pendiente nativo |
| 08 | 27 | reprogramarCita | citas.id_cita_origen | reprogramacion | test_programacion_periodos |
| 09 | 26 | cancelarCita | citas | detalle | test_gestion_citas |
| 10 | 28,29,32 | CitaService, ConsultaService | citas, consultas | detalle/consulta | test_reportes_perfiles, test_consultas_bitacora |
| 11 | 25,30 | listarCitas | citas, turnos, medicos | agenda | test_validaciones_agenda, escenario_http |
| 12 | 25,27 | obtenerResultadoReprogramacion | citas, historial_cambios | detalle | test_programacion_periodos, test_migracion_006 |
| 13 | 36 | ReporteService | citas, pacientes, usuarios | reportes | test_reportes_perfiles |
| 14 | 40 | BitacoraService y triggers 007 | bitacora_sistema | bitacora | test_consultas_bitacora |
| 15 | 32,35 | ConsultaService/Validator | consultas, tratamientos, citas | consultas | test_consultas_bitacora |
| 16 | 33,34 | HistoriaClinicaService | historias_clinicas, consultas | historial | test_consultas_bitacora |

Aceptacion sensible: 006 preserva eventos y no duplica al repetir; endpoint Confirmar devuelve 404; segunda consulta 409; fallo de tratamiento revierte todo; historia falsificada 422/P2003; version vieja 409; roles/propietarios ajenos 403/404; bitacora sin contenido/secretos y sin UPDATE/DELETE/TRUNCATE; SQL de estudio rechaza destinos no `_pruebas`.

La evidencia se ejecuta en el entorno de [pruebas](../ai/pruebas.md); esta matriz no implica que las utilidades nativas Windows ya se hayan probado.
