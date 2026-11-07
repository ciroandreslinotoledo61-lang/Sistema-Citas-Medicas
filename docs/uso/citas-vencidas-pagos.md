# Cancelación, pagos y revisión automática de citas vencidas

## Por qué no aparecía «Cancelar cita»

La vista dependía de `puedeCancelar`, que el backend desactivaba al llegar la hora de inicio del turno. El botón no se había eliminado: quedaba oculto, incluso cuando era necesario explicar al usuario qué ocurría.

Ahora permanece visible en el detalle. Se habilita para una cita programada, con asistencia pendiente, antes del inicio y según permisos; si no corresponde, queda deshabilitado con motivo y orientación. Una consulta iniciada en sala o con registro clínico no se cancela ni reprograma. Las citas finales conservan su resultado y su historial.

Tampoco se ofrece ni permite marcar inasistencia manual a un paciente que ya tiene llegada o consulta registrada: el personal debe resolver la atención pendiente o la incidencia médica, no atribuir una ausencia al paciente presente.

Administración y Recepción tienen una acción diferenciada: **Registrar ausencia del médico y cancelar**. Exige motivo, solo admite citas todavía programadas y sin consulta iniciada/finalizada, y puede usarse aunque haya pasado el horario. Guarda `Ausencia del médico: ...`, mantiene la asistencia del paciente en `pendiente` y registra la incidencia en bitácora. No concede esta excepción al médico ni al paciente. Se reutiliza la ruta de cancelación con `tipoCancelacion=medico_ausente`, autorización backend y CSRF; la cancelación ordinaria anterior sigue funcionando.

Si ya se registró un resultado final —incluido `no_asistio`— no se sobrescribe para convertirlo en una cancelación. Una incidencia conocida después de ese cierre requiere revisión administrativa y, si se desea corregir estados finales, una política y autorización separadas. No se desactivaron triggers para sortear esa protección. El sistema no puede inferir la ausencia del profesional a partir de una llegada que nadie registró: Recepción debe registrar la presencia/incidencia oportunamente.

## Pagos: presentación, no cobros reales

Los textos normales muestran **Pagar ahora**, **QR**, **PayPal**, **Pendiente**, **Pagado** y **Cancelado**. Se retiraron banners y sufijos repetitivos. La confirmación mantiene una aclaración discreta: **Prueba académica: no se realizarán cargos reales**. El reporte administrativo conserva una nota pequeña para no confundirse con ingresos reales.

No se cambiaron los códigos `qr_demo`/`paypal_demo`, referencias `DEMO-`, tablas, importes, permisos, transacciones, deduplicación ni el indicador `esSimulado`. No se solicitan datos bancarios ni se conecta con proveedores. La tarifa sigue siendo `DEMO_TARIFA_BOB`, sin importe oficial predeterminado. Recetas, sala y selectores médicos `(#ID)` permanecen intactos.

## Mecanismo automático

`backend/scripts/revisar-citas-vencidas.php` es una **tarea CLI de una sola ejecución**, diseñada para un Cron Job externo. No se invoca al visitar páginas, iniciar sesión o listar citas; no necesita que el servicio web tenga usuarios activos.

En cada ejecución:

1. Verifica que estén disponibles sala, eventos, consultas, auditoría y los triggers existentes de citas. Si faltan, se detiene sin adivinar asistencia ni instalar SQL.
2. Obtiene la hora de PostgreSQL y la interpreta en `America/La_Paz`.
3. Revisa hasta 200 citas por lote, con `estado_cita=programada`, asistencia pendiente y fin de turno más tolerancia ya cumplido.
4. Si no hay llegada en sala **ni eventos previos de sala ni consulta**, cambia a `no_asistio` y registra asistencia `no_asistio`.
5. Si el paciente llegó pero sigue en espera, conserva la cita programada y registra **una sola incidencia de revisión administrativa**. No lo marca como ausente ni lo cancela automáticamente.
6. Excluye estados finales, pacientes en consulta/atendidos y cualquier consulta registrada.

La actualización usa los triggers actuales: genera el historial de cita con responsable nulo (identidad de sistema, presentada como **Sistema**) y la auditoría estándar. Añade un evento explícito `inasistencia_automatica` en bitácora con `codigo_rol=sistema`, sin inventar cuentas. Para presentes se usa `revision_paciente_presente`. No se guardan diagnósticos, nombres o credenciales en los logs del cron; solo contadores.

El lote es transaccional: estado, historial y auditoría se confirman juntos o se revierten. Un bloqueo asesor transaccional evita dos revisiones simultáneas; `FOR UPDATE ... SKIP LOCKED` evita competir por citas bloqueadas. Las escrituras revalidan estado, vencimiento, llegada y consulta. Sala y consultas usan el mismo bloqueo de cita. Una repetición no duplica cambios o incidencias. Si hay bloqueo/error de BD, el cron puede reintentar en su siguiente ejecución sin crear resultados parciales.

Los 200 registros no limitan el total histórico: un lote completo se informa con `loteCompleto=true`; los siguientes minutos procesan el resto. Supervisar ese indicador si hay un atraso grande. No se ejecutaron cambios en producción como parte del desarrollo.

La primera ejecución también puede encontrar programadas vencidas anteriores a la activación del cron. Revisar ese atraso en una copia aislada antes de habilitarlo: el sistema protege la asistencia **registrada**, pero no puede reconstruir llegadas físicas que nadie documentó. No se inventó una fecha de corte ni se reescribieron registros productivos durante estas pruebas.

## Variables y ejecución

- `CITAS_TOLERANCIA_MINUTOS`: entero de 0 a 10080; **30 por defecto**, contado desde el fin del turno, no desde su inicio.
- `CITAS_AUTOMATICAS_HABILITADAS=true`: habilitación explícita del proceso de cron.
- `APP_ENV=production` en el cron de Render; conexión explícita mediante `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` y `DB_SSLMODE` conforme al entorno autorizado.
- En `local/test`, el ejecutable solo admite loopback y una base terminada en `_pruebas`. Las pruebas usan PostgreSQL independiente y no leen `.env`.

Vista previa/ayuda, sin conectar:

```powershell
& C:/php85/php.exe backend/scripts/revisar-citas-vencidas.php
```

Ejecución real, **solo después de configurar y autorizar el destino**:

```text
php backend/scripts/revisar-citas-vencidas.php --aplicar
```

Sin `--aplicar` no abre una conexión. Sin la habilitación explícita no procesa citas. Lee variables del proceso, no archivos `.env`. El marcador de pruebas `VIDASALUD_TEST_LOCAL` no debe habilitarse en producción; el CLI omite `.env` mediante una constante interna, no activando el modo de pruebas.

## Configuración necesaria en Render (pendiente, no realizada)

**Sí: hay que crear/configurar un Cron Job separado** después de revisar/publicar estos cambios con autorización. Añadir este código al servicio web, por sí solo, no activa un programador.

1. Usar el mismo repositorio/versión revisada y Dockerfile, sin crear otra rama.
2. Schedule: **`* * * * *`** (cada minuto). Render interpreta el cron en UTC; al ejecutarse cada minuto no hace falta convertir horas. La comparación clínica se hace en hora boliviana.
3. En **Advanced → Docker Command**, sobrescribir el arranque de la imagen con:

   ```text
   php /var/www/app/backend/scripts/revisar-citas-vencidas.php --aplicar
   ```

   **No usar el CMD por defecto ni `deploy/iniciar.sh`: arrancan Apache y pueden incluir tareas de preparación.** Este job debe terminar al completar su lote y no debe ejecutar migraciones, seeds ni restablecimientos.
4. Configurar únicamente su conexión autorizada y las variables anteriores. No copiar secretos al repositorio. Para Neon, conservar SSL según la configuración real del proveedor, normalmente `DB_SSLMODE=require`; no usar la configuración sin SSL de pruebas locales.
5. Confirmar que el esquema aprobado de sala/auditoría ya esté instalado. Esta corrección no cambia migraciones ni estructura de Neon; si faltan las dependencias previas, detenerse y solicitar una decisión separada, no ejecutar migraciones automáticamente.
6. Revisar logs y la primera ejecución autorizada: `noAsistieron`, `requierenRevision`, `ocupado`, `loteCompleto` y `toleranciaMinutos`. Supervisar fallos del cron. La siguiente ejecución programada vuelve a intentar lo pendiente.

La tarea funciona aunque nadie tenga abierta una página y aunque no haya tráfico al servicio web. Su efectividad productiva depende de que el cron esté realmente configurado y ejecutándose. Una caída del programador/BD retrasa el procesamiento; no se promete ejecución exacta al segundo. Con frecuencia de un minuto, el cierre sucede a partir de fin+tolerancia y la siguiente ejecución disponible.

Según la [documentación de Render](https://render.com/docs/cronjobs), los Cron Jobs Docker admiten ese comando personalizado y tienen facturación propia (mínimo publicado de USD 1/mes por servicio al consultar la documentación). No se creó ningún servicio ni se incurrió en cargos desde esta sesión.

## Pruebas

```powershell
$env:VIDASALUD_TEST_LOCAL='1'
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php
node --experimental-vm-modules backend/tests/test_cancelacion_pagos.mjs
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin" --navegador
```

`test_citas_vencidas.php` crea otra base sintética dentro del clúster desechable y verifica ausente, presente, médico ausente, atendida, cancelada, sala en consulta, consulta registrada, margen configurable/límite, historial, bitácora, dos procesos simultáneos y cita bloqueada por otra transacción. No necesita ni usa la conexión de Neon.

`test_cancelacion_pagos.mjs` ejecuta el detalle real con DOM/API controlados y comprueba botón visible/deshabilitado, motivo/resolución, permisos de incidencia y etiquetas de pagos. Los recorridos Edge conservados verifican la cancelación ordinaria y los módulos nuevos con frontend/API/BD reales. Los resultados finales se incorporan al terminar la verificación.

## Resultados finales

Verificado con PHP 8.5.11, PostgreSQL 18 local, Node y Edge. No se utilizó PHP 8.0 ni se accedió a Neon. No se editaron `.env`, migraciones, Dockerfile o archivos de `deploy/`. Los servidores y clústeres propios terminaron detenidos.

| Prueba | Resultado |
| --- | --- |
| Conjunto completo PHP | **31 suites aprobadas** en una ejecución completa |
| Escenarios de cierre automático | **34 comprobaciones**, incluidos los seis escenarios requeridos, margen, CLI, historial, bitácora y dos procesos concurrentes |
| Configuración/permisos del cierre | 14 comprobaciones sin BD |
| HTTP privacidad, autenticación y recuperación | 111, 330 y 214 aprobadas; recuperación con correo simulado |
| HTTP módulos nuevos e incidencia médica | 80 solicitudes en la suite completa; 82 en el recorrido independiente, que prepara su propia cancelación inicial |
| Contrato HTTP / configuración de despliegue | 137 comprobaciones / 10 casos aprobados |
| Edge, citas y recuperación anteriores | 35 + 31 comprobaciones aprobadas |
| Edge, recetas/sala/pagos actualizados | **22 comprobaciones** con frontend/API/BD reales, a 320, 390 y 1440 px |
| JS, cancelación y presentación de pagos | 11 comprobaciones sin red |
| JS, formularios / selectores existentes | 41 / 15 aprobadas |
| Sintaxis de archivos cambiados | 21 PHP y 6 JS/MJS correctos |
| Git | `git diff --check` correcto, misma rama, sin staging/commit/push/despliegue |

Evidencias excluidas de Git: `.tmp-auth/ejecucion-php-4c6500659358/`, `.tmp-auth/suites-php-3b614d9f04bd/`, `.tmp-auth/ejecucion-php-b154ce8dd377/` y capturas `.tmp-auth/navegador-783ffe56-279f-47d0-a982-cf6d0cadf094/`. No quedan fallos comprobados en estos recorridos. Pendiente: revisión humana de interfaz/accesibilidad y configuración/prueba productiva del cron **con autorización**.

## Archivos de esta corrección

**Nuevos:**
- `backend/scripts/revisar-citas-vencidas.php`
- `backend/src/business/services/CitasVencidasService.php`
- `backend/src/data/repositories/CitasVencidasRepository.php`
- `backend/src/shared/config/ConfiguracionCitasVencidas.php`
- `backend/tests/test_citas_vencidas.php`
- `backend/tests/test_validaciones_cierre.php`
- `backend/tests/test_cancelacion_pagos.mjs`
- `docs/uso/citas-vencidas-pagos.md`

**Modificados:**
- `backend/src/bootstrap.php`: modo CLI con variables de proceso sin leer `.env`.
- `backend/src/business/services/CitaService.php`, `BitacoraService.php`: cancelación, incidencia médica, restricciones de asistencia y auditoría.
- `backend/src/data/repositories/CitaRepository.php`, `backend/src/data/models/Cita.php`: contexto operativo y presentación del actor de sistema.
- `backend/src/shared/constants/PermisosSistema.php`: incidencia del médico solo para Administración/Recepción.
- `backend/src/business/services/PagoDemostracionService.php`, `backend/src/presentation/controllers/PagoDemostracionController.php`: mensajes de pagos, sin cambiar cobros/estados internos.
- `frontend/src/features/citas/pages/detalle-cita.php`, `detalle-cita.js`: botón, explicación y confirmación de incidencia médica.
- `frontend/src/features/pagos/pages/pagos.php`, `pagos.js`, `formulario-pago.php`, `formulario-pago.js`; `frontend/src/features/pagos/services/pagos-api.js`: presentación profesional y aclaración de prueba.
- `frontend/src/app/rutas.php`, `frontend/src/components/navegacion.php`: títulos y menú Pagos.
- `backend/tests/ejecutar-pruebas.php`, `test_modulos_http.php`, `test_paciente_navegador.mjs`: nuevas pruebas y regresiones actualizadas.
- `AGENTS.md`, `README.md`, `docs/uso/pruebas-php.md`, `docs/uso/recetas-sala-pagos.md`: reglas, comandos y referencias.

No se eliminaron archivos ni funcionalidades de recetas, sala o pagos. No hay migraciones nuevas.
