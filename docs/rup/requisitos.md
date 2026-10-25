# Requisitos y reglas - version 1.5

Problema: coordinar disponibilidad, reservas y atenciones sin duplicados, perdida de antecedentes o accesos ajenos. Objetivo: centralizar el proceso y reconstruir operaciones. Alcance universitario para Bolivia, sin sedes por ciudad.

RF01-RF13 conservan la numeracion del documento academico. RF14-RF16 son extensiones de esta entrega. Confirmada figuraba en la referencia original, pero la decision posterior la retira como estado operativo; 006 documenta la normalizacion conservando eventos anteriores.

## Requisitos funcionales

| ID | Requisito | Aceptacion |
| --- | --- | --- |
| RF01 | Autenticacion/permisos | Cuenta activa, sesion del rol y CSRF en cambios |
| RF02 | Pacientes | Alta atomica usuario/paciente/historia; datos personales/contacto y desactivacion sin borrar citas |
| RF03 | Medicos | Cuenta de rol fijo, matricula unica, telefono y especialidad activa |
| RF04 | Especialidades | Nombre unico normalizado; no desactivar con medicos activos |
| RF05 | Horarios/turnos | Dia o periodo sin cruces, 30 minutos predeterminados; repetir no duplica |
| RF06 | Reserva | Propia o por Admin/Recepcion; Programada con responsable |
| RF07 | Evitar duplicados | Un turno vigente por reserva y una Programada por paciente, revalidacion transaccional |
| RF08 | Reprogramacion | Origen y destino guardados juntos; fallo no pierde reserva |
| RF09 | Cancelacion | Antes del inicio; conserva motivo/actor y libera turno |
| RF10 | Resultado | Asistio implica Atendida desde inicio; No asistio, Inasistencia desde fin |
| RF11 | Agenda | Filtros de fecha/estado/medico y texto; medico solo asignadas |
| RF12 | Historial de citas | Eventos y cadena de reprogramacion conservados; resultado del ultimo destino |
| RF13 | Reportes | Periodo, medico, especialidad, estado, pacientes registrados y resultados |
| RF14 | Bitacora | Operaciones confirmadas y autenticacion; visor administrativo filtrable |
| RF15 | Consulta/tratamientos | Una por cita, medico asignado, atomicidad y correcciones versionadas |
| RF16 | Historia clinica | Medico asignado lee/actualiza; paciente solo lee lo propio; admin/recepcion sin contenido clinico |

## Requisitos no funcionales

| ID | Requisito | Evidencia/limite |
| --- | --- | --- |
| RNF01 | Capas mantenibles | Routes/Controllers, Services/Validators, Repositories/Models; test_arquitectura |
| RNF02 | Claves protegidas | password_hash/password_verify; DTO y auditoria sin hashes |
| RNF03 | SQL preparado | PDO con parametros y preparaciones emuladas desactivadas |
| RNF04 | Sesion protegida | HttpOnly, SameSite, expiracion, regeneracion, origen y CSRF |
| RNF05 | Alcance de acceso | Rol y propiedad/asignacion en backend |
| RNF06 | Integridad | PK/FK/UNIQUE/CHECK, bloqueos, triggers y rollback |
| RNF07 | UI adaptable | Paleta central; estados con texto; seis vistas en cuatro anchos |
| RNF08 | Configuracion externa | .env ignorado por Git; sin claves SQL en frontend |
| RNF09 | Trazabilidad | Eventos de cita, versiones clinicas y bitacora de metadatos |
| RNF10 | Recuperacion | Procedimiento de respaldo y restauracion; ensayo Windows pendiente |
| RNF11 | Explicabilidad | Catalogo, diccionario, API, defensa y tests |

No se promete carga maxima ni tiempo de respuesta medido. La revision visual no equivale a certificacion de accesibilidad.

## Reglas de negocio

| Regla | Definicion |
| --- | --- |
| RN01 | Correo, CI y matricula respetan unicidad y normalizacion |
| RN02 | El paciente existe y esta activo para nuevas reservas |
| RN03 | Medico/especialidad activos para programar y reservar |
| RN04 | Nuevos bloques respetan jornada 08:00-17:00 predeterminada |
| RN05 | No crear reservas o bloques cuyo inicio ya paso |
| RN06 | No superponer horarios o reservas; intervalos contiguos si se permiten |
| RN07 | Duracion configurable, 30 entregados; cada bloque conserva su valor |
| RN08 | Cancelar libera turno sin borrar cita |
| RN09 | Atendida conserva su resultado; no hay reversion de finales |
| RN10 | Desactivar conserva registros historicos |
| RN11 | Operaciones requieren permiso y alcance por registro |
| RN12 | Disponibilidad se revalida al guardar con bloqueos |
| RN13 | Los datos confirmados persisten en PostgreSQL |
| RN14 | Una Programada por paciente; excepciones legadas de 005 se conservan |
| RN15 | Reprogramar crea otra Programada enlazada en una transaccion |
| RN16 | Atendida desde inicio e Inasistencia desde fin |
| RN17 | Consulta unica, cita e historia del mismo paciente |
| RN18 | Solo el medico de la consulta la corrige; exige version actual |
| RN19 | Historial medico requiere una cita Programada o Atendida con el paciente |
| RN20 | Paciente solo consulta su historial |
| RN21 | Correcciones conservan contenido y tratamientos anteriores |
| RN22 | Auditoria administrativa sin secretos ni contenido clinico |

Asistencia pendiente es distinta del estado de cita Programada. Una cita pasada sin resultado sigue bloqueando una segunda Programada. El acceso clinico del medico es longitudinal: una Atendida conserva la relacion; Cancelada/Inasistencia por si solas no la habilitan.

No se implementan edicion de bloques existentes, correccion de resultados finales, laboratorio, consultorios, pagos, notificaciones, recuperacion de claves ni reasignacion libre de roles.
