# Agenda, perfiles y reportes - version 1.3.0

## Cambios solicitados

| Requisito de la revision | Resultado |
| --- | --- |
| RF13: reportes | Administrador filtra fechas inclusivas, medico, especialidad y estado. Totales globales de citas, atenciones, cancelaciones e inasistencias; agrupaciones y agenda paginada; pacientes registrados por periodo |
| RF10: Atendida | Asistio cambia tambien el estado a atendida. Medico asignado, Administrador o Recepcion pueden marcar atendida desde el inicio; no_asistio solo Administrador/Recepcion desde el fin |
| RF02: estado de pacientes | Desactivar o reactivar sin borrar cuenta, perfil, citas ni historia. Revoca sesiones y bloquea reservas nuevas; no cancela citas existentes |
| RF05: duracion | 30 minutos por defecto; configurable en backend/.env para bloques nuevos; validada en PHP y PostgreSQL |
| RF02: datos personales | Edicion de nombres, apellidos, correo, CI, nacimiento, sexo opcional, telefono, direccion y emergencia |
| RF03: telefono medico | Campo obligatorio al crear/editar; perfiles anteriores conservan vacio hasta completarlo |
| RF11: agenda | Selector de medico para Administrador/Recepcion, junto con fecha, estado, busqueda y filtro por paciente; medico/paciente mantienen alcance propio |

## Hora y duracion

La aplicacion usa la hora de Bolivia. El identificador tecnico queda solo en configuracion y SQL, fuera de las pantallas. Los turnos conservan su hora al abrirse desde un navegador con otra zona.

CITA_DURACION_MINUTOS acepta multiplos de 5 entre 5 y 120, con 30 predeterminado. Cada bloque almacena su duracion, y cada turno su intervalo real. Cambiar la configuracion afecta solo nuevos bloques. Inicio/fin usan pasos de 5 minutos y el bloque debe contener turnos completos.

La migracion convierte los bloques futuros existentes a 30 y deshabilita su oferta de 15 minutos. No modifica la hora ni la duracion de una cita ya reservada. Genera solo intervalos completos dentro del bloque: un remanente menor a 30 minutos no se ofrece. Un turno nuevo que cruce una reserva previa permanece oculto y no se puede reservar. Anular una reserva libera disponibilidad si el intervalo sigue siendo futuro y valido.

La reserva bloquea medico, especialidad, paciente y turno. Verifica cruces por medico y paciente; el indice unico parcial sigue protegiendo el mismo turno y el trigger tr_citas_evitar_cruces protege intervalos distintos. Las pruebas simultaneas con varias conexiones se deben ejecutar en PostgreSQL nativo.

## Estados y datos

Atendida cierra administrativamente la cita y guarda asistencia Asistio y responsable. No crea diagnosticos, consultas ni tratamientos. Esos modulos siguen pendientes. La migracion ajusta asistencias ya registradas sin inventar nuevos responsables ni horas.

Sexo permite no_especificado, femenino, masculino u otro; no se deduce de otros datos. Editar perfiles conserva la clave, rol e identificador de historia. CI/correo duplicados revierten toda la edicion.

## Semantica de los reportes

El periodo de citas se aplica al inicio del turno; el de pacientes a la fecha de registro en Bolivia. Los pacientes registrados no se filtran por medico, especialidad ni estado de cita. Se incluyen pacientes/medicos inactivos para conservar el historial.

Los totales se calculan con COUNT en toda la seleccion, no sobre veinte filas. Las citas canceladas o reprogramadas se cuentan como registros historicos de su propio estado; no como atenciones. Los listados de citas y pacientes tienen paginacion independiente. Cada respuesta lee una instantanea consistente de la base.

La exportacion PDF/CSV no se incluye en este avance. No se agregan estadisticas simuladas.
