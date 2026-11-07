# Usuarios y autenticacion: cambio y restablecimiento de contrasenas

Implementacion local sobre la arquitectura PHP existente. No requiere paquetes nuevos, servicio de correo ni otro servicio de despliegue.

## Migracion propuesta

La nueva `backend/src/data/migrations/009_seguridad_credenciales.sql` requiere 001-008. Agrega a `usuarios` el indicador de cambio obligatorio, vencimiento de la clave temporal y marca de uso. Agrega `intentos_autenticacion`, con claves SHA-256, contador y comienzo de ventana. No cambia hashes existentes, roles ni datos clinicos. Las migraciones historicas se conservan.

El codigo de autenticacion requiere 009 antes de ponerse en servicio. **No se ejecuto esta migracion en Neon ni en produccion.** El arranque de Render no aplica esta nueva migracion automaticamente. La actualizacion de la base real y el despliegue quedan fuera de esta entrega.

Para leer la propuesta sin cargar `.env` ni abrir conexiones:

```powershell
& C:\php85\php.exe backend/scripts/actualizar-seguridad-credenciales.php
```

Para una copia **local de pruebas**, configura explicitamente `DB_HOST=127.0.0.1`, puerto, usuario, contrasena y `DB_NAME` terminado en `_pruebas`, con `APP_ENV=local`. Instala 001-008 primero; luego ejecuta el mismo script con `--aplicar`. El script rechaza hosts remotos y produccion, aplica 009 en una transaccion y evita repetirla. No apuntes pruebas ni seeders a Neon. Conserva una copia de respaldo antes de actualizar una base existente.

## Funcionamiento

- Cualquier rol puede abrir **Cambiar contrasena**. Se exige la actual, una nueva de 12 a 72 bytes con letras y numeros sin patrones repetidos, y su confirmacion. No se recortan espacios. La clave nueva debe ser diferente de la actual.
- El usuario se obtiene exclusivamente de la sesion. El endpoint no acepta identificadores ni campos adicionales del formulario. Cambiar renueva el identificador de sesion y el token CSRF, conserva la sesion actual y revoca las otras mediante `version_credenciales`.
- **Administrar cuentas -> Restablecer contrasena** esta reservado al administrador. El permiso existente `usuarios.gestionar` abarca las cuentas de esta unica clinica; no existen tenants ni ambitos adicionales. Se rechaza restablecer la propia cuenta o una inactiva. La propia usa el formulario normal.
- El navegador genera 12 bytes criptograficos, representados como 24 caracteres hexadecimales. El administrador guarda la clave para entregarla privadamente y confirma la cuenta y el cierre de sesiones. La clave se envia solo en el cuerpo de la solicitud; el servidor devuelve exclusivamente el resultado y almacena solo su hash. No se usa almacenamiento del navegador, URL, correo automatico ni respuesta con contrasenas.
- La clave temporal caduca a los 30 minutos del restablecimiento y admite un solo inicio de sesion. Ese acceso permite consultar la sesion, cambiar la contrasena y cerrar sesion. El bloqueo es del backend ademas de la interfaz. Al cambiar, la temporal se introduce como contrasena actual. Si caduca, se pierde esa sesion o se cierra antes de elegir una personal, hace falta otro restablecimiento administrativo.
- Cambio, revocacion y bitacora se confirman en una transaccion. La bitacora registra actor, cuenta afectada, fecha y accion `cambiar_contrasena` o `restablecer_contrasena`, sin hashes, claves ni tokens. Los triggers anteriores siguen registrando sus metadatos habituales.
- Los limites persisten entre cookies y procesos: cinco solicitudes de login por correo, cinco de cambio por usuario y cinco de restablecimiento por administrador cada cinco minutos. El login admite ademas 50 solicitudes por direccion de conexion en ese intervalo. Tambien cuentan las solicitudes exitosas. El vencimiento permite volver a intentar; borrar la cookie no reinicia el contador. Los registros con mas de 24 horas se limpian periodicamente durante el uso.
- La direccion utilizada es `REMOTE_ADDR`; no se confia en cabeceras reenviadas arbitrarias. En Render, un proxy puede agrupar conexiones bajo una direccion y compartir el limite. Antes del despliegue, revisar ese comportamiento con la configuracion real del proxy y ajustar el limite o una politica explicita de proxies confiables.

## Comprobacion desde el navegador

Usa una base local de pruebas con 001-009 y cuentas sinteticas de los cuatro roles. Inicia los servidores PHP con la configuracion local indicada en README; frontend y backend deben usar el mismo nombre de cookie y el mismo host (`127.0.0.1`).

1. Entra como paciente, medico, recepcionista y administrador, sucesivamente. Abre **Cambiar contrasena**, prueba los botones Mostrar/Ocultar e intenta actual incorrecta, confirmacion diferente y clave debil. Deben aparecer errores sin cambiar la clave.
2. Con la misma cuenta abierta en otro navegador o perfil, cambia correctamente. La sesion que hizo el cambio sigue abierta; la otra debe exigir login en su siguiente solicitud. Sal y comprueba que la clave anterior falla y la nueva permite entrar.
3. Como administrador, entra a **Administrar cuentas**, selecciona otra cuenta activa y abre **Restablecer contrasena**. Verifica nombre/correo/rol, genera y guarda temporalmente la clave para entregarla, marca la confirmacion y envia. El formulario borra la clave al terminar.
4. En otra sesion, entra con la temporal. Debes ir a **Cambiar contrasena**. Intentar abrir panel o llamar a una API protegida no debe permitir continuar. Introduce la temporal como actual y elige una personal. Una segunda entrada con la temporal debe fallar.
5. En **Bitacora del sistema**, filtra modulo `usuarios` y accion `restablecer_contrasena`; comprueba administrador, identificador de cuenta afectada y fecha. No debe contener contrasenas ni tokens.
6. Como rol no administrativo, comprueba que no aparece el restablecimiento y que el servidor rechaza acceder directamente a esa ruta. Una sesion anonima tampoco puede cambiar claves.

Las sesiones ya abiertas se invalidan al siguiente acceso al servidor; una pantalla previamente renderizada no se borra a distancia.

## Pruebas reproducibles

Pruebas PHP sin conexion a base de datos:

```powershell
& C:\php85\php.exe backend/tests/ejecutar-pruebas.php
```

El ejecutor PHP con `--completo` crea su propio PostgreSQL local desechable dentro de `.tmp-auth/` con datos sinteticos, aplica las migraciones alli y detiene sus procesos al finalizar. Usa el mismo PHP que lo ejecuta y recibe la carpeta de binarios PostgreSQL mediante `--pg-bin`. No necesita Python ni toma las credenciales de Neon del proyecto. Los archivos temporales estan excluidos de Git.

```powershell
& C:/php85/php.exe backend/tests/ejecutar-pruebas.php --completo --pg-bin "C:/Program Files/PostgreSQL/18/bin"
& C:/php85/php.exe deploy/tests/test_configuracion.php
```

`--completo` incluye las suites PHP de integracion y el contrato HTTP anterior en otras dos bases nuevas del mismo cluster, ademas de los diez casos de configuracion. Los servidores fijan tambien `API_INTERNAL_URL` a su API local para evitar heredar la URL de otra instancia. Las salidas de las suites quedan en el directorio de artefactos indicado al terminar, incluso cuando falla una comprobacion. Agrega `--navegador` para los recorridos JavaScript opcionales (Node 22+ y Edge). Requisitos y equivalencias: [pruebas PHP](pruebas-php.md).

La suite de autenticacion comprueba cambio propio y restablecimiento de los cuatro roles, rechazo de permisos/CSRF/identidad manipulada, cambio obligatorio, expiracion y uso unico, revocacion, limites persistentes y ausencia de claves en respuestas y registros. `test_credenciales_transacciones.php` induce un fallo de auditoria para verificar que hash, version, estado temporal y bitacora se revierten juntos; tambien comprueba que dos procesos solo pueden consumir una vez la misma temporal y que el acceso se recupera al vencer la ventana de intentos.

## Recuperacion por correo (actualizacion posterior a 009)

La pantalla de inicio ofrece Olvidaste tu contrasena. Las rutas POST
`/api/autenticacion/recuperar-contrasena` y
`/api/autenticacion/confirmar-recuperacion` son publicas y conservan CSRF.
El token aleatorio se almacena como SHA-256, vence a los 30 minutos y solo sirve
una vez. Reemitir, cambiar la clave, modificar el correo o desactivar la cuenta
invalida el enlace anterior. Confirmar revoca las sesiones anteriores.

Aplicar 010 despues de 009, primero en una copia local de pruebas. Configurar
`MAIL_PROVIDER=resend`, `RESEND_API_KEY`, `MAIL_FROM` verificado y
`FRONTEND_URL` HTTPS en produccion. No guardar secretos en Git. El operador debe
verificar entrega real con una cuenta controlada; las pruebas automaticas solo
usan transporte simulado. El restablecimiento administrativo sigue siendo un
mecanismo independiente.

Detalles, pruebas actuales y limites: [informe de actualizacion](actualizacion-privacidad-recuperacion.md).

## Revision historica del 31 de octubre de 2026

La revision partio de los cambios locales existentes: servicios, migracion, rutas y formularios ya estaban implementados. Se conservaron. Se completo la cobertura de restablecimiento para los cuatro roles y se agregaron pruebas de transacciones, concurrencia y expiracion de limites. Se corrigio la URL interna heredada en el ejecutor del contrato HTTP, se conservaron diagnosticos de las suites y se alinearon la ayuda del formulario y el mensaje de validacion con el limite de 12-72 bytes. README ahora incluye la migracion 009 en la preparacion local.

Resultados en Windows nativo, PHP 8.5.11 y PostgreSQL 18.6, usando bases nuevas locales con datos sinteticos:

- 330 comprobaciones HTTP de autenticacion satisfactorias.
- 18 suites PHP satisfactorias: siete sin BD y once de integracion. Incluyen 19 comprobaciones nuevas de credenciales, la reserva concurrente anterior y las regresiones de citas, consultas, reportes y bitacora.
- 137 comprobaciones satisfactorias del contrato HTTP anterior y sus vistas por rol.
- 10 casos satisfactorios de configuracion de despliegue, sin conexion a BD ni despliegue.
- Sintaxis correcta en 173 archivos PHP y 38 JavaScript; `git diff --check` sin errores. Estos conteos son historicos; la revision del ejecutor PHP actual se registra en [pruebas PHP](pruebas-php.md).
- Edge headless con frontend real y API simulada local: formularios y navegacion de los cuatro roles, Mostrar/Ocultar, confirmacion, generacion temporal y limpieza de claves. Sin errores JavaScript ni desbordamiento horizontal a 390 y 1440 px. Las pruebas HTTP anteriores usaron backend y PostgreSQL reales; la comprobacion del JavaScript en navegador uso respuestas simuladas.

La migracion 009 se ejecuto exclusivamente en los clusters de pruebas. No se modificaron los `.env`, Neon de produccion ni Render, y no se hizo `git push`. Los procesos de las suites se detuvieron al terminar; los artefactos locales quedan excluidos de Git en `.tmp-auth/`.

No quedaron fallos detectados en esos flujos dentro del alcance historico probado. En aquella revision no se incluyo recuperacion por correo; la actualizacion posterior con 010 se describe arriba. Aplicar migraciones en produccion, desplegar y verificar proxy/TLS/cookies en Render son tareas operativas separadas.

## Archivos principales

- Negocio: `AutenticacionService.php`, `CredencialService.php`, `IntentosAutenticacionService.php`, `BitacoraService.php` y `UsuarioValidator.php`.
- Datos: `Usuario.php`, `UsuarioRepository.php`, `IntentoAutenticacionRepository.php` y migracion 009.
- HTTP: `CredencialController.php`, `AutenticacionController.php`, `FabricaControladores.php`, `EnrutadorApi.php`, `RutasAutenticacion.php`, `RutasUsuarios.php` y `SolicitudHttp.php`.
- Interfaz: rutas/proteccion, navegacion, cliente API, login, nuevos formularios de cambio y restablecimiento, lista/creacion de usuarios y componentes de contrasenas. El componente compartido de claves tambien agrega Mostrar/Ocultar en altas que ya lo utilizaban, sin cambiar sus reglas de negocio.
- Operacion y pruebas: script de migracion local, nuevas pruebas de autenticacion, ajustes de contratos/constructores en pruebas existentes, README y esta guia.

La verificacion automatizada no sustituye la revision visual y accesible en los navegadores que utilizara la clinica, ni la comprobacion del proxy/TLS/cookies del entorno Render. No se desplego ni se hizo `git push`.
