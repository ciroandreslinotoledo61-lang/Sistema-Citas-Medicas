# Verificación de la refactorización

Fecha: 24 de octubre de 2026. Se comparó la entrega con el ZIP original proporcionado. Los resultados describen las comprobaciones realizadas; no equivalen a certificar cualquier combinación de datos o una instalación de producción.

## Entorno de ejecución

- PHP CLI 8.3.6, con PDO PostgreSQL, mbstring, curl, session y ctype.
- PostgreSQL 16.15 nativo en una instancia local desechable, con conexiones PDO y procesos PHP independientes.
- Bases separadas de pruebas para el original y el refactorizado; migraciones 001–005 y cuentas sintéticas del seeder.
- Servidores integrados PHP separados para frontend y backend durante el recorrido HTTP.
- Node se usó solamente para verificar sintaxis JavaScript; no es una dependencia de ejecución del proyecto.
- El contenedor de pruebas necesitó adaptar la identidad de usuario del proceso PostgreSQL. Esa adaptación solo pertenece al entorno temporal de verificación; no se distribuye con el proyecto ni es un requisito para Windows.

## Resultados de código y conservación

| Comprobación | Resultado |
| --- | --- |
| Sintaxis PHP | 137 archivos comprobados; cero errores |
| Sintaxis JavaScript | 30 archivos comprobados; cero errores |
| Importaciones relativas de JavaScript | Todas resueltas a archivos existentes |
| Sintaxis Python de las pruebas HTTP opcionales | 2 archivos comprobados; cero errores |
| Frontend completo | 65 de 65 archivos idénticos al original por comparación de bytes y SHA-256 |
| Migraciones SQL | 001–005 idénticas al original |
| Archivos originales eliminados | 0 |
| Rutas declaradas | 48 combinaciones HTTP/ruta únicas; firmas y argumentos de sus controladores comprobados |
| Límites de capas | Sin superglobales HTTP o SQL directo en servicios; sin repositorios/SQL en controladores; sin negocio en rutas; sin decisión de roles o cálculo de fechas en repositorios |
| Formato del backend | Sin errores PSR-12 detectados; permanecen avisos de longitud de línea, principalmente consultas SQL y expresiones declarativas |

Las líneas largas no son errores de sintaxis ni incumplimientos de límites entre capas. No se añade PHP_CodeSniffer como dependencia necesaria del sistema.

## Pruebas PHP ejecutadas

Las doce pruebas originales se ejecutaron contra el código original y el refactorizado. La ejecución final del refactorizado incluye dos pruebas nuevas: arquitectura y atomicidad entre capas. **Las 14 finalizaron correctamente.**

| Archivo | Cobertura y resultado |
| --- | --- |
| `test_arquitectura.php` | Nueva: 48 rutas, unicidad, argumentos, métodos públicos y dependencias entre capas. OK |
| `test_validaciones.php` | Entradas, normalización, roles, fechas y tipos. OK |
| `test_permisos.php` | Permisos, datos públicos, hash y token CSRF. OK |
| `test_validaciones_especialidades_medicos.php` | Especialidades, médicos, normalización y tipos. OK |
| `test_validaciones_agenda.php` | 95 comprobaciones de fechas, intervalos, entradas y matriz de permisos. OK |
| `test_validaciones_actualizacion.php` | 15 rechazos de entradas inválidas, estado booleano y duración predeterminada. OK |
| `test_registro_paciente.php` | Usuario/paciente/historia, rol fijo y rollback por CI duplicada. OK |
| `test_gestion_especialidades_medicos.php` | Altas, edición, duplicados, estados, especialidad activa y sesiones invalidadas. OK |
| `test_gestion_citas.php` | 49 comprobaciones de agenda, alcance, reservas y transacciones. OK |
| `test_reportes_perfiles.php` | 30 comprobaciones de reportes, perfiles, atención y compatibilidad de duraciones. OK |
| `test_programacion_periodos.php` | 96 comprobaciones de períodos, reservas, estados, permisos y trazabilidad. OK |
| `test_migracion_005.php` | 15 comprobaciones de repetición de migración, datos legados y protección SQL. OK |
| `test_reserva_concurrente.php` | Dos pacientes compiten por un turno; un paciente compite por turnos de dos médicos. En cada escenario: un 201 y un 409. OK |
| `test_transacciones_capas.php` | Nueva: un trigger de prueba hace fallar el segundo turno; no queda horario parcial. Se comprueba un reintento correcto, READ COMMITTED y reporte de solo lectura. OK |

La prueba de concurrencia usa dos procesos PHP y conexiones PDO distintas que esperan una misma barrera. No se sustituyó por dos llamadas consecutivas ni por SQLite/PGlite. La prueba de atomicidad añade un trigger exclusivamente para sus datos de prueba y lo elimina en `finally`.

## Comparación HTTP original / refactorizado

Se realizó el mismo escenario sobre instalaciones separadas del original y el refactorizado:

- 137 comprobaciones HTTP registradas por versión, más verificaciones auxiliares de consistencia.
- Cobertura de las 48 combinaciones de método HTTP y endpoint.
- Se compararon códigos, estructura y contenido JSON; se normalizaron tokens CSRF y marcas temporales de auditoría. Resultado final: **0 diferencias en ese escenario**.
- Autenticación con los cuatro roles; registro público e interno; rol Paciente impuesto por el servidor.
- CRUD disponible de usuarios, pacientes, médicos y especialidades; cambios de estado y contacto.
- Programación diaria y por período, repetición sin duplicar turnos, configuración y disponibilidad de 30 minutos.
- Reserva, rechazo de duplicados, cita ajena, permisos de reprogramación, cancelación médica con motivo, reporte y cierre de sesión.
- Acciones temporales de atención, inasistencia y asistencia rechazadas antes del momento permitido; las transiciones válidas también se ejercitan en las pruebas PHP con reloj controlado.
- Errores de CSRF, origen, JSON, Content-Type, identificadores fuera de rango y rutas inexistentes.
- HTML de 20 vistas de Administración, perfil del paciente y rechazo de la vista de Usuarios a un paciente. No hubo avisos PHP ni errores de carga de las vistas comprobadas.

El escenario reutilizable está en `backend/tests/http/escenario_http.py`; su ejecutor está en `backend/tests/test_contrato_http.py`. Su ejecución distribuida también completó las 137 comprobaciones.

## Repetir las pruebas en Windows

Desde la raíz del proyecto, pruebas sin base:

```powershell
& "C:\php85\php.exe" backend/tests/ejecutar-pruebas.php
```

Ejecuta seis archivos: cinco originales sin base y arquitectura.

Para integración, crea una base **independiente** en pgAdmin, por ejemplo `citas_medicas_pruebas`. Configura las variables para esa base, aplica 001–005 y carga el seeder una vez. Las variables de PowerShell se heredan por los procesos hijos y tienen prioridad sobre `.env`:

```powershell
$env:DB_NAME = "citas_medicas_pruebas"
& "C:\php85\php.exe" backend/scripts/verificar-entorno.php --conexion
& "C:\php85\php.exe" backend/tests/ejecutar-pruebas.php --integracion
Remove-Item Env:DB_NAME
```

Configura también `DB_HOST`, `DB_PORT`, `DB_USER` y `DB_PASS` si la base de pruebas utiliza credenciales diferentes. No ejecutes estas pruebas sobre tu base de trabajo. El ejecutor detiene la ejecución si una prueba falla y conserva el mensaje de diagnóstico.

Las migraciones se preparan con los mismos seis scripts del README: migración inicial, actualización de especialidades/médicos, roles/citas, agenda/reportes, programación mensual y seeder. La integración no crea la base automáticamente.

### Prueba HTTP opcional

Requiere Python 3.9 o superior únicamente para el ejecutor de prueba. Usa otra base nueva terminada en `_pruebas`, con 001–005 y **solo las cuatro cuentas iniciales**. `SEED_USERS_PASSWORD` debe coincidir con la clave con que creaste esas cuentas.

```powershell
$env:DB_NAME = "citas_medicas_http_pruebas"
py backend/tests/test_contrato_http.py --php "C:\php85\php.exe"
Remove-Item Env:DB_NAME
```

El script inicia y cierra sus propios servidores en puertos locales libres. No necesita que tus servidores normales estén abiertos. Deja datos sintéticos en esa base para inspección; para repetir el escenario usa una base de pruebas nuevamente preparada. El programa del sistema no necesita Python.

## Límites pendientes de comprobación

1. No se ejecutó en tu Windows ni con tu PHP 8.5.11 concreto; la ejecución comprobada fue PHP 8.3.6 en Linux.
2. No se accedió a tu PostgreSQL real, credenciales, archivos `.env` ni datos existentes. La compatibilidad de tu instalación se debe confirmar con `verificar-entorno.php --conexion` y un recorrido local.
3. No se completó inspección visual ni interacción DOM en navegador: la descarga del navegador de pruebas falló en este entorno. Se verificaron HTML, API, sintaxis, importaciones e identidad de archivos de frontend. Queda el recorrido manual de menús, formularios y adaptación móvil.
4. No se hicieron pruebas de carga prolongada, alta concurrencia general ni despliegue con Apache/Nginx/HTTPS. Sí se probaron los dos conflictos de reservas entre procesos indicados.
5. Las funciones de consulta clínica, diagnósticos, tratamientos, visor general de bitácora y corrección de resultados finales estaban pendientes en el original y siguen pendientes. No se presentan como implementadas o probadas.
6. Se conserva la configuración histórica de duración: el valor entregado es 30 minutos, pero no se eliminó la posibilidad previa de configurar otra duración ni se modificaron reservas antiguas.

El ZIP incluye únicamente código, pruebas y documentación del proyecto; no contiene el runtime temporal de pruebas, la instancia PostgreSQL ni archivos de sesión.
