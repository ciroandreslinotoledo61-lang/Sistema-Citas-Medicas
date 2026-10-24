# Comparación arquitectónica y decisiones

Revisión realizada el 24 de octubre de 2026 sobre los dos ZIP adjuntos. CADEFAR se utilizó como referencia de código, sin modificarlo. La implementación de Clínica se verificó contra su propia versión original; las reglas del inventario de CADEFAR no se trasladaron al dominio médico.

## Archivos examinados en CADEFAR

- `AGENTS.md` y `backend/AGENTS.md`: definen separación por capas, transacciones en Services, autorización mediante middlewares y consultas en Repositories.
- `backend/src/presentation/routes/usuario.router.js`: declara HTTP, middleware de sesión/rol y acción del controlador.
- `backend/src/presentation/controllers/usuario.controller.js`: interpreta HTTP, valida y delega en el servicio.
- `backend/src/business/services/usuario.service.js`: coordina unicidad, roles activos, hash, cambios y transacciones.
- `backend/src/business/validators/usuario.validator.js`: valida entradas con Zod.
- `backend/src/data/repositories/usuario.repository.js`: consultas, opciones de bloqueo y persistencia mediante Sequelize.
- La organización general del frontend por funcionalidades y componentes compartidos.

CADEFAR también contiene métodos extensos y algunos fragmentos compactos. Se adoptaron sus principios de separación, sin reproducir esas irregularidades ni introducir su bitácora, JWT, Sequelize, Node o React en Clínica.

## Diagnóstico y solución aplicada

| Área | CADEFAR | Clínica original | Clínica refactorizada |
| --- | --- | --- | --- |
| Rutas | Archivos por módulo | `RutasApi` y `RutasAgenda` combinaban reconocimiento, construcción de objetos, sesión y respuestas | Archivos declarativos por módulo; `EnrutadorApi` y `FabricaControladores` resuelven HTTP y dependencias |
| Controladores | HTTP y delegación | Mayormente pequeños; configuración de agenda comprobaba permisos y obtenía configuración directamente | Controladores pequeños; configuración y disponibilidad delegadas en `DisponibilidadService` |
| Servicios | Casos de uso y transacciones | Ya contenían reglas, pero otras estaban en repositorios y había bloques de transacción repetidos | Casos de uso conservados; generación de turnos y decisión de alcance trasladadas aquí; transacciones reutilizables |
| Validadores | Zod por módulo | `AgendaValidator` mezclaba formatos comunes, horarios y citas; listado de pacientes validaba estado en el servicio | `HorarioMedicoValidator`, `CitaValidator` y validadores comunes; filtros de pacientes en `PacienteValidator` |
| Repositorios | Consultas Sequelize | `HorarioMedicoRepository` calculaba intervalos; consultas de pacientes y médicos dentro de `CitaRepository`; selección de alcance por rol en repositorios | `TurnoRepository` separado; SQL por entidad; reciben restricciones decididas por el servicio |
| Modelos | Entidades ORM | Entidades PHP y representación pública | Se conservan entidades PHP; no se incorpora un ORM innecesario |
| Transacciones | El servicio delimita la operación | Repetición de begin/commit/rollback y SQL de aislamiento dentro de servicios | El servicio delimita mediante `Transaccion::ejecutar`; infraestructura ejecuta aislamiento, commit y rollback |
| Sesión | JWT y cookie | Sesión PHP, con acceso directo a `$_SESSION` desde el servicio | Sesión PHP conservada; `SesionUsuario` encapsula almacenamiento, el servicio decide reglas de autenticación |
| Frontend | Features, componentes y API | Ya estaba organizado por features, componentes y API | Sus 65 archivos se conservaron idénticos; 30 módulos JavaScript verificados |

## Decisiones de compatibilidad

1. Se conservan los nombres públicos de los métodos de los servicios usados por las pruebas y las 48 combinaciones de método HTTP y ruta.
2. `AgendaValidator` permanece como fachada de compatibilidad. Delega en validadores específicos; no duplica reglas.
3. `HorarioMedicoService::listarMedicosDisponibles` y `listarTurnosDisponibles` permanecen como delegaciones para consumidores PHP anteriores. Las rutas nuevas invocan directamente a `DisponibilidadController`.
4. Las firmas internas de algunos repositorios cambian. Sus dependencias se actualizaron; no son endpoints públicos. La matriz de movimientos indica sus reemplazos.
5. Se mantienen migraciones 001–005, nombres SQL, índices, triggers, tablas y datos históricos. Esta refactorización no requiere una migración nueva.
6. Se mantiene la configuración predeterminada de citas de 30 minutos y horario 08:00–17:00. La versión recibida permite otros valores en `CITA_DURACION_MINUTOS` y conserva duraciones históricas. Se respetó esa compatibilidad; para el uso solicitado se debe conservar `CITA_DURACION_MINUTOS=30`.
7. Los estados y permisos existentes no se ampliaron. Administración y Recepción son roles que actúan sobre módulos, no servicios gigantes adicionales.
8. No se añadieron consultas clínicas ni tratamientos: estaban pendientes en el proyecto recibido. La creación automática de historia clínica continúa dentro del registro del paciente.
9. No se renombraron tablas a los nombres de un diagrama antiguo: se respetó el esquema real de este ZIP, que usa `usuarios`, `pacientes`, `historias_clinicas`, etc.
10. No se eliminó ningún archivo original. Se conserva el número de versión de la respuesta `/api/estado` para mantener su contrato.

## Movimientos principales de métodos

| Ubicación anterior | Ubicación actual |
| --- | --- |
| `RutasApi::atenderSolicitudApi` | `EnrutadorApi::atenderSolicitud`, definiciones en `RutasApi::definir` y módulos |
| `RutasAgenda::atenderRutasAgenda` | Definiciones de `RutasHorarios`, `RutasDisponibilidad`, `RutasCitas`, `RutasReportes`, agrupadas por `RutasAgenda::definir` |
| `HorarioMedicoRepository::registrarHorarioConTurnos` | Coordinación en `HorarioMedicoService::crearHorarioConTurnos`; cálculo en `generarIntervalosTurnos`; SQL en `HorarioMedicoRepository::registrarHorario` y `TurnoRepository::registrarTurnos` |
| `HorarioMedicoRepository::obtenerTurnoPorId` y `listarTurnosDisponibles` | Mismos nombres en `TurnoRepository` |
| `HorarioMedicoRepository::listarMedicosDisponibles` | `MedicoRepository::listarMedicosDisponibles` |
| `CitaRepository::listarMedicosParaAgenda` | `MedicoRepository::listarMedicosParaAgenda` |
| `CitaRepository::bloquearPaciente` y `obtenerIdPacientePorUsuario` | Mismos nombres en `PacienteRepository` |
| Validación de estado en `PacienteService::listarPacientes` | `PacienteValidator::validarFiltrosPacientes` |
| `AgendaValidator::validarHorario` y `validarProgramacionPeriodo` | Implementaciones en `HorarioMedicoValidator`; fachada anterior conservada |
| `AgendaValidator::validarFiltrosCitas` | Implementación en `CitaValidator`; fachada anterior conservada |
| `AgendaValidator::validarFecha` y `validarIdentificador` | Implementaciones en `ValidarCamposFormulario`; fachada anterior conservada |

## Criterio para nuevas funciones

Definir la ruta en el archivo del módulo; crear una acción breve en su controlador; desarrollar el caso de uso y sus permisos de negocio en el servicio; validar formatos en el validador; agregar consultas parametrizadas al repositorio de la entidad. Las restricciones generales de rol se declaran en las rutas y se ejecutan en middleware. La propiedad de una cita y las reglas de tiempo se verifican en el servicio, también cuando se llama desde una prueba sin HTTP.

Los scripts de migración, seeders y configuración de conexión son infraestructura. Contienen SQL de instalación o configuración de forma deliberada; la prohibición de SQL en rutas, controladores y servicios de negocio sí se comprueba automáticamente.
