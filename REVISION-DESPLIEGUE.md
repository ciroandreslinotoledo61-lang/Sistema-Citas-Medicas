# Resultado de la revision del despliegue

Fecha: 24 de octubre de 2026. Base: `Sistema-Citas-Medicas-main(1).zip`, adjuntado en este chat. Esta revision corresponde a ese ZIP; no incorpora cambios realizados en otros proyectos o conversaciones.

El paquete permite preparar un primer despliegue en una base vacia. La aplicacion no esta publicada todavia. La construccion de Docker y la comprobacion en Render requieren continuar con la cuenta del usuario.

## Correcciones de esta revision

| Archivo | Cambio y motivo |
|---|---|
| Dockerfile | Fija la familia Debian bookworm para PHP 8.3/Apache, instala pdo_pgsql y reutiliza las extensiones ya incluidas. Activa php.ini-production. Evita recompilar mbstring innecesariamente. |
| backend/src/shared/utils/RespuestaJson.php | Envia explicitamente `419 Page Expired`. En Apache, la ausencia de token CSRF estaba produciendo un HTTP 500 aunque el JSON contuviera el mensaje correcto. Se conserva el contrato 419 del frontend. |
| deploy/verificar-configuracion.php | Detecta variables DB_* ausentes, puerto invalido, SSL desactivado y origen publico que no sea HTTPS, sin imprimir sus valores. |
| deploy/iniciar.sh | Valida antes de conectar y elimina la barra final de FRONTEND_URL. |
| deploy/preparar-base.php | Lista explicitamente las cinco migraciones, comprueba el marcador de version y emite mensajes de error mas claros. Sigue usando una transaccion y un bloqueo para la inicializacion. |
| deploy/tests/test_configuracion.php | Diez casos reproducibles para validar la configuracion sin conectarse a una base; ejecutor trasladado posteriormente a PHP. |
| DESPLEGAR-REPOSITORIO-ACTUAL.md | Indica como aplicar el parche en el repositorio existente y subir la rama de despliegue conservando los commits. |

Se conserva la adaptacion previa de `frontend/src/api/ClienteApiServidor.php` para consultar internamente la sesion bajo el mismo servidor. Solo ese archivo y `RespuestaJson.php` cambian respecto del codigo original adjunto. Los otros 198 archivos originales mantienen sus bytes; no se elimina ningun archivo original. Las pantallas, CSS, JavaScript y SQL permanecen iguales.

## Verificaciones ejecutadas

| Comprobacion | Resultado |
|---|---|
| Sintaxis de 140 PHP | Sin errores |
| Sintaxis de 30 JavaScript | Sin errores |
| Script de arranque shell | Sin errores de sintaxis |
| Configuracion Apache | Syntax OK y servidor ejecutado |
| Suite del backend | 14 archivos de pruebas aprobados |
| Recorrido HTTP con Apache y HTTPS | 137 comprobaciones aprobadas; 48 endpoints cubiertos |
| Administrador inicial | Creacion e inicio de sesion correctos; acceso al panel mediante la consulta interna de sesion |
| Cookies | Secure y HttpOnly presentes en la sesion de la prueba HTTPS |
| Reinicio de inicializacion | Mantiene un solo administrador y cuatro roles; funciona sin la contrasena inicial |
| Base preexistente sin marcador | Rechazada sin sobrescribir datos |
| Acceso a archivos privados | /backend/.env, /deploy/preparar-base.php y /assets/app/configuracion.php rechazados |
| Parametros de despliegue | 10 casos aprobados, sin contrasenas en errores |

La suite incluye registro de paciente con historia, validaciones, permisos, especialidades y medicos, agenda de 30 minutos, programacion por periodos, reserva, cancelacion y reprogramacion, reportes, perfiles, migracion 005, carreras de reserva y rollback cuando falla la generacion de turnos. El recorrido HTTP comprueba autenticacion, permisos por rol, registro, disponibilidad, reserva duplicada rechazada, cambios de citas y acceso a vistas.

## Entorno y limites de las pruebas

Las pruebas usaron PHP 8.3.6, PostgreSQL 16 y Apache 2.4.58 locales. HTTPS utilizo un certificado exclusivo de pruebas, confiado solo por el cliente de prueba. No se desactivo la verificacion de certificados en el codigo entregado. El servidor de prueba recibio la misma configuracion de rutas Apache con las rutas de disco adaptadas al entorno y un virtual host TLS adicional. El contenedor de pruebas requirio una adaptacion temporal de identidad de usuario para ejecutar sus procesos; esa adaptacion no se incluye en el proyecto.

No hubo Docker disponible para construir la imagen exacta. La imagen oficial prevista tiene una version de mantenimiento de PHP distinta de la usada localmente. Tampoco se verifico la infraestructura de Render, el TLS de su PostgreSQL, una restauracion de datos locales, el comportamiento visual en navegador ni la carga sostenida. Las pruebas no sustituyen la comprobacion final tras el primer despliegue.

Este paquete inicializa una base nueva. No importa tu PostgreSQL local ni ejecuta futuras migraciones automaticamente en bases ya inicializadas. La tabla adicional `despliegue_inicial` es un marcador tecnico; las once tablas del dominio conservan su esquema. Las sesiones de una sola instancia viven en archivos y pueden cerrarse al reiniciar. Los datos dependen de la conservacion de PostgreSQL; el plan gratuito de Render expira a los 30 dias.

## Repetir pruebas de configuracion

Con PHP, desde la raiz del proyecto; no se requiere Python:

```powershell
& C:\php85\php.exe deploy/tests/test_configuracion.php
```

La prueba no se conecta a PostgreSQL. Para las pruebas del backend, el ejecutor `php backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin"` prepara sus propias bases locales y ejecuta tambien estos diez casos. Consulta [las instrucciones actuales](docs/uso/pruebas-php.md). No ejecutes las pruebas de integracion sobre la base publicada. Los resultados de las secciones anteriores pertenecen a la revision historica indicada al inicio.

## Siguiente paso

Usa `DESPLEGAR-REPOSITORIO-ACTUAL.md` para subir los cambios en la rama despliegue-render del repositorio actual, crear PostgreSQL y crear el servicio web. La direccion publica y sus resultados se verifican cuando Render termine el primer despliegue.
