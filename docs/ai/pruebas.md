> Actualización 24/10/2026: Los resultados de esta entrega, incluidos PHP/PDO, PostgreSQL nativo y concurrencia entre procesos, están en [VERIFICACION.md](../refactorizacion/VERIFICACION.md). Las limitaciones de PGlite descritas más abajo corresponden a la entrega anterior.

# Pruebas de la version 1.4

## Entorno usado y limites

Revision de esta entrega: PHP 8.2 WebAssembly, codigo real de servicios/repositorios y SQL en PostgreSQL embebido PGlite mediante un adaptador PDO exclusivo de QA. Chromium uso las vistas PHP renderizadas y JavaScript/CSS del proyecto, con solicitudes a la API PHP real y sesiones de los cuatro roles. Se usaron datos ficticios. El adaptador y las herramientas temporales no se distribuyen ni forman parte del proyecto.

No se ejecuto Apache/XAMPP, el cliente cURL del frontend ni PDO nativo contra un servidor PostgreSQL con conexiones independientes. La prueba de concurrencia se entrega implementada y revisada por sintaxis, pero no se declara aprobada. Los resultados de entregas anteriores no se presentan como nuevas ejecuciones.

## Resultados ejecutados

| Prueba | Resultado observado |
| --- | --- |
| test_validaciones.php | Aprobada: entradas, normalizacion, roles y fechas |
| test_permisos.php | Aprobada: permisos, datos publicos, hash y CSRF |
| test_validaciones_especialidades_medicos.php | Aprobada: datos y permisos de catalogo |
| test_validaciones_agenda.php | Aprobada: 95 comprobaciones de agenda y matriz de permisos |
| test_validaciones_actualizacion.php | Aprobada: 15 comprobaciones |
| test_registro_paciente.php | Aprobada: usuario, paciente, historia y rollback |
| test_gestion_especialidades_medicos.php | Aprobada: altas/edicion/estados y sesiones |
| test_gestion_citas.php | Aprobada: 49 comprobaciones, incluida reprogramacion atomica |
| test_reportes_perfiles.php | Aprobada: 30 comprobaciones; 22 citas historicas sin varias activas, dos paginas y filtros |
| test_programacion_periodos.php | Aprobada: 96 comprobaciones de generacion, reservas, estados, permisos y auditoria |
| test_migracion_005.php | Aprobada: 15 comprobaciones sobre datos legados y repeticion |
| Sintaxis PHP y JavaScript | 113 archivos PHP revisados con TOKEN_PARSE y 30 modulos JS con node --check |
| Navegador | Programar mes, repetir, reservar, bloquear segunda cita en Paciente/Recepcion, cancelacion medica, atencion, inasistencia e historial |
| Seguridad HTTP | 403 por rol, 404 por cita ajena, 409 por estado/reserva y 419 sin CSRF |
| Responsive | Seis vistas a 1440, 768, 390 y 320 px; sin overflow de pagina ni excepciones JS; tablas desplazables |
| test_reserva_concurrente.php | Pendiente de ejecucion con PHP CLI/PDO PostgreSQL nativos |

## Cobertura solicitada

| Escenario | Prueba |
| --- | --- |
| Mes completo y solo dias elegidos | test_programacion_periodos |
| No generar fechas/horas pasadas | test_programacion_periodos y test_validaciones_agenda |
| Limites 08:00-17:00 y minutos 00/30 | test_programacion_periodos; selects verificados en navegador |
| Dieciocho turnos consecutivos; media jornada con ocho | test_programacion_periodos verifica cada inicio/fin |
| Duplicados y superposicion | Repetir periodo y cruzar bloques; tests de agenda diaria |
| Una sola activa, incluso otro medico sin cruce | test_programacion_periodos, SQL directo y navegador |
| Liberar regla tras cancelacion/atencion/inasistencia | test_programacion_periodos; incluye atencion antes del fin nominal |
| Medico atiende y registra inasistencia | Servicio y navegador; inicio/fin validados |
| Cancelacion medica con motivo obligatorio | Servicio rechaza vacio, formulario required y envio real |
| Prohibir cambios de citas ajenas | Servicio y API 404 |
| Estados finales e historial | Rechazo por servicio/SQL, actor/tiempo y conservacion de citas |
| Recepcion conserva asistencia e inasistencia | test_programacion_periodos y test_gestion_citas |
| Movil | Horarios, reserva de paciente y recepcion, Mi agenda, detalle y reportes |

## Ejecutar en Windows

Desde la raiz del proyecto. Requiere PHP 8.2+, pdo_pgsql y las extensiones indicadas en README.

Pruebas sin base:

```powershell
php backend/tests/test_validaciones.php
php backend/tests/test_permisos.php
php backend/tests/test_validaciones_especialidades_medicos.php
php backend/tests/test_validaciones_agenda.php
php backend/tests/test_validaciones_actualizacion.php
```

Para integracion, usa una base de pruebas EXISTENTE y aislada cuyo nombre termine en _pruebas, con migraciones 001 a 005 y datos iniciales de prueba. No se crea ninguna base automaticamente. Si no tienes ese entorno, no ejecutes estos scripts contra la base de trabajo. El seeder nunca debe ejecutarse en una base con usuarios.

Las variables del proceso tienen prioridad sobre .env. Este ejemplo cambia solo el entorno de esta terminal y lo restaura al terminar; sustituye el nombre por el de tu base de pruebas. Comprueba que DB_HOST/DB_USER/DB_PASS correspondan tambien al entorno de pruebas.

```powershell
$baseAnteriorClinica = [Environment]::GetEnvironmentVariable('DB_NAME', 'Process')
try {
    $env:DB_NAME = 'citas_medicas_pruebas'
    php backend/scripts/verificar-entorno.php --conexion
    if ($LASTEXITCODE -ne 0) { throw 'Conexion de pruebas no disponible.' }
    # Si tu base de pruebas ya tiene 004, aplica solo la nueva migracion:
    php backend/scripts/actualizar-programacion-mensual.php
    if ($LASTEXITCODE -ne 0) { throw 'Fallo la migracion de la base de pruebas.' }
    $pruebasClinica = @(
        'test_registro_paciente.php',
        'test_gestion_especialidades_medicos.php',
        'test_gestion_citas.php',
        'test_reportes_perfiles.php',
        'test_programacion_periodos.php',
        'test_migracion_005.php',
        'test_reserva_concurrente.php'
    )
    foreach ($pruebaClinica in $pruebasClinica) {
        php "backend/tests/$pruebaClinica"
        if ($LASTEXITCODE -ne 0) { throw "Fallo: $pruebaClinica" }
    }
} finally {
    if ($null -eq $baseAnteriorClinica) {
        Remove-Item Env:DB_NAME -ErrorAction SilentlyContinue
    } else { $env:DB_NAME = $baseAnteriorClinica }
}
```

SoportePruebasAgenda verifica el sufijo _pruebas. Los tests crean sus propios usuarios, medicos, pacientes y citas, y limpian solo esos datos. Ejecuta los scripts en serie: test_gestion_citas agrega una restriccion temporal de prueba para comprobar rollback y la retira en finally. test_migracion_005 crea un esquema aleatorio dentro de la base de pruebas, aplica 001-004 alli, prueba 005 dos veces y elimina solo ese esquema; necesita permiso CREATE SCHEMA. No toca el esquema public de la aplicacion.

Los tests de turnos y periodos esperan la configuracion inicial: duracion 30 y clinica 08:00-17:00. Si tu entorno la modifico, usa esos valores en variables de esta terminal de pruebas y restablecelos despues.

## Concurrencia nativa pendiente

Ejecuta test_reserva_concurrente.php en CLI con proc_open habilitado. Inicia dos procesos PHP independientes, sincroniza su salida a reservar con una barrera y verifica:

1. Dos pacientes intentan ocupar el mismo turno: exactamente un 201 y un 409.
2. Un paciente intenta reservar dos turnos de medicos distintos, sin cruce de horas: exactamente un 201 y un 409.

El script cancela las reservas de prueba y elimina sus propios datos. No necesita dos navegadores ni usa el servidor php -S; este ultimo, con un unico proceso, no demuestra concurrencia real.

Queda por verificar tambien en PostgreSQL nativo: programaciones simultaneas superpuestas, reprogramacion mientras otro proceso toma el destino, cancelacion concurrente con reserva y migracion sobre una copia del volumen real. El entorno embebido permite probar reglas, indices y rollback, pero no esos bloqueos entre conexiones.

## Revision manual de instalacion

Sigue el recorrido de ACTUALIZAR-ESTA-VERSION.md. En Windows verifica cookies/sesion, ambos .env preservados, cURL del frontend, funcionamiento Apache/XAMPP si lo utilizas y reporte del script 005. No modifiques fechas ni estados de datos reales para probar asistencia. Solo despues de revisar los resultados prepara commits manuales.
