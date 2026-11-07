# Despliegue fuera de desarrollo

El proyecto usa Render y Neon y ya tiene una versión publicada. Las correcciones locales no autorizan un nuevo despliegue ni cambios en esos servicios. Conserva `Dockerfile` y `deploy/`; publica solo puntos de entrada y assets. No publiques .env, src, respaldos o SQL de pruebas ni uses php -S como alojamiento publico.

Configura URL reales, HTTPS, SESSION_SECURE=true, APP_ENV distinto de local, credenciales y permisos SQL limitados, respaldo probado y DB_SSLMODE apropiado al certificado del proveedor. Frontend consulta sesion a la API: verifica host, cookie y origen en la topologia real; no abras CORS global como solucion.

## Antes de publicar una nueva versión

1. Revisar `git diff`, permisos, rutas y resultados locales; ejecutar `git diff --check` y las pruebas aisladas de [pruebas-php.md](pruebas-php.md). No usar la conexión productiva para probar escrituras.
2. Verificar un respaldo y su restauración en una base aislada. Mantener secretos fuera del repositorio y de la imagen.
3. Revisar diferencias de esquema por separado: estas correcciones no agregan migraciones. **009 y 010 ya se trabajaron en Neon; no repetirlas.** Cualquier migración futura requiere autorización, respaldo y ventana controlada.
4. Solo con aprobación del propietario, guardar/publicar los cambios revisados y desplegar en Render. No ejecutar `cargar-demostracion.php` ni otros seeds como parte del despliegue.
5. Con autorización, hacer un smoke test por rol: sesión/CSRF, cookies HTTPS, assets, selectores de médicos, paginación, bloque propio, privacidad y mensajes de conflicto. No usar pacientes reales para ensayos.

Pendiente de comprobar en el servicio publicado: HTTPS/cookies, rutas/assets, SSL/conexión y entrega de Resend cuando se configure su remitente y credenciales. Las pruebas locales no certifican producción ni la restauración operativa. No ampliar CORS ni permisos clínicos para resolver problemas de publicación.
