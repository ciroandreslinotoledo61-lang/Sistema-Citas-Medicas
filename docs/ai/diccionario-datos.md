# Diccionario de datos

Fuentes: migraciones 001 a 005 en `backend/src/data/migrations`. La migracion contiene exactamente 11 tablas del dominio; no hay tablas de herramientas externas.

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

Bloques de atencion por medico y fecha. Modulo implementado.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_horario_medico | BIGINT | No | PK; IDENTITY |
| id_medico | BIGINT | No | FK -> medicos.id_medico |
| fecha_atencion | DATE | No | - |
| hora_inicio | TIME | No | - |
| hora_fin | TIME | No | - |

## turnos

Intervalos asociados a un horario. Duracion nueva de 30 minutos por defecto; se conserva la duracion historica.

| Campo | Tipo | Nulo | Restriccion o valor inicial |
| --- | --- | --- | --- |
| id_turno | BIGINT | No | PK; IDENTITY |
| id_horario_medico | BIGINT | No | FK -> horarios_medicos.id_horario_medico |
| fecha_hora_inicio | TIMESTAMP | No | - |
| fecha_hora_fin | TIMESTAMP | No | - |
| turno_habilitado | BOOLEAN | No | DEFAULT TRUE |

## citas

Reserva de un paciente para un turno. Modulo implementado.

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
- horarios_medicos: fin posterior al inicio; inicio unico por medico y fecha. Esto no evita todos los solapamientos: el servicio de horarios valida los cruces bajo bloqueo del medico.
- turnos: fin igual al inicio mas duracion_turno_minutos; intervalo completo unico dentro de un horario.
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
| motivo_cancelacion | VARCHAR(300), NOT NULL, defecto vacio | Motivo al cancelar; obligatorio por servicio para Medico desde 005 |
| id_usuario_registro | BIGINT, FK usuarios, nullable | Responsable de la reserva |
| id_usuario_actualizacion | BIGINT, FK usuarios, nullable | Responsable del ultimo cambio de estado o asistencia |
| fecha_actualizacion_cita | TIMESTAMPTZ, nullable | Momento del ultimo cambio |
| id_especialidad_reserva | BIGINT, FK especialidades, nullable | Especialidad asignada al reservar; evita reinterpretar una cita nueva tras reasignar al medico |
| id_cita_origen | BIGINT, FK citas, nullable | Reserva anterior al reprogramar |

Los campos nullable permiten conservar registros previos sin inventar sus responsables. Para reservas anteriores sin id_especialidad_reserva, la consulta usa la especialidad actual del medico. No se almacena una copia textual del nombre de especialidad.

Indices nuevos: horarios_medicos(id_medico, fecha_atencion), turnos(fecha_hora_inicio) y citas(id_cita_origen). Se mantiene el indice unico parcial uq_citas_turno_ocupado: cancelada y reprogramada no ocupan el turno. No asistio y atendida conservan la ocupacion historica.

La ausencia de cruces de bloques y de citas del mismo paciente se controla en servicios con bloqueos de filas y transacciones. No es una restriccion de exclusion independiente en SQL: las escrituras de la aplicacion deben pasar por esos servicios. La migracion 004 agrega tambien un trigger para cruces de reservas.

## Migracion 004

| Tabla | Campo | Tipo y regla |
| --- | --- | --- |
| pacientes | sexo_paciente | VARCHAR(20), NOT NULL, defecto no_especificado; CHECK: no_especificado/femenino/masculino/otro |
| medicos | telefono_medico | VARCHAR(25), NOT NULL, defecto vacio para datos anteriores; obligatorio en altas/ediciones nuevas |
| horarios_medicos | duracion_cita_minutos | SMALLINT, NOT NULL, defecto 30; 5 a 120, multiplo de 5 |
| turnos | duracion_turno_minutos | SMALLINT, NOT NULL, defecto 30; 5 a 120, multiplo de 5 |

ck_turnos_duracion valida el intervalo contra duracion_turno_minutos. uq_turnos_horario_intervalo reemplaza la unicidad solo por inicio, para conservar reservas anteriores de 15 minutos durante la conversion. tr_citas_evitar_cruces bloquea medico/paciente y rechaza solapamientos con SQLSTATE 23P01; la ocupacion del mismo turno mantiene el indice parcial previo. Indices nuevos: usuarios(fecha_registro_usuario), citas(estado_cita).

pacienteActivo y medicoActivo representan usuarios.usuario_activo. No se duplica ese estado en los perfiles. No se agregan tablas.

## Migracion 005

Se conservan las once tablas. No se cambian las horas ni estados de citas existentes, usuarios o claves.

| Tabla | Campo / indice | Definicion |
| --- | --- | --- |
| citas | excepcion_cita_activa_previa | BOOLEAN NOT NULL DEFAULT FALSE; true solo en duplicidades activas detectadas al instalar 005 |
| citas | historial_cambios | JSONB NOT NULL DEFAULT []; arreglo de eventos posteriores a 005 |
| citas | uq_citas_paciente_activa | UNIQUE(id_paciente) parcial: estado pendiente/confirmada y excepcion=false |

tr_citas_politica_y_trazabilidad valida estados, no permite cambiar paciente/turno en la misma cita, bloquea el paciente y rechaza nuevas activas con SQLSTATE P2001. Incluye todas las citas activas al comprobar, tambien las exceptuadas. Un INSERT no puede activar la excepcion y un UPDATE solo la conserva en una cita legada que sigue activa. Al finalizar la cita, la excepcion se retira. La migracion repetida no vuelve a marcar excepciones.

P2002 identifica transicion invalida o estado/asistencia incoherente. El trigger agrega auditoria y conserva el arreglo anterior en UPDATE. El indice parcial protege nuevas reservas incluso ante escrituras simultaneas; el servicio vuelve a validar bajo bloqueo del paciente. El trigger 004 y el indice de ocupacion por turno permanecen.

El horario general se centraliza en ConfiguracionAgenda; no se agrega un CHECK que invalide los bloques historicos fuera de 08:00-17:00. Solo las altas de horarios nuevas se limitan. Los turnos reservados anteriores conservan su duracion, aunque sea de 15 minutos.

005 ajusta validar_cruces_reserva_cita: las citas finales siguen ocupando el intervalo del medico, pero dejan de bloquear al paciente para otra reserva futura. Esto tambien se comprueba con una atencion terminada antes del fin nominal de un turno mas largo.
