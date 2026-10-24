# Subir Clínica Vida Salud a Render

Esta copia está preparada para un primer despliegue con una base NUEVA. No importa automáticamente los datos de tu computadora. Conserva tu proyecto local y tu base local. No está publicada todavía: falta crear los servicios en tu cuenta y comprobar el arranque real.

## 1. Usar tu repositorio actual y conservar los commits

Trabaja en la carpeta original que ya contiene Git, backend y frontend. Tu estado compartido es main, sincronizado con origin/main y sin cambios pendientes. Conserva ese repositorio y sus archivos .env locales. No ejecutes git init ni reemplaces la carpeta completa.

Si ya aplicaste este parche, estos archivos son los cambios del despliegue. Para crear la rama ANTES de aplicarlo:

```powershell
git switch -c despliegue-render
```

El archivo descargado `despliegue-render.patch` se puede comprobar y aplicar desde la raíz del repositorio:

```powershell
git apply --check "$env:USERPROFILE\Downloads\despliegue-render.patch"
```

Si el comando termina sin mensajes, la comprobación pasó. Entonces:

```powershell
git apply "$env:USERPROFILE\Downloads\despliegue-render.patch"
git diff --stat
git status --short
```

Si la comprobación indica un error, detente y comparte el mensaje. No uses --reject ni --force; puede tratarse de una versión distinta o de cambios ya aplicados. Si descargaste el parche en otra carpeta, usa esa ruta.

Para registrar únicamente los archivos del despliegue y subir la rama:

```powershell
git add Dockerfile .dockerignore deploy backend/src/shared/utils/RespuestaJson.php frontend/src/api/ClienteApiServidor.php DESPLEGAR-REPOSITORIO-ACTUAL.md REVISION-DESPLIEGUE.md
git commit -m "Preparar despliegue en Render conservando el sistema"
git push -u origin despliegue-render
```

Los commits anteriores siguen en el historial. La rama main no cambia. El remoto informado es `https://github.com/ciroandreslinotoledo61-lang/ClinicaVidaSalud.git`; la captura muestra `Sistema-Citas-Medicas`. Si GitHub lo renombró, puede redirigir el remoto antiguo. Conserva origin inicialmente y, si el push avisa de un cambio de dirección o falla, revisa el mensaje antes de modificarlo.

## 2. Crear la base de datos

1. En https://dashboard.render.com crea una cuenta o inicia sesión con GitHub.
2. Pulsa **New → Postgres**.
3. Nombre: `vida-salud-db`. Base de datos: `citas_medicas`. Elige una región y recuérdala.
4. Para una demostración puedes elegir **Free**, si está disponible en tu cuenta. Antes de confirmar revisa que el precio sea cero. No selecciones un plan de pago sin decidirlo.
5. Crea la base y espera a que esté disponible.
6. En su sección de conexiones encontrarás **Hostname, Port, Database, Username y Password**. Esos valores se copian en Render, no en GitHub ni en el chat.

El PostgreSQL gratuito de Render caduca a los 30 días y no incluye respaldos. El servicio web gratuito se duerme tras 15 minutos sin tráfico y puede tardar aproximadamente un minuto en despertar. Para conservar el sistema a largo plazo hay que elegir otra modalidad antes del vencimiento. Fuente: https://render.com/docs/free

## 3. Crear el servicio web

1. Pulsa **New → Web Service**.
2. Conecta GitHub y autoriza el acceso a tu repositorio actual: `Sistema-Citas-Medicas` (o `ClinicaVidaSalud` si ese es el nombre que aparece en tu cuenta).
3. Selecciona ese repositorio y configura:

| Opción | Valor |
|---|---|
| Name | clinica-vida-salud, o un nombre disponible |
| Language / Runtime | Docker |
| Branch | despliegue-render |
| Region | La misma que la base PostgreSQL |
| Root Directory | Vacío |
| Dockerfile Path | ./Dockerfile |
| Instance Type | Free para la demostración, si está disponible |
| Health Check Path | /api/estado |

No necesitas instalar Docker en Windows. Render construye la imagen desde el Dockerfile. No elijas **Static Site**, porque las pantallas también ejecutan PHP.

## 4. Variables de entorno

Antes del primer despliegue agrega estas variables en **Environment**:

| Key | Value |
|---|---|
| DB_HOST | Hostname INTERNO de la base de Render |
| DB_PORT | Puerto mostrado por Render, normalmente 5432 |
| DB_NAME | Database de Render |
| DB_USER | Username de Render |
| DB_PASS | Password de Render |
| DB_SSLMODE | require |
| INITIALIZE_EMPTY_DATABASE | true |
| INITIAL_ADMIN_EMAIL | Tu correo para iniciar sesión como administrador |
| INITIAL_ADMIN_PASSWORD | Una contraseña nueva de al menos 12 y como máximo 72 bytes |

La contraseña inicial la eliges tú. No uses una clave de otro servicio. Se guarda como hash en la base de datos. No se crean las cuatro cuentas de prueba del entorno local.

El arranque configura automáticamente `APP_ENV=production`, `SESSION_SECURE=true`, `PORT=10000`, `FRONTEND_URL` desde la URL asignada por Render y `API_URL` bajo `/api`. No pongas localhost en las variables públicas. También configura una dirección interna para que el frontend consulte la sesión sin salir del servidor.

## 5. Publicar y entrar

1. Pulsa **Create Web Service** o **Deploy Web Service**.
2. Espera a que termine la construcción. En Logs debería aparecer **Base creada y administrador registrado** en el primer arranque.
3. Cuando el servicio esté activo, abre la dirección HTTPS que Render muestra arriba.
4. Entra a **Iniciar sesión** con `INITIAL_ADMIN_EMAIL` y `INITIAL_ADMIN_PASSWORD`.
5. Desde administración crea especialidades, médicos, horarios y usuarios de recepción. Puedes registrar un paciente desde el formulario público.
6. Tras comprobar el acceso, elimina `INITIAL_ADMIN_PASSWORD` de las variables de Render y cambia `INITIALIZE_EMPTY_DATABASE` a `false`. Guarda y deja que reinicie. La cuenta permanece y su contraseña no se restablece.

La inicialización aplica las cinco migraciones en una transacción, crea cuatro roles y un administrador, y agrega la tabla técnica `despliegue_inicial` como marcador. En reinicios conserva los datos. Si encuentra una base con tablas sin ese marcador, se detiene: no intenta actualizar ni reemplazar bases existentes.

## 6. Comprobaciones después de publicar

- Abrir inicio e iniciar sesión; comprobar que se vean los estilos.
- Registrar especialidad, médico, horario y paciente; reservar una cita.
- Verificar que otro paciente no reserve el mismo turno.
- Probar cierre de sesión y permisos de cada rol.
- Acceder desde el celular a la misma dirección HTTPS.

Las sesiones se guardan en archivos temporales del contenedor: un reinicio o nuevo despliegue puede cerrar las sesiones. Los registros permanecen en PostgreSQL mientras la base exista. Esta configuración usa una sola instancia; no está preparada para múltiples réplicas compartiendo sesiones.

## Si aparece un error

- **Dockerfile not found**: Dockerfile debe estar en la raíz del repositorio; Root Directory vacío.
- **Error PostgreSQL**: revisar las seis variables DB_* y que la base esté disponible.
- **Inicialización detenida**: comprobar base vacía, correo válido y contraseña de 12–72 bytes. No uses tu base existente con este inicializador.
- **Origen no permitido**: comprobar que estés usando la dirección HTTPS de Render. Si agregas un dominio propio, configura FRONTEND_URL con ese origen exacto, sin barra final.
- **No carga o tarda**: el servicio gratuito puede estar despertando; revisar estado y Logs.
- Para pedir ayuda comparte el mensaje de error o una captura de Logs sin mostrar contraseñas ni URLs de conexión con credenciales.

## Qué se cambió y qué se probó

Revisión actualizada el 24 de octubre de 2026. Consulta también `REVISION-DESPLIEGUE.md`.

- Dockerfile usa PHP 8.3 con Apache e instala `pdo_pgsql`; reutiliza `curl`, `mbstring` y `ctype` de la imagen y activa php.ini-production.
- El arranque valida las variables de base de datos y el origen HTTPS, y conserva los datos cuando reinicia.
- `ClienteApiServidor.php` admite API_INTERNAL_URL, conservando API_URL como alternativa para localhost.
- `RespuestaJson.php` conserva el estado HTTP 419 en Apache mediante una línea de estado explícita. Antes, Apache lo transformaba en 500 al faltar el token CSRF.
- Se conservaron pantallas, estilos, módulos y las cinco migraciones del ZIP. No se incluyeron archivos .env privados.
- 140 archivos PHP y 30 JavaScript sin errores de sintaxis; script shell válido.
- 14 archivos de pruebas del backend pasaron con PostgreSQL 16, incluidos reservas simultáneas y rollback.
- 137 comprobaciones HTTP pasaron bajo Apache con HTTPS local, cubriendo los 48 endpoints y vistas por rol.
- También se comprobó el administrador inicial, sesión con cookie Secure/HttpOnly, acceso al panel, CSS, rechazo de archivos privados, reinicio sin duplicar cuentas y rechazo de una base preexistente.
- 10 casos de validación del arranque pasaron sin revelar valores secretos en los mensajes.
- Pendiente: construir la imagen Docker en Render, comprobar su conexión TLS a PostgreSQL y verificar la URL pública desde tu cuenta. Apache/HTTPS se probaron localmente, no en Render. No se hicieron pruebas visuales en navegador ni pruebas de carga.

Documentación consultada: https://render.com/docs/docker y https://render.com/docs/environment-variables
