# Resultado de la revisión del despliegue

Fecha: 24 de octubre de 2026. Base: `Sistema-Citas-Medicas-main(1).zip`, adjuntado en este chat. Esta revisión corresponde a ese ZIP; no incorpora cambios realizados en otros proyectos o conversaciones.

El paquete permite preparar un primer despliegue en una base vacía. La aplicación no está publicada todavía. La construcción de Docker y la comprobación en Render requieren continuar con la cuenta del usuario.

## Correcciones de esta revisión

| Archivo | Cambio y motivo |
|---|---|
| Dockerfile | Fija la familia Debian bookworm para PHP 8.3/Apache, instala pdo_pgsql y reutiliza las extensiones ya incluidas. Activa php.ini-production. Evita recompilar mbstring innecesariamente. |
| backend/src/shared/utils/RespuestaJson.php | Envía explícitamente `419 Page Expired`. En Apache, la ausencia de token CSRF estaba produciendo un HTTP 500 aunque el JSON contuviera el mensaje correcto. Se conserva el contrato 419 del frontend. |
| deploy/verificar-configuracion.php | Detecta variables DB_* ausentes, puerto inválido, SSL desactivado y origen público que no sea HTTPS, sin imprimir sus valores. |
| deploy/iniciar.sh | Valida antes de conectar y elimina la barra final de FRONTEND_URL. |
| deploy/preparar-base.php | Lista explícitamente las cinco migraciones, comprueba el marcador de versión y emite mensajes de error más claros. Sigue usando una transacción y un bloqueo para la inicialización. |
| deploy/tests/test_configuracion.py | Diez casos reproducibles para validar la configuración sin conectarse a una base. |
| DESPLEGAR-REPOSITORIO-ACTUAL.md | Indica cómo aplicar el parche en el repositorio existente y subir la rama de despliegue conservando los commits. |

Se conserva la adaptación previa de `frontend/src/api/ClienteApiServidor.php` para consultar internamente la sesión bajo el mismo servidor. Solo ese archivo y `RespuestaJson.php` cambian respecto del código original adjunto. Los otros 198 archivos originales mantienen sus bytes; no se elimina ningún archivo original. Las pantallas, CSS, JavaScript y SQL permanecen iguales.

## Verificaciones ejecutadas

| Comprobación | Resultado |
|---|---|
| Sintaxis de 140 PHP | Sin errores |
| Sintaxis de 30 JavaScript | Sin errores |
| Script de arranque shell | Sin errores de sintaxis |
| Configuración Apache | Syntax OK y servidor ejecutado |
| Suite del backend | 14 archivos de pruebas aprobados |
| Recorrido HTTP con Apache y HTTPS | 137 comprobaciones aprobadas; 48 endpoints cubiertos |
| Administrador inicial | Creación e inicio de sesión correctos; acceso al panel mediante la consulta interna de sesión |
| Cookies | Secure y HttpOnly presentes en la sesión de la prueba HTTPS |
| Reinicio de inicialización | Mantiene un solo administrador y cuatro roles; funciona sin la contraseña inicial |
| Base preexistente sin marcador | Rechazada sin sobrescribir datos |
| Acceso a archivos privados | /backend/.env, /deploy/preparar-base.php y /assets/app/configuracion.php rechazados |
| Parámetros de despliegue | 10 casos aprobados, sin contraseñas en errores |

La suite incluye registro de paciente con historia, validaciones, permisos, especialidades y médicos, agenda de 30 minutos, programación por períodos, reserva, cancelación y reprogramación, reportes, perfiles, migración 005, carreras de reserva y rollback cuando falla la generación de turnos. El recorrido HTTP comprueba autenticación, permisos por rol, registro, disponibilidad, reserva duplicada rechazada, cambios de citas y acceso a vistas.

## Entorno y límites de las pruebas

Las pruebas usaron PHP 8.3.6, PostgreSQL 16 y Apache 2.4.58 locales. HTTPS utilizó un certificado exclusivo de pruebas, confiado solo por el cliente de prueba. No se desactivó la verificación de certificados en el código entregado. El servidor de prueba recibió la misma configuración de rutas Apache con las rutas de disco adaptadas al entorno y un virtual host TLS adicional. El contenedor de pruebas requirió una adaptación temporal de identidad de usuario para ejecutar sus procesos; esa adaptación no se incluye en el proyecto.

No hubo Docker disponible para construir la imagen exacta. La imagen oficial prevista tiene una versión de mantenimiento de PHP distinta de la usada localmente. Tampoco se verificó la infraestructura de Render, el TLS de su PostgreSQL, una restauración de datos locales, el comportamiento visual en navegador ni la carga sostenida. Las pruebas no sustituyen la comprobación final tras el primer despliegue.

Este paquete inicializa una base nueva. No importa tu PostgreSQL local ni ejecuta futuras migraciones automáticamente en bases ya inicializadas. La tabla adicional `despliegue_inicial` es un marcador técnico; las once tablas del dominio conservan su esquema. Las sesiones de una sola instancia viven en archivos y pueden cerrarse al reiniciar. Los datos dependen de la conservación de PostgreSQL; el plan gratuito de Render expira a los 30 días.

## Repetir pruebas de configuración

Con Python 3.9 o superior y tu PHP de Windows, desde la raíz del proyecto:

```powershell
python deploy/tests/test_configuracion.py --php C:\php85\php.exe
```

La prueba no se conecta a PostgreSQL. Para las pruebas existentes del backend, utiliza una base separada terminada en `_pruebas` según las instrucciones de `backend/tests/ejecutar-pruebas.php`. No ejecutes las pruebas de integración sobre la base publicada.

## Siguiente paso

Usa `DESPLEGAR-REPOSITORIO-ACTUAL.md` para subir los cambios en la rama despliegue-render del repositorio actual, crear PostgreSQL y crear el servicio web. La dirección pública y sus resultados se verifican cuando Render termine el primer despliegue.
