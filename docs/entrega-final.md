# Entrega final — limpieza y verificación

Fecha: 7 de noviembre de 2026. Preparación local sobre `main`, sin commit, push, despliegue ni operaciones sobre Neon/Render.

## Alcance conservado

Se mantiene toda la arquitectura modular, módulos funcionales, scripts, pruebas, SQL didáctico y migraciones 001–013. No se cambiaron fuentes ejecutables ni configuración de publicación. [Árbol real de directorios](refactorizacion/ESTRUCTURA-CARPETAS.md).

El README describe objetivo, tecnologías, roles, funciones, variables, instalación, arranque y pruebas. Incluye la URL pública confirmada por el propietario: <https://clinica-vida-salud-bavz.onrender.com>. No se intervino ese servicio.

## Movimientos y limpieza

| Origen | Destino / decisión | Motivo |
| --- | --- | --- |
| `.tmp-auth/` | Archivo privado externo, `evidencias-anteriores/` | 54.659 archivos, 2.166.582.490 bytes de clústeres, perfiles, logs y evidencias; preservados íntegramente tras comprobar ausencia de procesos usuarios. |
| `backups/` | Archivo privado externo, `backups/` | Tres ZIP privados, conservados y verificados por SHA-256 sin abrirlos ni publicarlos. |
| `ENTREGA-EXPRESS-RENDER.md` | Archivo privado externo, `documentos-previos/` | Documento local ignorado, referido a otra rama y un parche anterior; no es un procedimiento actual. |
| `GUIA-ARQUITECTURA-Y-DEFENSA.md` | `docs/arquitectura/GUIA-ARQUITECTURA-Y-DEFENSA.md` | Guía actualizada y agrupada con la documentación técnica. |
| `docs/ai/catalogo-metodos.md` | `docs/arquitectura/catalogo-metodos.md` | Material académico antes ignorado; ahora visible como catálogo histórico, no exhaustivo. |
| Inventario antiguo de estructura | `docs/historico/ESTRUCTURA-1.5.md` | Preservado como antecedente; su ubicación anterior contiene el árbol actual. |
| Dos reglas repetidas de `.gitignore` | Eliminadas solo las repeticiones | Se mantienen las reglas efectivas de exclusión. |
| `.tmp-auth/` recreada por estas pruebas | Archivo privado externo, `evidencias-entrega-final/` | 6.816 archivos, 293.358.371 bytes; procesos finalizados, archivos legibles e inventario SHA-256 privado generado después del traslado. |

Se retiró únicamente la carpeta vacía `docs/ai/` después de rescatar su documento. PowerShell encontró una limitación de rutas largas al enumerar algunos perfiles Edge archivados; se verificó después la lectura completa y los hashes mediante Node.js, sin errores ni eliminación de contenidos.

**No se eliminó contenido funcional.** La comparación SHA-256 de los archivos versionados originales no encontró duplicados exactos que justificaran eliminar fuentes.

El archivo privado está fuera del repositorio, en una carpeta hermana del directorio que lo contiene, llamada `archivo-privado-vida-salud-20261107-173526`. No adjuntar su contenido a GitHub. Los documentos antiguos que apuntan a `.tmp-auth/` conservan referencias históricas; las evidencias anteriores correspondientes quedaron archivadas, no son enlaces de navegación pública.

## Elementos dudosos preservados

- `AGENTS.md`: instrucciones vigentes descubiertas automáticamente por herramientas; permanece en la raíz.
- Documentación RUP e informes de refactorización: valor académico/histórico; no se borran aunque describan etapas anteriores.
- Catálogo 1.5 e inventario histórico: se advierte que no son contratos vigentes.
- Todos los scripts y pruebas: incluyen utilidades operativas y regresiones que no necesariamente se ejecutan en cada arranque.
- `.env` locales: no se modificaron, movieron ni publicaron; sus valores no forman parte de la entrega.
- Reglas de exclusión de documentación local antigua: se mantienen para evitar su publicación accidental si reaparece.

## Verificaciones de esta preparación

Entorno ejecutado: PHP 8.5.11, PostgreSQL 18.6 y Node.js 24.18.0 en Windows; Microsoft Edge para los recorridos de navegador. Las conexiones de las pruebas completas se fijan a clústeres desechables locales con datos sintéticos y correo simulado.

| Verificación ejecutada | Resultado |
| --- | --- |
| PHP sin conexión, con `VIDASALUD_TEST_LOCAL=1` | 11 suites aprobadas; incluye 74 contratos HTTP y separación de capas. |
| Suite completa en PostgreSQL local desechable | 31 suites PHP aprobadas, incluidas concurrencia real, rollback, agenda, consultas, credenciales, migraciones y vencidas. |
| HTTP de la suite completa | 111 comprobaciones de privacidad, 80 solicitudes de módulos, 214 comprobaciones de recuperación, 330 de autenticación y 137 de contrato/vistas aprobadas. |
| Navegador de la suite completa | 35 comprobaciones Edge de paciente y 31 de recuperación aprobadas; frontend/API/BD reales locales, correo simulado. |
| JavaScript sin red | 15 comprobaciones de filtros, 41 de formularios y 11 de cancelación/pagos aprobadas. |
| Configuración de despliegue, sin conexiones | 10 casos aprobados; no se modificó la configuración. |
| HTTP e interfaz de módulos nuevos, clúster local propio | 82 solicitudes HTTP y 22 comprobaciones Edge aprobadas; vistas a 320, 390 y 1440 px. |
| Sintaxis e imports | 240 archivos PHP y 56 JS válidos; 115 imports relativos resueltos. |
| Documentación | 201 enlaces locales y cuatro anclas entre documentos resueltos, excluidos bloques de código históricos. |
| Árbol | Los 90 directorios reales coinciden con el árbol documentado; las 13 migraciones enumeradas existen. |
| Seguridad de candidatos a versionar | 357 archivos revisados: sin `.env` reales, respaldos, logs ni artefactos privados candidatos; sin coincidencias en los patrones de secretos examinados. No equivale a una auditoría exhaustiva ni revisa el historial remoto. |
| Exclusiones | `git check-ignore` confirma protección de ambos `.env`, `.tmp-auth/` y `backups/`; ambos `.env.example` siguen versionados. |
| Diff funcional | Sin cambios en `backend/`, `frontend/`, `database/`, `deploy/`, `Dockerfile`, `.dockerignore`, `.editorconfig` ni `AGENTS.md`. |
| `git diff --check` | Sin errores; Git solo advirtió normalización LF/CRLF de archivos de texto en Windows. |

Todas las ejecuciones indicadas terminaron sin fallos. Los resultados históricos no se consideran evidencia de esta revisión. Las advertencias `ExperimentalWarning` de Node corresponden a `--experimental-vm-modules`, sin fallos de pruebas.

Comandos principales ejecutados desde la raíz:

```powershell
$env:VIDASALUD_TEST_LOCAL = '1'
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php
node --experimental-vm-modules backend/tests/test_filtros_listados.mjs
node --experimental-vm-modules backend/tests/test_formularios.mjs
node --experimental-vm-modules backend/tests/test_cancelacion_pagos.mjs
& C:/php85/php.exe deploy/tests/test_configuracion.php
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin" --navegador
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --solo-modulos --solo-interfaz --navegador --pg-bin "C:/Program Files/PostgreSQL/18/bin"
git diff --check
```

Logs de suites y ejecuciones: `evidencias-entrega-final/suites-php-8a6664e4e71e/`, `suites-php-949e74f3d13f/`, `ejecucion-php-25758e33aaba/` y `ejecucion-php-c21d2d2d8534/`, dentro del archivo externo. Las capturas permanecen allí en los directorios `navegador-*`. El inventario de estos artefactos es privado y no se publica.

## Límites

Las pruebas aisladas no certifican la instalación publicada, correo Resend, restauración de los respaldos ni configuración productiva del Cron Job. Quedan pendientes una revisión humana por rol y cualquier publicación expresamente autorizada. No se han creado ramas, commits ni push.
