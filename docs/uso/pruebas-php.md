# Pruebas automaticas con PHP

El ejecutor de pruebas y los escenarios HTTP utilizan PHP. La aplicacion
conserva PHP, HTML, CSS, JavaScript y PostgreSQL; Python no es un requisito.
Las pruebas de navegador existentes siguen en JavaScript y son opcionales.

## Requisitos y comandos

Usar PHP con cURL, pdo_pgsql y mbstring, y los binarios locales de PostgreSQL
(initdb y pg_ctl). El entorno actual usa PHP 8.5 y PostgreSQL 18. El ejecutor
lanza sus procesos con el mismo PHP que lo inicia, mediante PHP_BINARY.

Desde la raiz del repositorio:

```powershell
# Validaciones sin base de datos
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php

# Todas las suites PHP y HTTP, PostgreSQL local aislado y correo simulado
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin"

# Agregar los recorridos JavaScript existentes en navegador (Node 22+ y Edge)
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin" --navegador

# Solo configuracion de despliegue, sin base de datos
& C:/php85/php.exe deploy/tests/test_configuracion.php
```

Los comandos no necesitan paquetes Composer ni instalar Python. El modo
completo crea un cluster PostgreSQL local nuevo en `.tmp-auth/`, selecciona
puertos libres y prepara tres bases con nombres terminados en `_pruebas`.
Aplica 001-010 a esas bases sinteticas sin modificar los archivos SQL. Fija
las conexiones a `127.0.0.1`; no lee la conexion de Neon para estas pruebas.
No envia correos reales: el flujo de recuperacion usa el transporte simulado.
Al finalizar detiene los servidores PHP y su propio cluster PostgreSQL.

El modo `--integracion` anterior se conserva para una base local de pruebas
preparada explicitamente. El modo `--completo` evita esa preparacion manual y
aisla ademas el contrato HTTP anterior en otra base del mismo cluster.

## Archivos sustituidos

| Archivo anterior | Equivalente PHP | Alcance conservado |
| --- | --- | --- |
| `backend/tests/test_autenticacion_http.py` | `backend/tests/test_autenticacion_http.php` y `backend/tests/http/escenario_autenticacion.php` | Preparacion aislada y 330 comprobaciones de autenticacion y credenciales |
| `backend/tests/test_privacidad_http.py` | `backend/tests/test_privacidad_http.php` | 111 comprobaciones de privacidad y permisos clinicos |
| `backend/tests/test_recuperacion_http.py` | `backend/tests/test_recuperacion_http.php` | 214 comprobaciones de recuperacion con correo simulado |
| `backend/tests/test_contrato_http.py` | `backend/tests/test_contrato_http.php` | Servidores aislados para el contrato anterior |
| `backend/tests/http/escenario_http.py` | `backend/tests/http/escenario_http.php` | 137 comprobaciones de contratos API, seguridad HTTP y compatibilidad |
| `deploy/tests/test_configuracion.py` | `deploy/tests/test_configuracion.php` | 10 casos de configuracion sin conexion a BD |

Soportes nuevos: `backend/tests/http/ClienteHttpPruebas.php`, que mantiene
cookies, solicitudes CSRF, comprobaciones de respuestas y deteccion de
credenciales expuestas; y `backend/tests/SoporteProcesosPrueba.php`, que maneja
procesos locales, puertos, limites de tiempo y sus archivos de salida.
Las aserciones HTTP lanzan excepciones aunque PHP tenga `zend.assertions=-1`.

El ejecutor principal `backend/tests/ejecutar-pruebas.php` reutiliza las 22
suites PHP existentes, incluidas reserva concurrente, transacciones,
credenciales y recuperacion. Los ensayos concurrentes usan procesos PHP
separados; no se sustituyen por peticiones secuenciales.

La equivalencia exige mantener las aserciones y los escenarios, ademas del
conteo de solicitudes. Incluye expiracion, uso unico, rechazo de tokens,
revocacion, limites persistentes, sesiones invalidadas, acceso por roles,
origen, CSRF, privacidad de respuestas y rollback ante fallos de auditoria.

## Verificacion de la sustitucion

Verificado el 2026-11-05 sobre `integracion-credenciales-render`, conservando
los cambios locales anteriores. Entorno: Windows, PHP 8.5.11, PostgreSQL 18,
Node 24 y Edge. No se ejecuto Python durante esta sustitucion.

Primero se ejecuto el conjunto completo PHP con navegador, manteniendo los
seis archivos anteriores hasta confirmar su equivalencia. Despues se retiraron
y se repitio el conjunto PHP/HTTP completo sin ellos, con resultado correcto.
Tambien se ejecuto por separado el modo de ocho suites sin base de datos.

| Verificacion | Resultado |
| --- | --- |
| Suites PHP, incluidas integracion y regresion | 22 archivos aprobados |
| Autenticacion, cambio y restablecimiento HTTP | 330 comprobaciones aprobadas |
| Privacidad y permisos clinicos HTTP | 111 comprobaciones aprobadas |
| Recuperacion HTTP con correo simulado | 214 comprobaciones aprobadas |
| Contrato API anterior y vistas por rol | 137 comprobaciones aprobadas |
| Configuracion para despliegue, sin conexion a BD | 10 casos aprobados |
| Edge: citas y formularios por rol | 35 comprobaciones aprobadas |
| Edge: recuperacion y acceso con nueva clave | 31 comprobaciones aprobadas |
| Sintaxis | 198 PHP y 43 JavaScript/MJS sin errores |
| Codificacion | 282 fuentes UTF-8 sin BOM; 25 Markdown ASCII |
| Espacios | git diff --check correcto con la configuracion del repositorio |
| Migraciones 001-010 | Diez hashes SHA-256 conservados |

Los 792 casos HTTP contabilizan solicitudes, incluidas las de CSRF donde las
contaba el ejecutor anterior. La equivalencia tambien se reviso comparando
aserciones, bucles, permisos, estados y condiciones de rechazo, no solo totales.
Los ensayos concurrentes de reservas, credenciales y recuperacion conservaron
procesos PHP simultaneos y sus barreras. Se aprobaron las 14 comprobaciones de
recuperacion transaccional y las 15 de compatibilidad de la migracion 010.
Los recorridos de navegador comprobaron 320, 390 y 1440 px y ausencia de
excepciones JavaScript. No se repitieron en la segunda ejecucion: su codigo
funcional no cambio.

Errores corregidos en la sustitucion: se recupero la validacion UTF-8 estricta
del HTML en el cliente HTTP y se corrigio la sobrescritura de registros al
heredar STDOUT entre procesos PHP en Windows. Cada suite conserva ahora su
archivo de salida y cualquier codigo de error detiene el ejecutor.
Fallos de pruebas funcionales en las ejecuciones finales: ninguno.

Evidencias locales conservadas, excluidas de Git y de la imagen Docker:

- `.tmp-auth/ejecucion-php-203cd92dfd44`: primera ejecucion y resultados del navegador.
- `.tmp-auth/ejecucion-php-1fc2a1a86013`: ejecucion final sin archivos Python.
- `.tmp-auth/suites-php-881102b3da61`: salida individual de las 22 suites finales.
- `.tmp-auth/navegador-b1ff43d2-8e82-49ec-8d56-52a99e0bc095`: capturas de citas.
- `.tmp-auth/navegador-recuperacion-b7d1d936-ca91-44fa-bec4-bc79058b856f`: capturas de recuperacion.

## Limpieza y archivos adicionales

Se retiraron las dos carpetas `__pycache__` con cuatro `.pyc`, doce clusters
antiguos detenidos, cuatro carpetas temporales de frontend y los auxiliares
de edicion `corregir-textos-frontend.py`, `corregir-textos-backend.php` y
`frontend-audit.mjs` dentro de `.tmp-auth/`. Estos auxiliares no eran pruebas
ni componentes de la aplicacion. De las ejecuciones nuevas se eliminaron las
bases sinteticas y los buzones despues de detener sus procesos; tambien los
perfiles desechables de Edge, comprobando que Edge ya no estaba activo.
Se conservaron logs y capturas. No quedan archivos `.py`, `.pyc` ni caches
Python en el proyecto.

Ademas de los nueve archivos PHP nuevos indicados arriba, se actualizaron:

- `backend/tests/ejecutar-pruebas.php`: modo completo y registros por suite.
- `.gitignore` y `.dockerignore`: exclusion de temporales y caches heredadas.
- `backend/tests/autenticacion_aislada.php`, `test_paciente_navegador.mjs` y
  `test_recuperacion_navegador.mjs`: comentarios sobre el ejecutor actual.
- `README.md`, `REVISION-DESPLIEGUE.md`, `docs/uso/usuarios-autenticacion.md`,
  `docs/uso/actualizacion-privacidad-recuperacion.md`,
  `docs/refactorizacion/ARCHIVOS-MODIFICADOS.md` y `ESTRUCTURA-CARPETAS.md`:
  comandos, enlaces e identificacion de resultados historicos.
- Este documento: instrucciones, equivalencias y resultados actuales.

Esta sustitucion no cambia la arquitectura funcional, migraciones 001-010,
configuracion privada ni datos de la aplicacion. No publica en Render ni
realiza operaciones Git remotas. La entrega real de Resend y la validacion
del servicio publicado siguen siendo verificaciones externas separadas. Para
Resend faltan la clave real, remitente/dominio verificado y una comprobacion
de entrega autorizada; no se inventaron credenciales ni se enviaron correos.
No se construyo ni desplego la imagen de Render en esta revision. La
configuracion PHP/PostgreSQL prevista se conserva y sus diez casos pasaron;
no se crearon ni editaron migraciones SQL para sustituir las pruebas.
