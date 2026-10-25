# Informe academico y organizacion RUP

## Portada por completar

Proyecto Clinica Vida Salud, version 1.5. Completar integrantes, docente, asignatura, institucion, gestion y enlace real del repositorio. No se inventan nombres, fechas de aprobacion ni URL/QR de GitHub.

## Perfil y objetivos

Problema: coordinar disponibilidad, reservas y resultados manteniendo integridad, antecedentes y acceso por responsabilidades. Objetivo general: sistema de citas para cuatro roles. Objetivos especificos: registrar perfiles, generar turnos, evitar conflictos, conservar cambios, registrar atencion, ofrecer reportes, historia y auditoria. Alcance: administracion y registro clinico basico, no un sistema hospitalario completo.

## Marco teorico aplicado

Las capas separan HTTP, negocio y persistencia. PDO enlaza valores en sentencias preparadas. Transacciones confirman o revierten escrituras relacionadas; bloqueos e indices respaldan la comprobacion de disponibilidad. Triggers controlan integridad y registran eventos/versiones. Hashes verifican claves sin almacenarlas en texto. Autenticacion identifica; autorizacion delimita acciones y registros. Versiones optimistas evitan sobrescribir una correccion mas reciente.

## Negocio

Admin mantiene catalogos/disponibilidad; Recepcion atiende registro y cambios de citas; Paciente solicita turno y consulta lo propio; Medico recibe agenda y registra resultado/consulta. Flujo principal: programar, reservar, atender o registrar inasistencia y conservar historial. Cancelar libera oferta; reprogramar enlaza otra reserva. Bitacora acompana operaciones confirmadas sin copiar contenido clinico.

## Fases RUP

La tabla organiza entregables, sin inventar fechas o ceremonias no documentadas.

| Fase | Entregable | Criterio |
| --- | --- | --- |
| Inicio | Perfil, actores, requisitos y limites | Alcance explicito |
| Elaboracion | Casos, arquitectura y modelos | Riesgos de acceso, duplicidad y migracion definidos |
| Construccion | Capas, UI, migraciones y pruebas | Flujos implementados y evidencia documentada |
| Transicion | Manuales, respaldo, parche y defensa | Validacion nativa del grupo pendiente |

## Analisis, diseno e implementacion

[Requisitos](requisitos.md), [casos](casos-uso.md), [modelos](analisis-diseno.md) y [trazabilidad](trazabilidad.md) incluyen clases, modelo logico/fisico, secuencia, comunicacion y estados. El diccionario describe doce tablas reales. PHP/PDO/PostgreSQL y el frontend existente se conservan. 006 normaliza estado, 007 agrega bitacora y 008 completa tablas clinicas, sin reiniciar la BD. La paleta marina/turquesa/celeste se centraliza en CSS; los nuevos modulos reutilizan componentes.

## Pruebas y conclusiones

[Pruebas](../ai/pruebas.md) separa PHP WebAssembly/PGlite/Chromium de lo pendiente en Windows. El sistema sigue una cita hasta su resultado, conserva cambios y permite consulta/tratamientos atomicos. Administrar no concede automaticamente acceso a diagnosticos. No se inventan resultados de carga ni certificaciones.

## Recomendaciones

Antes de defender: ejecutar suites nativas, concurrencia y ensayo de respaldo/restauracion en BD desechable; completar portada y enlace real. Para otra etapa definir reglas de correccion final, recuperacion de acceso, consultorios y laboratorio. Publicar exige validar infraestructura y politicas de datos.

## Bibliografia

Fuentes oficiales consultadas el 24/10/2026:

- [PostgreSQL 18: Explicit Locking](https://www.postgresql.org/docs/18/explicit-locking.html).
- [PostgreSQL 18: CREATE TRIGGER](https://www.postgresql.org/docs/18/sql-createtrigger.html).
- [PHP: Prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php).
- [PHP: password_hash](https://www.php.net/manual/en/function.password-hash.php).

Fuentes del proyecto: documento academico y observaciones facilitados por el grupo, Sistema-Citas-Medicas-main(2).zip y referencia CADEFAR para organizacion/estilo SQL. No se incorporan entidades o stack ajenos a la clinica.
