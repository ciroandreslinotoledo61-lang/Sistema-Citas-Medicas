# Despliegue fuera de desarrollo

No se publico ni valido un proveedor de nube en esta entrega. Usa PHP compatible/PostgreSQL 18 y publica solo puntos de entrada y assets. Adapta rutas de backend/src/server.php y frontend/src/server.php al servidor web. No publiques .env, src, respaldos o SQL de pruebas ni uses php -S como alojamiento publico.

Configura URL reales, HTTPS, SESSION_SECURE=true, APP_ENV distinto de local, credenciales y permisos SQL limitados, respaldo probado y DB_SSLMODE apropiado al certificado del proveedor. Frontend consulta sesion a la API: verifica host, cookie y origen en la topologia real; no abras CORS global como solucion.

Migra en una ventana controlada con credenciales administrativas. No repitas seeder en una base existente ni publiques cuentas de demostracion. Pendiente de validar: HTTPS/cookies, rewrite, assets, SSL/conexion, concurrencia nativa y restauracion.
