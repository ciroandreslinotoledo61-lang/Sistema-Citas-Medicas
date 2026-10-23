# Diccionario de datos

Fuente: `backend/src/data/migrations/001_crear_tablas_sistema.sql`. La migracion contiene exactamente 11 tablas del dominio; no hay tablas de herramientas externas.

Las claves IDENTITY se generan en PostgreSQL. Las FK usan la accion predeterminada NO ACTION: no se eliminan registros relacionados de forma automatica. Los valores de entrada se validan tambien en la capa business.

## roles

Los cuatro tipos de acceso del sistema. Catalogo fijo en la V1.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_rol | SMALLINT | No | PK; IDENTITY |
| codigo_rol | VARCHAR(30) | No | UNIQUE |
| nombre_rol | VARCHAR(50) | No | - |

## usuarios

Credenciales y datos basicos de todas las cuentas. Modulo implementado.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_usuario | BIGINT | No | PK; IDENTITY |
| id_rol | SMALLINT | No | FK -> roles.id_rol |
| nombres_usuario | VARCHAR(80) | No | - |
| apellidos_usuario | VARCHAR(80) | No | - |
| correo_usuario | VARCHAR(160) | No | UNIQUE; Minusculas y sin espacios externos |
| contrasena_hash | VARCHAR(255) | No | Hash PHP; nunca texto plano |
| usuario_activo | BOOLEAN | No | DEFAULT TRUE |
| version_credenciales | INTEGER | No | DEFAULT 1; CHECK en SQL; Invalida sesiones anteriores |
| fecha_registro_usuario | TIMESTAMPTZ | No | DEFAULT CURRENT_TIMESTAMP |

## pacientes

Datos personales y de contacto del paciente. Modulo implementado.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_paciente | BIGINT | No | PK; IDENTITY |
| id_usuario | BIGINT | No | UNIQUE; FK -> usuarios.id_usuario |
| cedula_identidad | VARCHAR(25) | No | UNIQUE |
| fecha_nacimiento | DATE | No | - |
| telefono_paciente | VARCHAR(25) | No | - |
| direccion_paciente | VARCHAR(200) | No | DEFAULT '' |
| nombre_contacto_emergencia | VARCHAR(120) | No | DEFAULT '' |
| telefono_contacto_emergencia | VARCHAR(25) | No | DEFAULT '' |

## historias_clinicas

Una historia por paciente. En V1 se crea vacia; la edicion clinica sigue pendiente.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_historia_clinica | BIGINT | No | PK; IDENTITY |
| id_paciente | BIGINT | No | UNIQUE; FK -> pacientes.id_paciente |
| tipo_sangre | VARCHAR(5) | Si | - |
| alergias | TEXT | No | DEFAULT '' |
| antecedentes_medicos | TEXT | No | DEFAULT '' |
| fecha_apertura_historia | TIMESTAMPTZ | No | DEFAULT CURRENT_TIMESTAMP |

## especialidades

Catalogo medico. Incluye tres datos de prueba; gestion implementada en el avance 2.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_especialidad | BIGINT | No | PK; IDENTITY |
| nombre_especialidad | VARCHAR(100) | No | UNIQUE |
| descripcion_especialidad | VARCHAR(300) | No | DEFAULT '' |
| especialidad_activa | BOOLEAN | No | DEFAULT TRUE |

## medicos

Perfil profesional asociado a usuario y especialidad. Gestion implementada en el avance 2.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_medico | BIGINT | No | PK; IDENTITY |
| id_usuario | BIGINT | No | UNIQUE; FK -> usuarios.id_usuario |
| id_especialidad | BIGINT | No | FK -> especialidades.id_especialidad |
| matricula_profesional | VARCHAR(40) | No | UNIQUE |

## horarios_medicos

Bloques de atencion por medico y fecha. Modulo pendiente.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_horario_medico | BIGINT | No | PK; IDENTITY |
| id_medico | BIGINT | No | FK -> medicos.id_medico |
| fecha_atencion | DATE | No | - |
| hora_inicio | TIME | No | - |
| hora_fin | TIME | No | - |

## turnos

Intervalos de 15 minutos asociados a un horario. Generacion pendiente.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_turno | BIGINT | No | PK; IDENTITY |
| id_horario_medico | BIGINT | No | FK -> horarios_medicos.id_horario_medico |
| fecha_hora_inicio | TIMESTAMP | No | - |
| fecha_hora_fin | TIMESTAMP | No | - |
| turno_habilitado | BOOLEAN | No | DEFAULT TRUE |

## citas

Reserva de un paciente para un turno. Modulo pendiente.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_cita | BIGINT | No | PK; IDENTITY |
| id_paciente | BIGINT | No | FK -> pacientes.id_paciente |
| id_turno | BIGINT | No | FK -> turnos.id_turno |
| estado_cita | VARCHAR(20) | No | DEFAULT 'pendiente' |
| motivo_cita | VARCHAR(500) | No | DEFAULT '' |
| fecha_reserva_cita | TIMESTAMPTZ | No | DEFAULT CURRENT_TIMESTAMP |

## consultas

Resultado clinico de una cita. Modulo pendiente.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_consulta | BIGINT | No | PK; IDENTITY |
| id_cita | BIGINT | No | UNIQUE; FK -> citas.id_cita |
| id_historia_clinica | BIGINT | No | FK -> historias_clinicas.id_historia_clinica |
| diagnostico_consulta | TEXT | No | - |
| observaciones_consulta | TEXT | No | DEFAULT '' |
| fecha_registro_consulta | TIMESTAMPTZ | No | DEFAULT CURRENT_TIMESTAMP |

## tratamientos

Indicaciones de medicamentos asociadas a una consulta. Modulo pendiente.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_tratamiento | BIGINT | No | PK; IDENTITY |
| id_consulta | BIGINT | No | FK -> consultas.id_consulta |
| medicamento_tratamiento | VARCHAR(150) | No | - |
| dosis_tratamiento | VARCHAR(100) | No | - |
| frecuencia_tratamiento | VARCHAR(100) | No | - |
| duracion_tratamiento | VARCHAR(100) | No | - |
| indicaciones_tratamiento | TEXT | No | DEFAULT '' |

## Restricciones e indices importantes

- roles: codigo unico y restringido a los cuatro roles previstos.
- usuarios: correo unico y normalizado; indice por rol; version de credenciales positiva.
- pacientes: un perfil por usuario; CI unica. La fecha y el contacto se validan en el servicio.
- historias_clinicas: un registro por paciente; tipo de sangre limitado a los ocho grupos previstos o NULL.
- medicos: un perfil por usuario, matricula unica e indice por especialidad.
- horarios_medicos: fin posterior al inicio; inicio unico por medico y fecha. Esto no evita todos los solapamientos: esa regla se implementara en el servicio de horarios.
- turnos: fin exactamente 15 minutos despues del inicio; inicio unico dentro de un horario.
- citas: indice unico parcial por turno cuando el estado no es cancelada ni reprogramada. Indice por paciente.
- consultas: una consulta por cita; indice por historia clinica.
- tratamientos: indice por consulta.

## Relaciones principales

| Origen | Destino | Cardinalidad |
| --- | --- | --- |
| roles | usuarios | 1 a muchos |
| usuarios | pacientes | 1 a 0..1 |
| usuarios | medicos | 1 a 0..1 |
| pacientes | historias_clinicas | 1 a 1 por regla del registro; la FK por si sola permite ausencia |
| especialidades | medicos | 1 a muchos |
| medicos | horarios_medicos | 1 a muchos |
| horarios_medicos | turnos | 1 a muchos |
| pacientes | citas | 1 a muchos |
| turnos | citas | 1 a muchos historicos; maximo una que ocupe el turno |
| citas | consultas | 1 a 0..1 |
| historias_clinicas | consultas | 1 a muchos |
| consultas | tratamientos | 1 a muchos |

El esquema de las tablas clinicas es inicial. Las reglas entre medico, cita e historia, los conflictos entre horarios y las transiciones de estado requieren servicios y pruebas antes de habilitar esos modulos.

## Indices del avance 2

La migracion 002_unicidad_especialidades_medicos.sql agrega:

| Indice | Tabla | Expresion unica |
| --- | --- | --- |
| uq_especialidades_nombre_normalizado | especialidades | Nombre en minusculas, sin espacios de los extremos y con espacios internos agrupados |
| uq_medicos_matricula_normalizada | medicos | Matricula en mayusculas, sin espacios de los extremos y con espacios internos agrupados |

No se agregan tablas ni columnas. medicoActivo, devuelto por la API, representa usuarios.usuario_activo. cantidadMedicosActivos es un conteo calculado, no una columna guardada.

## Migracion 003: citas y responsabilidades

Las 11 tablas se conservan. Campos agregados a citas:

| Campo | Tipo | Proposito |
| --- | --- | --- |
| asistencia_registrada | VARCHAR(12), NOT NULL, defecto pendiente | pendiente, asistio o no_asistio; CHECK |
| motivo_cancelacion | VARCHAR(300), NOT NULL, defecto vacio | Motivo opcional al anular |
| id_usuario_registro | BIGINT, FK usuarios, nullable | Responsable de la reserva |
| id_usuario_actualizacion | BIGINT, FK usuarios, nullable | Responsable del ultimo cambio de estado o asistencia |
| fecha_actualizacion_cita | TIMESTAMPTZ, nullable | Momento del ultimo cambio |
| id_especialidad_reserva | BIGINT, FK especialidades, nullable | Especialidad asignada al reservar; evita reinterpretar una cita nueva tras reasignar al medico |
| id_cita_origen | BIGINT, FK citas, nullable | Reserva anterior al reprogramar |

Los campos nullable permiten conservar registros previos sin inventar sus responsables. Para reservas anteriores sin id_especialidad_reserva, la consulta usa la especialidad actual del medico. No se almacena una copia textual del nombre de especialidad.

Indices nuevos: horarios_medicos(id_medico, fecha_atencion), turnos(fecha_hora_inicio) y citas(id_cita_origen). Se mantiene el indice unico parcial uq_citas_turno_ocupado: cancelada y reprogramada no ocupan el turno. No asistio y atendida conservan la ocupacion historica.

La ausencia de cruces de bloques y de citas del mismo paciente se controla en servicios con bloqueos de filas y transacciones. No es una restriccion de exclusion independiente en SQL: las escrituras de la aplicacion deben pasar por esos servicios.
