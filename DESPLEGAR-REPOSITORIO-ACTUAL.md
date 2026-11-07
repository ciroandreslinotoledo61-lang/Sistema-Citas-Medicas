# Subir Clinica Vida Salud a Render

Esta copia esta preparada para un primer despliegue con una base NUEVA. No importa automaticamente los datos de tu computadora. Conserva tu proyecto local y tu base local. No esta publicada todavia: falta crear los servicios en tu cuenta y comprobar el arranque real.

## 1. Usar tu repositorio actual y conservar los commits

Trabaja en la carpeta original que ya contiene Git, backend y frontend. Tu estado compartido es main, sincronizado con origin/main y sin cambios pendientes. Conserva ese repositorio y sus archivos .env locales. No ejecutes git init ni reemplaces la carpeta completa.

Si ya aplicaste este parche, estos archivos son los cambios del despliegue. Para crear la rama ANTES de aplicarlo:

```powershell
git switch -c despliegue-render
```

El archivo descargado `despliegue-render.patch` se puede comprobar y aplicar desde la raiz del repositorio:

```powershell
git apply --check "$env:USERPROFILE\Downloads\despliegue-render.patch"
```

Si el comando termina sin mensajes, la comprobacion paso. Entonces:

```powershell
git apply "$env:USERPROFILE\Downloads\despliegue-render.patch"
git diff --stat
git status --short
```

Si la comprobacion indica un error, detente y comparte el mensaje. No uses --reject ni --force; puede tratarse de una version distinta o de cambios ya aplicados. Si descargaste el parche en otra carpeta, usa esa ruta.

Para registrar unicamente los archivos del despliegue y subir la rama:

```powershell
git add Dockerfile .dockerignore deploy backend/src/shared/utils/RespuestaJson.php frontend/src/api/ClienteApiServidor.php DESPLEGAR-REPOSITORIO-ACTUAL.md REVISION-DESPLIEGUE.md
git commit -m "Preparar despliegue en Render conservando el sistema"
git push -u origin despliegue-render
```

Los commits anteriores siguen en el historial. La rama main no cambia. El remoto informado es `https://github.com/ciroandreslinotoledo61-lang/ClinicaVidaSalud.git`; la captura muestra `Sistema-Citas-Medicas`. Si GitHub lo renombro, puede redirigir el remoto antiguo. Conserva origin inicialmente y, si el push avisa de un cambio de direccion o falla, revisa el mensaje antes de modificarlo.

## 2. Crear la base de datos

1. En https://dashboard.render.com crea una cuenta o inicia sesion con GitHub.
2. Pulsa **New -> Postgres**.
3. Nombre: `vida-salud-db`. Base de datos: `citas_medicas`. Elige una region y recuerdala.
4. Para una demostracion puedes elegir **Free**, si esta disponible en tu cuenta. Antes de confirmar revisa que el precio sea cero. No selecciones un plan de pago sin decidirlo.
5. Crea la base y espera a que este disponible.
6. En su seccion de conexiones encontraras **Hostname, Port, Database, Username y Password**. Esos valores se copian en Render, no en GitHub ni en el chat.

El PostgreSQL gratuito de Render caduca a los 30 dias y no incluye respaldos. El servicio web gratuito se duerme tras 15 minutos sin trafico y puede tardar aproximadamente un minuto en despertar. Para conservar el sistema a largo plazo hay que elegir otra modalidad antes del vencimiento. Fuente: https://render.com/docs/free

## 3. Crear el servicio web

1. Pulsa **New -> Web Service**.
2. Conecta GitHub y autoriza el acceso a tu repositorio actual: `Sistema-Citas-Medicas` (o `ClinicaVidaSalud` si ese es el nombre que aparece en tu cuenta).
3. Selecciona ese repositorio y configura:

| Opcion | Valor |
|---|---|
| Name | clinica-vida-salud, o un nombre disponible |
| Language / Runtime | Docker |
| Branch | despliegue-render |
| Region | La misma que la base PostgreSQL |
| Root Directory | Vacio |
| Dockerfile Path | ./Dockerfile |
| Instance Type | Free para la demostracion, si esta disponible |
| Health Check Path | /api/estado |

No necesitas instalar Docker en Windows. Render construye la imagen desde el Dockerfile. No elijas **Static Site**, porque las pantallas tambien ejecutan PHP.

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
| INITIAL_ADMIN_EMAIL | Tu correo para iniciar sesion como administrador |
| INITIAL_ADMIN_PASSWORD | Una contrasena nueva de al menos 12 y como maximo 72 bytes |

La contrasena inicial la eliges tu. No uses una clave de otro servicio. Se guarda como hash en la base de datos. No se crean las cuatro cuentas de prueba del entorno local.

El arranque configura automaticamente `APP_ENV=production`, `SESSION_SECURE=true`, `PORT=10000`, `FRONTEND_URL` desde la URL asignada por Render y `API_URL` bajo `/api`. No pongas localhost en las variables publicas. Tambien configura una direccion interna para que el frontend consulte la sesion sin salir del servidor.

## 5. Publicar y entrar

1. Pulsa **Create Web Service** o **Deploy Web Service**.
2. Espera a que termine la construccion. En Logs deberia aparecer **Base creada y administrador registrado** en el primer arranque.
3. Cuando el servicio este activo, abre la direccion HTTPS que Render muestra arriba.
4. Entra a **Iniciar sesion** con `INITIAL_ADMIN_EMAIL` y `INITIAL_ADMIN_PASSWORD`.
5. Desde administracion crea especialidades, medicos, horarios y usuarios de recepcion. Puedes registrar un paciente desde el formulario publico.
6. Tras comprobar el acceso, elimina `INITIAL_ADMIN_PASSWORD` de las variables de Render y cambia `INITIALIZE_EMPTY_DATABASE` a `false`. Guarda y deja que reinicie. La cuenta permanece y su contrasena no se restablece.

La inicializacion aplica las cinco migraciones en una transaccion, crea cuatro roles y un administrador, y agrega la tabla tecnica `despliegue_inicial` como marcador. En reinicios conserva los datos. Si encuentra una base con tablas sin ese marcador, se detiene: no intenta actualizar ni reemplazar bases existentes.

## 6. Comprobaciones despues de publicar

- Abrir inicio e iniciar sesion; comprobar que se vean los estilos.
- Registrar especialidad, medico, horario y paciente; reservar una cita.
- Verificar que otro paciente no reserve el mismo turno.
- Probar cierre de sesion y permisos de cada rol.
- Acceder desde el celular a la misma direccion HTTPS.

Las sesiones se guardan en archivos temporales del contenedor: un reinicio o nuevo despliegue puede cerrar las sesiones. Los registros permanecen en PostgreSQL mientras la base exista. Esta configuracion usa una sola instancia; no esta preparada para multiples replicas compartiendo sesiones.

## Si aparece un error

- **Dockerfile not found**: Dockerfile debe estar en la raiz del repositorio; Root Directory vacio.
- **Error PostgreSQL**: revisar las seis variables DB_* y que la base este disponible.
- **Inicializacion detenida**: comprobar base vacia, correo valido y contrasena de 12-72 bytes. No uses tu base existente con este inicializador.
- **Origen no permitido**: comprobar que estes usando la direccion HTTPS de Render. Si agregas un dominio propio, configura FRONTEND_URL con ese origen exacto, sin barra final.
- **No carga o tarda**: el servicio gratuito puede estar despertando; revisar estado y Logs.
- Para pedir ayuda comparte el mensaje de error o una captura de Logs sin mostrar contrasenas ni URLs de conexion con credenciales.

## Que se cambio y que se probo

Revision actualizada el 24 de octubre de 2026. Consulta tambien `REVISION-DESPLIEGUE.md`.

- Dockerfile usa PHP 8.3 con Apache e instala `pdo_pgsql`; reutiliza `curl`, `mbstring` y `ctype` de la imagen y activa php.ini-production.
- El arranque valida las variables de base de datos y el origen HTTPS, y conserva los datos cuando reinicia.
- `ClienteApiServidor.php` admite API_INTERNAL_URL, conservando API_URL como alternativa para localhost.
- `RespuestaJson.php` conserva el estado HTTP 419 en Apache mediante una linea de estado explicita. Antes, Apache lo transformaba en 500 al faltar el token CSRF.
- Se conservaron pantallas, estilos, modulos y las cinco migraciones del ZIP. No se incluyeron archivos .env privados.
- 140 archivos PHP y 30 JavaScript sin errores de sintaxis; script shell valido.
- 14 archivos de pruebas del backend pasaron con PostgreSQL 16, incluidos reservas simultaneas y rollback.
- 137 comprobaciones HTTP pasaron bajo Apache con HTTPS local, cubriendo los 48 endpoints y vistas por rol.
- Tambien se comprobo el administrador inicial, sesion con cookie Secure/HttpOnly, acceso al panel, CSS, rechazo de archivos privados, reinicio sin duplicar cuentas y rechazo de una base preexistente.
- 10 casos de validacion del arranque pasaron sin revelar valores secretos en los mensajes.
- Pendiente: construir la imagen Docker en Render, comprobar su conexion TLS a PostgreSQL y verificar la URL publica desde tu cuenta. Apache/HTTPS se probaron localmente, no en Render. No se hicieron pruebas visuales en navegador ni pruebas de carga.

Documentacion consultada: https://render.com/docs/docker y https://render.com/docs/environment-variables
