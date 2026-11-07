# Recetas, sala de espera y pagos de demostración

## Alcance y permisos

| Módulo | Médico | Paciente | Recepción | Administración |
| --- | --- | --- | --- | --- |
| Recetas | Emite, consulta, anula y corrige las de sus consultas | Solo recetas propias | Sin acceso | Sin acceso |
| Sala | Citas asignadas, iniciar/finalizar atención | Sin acceso | Consulta y registra llegada | Solo consulta operativa |
| Pagos demo | Sin acceso | Crea/confirma/cancela intentos propios | Consulta y reporte simulado | Consulta y reporte simulado |

Son permisos nuevos, no una ampliación del acceso clínico administrativo ni cambios en permisos anteriores de citas. Autenticación y CSRF se conservan. Las relaciones se verifican con IDs y sesión en backend, no con nombres ni botones. Los selectores médicos mantienen los números `(#ID)` sin cambios.

### Recetas

Desde el detalle de consulta: **Emitir receta**. Hasta 20 medicamentos con dosis, frecuencia, duración e indicaciones. Los tratamientos ya registrados no se convierten automáticamente en recetas ni se sobrescriben.

Cada receta referencia consulta, paciente y médico. Guarda una instantánea de nombres, CI, matrícula y datos de clínica al emitir. Los cambios posteriores en perfiles o consultas no reescriben el documento emitido. Solo el autor puede anular con motivo obligatorio; el original se conserva. Para corregir, anular y usar **Emitir corrección**, que enlaza una nueva receta con la anterior; una receta anulada solo admite una sustitución directa, pero puede continuar una cadena de correcciones. No hay edición ni borrado desde la API. Triggers impiden modificaciones silenciosas.

El paciente dispone de **Mis recetas** y del enlace desde consulta/historial. El listado está paginado y el detalle/PDF se limita al paciente titular o médico autor. Otros médicos no reciben acceso a recetas ajenas por el solo hecho de estar asignados al mismo paciente.

PDF real descargable, sin servicios externos ni paquetes adicionales: texto multipágina, datos de clínica/paciente/médico, fecha, medicamentos, anulación y vínculo de corrección. `Cache-Control: no-store`; acceso autenticado incluso al cambiar IDs. Usa Helvetica/WinAnsi, compatible con acentos españoles; caracteres no representables provocan un error explícito, **no una sustitución silenciosa**. El original UTF-8 sigue consultable en pantalla. Una fuente Unicode embebida queda como mejora de compatibilidad.

**No tiene firma digital certificada ni acredita por sí mismo validez legal o habilitación sanitaria.** Debe revisarse el formato y el procedimiento profesional antes de uso clínico real. Un PDF descargado antes de anular no puede retirarse del dispositivo del destinatario; consultar el estado vigente en el sistema.

### Sala de espera

Sin fila nueva, una cita programada aparece como **pendiente de llegada**. Recepción registra **en espera**; el médico asignado pasa a **en consulta** y después a **atendido**, desde el inicio de la cita. Se bloquea la cita y se exige la versión actual para evitar acciones simultáneas o sobre una pantalla desactualizada.

La sala tiene estado propio: no reemplaza `estado_cita` ni inventa nuevos estados de citas. Finalizar registra la asistencia y pasa la cita a `atendida`. Registrar consulta o marcar atendida desde los flujos existentes sincroniza automáticamente la sala si ya había llegada. Si nunca hubo llegada, una cita atendida se muestra atendida, sin reconstruir eventos ficticios. Una cancelación/reprogramación/inasistencia conserva el último estado operativo y registra el evento de cita; no permite nuevas transiciones de sala. La tabla muestra también el estado de la cita para distinguir casos cerrados.

Los eventos son inmutables e incluyen actor y fecha. La interfaz es privada: muestra nombres, cita, hora y estados; nunca diagnóstico, motivo, medicamentos o antecedentes. No se creó una pantalla pública.

Una cita que ya está **en consulta** no puede marcarse como inasistencia, cancelarse o reprogramarse: la defensa SQL también protege los flujos anteriores de citas y devuelve conflicto sin modificar el registro. No se retiran permisos generales; se evita un resultado contradictorio con una atención iniciada.

**Políticas pendientes de definición:** ventana horaria/día permitido para registrar llegada, tardanzas, prioridad, reingreso y límite de consultas simultáneas por médico. No se inventaron esas reglas: hoy Recepción puede registrar llegada de cualquier cita programada; la atención médica no empieza antes del horario. La vista abre por defecto el día actual y permite consultar otra fecha.

### Pagos simulados

El paciente abre una cita propia → **Pagar ahora · Demostración**, selecciona QR o PayPal de demostración y prepara un intento pendiente. Desde **Pagos · Demostración** confirma o cancela explícitamente la simulación. QR es una representación textual **no escaneable**; no se genera un QR bancario. PayPal no solicita usuario/contraseña, abre páginas externas ni invoca APIs. Toda la interfaz advierte que no hay cobros reales.

Se guardan monto en centavos enteros BOB, método, referencia aleatoria `DEMO-`, fecha de creación, confirmación/cancelación y estado. El importe procede del servidor y no se acepta desde HTTP. Una cita solo puede tener un intento pendiente o pagado; repetir la preparación devuelve el pendiente. Cancelarlo permite crear otro intento conservando el anterior. Confirmar dos veces genera conflicto, no otro cobro. Un pagado es final; no se implementan reembolsos ni reversión de pagos pagados.

Se admiten simulaciones sobre citas `programada` o `atendida`. Una cita cancelada/reprogramada/no asistida no permite preparar o confirmar un pago; un pendiente aún puede cancelarse. Cancelar una cita no borra ni transforma una simulación pagada y el pago no cambia los estados médicos. Son registros separados, sin efectos financieros ni bloqueos sobre reservas.

Los reportes por fechas/método usan exclusivamente `pagos_demostracion`, `es_simulado=true` y estado `pagado`. No se mezclan con reportes administrativos previos ni con ingresos reales. No hay integración fiscal, factura, cobro bancario o reconciliación.

## Configuración adicional

No se editaron `.env` ni secretos. Variables del proceso, a configurar **solo cuando el propietario lo decida**:

- `DEMO_TARIFA_BOB`: importe de demostración positivo con hasta dos decimales. **Sin valor por defecto**; si falta, se deshabilita preparar nuevos pagos. Cambiarlo afecta intentos nuevos, no los anteriores. Valores de pruebas automáticas son sintéticos y no constituyen tarifas oficiales.
- `CLINICA_NOMBRE`: por defecto Clínica Vida Salud.
- `CLINICA_DIRECCION`, `CLINICA_TELEFONO`: opcionales; no se inventaron datos de contacto. Los valores se capturan al emitir cada receta.

No hacen falta claves QR, PayPal ni nuevas dependencias. Resend continúa fuera de estos módulos y no se activó.

## Migraciones y ejecución segura

Conservar los archivos 001–010. Después de una base con esas migraciones, aplicar en orden:

1. `backend/src/data/migrations/011_recetas_medicas.sql`
2. `backend/src/data/migrations/012_sala_espera.sql`
3. `backend/src/data/migrations/013_pagos_demostracion.sql`

No contienen `UPDATE`, `DELETE` o poblado sobre registros previos al instalarse; crean tablas, índices, funciones y triggers. Las funciones contienen operaciones para cambios **futuros**. Se prueba que los datos existentes permanecen iguales. No se ejecutaron en Neon.

Vista previa, sin conexión y sin cargar `.env`:

```powershell
& C:/php85/php.exe backend/scripts/actualizar-recetas-sala-pagos.php
```

Para aplicar solo en una base **local independiente de pruebas** con 001–010, configurar en una terminal dedicada `APP_ENV=local`, `DB_HOST=127.0.0.1`, `DB_PORT`, `DB_NAME` terminado en `_pruebas`, `DB_USER`, `DB_PASS` y `DB_SSLMODE=disable`, utilizando credenciales locales fuera del repositorio. Después:

```powershell
& C:/php85/php.exe backend/scripts/actualizar-recetas-sala-pagos.php --aplicar
```

El script exige variables explícitas del proceso, no lee `.env`, rechaza producción/hosts remotos, verifica el destino real y aplica 011–013 juntas en una transacción. Si ya existe una tabla de esos módulos, rechaza la repetición sin cambios; no intenta adivinar un esquema parcialmente instalado. No usar túneles a producción. **No ejecutar este comando contra Neon ni repetir 009/010.**

No hay migración automática al desplegar. Antes de una futura publicación autorizada, revisar respaldo/restauración aislada, versión de esquema y plan de instalación de 011–013. El código nuevo requiere esas tablas.

## API y arquitectura

- `GET/POST /api/recetas`; `GET /api/recetas/{idReceta}`; `GET .../pdf`; `PATCH .../anulacion`.
- `GET /api/sala-espera?fecha=YYYY-MM-DD&pagina=1`; `PATCH /api/sala-espera/{idCita}` con `accion` y `version`; `GET .../historial`.
- `GET/POST /api/pagos-demo`; `GET .../configuracion`; `GET .../reporte?fechaDesde=...&fechaHasta=...`; `GET/PATCH /api/pagos-demo/{idPago}`.
- Validadores y reglas en `business/`, SQL en repositorios, entidades en modelos, respuestas/PDF en controladores/utilidades y vistas/servicios JS por módulo. No se reorganizó la arquitectura.
- Formularios de creación limpian tras confirmación del servidor y muestran éxito; al fallar se conservan. Las ediciones no se resetean. Los formularios contextuales de cita/consulta conservan su vínculo y bloquean un segundo alta; la reserva sigue navegando al detalle. Los listados se cargan del backend al abrirse o actualizarse, sin caché local de registros.

## Pruebas y revisión manual

```powershell
$env:VIDASALUD_TEST_LOCAL='1'
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php
node --experimental-vm-modules backend/tests/test_formularios.mjs
node --experimental-vm-modules backend/tests/test_filtros_listados.mjs
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin" --navegador

# Solo módulos nuevos, en otro clúster desechable
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --solo-modulos --pg-bin "C:/Program Files/PostgreSQL/18/bin"
# Solo HTTP e interfaz nueva en Edge, sin repetir suites PHP
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --solo-modulos --solo-interfaz --navegador --pg-bin "C:/Program Files/PostgreSQL/18/bin"
```

El ejecutor crea su propio PostgreSQL, datos sintéticos, servidores y variables; no lee `.env`, Neon o datos reales. Incluye IDs manipulados/roles/CSRF por HTTP, PDF binario privado, transiciones de sala, inmutabilidad de recetas, tarifas, conservación de datos anteriores y procesos concurrentes independientes para confirmación de pago/llegada. Las pruebas JavaScript ejecutan módulos reales con DOM/API controlados para creación/error/edición/doble envío y selectores existentes. El marcador `VIDASALUD_TEST_LOCAL` es exclusivo de procesos aislados; no habilitarlo en producción.

Antes del despliegue revisar manualmente: receta multipágina y anulación/corrección, PDF impreso, visualización responsive/accesible de las tres pantallas, actualización de sala entre dos sesiones y mensajes de conflicto, flujo QR/PayPal claramente simulado y reporte con rango de fechas. Los recorridos Edge anteriores no equivalen por sí solos a una aprobación visual de módulos nuevos. Resultados reales y limitaciones se registran al terminar en esta guía.

## Resultados de esta ampliación

Entorno: Windows, PHP 8.5.11 nativo, PostgreSQL 18, Node y Edge. Sin PHP 8.0, lectura de `.env`, conexiones a Neon ni APIs reales de pago/correo. Los clústeres y servidores propios quedaron detenidos.

| Verificación | Resultado real |
| --- | --- |
| Suites PHP existentes y nuevas | 29 aprobadas entre ejecución inicial y repeticiones focalizadas |
| Contrato de rutas | 74 verificadas: 60 conservadas + 14 nuevas privadas |
| HTTP nuevos módulos | 75 solicitudes verificadas: roles, IDs, CSRF, PDF y ambos métodos de pago |
| HTTP privacidad / autenticación / recuperación simulada | 111 / 330 / 214 comprobaciones aprobadas |
| Contrato HTTP anterior / configuración de despliegue | 137 comprobaciones / 10 casos aprobados |
| Validaciones, tarifas y PDF multipágina | 37 comprobaciones aprobadas |
| Servicios de recetas, sala y pagos | 41 comprobaciones aprobadas |
| Carreras reales de pago y llegada | Una aceptación y un conflicto en cada caso, desde dos procesos independientes |
| Migraciones y actualizador | Conservación de usuarios, citas, consultas y tratamientos anteriores; destinos inseguros/repetición rechazados; sin poblado retroactivo; ausencia durante consulta rechazada |
| Formularios JS reales con DOM/API controlados | 41 comprobaciones de éxito, error, edición y doble envío |
| Selectores/filtros JS anteriores | 15 aprobadas; IDs médicos conservados |
| Edge: módulos nuevos con frontend/API/BD reales | 20 comprobaciones: emisión/anulación de receta, PayPal simulado y llegada; 320/390/1440 px sin desbordamiento |
| Edge: recorridos anteriores | 35 de citas + 31 de recuperación aprobadas |
| Sintaxis | 46 PHP y 22 JS/MJS modificados/nuevos correctos |
| Referencias | 115 imports relativos del frontend resueltos; rutas/includes por contrato y vistas HTTP |
| Git | Sin cambios en 001–010, `.env`, Dockerfile o `deploy/`; sin eliminaciones, commits ni despliegue; `git diff --check` correcto |

No fue una única ejecución completa aprobada de primera pasada. Se corrigieron una comparación de objetos JSONB en la prueba, el tamaño SQL insuficiente para `paypal_demo` y una variable reutilizada en el ensayo reforzado de migraciones. Se verificaron también el rechazo explícito de caracteres no representables en PDF, la protección frente a insertar pagos ya confirmados y la coherencia de sala. Después se ejecutaron las pruebas focalizadas correspondientes sobre bases nuevas, sin repetir todas las suites independientes aprobadas.

Evidencias excluidas de Git: `.tmp-auth/ejecucion-php-c4302d3ff171/` y `.tmp-auth/suites-php-1f2a1e173de2/` (regresión inicial), `.tmp-auth/ejecucion-php-4edce7363319/` (módulos), `.tmp-auth/ejecucion-php-f1b8e8f30492/` (interfaz) y capturas `.tmp-auth/navegador-7f0b096f-33e3-462e-b6f3-ef21d314bf14/`. Las verificaciones finales del actualizador y de ausencia contradictoria constan también en la salida de la sesión.

Pendiente: revisión humana del PDF impreso y su validez profesional/legal, accesibilidad asistida, políticas operativas aún no definidas y smoke test productivo autorizado. PDF Unicode fuera de WinAnsi requiere una fuente embebida; actualmente se informa el error sin alterar el original. No se afirma certificación clínica o de pagos.

## Inventario de archivos

**45 creados, 33 modificados, ninguno eliminado.** Los nombres son relativos a la carpeta indicada.

### Creados

| Carpeta | Archivos |
| --- | --- |
| `backend/scripts/` | `actualizar-recetas-sala-pagos.php` |
| `backend/src/business/services/` | `RecetaService.php`, `SalaEsperaService.php`, `PagoDemostracionService.php` |
| `backend/src/business/validators/` | `RecetaValidator.php`, `SalaEsperaValidator.php` |
| `backend/src/data/migrations/` | `011_recetas_medicas.sql`, `012_sala_espera.sql`, `013_pagos_demostracion.sql` |
| `backend/src/data/models/` | `Receta.php`, `EstadoSala.php`, `PagoDemostracion.php` |
| `backend/src/data/repositories/` | `RecetaRepository.php`, `SalaEsperaRepository.php`, `PagoDemostracionRepository.php` |
| `backend/src/presentation/controllers/` | `RecetaController.php`, `SalaEsperaController.php`, `PagoDemostracionController.php` |
| `backend/src/presentation/routes/` | `RutasRecetas.php`, `RutasSalaEspera.php`, `RutasPagosDemostracion.php` |
| `backend/src/shared/` | `config/ConfiguracionPagosDemo.php`, `utils/RecetaPdf.php` |
| `backend/tests/` | `test_formularios.mjs`, `test_modulos_validacion.php`, `test_modulos_atencion.php`, `test_modulos_migraciones.php`, `test_modulos_concurrencia.php`, `test_modulos_http.php` |
| `frontend/src/features/recetas/` | `services/recetas-api.js`; `pages/lista-recetas.php`, `pages/listar-recetas.js`, `pages/formulario-receta.php`, `pages/formulario-receta.js`, `pages/detalle-receta.php`, `pages/detalle-receta.js` |
| `frontend/src/features/sala-espera/` | `services/sala-api.js`, `pages/sala-espera.php`, `pages/sala-espera.js` |
| `frontend/src/features/pagos/` | `services/pagos-api.js`, `pages/pagos.php`, `pages/pagos.js`, `pages/formulario-pago.php`, `pages/formulario-pago.js` |
| `docs/uso/` | `recetas-sala-pagos.md` |

### Modificados

| Carpeta | Archivos |
| --- | --- |
| Raíz | `AGENTS.md`, `README.md` |
| `backend/src/` | `bootstrap.php`, `business/validators/ValidarCamposFormulario.php`, `presentation/http/FabricaControladores.php`, `presentation/routes/RutasApi.php`, `shared/constants/PermisosSistema.php` |
| `backend/tests/` | `autenticacion_aislada.php`, `ejecutar-pruebas.php`, `test_arquitectura.php`, `test_autenticacion_http.php`, `test_paciente_navegador.mjs`, `contratos/rutas.json`, `http/ClienteHttpPruebas.php` |
| `frontend/src/` | `api/cliente-api.js`, `app/configuracion.php`, `app/rutas.php`, `components/formularios.js`, `components/navegacion.php` |
| `frontend/src/features/citas/pages/` | `detalle-cita.php`, `detalle-cita.js`, `formulario-cita.js` |
| `frontend/src/features/consultas/pages/` | `detalle-consulta.php`, `detalle-consulta.js`, `formulario-consulta.js` |
| `frontend/src/features/especialidades/pages/` | `formulario-especialidad.js` |
| `frontend/src/features/medicos/pages/` | `formulario-medico.js` |
| `frontend/src/features/pacientes/pages/` | `registrar-paciente.js` |
| `frontend/src/features/usuarios/pages/` | `formulario-usuario.js` |
| `frontend/src/features/horarios/pages/` | `formulario-horario.js` |
| `docs/` | `refactorizacion/ENDPOINTS.md`, `uso/despliegue.md`, `uso/pruebas-php.md` |
