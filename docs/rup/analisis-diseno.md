# Analisis y diseno

## Dominio y responsabilidades

Usuario y Rol resuelven identidad/acceso. Paciente y Medico son perfiles, Especialidad clasifica. HorarioMedico define un bloque y Turno un intervalo. Cita une paciente/turno y conserva resultados/reprogramaciones. HistoriaClinica agrupa consultas; Consulta documenta una cita y Tratamiento depende de ella. EventoBitacora representa trazabilidad administrativa. Se reutilizan las entidades existentes: no se inventa otro expediente o tabla de atencion.

| Tipo de clase | Responsabilidad |
| --- | --- |
| Frontera: vistas, JS, controllers | Interaccion y HTTP |
| Control: services/validators | Reglas y coordinacion del caso |
| Entidad: models | Datos del dominio y proyecciones publicas |
| Persistencia: repositories/conexion/transaccion | SQL y atomicidad |
| Transversal: permisos/sesion/CSRF/bitacora | Acceso y trazabilidad |

```mermaid
classDiagram
 direction TB
 class ConsultaController {
  registrarConsulta(usuario)
  actualizarConsulta(usuario, idConsulta)
  obtenerMiHistorial(usuario)
 }
 class ConsultaService {
  registrarConsulta(usuario, datos)
  actualizarConsulta(usuario, idConsulta, datos)
  obtenerHistorial(usuario, idPaciente, filtros)
 }
 class ConsultaValidator {
  validarConsulta(datos, edicion)
  validarHistoria(datos)
 }
 class ConsultaRepository {
  obtenerConsultaPorId(idConsulta, bloquear)
  registrarConsulta(idCita, idHistoria, datos, idUsuario)
  actualizarConsulta(idConsulta, datos, idUsuario)
 }
 class TratamientoRepository {
  guardarTratamientosConsulta(idConsulta, tratamientos)
 }
 class HistoriaClinicaService {
  exigirHistoriaAutorizada(usuario, idPaciente)
 }
 ConsultaController --> ConsultaService
 ConsultaService --> ConsultaValidator
 ConsultaService --> ConsultaRepository
 ConsultaService --> TratamientoRepository
 ConsultaService --> HistoriaClinicaService
```

Figura focalizada en el caso clinico; el [catalogo completo](../ai/catalogo-metodos.md) contiene los metodos reales por clase.

## Modelo logico

```mermaid
erDiagram
 roles ||--o{ usuarios : asigna
 usuarios ||--o| pacientes : identifica
 usuarios ||--o| medicos : identifica
 especialidades ||--o{ medicos : clasifica
 medicos ||--o{ horarios_medicos : programa
 horarios_medicos ||--o{ turnos : divide
```

```mermaid
erDiagram
 pacientes ||--o{ citas : reserva
 turnos ||--o{ citas : conserva_historial
 citas o|--o{ citas : origina
 pacientes ||--o| historias_clinicas : tiene
 historias_clinicas ||--o{ consultas : contiene
 citas ||--o| consultas : documenta
 consultas ||--o{ tratamientos : prescribe
```

Varias citas historicas pueden referirse al mismo turno; un indice parcial protege la ocupacion vigente. La cardinalidad fisica de historia es 0..1: una FK no obliga a insertar la hija, pero la transaccion de registro cumple la regla de historia obligatoria. Bitacora guarda ID/nombre/rol del actor como instantanea sin FK de actor. Relaciones adicionales a usuario responsable y especialidad de reserva estan en el diccionario.

## Diseno fisico

Tras 001-008: doce tablas, 93 columnas. Claves bigint con identidad; tipos timestamp/timestamptz segun el esquema existente; JSONB para historiales/versiones. La conexion establece hora de Bolivia. El [diccionario](../ai/diccionario-datos.md) se extrae del esquema migrado, con nulabilidad, defaults, restricciones e indices. El SQL educativo crea el estado final en una BD vacia; las migraciones actualizan instalaciones existentes.

## Secuencia: registro completo

```mermaid
sequenceDiagram
 participant F as Formulario
 participant C as PacienteController
 participant S as PacienteService
 participant R as Repositories
 participant D as PostgreSQL
 F->>C: POST datos y CSRF
 C->>S: registrarPacienteConHistoriaClinica
 S->>S: Validar y generar hash
 S->>D: BEGIN
 S->>R: Obtener rol e insertar usuario
 R->>D: SELECT e INSERT RETURNING
 S->>R: Insertar paciente e historia
 R->>D: INSERT con restricciones
 alt Todo valido
  S->>D: COMMIT
  C-->>F: 201 y datos publicos
 else Error
  S->>D: ROLLBACK
  C-->>F: Error sin alta parcial
 end
```

## Secuencia: consulta

```mermaid
sequenceDiagram
 participant F as Formulario consulta
 participant C as ConsultaController
 participant S as ConsultaService
 participant R as Repositories
 participant D as PostgreSQL
 F->>C: POST consulta y tratamientos
 C->>S: registrarConsulta(usuario, datos)
 S->>S: Validar rol y campos
 S->>D: BEGIN READ COMMITTED
 S->>R: Bloquear cita y obtener historia
 R->>D: SELECT FOR UPDATE
 S->>S: Asignacion, tiempo y unicidad
 S->>R: Guardar consulta y tratamientos
 R->>D: INSERT y triggers
 S->>R: Marcar Atendida si Programada
 R->>D: UPDATE
 alt Escrituras completas
  S->>D: COMMIT
  C-->>F: 201 y consulta
 else Fallo
  S->>D: ROLLBACK
  C-->>F: Error sin cambios parciales
 end
```

## Comunicacion: reprogramar

```mermaid
flowchart TB
 V[Vista cita] -->|1. PATCH destino| C[CitaController]
 C -->|2. reprogramarCita| S[CitaService]
 S -->|3. autorizar y bloquear| R[CitaRepository]
 R -->|3.1 SELECT FOR UPDATE| B[PostgreSQL]
 S -->|4. validar destino y guardar| R
 R -->|4.1 UPDATE origen e INSERT sucesora| B
 S -->|5. COMMIT o ROLLBACK| B
 S -->|6. resultado| C
 C -->|7. JSON| V
```

El resumen omite repositorios auxiliares de medico/especialidad/paciente/turno usados al preparar destino; no existe escritura directa desde vista a BD.

## Estados

```mermaid
stateDiagram-v2
 [*] --> Programada: Reserva valida
 Programada --> Cancelada: Antes de inicio
 Programada --> Reprogramada: Cambio atomico
 Programada --> Atendida: Desde inicio o consulta
 Programada --> Inasistencia: Desde fin
 Cancelada --> [*]
 Reprogramada --> [*]
 Atendida --> [*]
 Inasistencia --> [*]
```

Reprogramar crea otra entidad Programada. No vuelve la misma fila al inicio ni hay estado operativo Confirmada.
