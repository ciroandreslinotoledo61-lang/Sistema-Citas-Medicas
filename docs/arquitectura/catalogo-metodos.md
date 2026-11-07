# Catálogo histórico de clases y métodos — versión 1.5

Conservado como material académico de la versión 1.5, no como catálogo exhaustivo ni contrato de firmas de la versión actual. Puede diferir en constructores y omitir módulos posteriores; las firmas vigentes están en el código. El recorrido actualizado está en [la guía de defensa](GUIA-ARQUITECTURA-Y-DEFENSA.md) y las rutas en [el contrato verificable](../refactorizacion/ENDPOINTS.md). `public` expresa una operación expuesta por la clase; `private`, un auxiliar.

## AutenticacionService

[backend/src/business/services/AutenticacionService.php](../../backend/src/business/services/AutenticacionService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(private readonly UsuarioRepository $usuarioRepository,private readonly BitacoraService $bitacoraService)`
- `public function iniciarSesionUsuario(array $datos): Usuario`
- `public function obtenerUsuarioAutenticado(): Usuario`
- `public function cerrarSesionUsuario(): void`

## BitacoraService

[backend/src/business/services/BitacoraService.php](../../backend/src/business/services/BitacoraService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function establecerUsuarioResponsable(?Usuario $usuario):void`
- `public function registrarEventoAutenticacion(?Usuario $usuario,string $accion,string $resultado):void`
- `public function registrarLecturaClinica(Usuario $usuario,int $idPaciente):void`
- `public function listarBitacora(Usuario $usuario,array $datos):array`
- `public function obtenerFiltrosBitacora(Usuario $usuario):array`

## CitaService

[backend/src/business/services/CitaService.php](../../backend/src/business/services/CitaService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql, private readonly ?\DateTimeImmutable $momentoReferencia = null)`
- `public function listarCitas(Usuario $usuario, array $parametros): array`
- `public function obtenerCita(Usuario $usuario, int $idCita): array`
- `public function reservarCita(Usuario $usuario, array $datos): array`
- `public function cancelarCita(Usuario $usuario, int $idCita, array $datos): array`
- `public function reprogramarCita(Usuario $usuario, int $idCita, array $datos): array`
- `public function registrarAsistencia(Usuario $usuario, int $idCita, array $datos): array`
- `public function marcarCitaAtendida(Usuario $usuario, int $idCita): array`
- `public function listarMedicosParaAgenda(Usuario $usuario): array`
- `public function marcarCitaInasistencia(Usuario $usuario, int $idCita): array`
- `public function listarCitasActivasPaciente(Usuario $usuario, array $parametros): array`
- `private function obtenerAhora(): \DateTimeImmutable`
- `private function guardarAsistenciaCita(Usuario $usuario, int $idCita, string $asistencia): array`
- `private function exigirCitaAutorizada(Usuario $usuario, int $idCita, bool $bloquear = false): Cita`
- `private function exigirCitaModificable(Cita $cita): void`
- `private function prepararReserva(int $idPaciente, int $idTurno, int $idCitaExcluida = 0): array`
- `private function bloquearMedicoDisponible(int $idMedico): Medico`
- `private function exigirPacienteDisponible(int $idPaciente, int $idCitaExcluida): void`
- `private function exigirTurnoVigente(array $turno): void`
- `private function exigirReservaSinCruces(int $idPaciente, int $idMedico, int $idTurno, array $turno, int $idCitaExcluida): void`
- `private function prepararDatosCita(Cita $cita, Usuario $usuario): array`
- `private function ejecutarTransaccion(callable $operacion): array`

## ConsultaService

[backend/src/business/services/ConsultaService.php](../../backend/src/business/services/ConsultaService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql,private readonly ?\DateTimeImmutable $momentoReferencia=null)`
- `public function registrarConsulta(Usuario $usuario,array $datos):array`
- `public function actualizarConsulta(Usuario $usuario,int $idConsulta,array $datos):array`
- `public function obtenerConsulta(Usuario $usuario,int $idConsulta,bool $registrarLectura=true):array`
- `public function listarConsultasMedico(Usuario $usuario,array $datos):array`
- `public function obtenerHistorial(Usuario $usuario,?int $idPaciente,array $datos):array`
- `private function prepararListado(array $filas,int $pagina):array`
- `private function ejecutarTransaccion(callable $operacion):array`

## DisponibilidadService

[backend/src/business/services/DisponibilidadService.php](../../backend/src/business/services/DisponibilidadService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexion, private readonly ?\DateTimeImmutable $momentoReferencia = null)`
- `public function obtenerConfiguracionAgenda(Usuario $usuario): array`
- `public function listarMedicosDisponibles(Usuario $usuario): array`
- `public function listarTurnosDisponibles(Usuario $usuario, array $parametros): array`

## EspecialidadService

[backend/src/business/services/EspecialidadService.php](../../backend/src/business/services/EspecialidadService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function listarEspecialidades(array $parametros): array`
- `public function listarEspecialidadesActivas(): array`
- `public function obtenerEspecialidadPorId(int $idEspecialidad): Especialidad`
- `public function registrarEspecialidad(array $datos): Especialidad`
- `public function actualizarDatosEspecialidad(int $idEspecialidad, array $datos): Especialidad`
- `public function cambiarEstadoEspecialidad(int $idEspecialidad, array $datos): Especialidad`
- `private function exigirNombreEspecialidadDisponible(string $nombreEspecialidad, int $idEspecialidadExcluida = 0): void`

## HistoriaClinicaService

[backend/src/business/services/HistoriaClinicaService.php](../../backend/src/business/services/HistoriaClinicaService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function obtenerIdPacientePropio(Usuario $usuario):int`
- `public function exigirHistoriaAutorizada(Usuario $usuario,int $idPaciente):HistoriaClinica`
- `public function actualizarHistoria(Usuario $usuario,int $idPaciente,array $datos):array`

## HorarioMedicoService

[backend/src/business/services/HorarioMedicoService.php](../../backend/src/business/services/HorarioMedicoService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql, private readonly ?\DateTimeImmutable $momentoReferencia = null)`
- `public function listarHorarios(Usuario $usuario, array $parametros): array`
- `public function registrarHorario(Usuario $usuario, array $datos): array`
- `public function programarHorariosPorPeriodo(Usuario $usuario, array $datos): array`
- `public function listarMedicosDisponibles(Usuario $usuario): array`
- `public function listarTurnosDisponibles(Usuario $usuario, array $parametros): array`
- `private function obtenerAhora(): \DateTimeImmutable`
- `private function bloquearMedicoDisponible(int $idMedico): void`
- `private function crearHorarioConTurnos(array $datos): array`
- `private function generarIntervalosTurnos(array $datos): array`
- `private function procesarPeriodo(array $datos): array`
- `private function procesarFecha(array $bloque): array`

## MedicoService

[backend/src/business/services/MedicoService.php](../../backend/src/business/services/MedicoService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function listarMedicos(array $parametros): array`
- `public function obtenerMedicoPorId(int $idMedico): Medico`
- `public function registrarMedicoConUsuario(array $datos): Medico`
- `public function actualizarDatosMedico(int $idMedico, array $datos): Medico`
- `public function cambiarEstadoMedico(int $idMedico, array $datos): Medico`
- `private function exigirMedicoSinCitasVigentes(int $idMedico): void`
- `private function exigirEspecialidadActiva(int $idEspecialidad): void`
- `private function exigirDatosUnicosMedico(array $datosMedico, int $idMedicoExcluido = 0, int $idUsuarioExcluido = 0): void`

## PacienteService

[backend/src/business/services/PacienteService.php](../../backend/src/business/services/PacienteService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function registrarPacienteConHistoriaClinica(array $datos): Paciente`
- `public function listarPacientes(array $parametros): array`
- `public function obtenerPacientePorId(int $idPaciente): Paciente`
- `public function obtenerPacientePorUsuario(int $idUsuario): Paciente`
- `public function actualizarContactoPaciente(int $idPaciente, array $datos): Paciente`
- `public function actualizarDatosPaciente(Usuario $usuario, int $idPaciente, array $datos): Paciente`
- `public function cambiarEstadoPaciente(Usuario $usuario, int $idPaciente, array $datos): Paciente`

## ReporteService

[backend/src/business/services/ReporteService.php](../../backend/src/business/services/ReporteService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function obtenerReporteAdministrativo(Usuario $usuario, array $parametros): array`

## UsuarioService

[backend/src/business/services/UsuarioService.php](../../backend/src/business/services/UsuarioService.php)

Coordina reglas, autorizacion por registro y operaciones del caso de uso.

- `public function __construct(\PDO $conexionPostgresql)`
- `public function listarUsuarios(array $parametros): array`
- `public function obtenerUsuarioPorId(int $idUsuario): Usuario`
- `public function registrarUsuarioAdministrativo(array $datos): Usuario`
- `public function actualizarDatosUsuario(int $idUsuario, array $datos): Usuario`
- `public function cambiarEstadoUsuario(int $idUsuario, array $datos, Usuario $usuarioAutenticado): Usuario`
- `public function listarRolesSistema(): array`

## AgendaValidator

[backend/src/business/validators/AgendaValidator.php](../../backend/src/business/validators/AgendaValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarIdentificador(array $datos, string $campo): int`
- `public static function validarFecha(mixed $valor, bool $opcional = false): string`
- `public static function validarHorario(array $datos, \DateTimeImmutable $ahora): array`
- `public static function validarFiltrosCitas(array $parametros): array`
- `public static function validarProgramacionPeriodo(array $datos, \DateTimeImmutable $ahora): array`

## BitacoraValidator

[backend/src/business/validators/BitacoraValidator.php](../../backend/src/business/validators/BitacoraValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarFiltrosBitacora(array $datos): array`

## CitaValidator

[backend/src/business/validators/CitaValidator.php](../../backend/src/business/validators/CitaValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarFiltrosCitas(array $parametros): array`
- `public static function validarAsistencia(array $datos): string`

## ConsultaValidator

[backend/src/business/validators/ConsultaValidator.php](../../backend/src/business/validators/ConsultaValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarTextoClinico(array $datos,string $campo,int $maximo,bool $obligatorio=true):string`
- `public static function validarConsulta(array $datos,bool $edicion=false):array`
- `public static function validarHistoria(array $d):array`
- `public static function validarFiltrosConsultas(array $d):array`

## EspecialidadValidator

[backend/src/business/validators/EspecialidadValidator.php](../../backend/src/business/validators/EspecialidadValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarDatosEspecialidad(array $datos): array`
- `public static function validarEstadoEspecialidad(array $datos): bool`

## HorarioMedicoValidator

[backend/src/business/validators/HorarioMedicoValidator.php](../../backend/src/business/validators/HorarioMedicoValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarHorario(array $datos, \DateTimeImmutable $ahora): array`
- `public static function validarProgramacionPeriodo(array $datos, \DateTimeImmutable $ahora): array`
- `public static function validarFiltrosHorarios(array $parametros): array`

## MedicoValidator

[backend/src/business/validators/MedicoValidator.php](../../backend/src/business/validators/MedicoValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarDatosActualizacionMedico(array $datos): array`
- `public static function validarDatosRegistroMedico(array $datos): array`
- `public static function validarEstadoMedico(array $datos): bool`

## PacienteValidator

[backend/src/business/validators/PacienteValidator.php](../../backend/src/business/validators/PacienteValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarDatosActualizacionPaciente(array $datos): array`
- `public static function validarDatosContactoPaciente(array $datos): array`
- `public static function validarDatosRegistroPaciente(array $datos): array`
- `public static function validarEstadoPaciente(array $datos): bool`
- `public static function validarFiltrosPacientes(array $parametros): array`

## ReporteValidator

[backend/src/business/validators/ReporteValidator.php](../../backend/src/business/validators/ReporteValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarFiltrosReporte(array $parametros): array`

## UsuarioValidator

[backend/src/business/validators/UsuarioValidator.php](../../backend/src/business/validators/UsuarioValidator.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarDatosActualizacionUsuario(array $datos): array`
- `public static function validarDatosRegistroUsuario(array $datos): array`
- `public static function validarDatosInicioSesion(array $datos): array`
- `public static function validarEstadoUsuario(array $datos): bool`

## ValidarCamposFormulario

[backend/src/business/validators/ValidarCamposFormulario.php](../../backend/src/business/validators/ValidarCamposFormulario.php)

Comprueba estructura, tipos, formatos y rangos de la entrada.

- `public static function validarTexto(array $datos, string $nombreCampo, string $etiquetaCampo, int $longitudMaxima, bool $obligatorio = true): string`
- `public static function validarCorreoUsuario(array $datos): string`
- `public static function validarContrasenaRegistro(array $datos): string`
- `public static function validarTelefono(array $datos, string $nombreCampo, bool $obligatorio): string`
- `public static function validarParametrosBusqueda(array $parametros): array`
- `public static function validarIdentificador(array $datos, string $campo): int`
- `public static function validarFecha(mixed $valor, bool $opcional = false): string`

## ConexionPostgresql

[backend/src/data/config/ConexionPostgresql.php](../../backend/src/data/config/ConexionPostgresql.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function obtenerConexionPostgresql(): \PDO`

## Transaccion

[backend/src/data/config/Transaccion.php](../../backend/src/data/config/Transaccion.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public function __construct(private readonly \PDO $conexion)`
- `public function ejecutar(callable $operacion, string $aislamiento = self::PREDETERMINADA): mixed`

## Cita

[backend/src/data/models/Cita.php](../../backend/src/data/models/Cita.php)

Representa datos y su proyeccion autorizada.

- `public function __construct(private readonly array $registro)`
- `public function perteneceAlPaciente(int $idUsuario): bool`
- `public function perteneceAlMedico(int $idUsuario): bool`
- `public function obtenerDatosPublicosCita(): array`

## Consulta

[backend/src/data/models/Consulta.php](../../backend/src/data/models/Consulta.php)

Representa datos y su proyeccion autorizada.

- `public function __construct(private readonly array $registro)`
- `public function obtenerDatosConsulta():array`

## Especialidad

[backend/src/data/models/Especialidad.php](../../backend/src/data/models/Especialidad.php)

Representa datos y su proyeccion autorizada.

- `public function __construct( public readonly int $idEspecialidad, public readonly string $nombreEspecialidad, public readonly string $descripcionEspecialidad, public readonly bool $especialidadActiva, public readonly int $cantidadMedicosActivos )`
- `public static function crearEspecialidadDesdeRegistro(array $registroEspecialidad): self`
- `public function obtenerDatosPublicosEspecialidad(): array`

## EventoBitacora

[backend/src/data/models/EventoBitacora.php](../../backend/src/data/models/EventoBitacora.php)

Representa datos y su proyeccion autorizada.

- `public function __construct(private readonly array $r)`
- `public function obtenerDatosEvento(): array`

## HistoriaClinica

[backend/src/data/models/HistoriaClinica.php](../../backend/src/data/models/HistoriaClinica.php)

Representa datos y su proyeccion autorizada.

- `public function __construct( public readonly int $idHistoriaClinica, public readonly int $idPaciente, private readonly array $registro=[] )`
- `public function obtenerDatosHistoria():array`

## HorarioMedico

[backend/src/data/models/HorarioMedico.php](../../backend/src/data/models/HorarioMedico.php)

Representa datos y su proyeccion autorizada.

- `public function __construct(private readonly array $registro)`
- `public function obtenerDatosPublicosHorario(): array`

## Medico

[backend/src/data/models/Medico.php](../../backend/src/data/models/Medico.php)

Representa datos y su proyeccion autorizada.

- `public function __construct( public readonly int $idMedico, public readonly int $idUsuario, public readonly int $idEspecialidad, public readonly string $nombresUsuario, public readonly string $apellidosUsuario, public readonly string $correoUsuario, public readonly string $matriculaProfesional, public readonly string $nombreEspecialidad, public readonly bool $especialidadActiva, public readonly bool $medicoActivo, public readonly string $telefonoMedico = '' )`
- `public static function crearMedicoDesdeRegistro(array $registroMedico): self`
- `public function obtenerDatosPublicosMedico(): array`

## Paciente

[backend/src/data/models/Paciente.php](../../backend/src/data/models/Paciente.php)

Representa datos y su proyeccion autorizada.

- `public function __construct(private readonly array $registroPaciente)`
- `public function obtenerDatosPublicosPaciente(): array`

## Rol

[backend/src/data/models/Rol.php](../../backend/src/data/models/Rol.php)

Representa datos y su proyeccion autorizada.

- `public function __construct( public readonly int $idRol, public readonly string $codigoRol, public readonly string $nombreRol )`
- `public static function crearRolDesdeRegistro(array $registroRol): self`
- `public function obtenerDatosPublicosRol(): array`

## Tratamiento

[backend/src/data/models/Tratamiento.php](../../backend/src/data/models/Tratamiento.php)

Representa datos y su proyeccion autorizada.

- `public function __construct(private readonly array $registro)`
- `public function obtenerDatosTratamiento():array`

## Usuario

[backend/src/data/models/Usuario.php](../../backend/src/data/models/Usuario.php)

Representa datos y su proyeccion autorizada.

- `public function __construct( public readonly int $idUsuario, public readonly string $nombresUsuario, public readonly string $apellidosUsuario, public readonly string $correoUsuario, private readonly string $contrasenaHash, public readonly bool $usuarioActivo, public readonly int $versionCredenciales, public readonly int $idRol, public readonly string $codigoRol, public readonly string $nombreRol )`
- `public static function crearUsuarioDesdeRegistro(array $registroUsuario): self`
- `public function verificarContrasenaUsuario(string $contrasena): bool`
- `public function obtenerDatosPublicosUsuario(): array`

## BitacoraRepository

[backend/src/data/repositories/BitacoraRepository.php](../../backend/src/data/repositories/BitacoraRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function establecerUsuarioResponsable(?int $idUsuario):void`
- `public function registrarEvento(?int $idUsuario,string $modulo,string $accion,?int $idRegistro,string $resultado,string $descripcion):void`
- `public function listarEventos(array $f):array`
- `public function obtenerCatalogosFiltros():array`

## CitaRepository

[backend/src/data/repositories/CitaRepository.php](../../backend/src/data/repositories/CitaRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function obtenerCitaPorId(int $idCita, bool $bloquear = false): ?Cita`
- `public function listarCitas(array $filtros, string $ahora, ?int $idUsuarioPaciente = null, ?int $idUsuarioMedico = null): array`
- `public function turnoEstaOcupado(int $idTurno): bool`
- `public function pacienteTieneCruce(int $idPaciente, string $inicio, string $fin, int $idCitaExcluida = 0): bool`
- `public function medicoTieneCitasVigentes(int $idMedico, string $ahora): bool`
- `public function registrarCita(int $idPaciente, int $idTurno, string $motivo, int $idUsuario, ?int $idCitaOrigen = null, ?int $idEspecialidad = null): int`
- `public function actualizarEstadoCita(int $idCita, string $estado, int $idUsuario, string $motivoCancelacion = ''): void`
- `public function registrarAsistencia(int $idCita, string $asistencia, string $estado, int $idUsuario): void`
- `public function medicoTieneCruce(int $idMedico, string $inicio, string $fin, int $idCitaExcluida = 0): bool`
- `public function pacienteTieneCitaActiva(int $idPaciente, int $idCitaExcluida = 0): bool`
- `public function listarCitasActivasPaciente(int $idPaciente): array`
- `public function obtenerResultadoReprogramacion(int $idCita): ?array`

## ConsultaRepository

[backend/src/data/repositories/ConsultaRepository.php](../../backend/src/data/repositories/ConsultaRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function obtenerConsultaPorId(int $idConsulta,bool $bloquear=false):?Consulta`
- `public function obtenerIdConsultaPorCita(int $idCita):?int`
- `public function listarConsultas(array $f,?int $idUsuarioMedico=null,?int $idPaciente=null):array`
- `public function registrarConsulta(int $idCita,int $idHistoria,array $d,int $idUsuario):int`
- `public function actualizarConsulta(int $idConsulta,array $d,int $idUsuario):bool`

## EspecialidadRepository

[backend/src/data/repositories/EspecialidadRepository.php](../../backend/src/data/repositories/EspecialidadRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function listarEspecialidadesPorBusqueda(string $busqueda, int $pagina): array`
- `public function listarEspecialidadesActivas(): array`
- `public function obtenerEspecialidadPorId(int $idEspecialidad, bool $bloquearRegistro = false): ?Especialidad`
- `public function existeEspecialidadConNombre(string $nombreEspecialidad, int $idEspecialidadExcluida = 0): bool`
- `public function registrarEspecialidad(array $datosEspecialidad): int`
- `public function actualizarDatosEspecialidad(int $idEspecialidad, array $datosEspecialidad): void`
- `public function cambiarEstadoEspecialidad(int $idEspecialidad, bool $especialidadActiva): void`
- `public function contarMedicosActivosPorEspecialidad(int $idEspecialidad): int`

## HistoriaClinicaRepository

[backend/src/data/repositories/HistoriaClinicaRepository.php](../../backend/src/data/repositories/HistoriaClinicaRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function crearHistoriaClinicaParaPaciente(int $idPaciente): HistoriaClinica`
- `public function obtenerHistoriaPorPaciente(int $idPaciente):?HistoriaClinica`
- `public function medicoTienePacienteAsignado(int $idUsuarioMedico,int $idPaciente):bool`
- `public function actualizarAntecedentes(int $idPaciente,array $d):bool`

## HorarioMedicoRepository

[backend/src/data/repositories/HorarioMedicoRepository.php](../../backend/src/data/repositories/HorarioMedicoRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function listarHorarios(array $filtros, ?int $idUsuarioMedico = null): array`
- `public function existeCruceHorario(array $datos): bool`
- `public function registrarHorario(array $datos): int`
- `public function existeHorarioEquivalente(array $datos): bool`

## MedicoRepository

[backend/src/data/repositories/MedicoRepository.php](../../backend/src/data/repositories/MedicoRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function listarMedicosPorBusqueda(string $busqueda, int $pagina): array`
- `public function obtenerMedicoPorId(int $idMedico, bool $bloquearRegistro = false): ?Medico`
- `public function existeMedicoConMatricula(string $matriculaProfesional, int $idMedicoExcluido = 0): bool`
- `public function registrarPerfilMedico(int $idUsuario, array $datosMedico): int`
- `public function actualizarPerfilMedico(int $idMedico, array $datosMedico): void`
- `public function listarMedicosDisponibles(): array`
- `public function listarMedicosParaAgenda(?int $idUsuarioMedico = null): array`

## PacienteRepository

[backend/src/data/repositories/PacienteRepository.php](../../backend/src/data/repositories/PacienteRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function registrarPaciente(array $datosPaciente, int $idUsuario): int`
- `public function obtenerPacientePorId(int $idPaciente, bool $bloquear = false): ?Paciente`
- `public function obtenerPacientePorIdUsuario(int $idUsuario): ?Paciente`
- `public function listarPacientesPorBusqueda(string $busqueda, int $pagina, string $estado = ''): array`
- `public function actualizarContactoPaciente(int $idPaciente, array $datosPaciente): void`
- `public function actualizarDatosPaciente(int $idPaciente, array $datos): void`
- `public function bloquearPaciente(int $idPaciente): ?array`
- `public function obtenerIdPacientePorUsuario(int $idUsuario): ?int`

## ReporteRepository

[backend/src/data/repositories/ReporteRepository.php](../../backend/src/data/repositories/ReporteRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function obtenerResumenCitas(array $filtros): array`
- `public function obtenerCitasAgrupadas(array $filtros, string $grupo): array`
- `public function listarCitasReporte(array $filtros): array`
- `public function contarPacientesRegistrados(array $filtros): int`
- `public function listarPacientesRegistrados(array $filtros): array`
- `public function listarEspecialidadesReporte(): array`
- `private function construirFiltrosCitas(array $filtros): array`

## RolRepository

[backend/src/data/repositories/RolRepository.php](../../backend/src/data/repositories/RolRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function listarRolesSistema(): array`
- `public function obtenerRolPorCodigo(string $codigoRol, bool $bloquearRegistro = false): ?Rol`

## TratamientoRepository

[backend/src/data/repositories/TratamientoRepository.php](../../backend/src/data/repositories/TratamientoRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function listarTratamientosConsulta(int $idConsulta):array`
- `public function guardarTratamientosConsulta(int $idConsulta,array $tratamientos):void`

## TurnoRepository

[backend/src/data/repositories/TurnoRepository.php](../../backend/src/data/repositories/TurnoRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function registrarTurnos(int $idHorario, array $turnos): void`
- `public function listarTurnosDisponibles(int $idMedico, string $fecha, string $ahora): array`
- `public function obtenerTurnoPorId(int $idTurno, bool $bloquear = false): ?array`

## UsuarioRepository

[backend/src/data/repositories/UsuarioRepository.php](../../backend/src/data/repositories/UsuarioRepository.php)

Encapsula SQL preparado, filtros y persistencia.

- `public function __construct(private readonly \PDO $conexionPostgresql)`
- `public function obtenerUsuarioPorId(int $idUsuario, bool $bloquearRegistro = false): ?Usuario`
- `public function obtenerUsuarioPorCorreo(string $correoUsuario): ?Usuario`
- `public function listarUsuariosPorBusqueda(string $busqueda, int $pagina): array`
- `public function registrarUsuario(array $datosUsuario, int $idRol, string $contrasenaHash): int`
- `public function actualizarDatosUsuario(int $idUsuario, array $datosUsuario): void`
- `public function cambiarEstadoUsuario(int $idUsuario, bool $usuarioActivo): void`
- `public function contarAdministradoresActivos(): int`

## DatosInicialesSeeder

[backend/src/data/seeders/DatosInicialesSeeder.php](../../backend/src/data/seeders/DatosInicialesSeeder.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function cargarDatosIniciales(\PDO $conexionPostgresql, string $contrasenaPrueba): void`

## AutenticacionController

[backend/src/presentation/controllers/AutenticacionController.php](../../backend/src/presentation/controllers/AutenticacionController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly AutenticacionService $autenticacionService)`
- `public function iniciarSesionUsuario(): never`
- `public function obtenerSesionUsuario(): never`
- `public function cerrarSesionUsuario(): never`

## BitacoraController

[backend/src/presentation/controllers/BitacoraController.php](../../backend/src/presentation/controllers/BitacoraController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly BitacoraService $bitacoraService)`
- `public function listarBitacora(Usuario $usuario):never`
- `public function obtenerFiltrosBitacora(Usuario $usuario):never`

## CitaController

[backend/src/presentation/controllers/CitaController.php](../../backend/src/presentation/controllers/CitaController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly CitaService $citaService)`
- `public function listarCitas(Usuario $usuario): never`
- `public function obtenerCita(Usuario $usuario, int $idCita): never`
- `public function reservarCita(Usuario $usuario): never`
- `public function cancelarCita(Usuario $usuario, int $idCita): never`
- `public function reprogramarCita(Usuario $usuario, int $idCita): never`
- `public function registrarAsistencia(Usuario $usuario, int $idCita): never`
- `public function marcarCitaAtendida(Usuario $usuario, int $idCita): never`
- `public function listarMedicosParaAgenda(Usuario $usuario): never`
- `public function marcarCitaInasistencia(Usuario $usuario, int $idCita): never`
- `public function listarCitasActivasPaciente(Usuario $usuario): never`

## ConsultaController

[backend/src/presentation/controllers/ConsultaController.php](../../backend/src/presentation/controllers/ConsultaController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly ConsultaService $consultas,private readonly HistoriaClinicaService $historias)`
- `public function listarConsultasMedico(Usuario $usuario):never`
- `public function registrarConsulta(Usuario $usuario):never`
- `public function obtenerConsulta(Usuario $usuario,int $idConsulta):never`
- `public function actualizarConsulta(Usuario $usuario,int $idConsulta):never`
- `public function obtenerMiHistorial(Usuario $usuario):never`
- `public function obtenerHistorialPaciente(Usuario $usuario,int $idPaciente):never`
- `public function actualizarHistoria(Usuario $usuario,int $idPaciente):never`

## DisponibilidadController

[backend/src/presentation/controllers/DisponibilidadController.php](../../backend/src/presentation/controllers/DisponibilidadController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly DisponibilidadService $disponibilidadService)`
- `public function obtenerConfiguracionAgenda(Usuario $usuario): never`
- `public function listarMedicosDisponibles(Usuario $usuario): never`
- `public function listarTurnosDisponibles(Usuario $usuario): never`

## EspecialidadController

[backend/src/presentation/controllers/EspecialidadController.php](../../backend/src/presentation/controllers/EspecialidadController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly EspecialidadService $especialidadService)`
- `public function listarEspecialidades(): never`
- `public function listarEspecialidadesActivas(): never`
- `public function obtenerEspecialidadPorId(int $idEspecialidad): never`
- `public function registrarEspecialidad(): never`
- `public function actualizarDatosEspecialidad(int $idEspecialidad): never`
- `public function cambiarEstadoEspecialidad(int $idEspecialidad): never`

## EstadoController

[backend/src/presentation/controllers/EstadoController.php](../../backend/src/presentation/controllers/EstadoController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function obtenerEstado(): never`

## HorarioMedicoController

[backend/src/presentation/controllers/HorarioMedicoController.php](../../backend/src/presentation/controllers/HorarioMedicoController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly HorarioMedicoService $horarioService)`
- `public function listarHorarios(Usuario $usuario): never`
- `public function registrarHorario(Usuario $usuario): never`
- `public function programarHorariosPorPeriodo(Usuario $usuario): never`

## MedicoController

[backend/src/presentation/controllers/MedicoController.php](../../backend/src/presentation/controllers/MedicoController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly MedicoService $medicoService)`
- `public function listarMedicos(): never`
- `public function obtenerMedicoPorId(int $idMedico): never`
- `public function registrarMedicoConUsuario(): never`
- `public function actualizarDatosMedico(int $idMedico): never`
- `public function cambiarEstadoMedico(int $idMedico): never`

## PacienteController

[backend/src/presentation/controllers/PacienteController.php](../../backend/src/presentation/controllers/PacienteController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly PacienteService $pacienteService)`
- `public function registrarPacienteConHistoriaClinica(): never`
- `public function listarPacientes(): never`
- `public function obtenerPacientePorId(int $idPaciente): never`
- `public function obtenerPerfilPaciente(int $idUsuario): never`
- `public function actualizarContactoPaciente(int $idPaciente): never`
- `public function actualizarDatosPaciente(Usuario $usuario, int $idPaciente): never`
- `public function cambiarEstadoPaciente(Usuario $usuario, int $idPaciente): never`

## ReporteController

[backend/src/presentation/controllers/ReporteController.php](../../backend/src/presentation/controllers/ReporteController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly ReporteService $reporteService)`
- `public function obtenerReporteAdministrativo(Usuario $usuario): never`

## TokenCsrfController

[backend/src/presentation/controllers/TokenCsrfController.php](../../backend/src/presentation/controllers/TokenCsrfController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function obtenerToken(): never`

## UsuarioController

[backend/src/presentation/controllers/UsuarioController.php](../../backend/src/presentation/controllers/UsuarioController.php)

Lee la solicitud HTTP, delega y construye respuesta JSON.

- `public function __construct(private readonly UsuarioService $usuarioService)`
- `public function listarUsuarios(): never`
- `public function obtenerUsuarioPorId(int $idUsuario): never`
- `public function registrarUsuarioAdministrativo(): never`
- `public function actualizarDatosUsuario(int $idUsuario): never`
- `public function cambiarEstadoUsuario(int $idUsuario, Usuario $usuarioAutenticado): never`
- `public function listarRolesSistema(): never`

## EnrutadorApi

[backend/src/presentation/http/EnrutadorApi.php](../../backend/src/presentation/http/EnrutadorApi.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public function __construct(private readonly FabricaControladores $controladores)`
- `public function atenderSolicitud(): never`
- `private function autorizarRuta(RutaHttp $ruta): ?Usuario`
- `private function resolverArgumentos(RutaHttp $ruta, array $parametros, ?Usuario $usuario): array`

## FabricaControladores

[backend/src/presentation/http/FabricaControladores.php](../../backend/src/presentation/http/FabricaControladores.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public function obtenerAutenticacionService(): Services\AutenticacionService`
- `public function obtenerBitacoraService():Services\BitacoraService`
- `public function obtener(string $clase): object`
- `private function conexion(): \PDO`

## RutaHttp

[backend/src/presentation/http/RutaHttp.php](../../backend/src/presentation/http/RutaHttp.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public function __construct( public readonly string $metodo, public readonly string $ruta, public readonly string $controlador, public readonly string $accion, public readonly ?array $roles = [], public readonly array $argumentos = [] )`
- `public function coincidir(string $ruta): ?array`

## VerificarOrigenSolicitud

[backend/src/presentation/middlewares/VerificarOrigenSolicitud.php](../../backend/src/presentation/middlewares/VerificarOrigenSolicitud.php)

Comprueba una condicion transversal de acceso HTTP.

- `public static function aplicarPoliticaOrigen(): void`

## VerificarRolUsuario

[backend/src/presentation/middlewares/VerificarRolUsuario.php](../../backend/src/presentation/middlewares/VerificarRolUsuario.php)

Comprueba una condicion transversal de acceso HTTP.

- `public static function exigirRolPermitido(Usuario $usuario, array $rolesPermitidos): void`

## VerificarSesionUsuario

[backend/src/presentation/middlewares/VerificarSesionUsuario.php](../../backend/src/presentation/middlewares/VerificarSesionUsuario.php)

Comprueba una condicion transversal de acceso HTTP.

- `public static function exigirUsuarioAutenticado(AutenticacionService $autenticacionService): Usuario`

## VerificarTokenCsrf

[backend/src/presentation/middlewares/VerificarTokenCsrf.php](../../backend/src/presentation/middlewares/VerificarTokenCsrf.php)

Comprueba una condicion transversal de acceso HTTP.

- `public static function exigirTokenCsrfValido(): void`

## RutasAgenda

[backend/src/presentation/routes/RutasAgenda.php](../../backend/src/presentation/routes/RutasAgenda.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasApi

[backend/src/presentation/routes/RutasApi.php](../../backend/src/presentation/routes/RutasApi.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasAutenticacion

[backend/src/presentation/routes/RutasAutenticacion.php](../../backend/src/presentation/routes/RutasAutenticacion.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasBitacora

[backend/src/presentation/routes/RutasBitacora.php](../../backend/src/presentation/routes/RutasBitacora.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir():array`

## RutasCitas

[backend/src/presentation/routes/RutasCitas.php](../../backend/src/presentation/routes/RutasCitas.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasConsultas

[backend/src/presentation/routes/RutasConsultas.php](../../backend/src/presentation/routes/RutasConsultas.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir():array`

## RutasDisponibilidad

[backend/src/presentation/routes/RutasDisponibilidad.php](../../backend/src/presentation/routes/RutasDisponibilidad.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasEspecialidades

[backend/src/presentation/routes/RutasEspecialidades.php](../../backend/src/presentation/routes/RutasEspecialidades.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasHorarios

[backend/src/presentation/routes/RutasHorarios.php](../../backend/src/presentation/routes/RutasHorarios.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasMedicos

[backend/src/presentation/routes/RutasMedicos.php](../../backend/src/presentation/routes/RutasMedicos.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasPacientes

[backend/src/presentation/routes/RutasPacientes.php](../../backend/src/presentation/routes/RutasPacientes.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasReportes

[backend/src/presentation/routes/RutasReportes.php](../../backend/src/presentation/routes/RutasReportes.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasSistema

[backend/src/presentation/routes/RutasSistema.php](../../backend/src/presentation/routes/RutasSistema.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## RutasUsuarios

[backend/src/presentation/routes/RutasUsuarios.php](../../backend/src/presentation/routes/RutasUsuarios.php)

Declara rutas, controladores, argumentos y acceso inicial.

- `public static function definir(): array`

## CargarVariablesEntorno

[backend/src/shared/config/CargarVariablesEntorno.php](../../backend/src/shared/config/CargarVariablesEntorno.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function cargarArchivoEntorno(string $rutaArchivo): void`
- `public static function obtenerVariableEntorno(string $nombreVariable, string $valorPredeterminado = ''): string`

## ConfiguracionAgenda

[backend/src/shared/config/ConfiguracionAgenda.php](../../backend/src/shared/config/ConfiguracionAgenda.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function obtenerDuracionCitaMinutos(): int`
- `public static function obtenerHorarioClinica(): array`
- `public static function obtenerConfiguracionPublica(): array`

## PermisosSistema

[backend/src/shared/constants/PermisosSistema.php](../../backend/src/shared/constants/PermisosSistema.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function tienePermiso(string $codigoRol, string $operacion): bool`
- `public static function exigirPermiso(Usuario $usuario, string $operacion): void`

## ErrorAplicacion

[backend/src/shared/errors/ErrorAplicacion.php](../../backend/src/shared/errors/ErrorAplicacion.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public function __construct( string $mensaje, public readonly int $codigoHttp = 400, public readonly array $erroresCampos = [] )`

## RespuestaJson

[backend/src/shared/utils/RespuestaJson.php](../../backend/src/shared/utils/RespuestaJson.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function enviarRespuestaJson(array $contenido, int $codigoHttp = 200): never`

## SesionUsuario

[backend/src/shared/utils/SesionUsuario.php](../../backend/src/shared/utils/SesionUsuario.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function iniciarSesionSegura(): void`
- `public static function obtenerTokenCsrf(): string`
- `public static function establecerUsuarioAutenticado(int $idUsuario, int $versionCredenciales): void`
- `public static function limpiarSesionUsuario(): void`
- `public static function obtenerIdUsuario(): int`
- `public static function obtenerVersionCredenciales(): int`
- `public static function obtenerInicioVentanaIntentos(): int`
- `public static function obtenerIntentosFallidos(): int`
- `public static function reiniciarIntentos(int $momento): void`
- `public static function registrarIntentoFallido(): void`

## SolicitudHttp

[backend/src/shared/utils/SolicitudHttp.php](../../backend/src/shared/utils/SolicitudHttp.php)

Soporte de configuracion, infraestructura o utilidades compartidas.

- `public static function obtenerCuerpoJson(): array`
- `public static function obtenerIdPositivo(string $valor): int`
