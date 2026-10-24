# Inventario de cambios

Comparación con el ZIP original adjunto: **66 archivos modificados, 34 archivos nuevos y 0 eliminados**. Los 65 archivos de frontend y las cinco migraciones son idénticos.

## Cambios de arquitectura

Los principales movimientos están explicados en [COMPARACION-CADEFAR.md](COMPARACION-CADEFAR.md). Los archivos indicados como formato conservan la misma secuencia de tokens PHP al excluir espacios y comentarios. No se debe confundir el tamaño del diff de formato con cambios de comportamiento.

## Archivos modificados

| Archivo | Cambio |
| --- | --- |
| `ACTUALIZAR-ESTA-VERSION.md` | Actualización de esta entrega completa, sin nueva migración; instrucciones previas preservadas como historial. |
| `README.md` | Enlaza guía y verificación actuales; comandos adaptados a C:\php85. |
| `backend/scripts/verificar-entorno.php` | Incluye ctype en extensiones requeridas, usada por validaciones existentes. |
| `backend/src/app.php` | Conecta con EnrutadorApi y FabricaControladores; conserva middlewares y manejo de errores. |
| `backend/src/bootstrap.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/business/services/AutenticacionService.php` | Encapsula acceso a sesión a través de SesionUsuario; conserva autenticación y límites de intentos. |
| `backend/src/business/services/CitaService.php` | Decide alcance por propietario, utiliza repositorios por entidad, divide preparación de reserva y reutiliza transacciones. |
| `backend/src/business/services/EspecialidadService.php` | Reutiliza transacciones conservando bloqueo y reglas de médicos activos. |
| `backend/src/business/services/HorarioMedicoService.php` | Genera intervalos en negocio; coordina horario y turnos; separa procesamiento de período y fecha. |
| `backend/src/business/services/MedicoService.php` | Dependencias uniformes y transacciones compartidas; mantiene especialidades, duplicados y citas vigentes. |
| `backend/src/business/services/PacienteService.php` | Centraliza dependencias y transacciones; delega filtros en PacienteValidator. |
| `backend/src/business/services/ReporteService.php` | Snapshot de solo lectura con Transaccion; catálogo de médicos desde MedicoRepository. |
| `backend/src/business/services/UsuarioService.php` | Reutiliza transacciones conservando bloqueo del último administrador y cambios de estado. |
| `backend/src/business/validators/AgendaValidator.php` | Fachada compatible que delega en validadores por módulo y comunes. |
| `backend/src/business/validators/EspecialidadValidator.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/business/validators/MedicoValidator.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/business/validators/PacienteValidator.php` | Incorpora validarFiltrosPacientes; separa esa validación del servicio. |
| `backend/src/business/validators/ReporteValidator.php` | Usa validadores comunes y CitaValidator; conserva entradas y errores. |
| `backend/src/business/validators/UsuarioValidator.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/business/validators/ValidarCamposFormulario.php` | Reúne validación común de identificadores y fechas. |
| `backend/src/data/config/ConexionPostgresql.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/Cita.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/Especialidad.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/HistoriaClinica.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/HorarioMedico.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/Medico.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/Paciente.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/Rol.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/models/Usuario.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/repositories/CitaRepository.php` | Recibe alcance como filtros; mueve consultas de pacientes y catálogo médico a sus entidades. |
| `backend/src/data/repositories/EspecialidadRepository.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/repositories/HistoriaClinicaRepository.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/repositories/HorarioMedicoRepository.php` | Solo persistencia de bloques; sin cálculo de intervalos ni decisión por rol. |
| `backend/src/data/repositories/MedicoRepository.php` | Recibe consultas de disponibilidad y catálogo médico; filtra por ID recibido. |
| `backend/src/data/repositories/PacienteRepository.php` | Recibe bloqueo del paciente y búsqueda de su ID para reservas. |
| `backend/src/data/repositories/ReporteRepository.php` | Orden de métodos, formato e importaciones; comportamiento público conservado. |
| `backend/src/data/repositories/RolRepository.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/repositories/UsuarioRepository.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/data/seeders/DatosInicialesSeeder.php` | Orden de métodos, formato e importaciones; comportamiento público conservado. |
| `backend/src/presentation/controllers/AutenticacionController.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/controllers/CitaController.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/controllers/EspecialidadController.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/controllers/HorarioMedicoController.php` | Se concentra en programación y consulta de horarios; disponibilidad va a su propio controlador. |
| `backend/src/presentation/controllers/MedicoController.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/controllers/PacienteController.php` | Formato e importación explícita de Usuario; acciones y contrato conservados. |
| `backend/src/presentation/controllers/ReporteController.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/controllers/UsuarioController.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/middlewares/VerificarOrigenSolicitud.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/middlewares/VerificarRolUsuario.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/middlewares/VerificarSesionUsuario.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/middlewares/VerificarTokenCsrf.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/presentation/routes/RutasAgenda.php` | Agrega definiciones de horarios, disponibilidad, citas y reportes. |
| `backend/src/presentation/routes/RutasApi.php` | Agregador declarativo de rutas; transporte y composición fuera de Routes. |
| `backend/src/server.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/shared/config/CargarVariablesEntorno.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/shared/config/ConfiguracionAgenda.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/shared/constants/PermisosSistema.php` | Reutiliza constantes de RolesSistema sin cambiar la matriz. |
| `backend/src/shared/constants/RolesSistema.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/shared/errors/ErrorAplicacion.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/shared/utils/RespuestaJson.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `backend/src/shared/utils/SesionUsuario.php` | Agrega acceso explícito al almacenamiento de autenticación y contador de intentos. |
| `backend/src/shared/utils/SolicitudHttp.php` | Formato PHP uniforme; sin cambios de tokens ejecutables. |
| `docs/ai/arquitectura-y-nombres.md` | Documentación y referencia a la arquitectura/verificación vigente. |
| `docs/ai/contexto-proyecto.md` | Documentación y referencia a la arquitectura/verificación vigente. |
| `docs/ai/programacion-mensual.md` | Documentación y referencia a la arquitectura/verificación vigente. |
| `docs/ai/pruebas.md` | Documentación y referencia a la arquitectura/verificación vigente. |

## Archivos nuevos

| Archivo | Propósito |
| --- | --- |
| `.editorconfig` | Convenciones de edición para el código. |
| `GUIA-ARQUITECTURA-Y-DEFENSA.md` | Documentación de entrega, estructura, resultados o guía para defensa. |
| `backend/src/business/services/DisponibilidadService.php` | Consulta de disponibilidad y configuración con sus permisos. |
| `backend/src/business/validators/CitaValidator.php` | Validación específica del módulo. |
| `backend/src/business/validators/HorarioMedicoValidator.php` | Validación específica del módulo. |
| `backend/src/data/config/Transaccion.php` | Infraestructura reutilizable de aislamiento, commit y rollback. |
| `backend/src/data/repositories/TurnoRepository.php` | SQL específico de turnos; sin generación de intervalos. |
| `backend/src/presentation/controllers/DisponibilidadController.php` | Acciones HTTP breves para disponibilidad, estado o CSRF. |
| `backend/src/presentation/controllers/EstadoController.php` | Acciones HTTP breves para disponibilidad, estado o CSRF. |
| `backend/src/presentation/controllers/TokenCsrfController.php` | Acciones HTTP breves para disponibilidad, estado o CSRF. |
| `backend/src/presentation/http/EnrutadorApi.php` | Despacho HTTP y aplicación de middleware. |
| `backend/src/presentation/http/FabricaControladores.php` | Composición de controladores y conexión diferida. |
| `backend/src/presentation/http/RutaHttp.php` | Descriptor de método, ruta, controlador, permisos y argumentos. |
| `backend/src/presentation/routes/RutasAutenticacion.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasCitas.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasDisponibilidad.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasEspecialidades.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasHorarios.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasMedicos.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasPacientes.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasReportes.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasSistema.php` | Definiciones HTTP del módulo. |
| `backend/src/presentation/routes/RutasUsuarios.php` | Definiciones HTTP del módulo. |
| `backend/tests/ejecutar-pruebas.php` | Pruebas nuevas o ejecutor reproducible; no es dependencia del sistema. |
| `backend/tests/http/escenario_http.py` | Pruebas nuevas o ejecutor reproducible; no es dependencia del sistema. |
| `backend/tests/test_arquitectura.php` | Pruebas nuevas o ejecutor reproducible; no es dependencia del sistema. |
| `backend/tests/test_contrato_http.py` | Pruebas nuevas o ejecutor reproducible; no es dependencia del sistema. |
| `backend/tests/test_transacciones_capas.php` | Pruebas nuevas o ejecutor reproducible; no es dependencia del sistema. |
| `docs/refactorizacion/ACTUALIZACION-1.4-HISTORICA.md` | Documentación de entrega, estructura, resultados o guía para defensa. |
| `docs/refactorizacion/ARCHIVOS-MODIFICADOS.md` | Documentación de entrega, estructura, resultados o guía para defensa. |
| `docs/refactorizacion/COMPARACION-CADEFAR.md` | Documentación de entrega, estructura, resultados o guía para defensa. |
| `docs/refactorizacion/ENDPOINTS.md` | Documentación de entrega, estructura, resultados o guía para defensa. |
| `docs/refactorizacion/ESTRUCTURA-CARPETAS.md` | Documentación de entrega, estructura, resultados o guía para defensa. |
| `docs/refactorizacion/VERIFICACION.md` | Documentación de entrega, estructura, resultados o guía para defensa. |

## Compatibilidad y archivos conservados

No se borró ningún archivo original. Se cambiaron firmas internas de repositorios después de actualizar sus consumidores. Se conservaron las fachadas PHP necesarias para las pruebas existentes. Los 48 endpoints están documentados en [ENDPOINTS.md](ENDPOINTS.md).
