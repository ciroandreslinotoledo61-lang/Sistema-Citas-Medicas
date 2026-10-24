# Endpoints conservados

48 combinaciones de método HTTP y URL. `Pública` permite acceso sin sesión; las escrituras igualmente exigen CSRF. `Sesión` implica autenticación y el servicio aplica los permisos de la operación y la propiedad del recurso.

| Método | Endpoint | Controlador y método | Acceso general |
| --- | --- | --- | --- |
| GET | `/api/estado` | `EstadoController::obtenerEstado` | Pública |
| GET | `/api/autenticacion/token` | `TokenCsrfController::obtenerToken` | Pública |
| POST | `/api/autenticacion/iniciar-sesion` | `AutenticacionController::iniciarSesionUsuario` | Pública |
| POST | `/api/autenticacion/cerrar-sesion` | `AutenticacionController::cerrarSesionUsuario` | Pública |
| GET | `/api/autenticacion/sesion` | `AutenticacionController::obtenerSesionUsuario` | Sesión + permiso del servicio |
| POST | `/api/autenticacion/registro-paciente` | `PacienteController::registrarPacienteConHistoriaClinica` | Pública |
| GET | `/api/roles` | `UsuarioController::listarRolesSistema` | administrador |
| GET | `/api/usuarios` | `UsuarioController::listarUsuarios` | administrador |
| POST | `/api/usuarios` | `UsuarioController::registrarUsuarioAdministrativo` | administrador |
| GET | `/api/usuarios/{idUsuario}` | `UsuarioController::obtenerUsuarioPorId` | administrador |
| PATCH | `/api/usuarios/{idUsuario}` | `UsuarioController::actualizarDatosUsuario` | administrador |
| PATCH | `/api/usuarios/{idUsuario}/estado` | `UsuarioController::cambiarEstadoUsuario` | administrador |
| GET | `/api/pacientes/mi-perfil` | `PacienteController::obtenerPerfilPaciente` | paciente |
| GET | `/api/pacientes` | `PacienteController::listarPacientes` | administrador, recepcionista |
| POST | `/api/pacientes` | `PacienteController::registrarPacienteConHistoriaClinica` | administrador, recepcionista |
| GET | `/api/pacientes/{idPaciente}` | `PacienteController::obtenerPacientePorId` | administrador, recepcionista |
| PATCH | `/api/pacientes/{idPaciente}` | `PacienteController::actualizarDatosPaciente` | administrador, recepcionista |
| PATCH | `/api/pacientes/{idPaciente}/estado` | `PacienteController::cambiarEstadoPaciente` | administrador, recepcionista |
| PATCH | `/api/pacientes/{idPaciente}/contacto` | `PacienteController::actualizarContactoPaciente` | administrador, recepcionista |
| GET | `/api/especialidades/activas` | `EspecialidadController::listarEspecialidadesActivas` | administrador |
| GET | `/api/especialidades` | `EspecialidadController::listarEspecialidades` | administrador |
| POST | `/api/especialidades` | `EspecialidadController::registrarEspecialidad` | administrador |
| GET | `/api/especialidades/{idEspecialidad}` | `EspecialidadController::obtenerEspecialidadPorId` | administrador |
| PATCH | `/api/especialidades/{idEspecialidad}` | `EspecialidadController::actualizarDatosEspecialidad` | administrador |
| PATCH | `/api/especialidades/{idEspecialidad}/estado` | `EspecialidadController::cambiarEstadoEspecialidad` | administrador |
| GET | `/api/medicos` | `MedicoController::listarMedicos` | administrador |
| POST | `/api/medicos` | `MedicoController::registrarMedicoConUsuario` | administrador |
| GET | `/api/medicos/{idMedico}` | `MedicoController::obtenerMedicoPorId` | administrador |
| PATCH | `/api/medicos/{idMedico}` | `MedicoController::actualizarDatosMedico` | administrador |
| PATCH | `/api/medicos/{idMedico}/estado` | `MedicoController::cambiarEstadoMedico` | administrador |
| GET | `/api/horarios` | `HorarioMedicoController::listarHorarios` | Sesión + permiso del servicio |
| POST | `/api/horarios` | `HorarioMedicoController::registrarHorario` | Sesión + permiso del servicio |
| POST | `/api/horarios/programacion` | `HorarioMedicoController::programarHorariosPorPeriodo` | Sesión + permiso del servicio |
| GET | `/api/disponibilidad/configuracion` | `DisponibilidadController::obtenerConfiguracionAgenda` | Sesión + permiso del servicio |
| GET | `/api/disponibilidad/medicos` | `DisponibilidadController::listarMedicosDisponibles` | Sesión + permiso del servicio |
| GET | `/api/disponibilidad/turnos` | `DisponibilidadController::listarTurnosDisponibles` | Sesión + permiso del servicio |
| GET | `/api/agenda/medicos` | `CitaController::listarMedicosParaAgenda` | Sesión + permiso del servicio |
| GET | `/api/citas/activas` | `CitaController::listarCitasActivasPaciente` | Sesión + permiso del servicio |
| GET | `/api/citas` | `CitaController::listarCitas` | Sesión + permiso del servicio |
| POST | `/api/citas` | `CitaController::reservarCita` | Sesión + permiso del servicio |
| GET | `/api/citas/{idCita}` | `CitaController::obtenerCita` | Sesión + permiso del servicio |
| PATCH | `/api/citas/{idCita}/cancelacion` | `CitaController::cancelarCita` | Sesión + permiso del servicio |
| PATCH | `/api/citas/{idCita}/reprogramacion` | `CitaController::reprogramarCita` | Sesión + permiso del servicio |
| PATCH | `/api/citas/{idCita}/asistencia` | `CitaController::registrarAsistencia` | Sesión + permiso del servicio |
| PATCH | `/api/citas/{idCita}/atencion` | `CitaController::marcarCitaAtendida` | Sesión + permiso del servicio |
| PATCH | `/api/citas/{idCita}/inasistencia` | `CitaController::marcarCitaInasistencia` | Sesión + permiso del servicio |
| PATCH | `/api/citas/{idCita}/confirmacion` | `CitaController::confirmarCita` | Sesión + permiso del servicio |
| GET | `/api/reportes` | `ReporteController::obtenerReporteAdministrativo` | Sesión + permiso del servicio |
