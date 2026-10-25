#!/bin/sh
set -eu
export APP_ENV=production
export SESSION_SECURE=true
export PORT=10000
: "${FRONTEND_URL:=${RENDER_EXTERNAL_URL:-}}"
if [ -z "$FRONTEND_URL" ]; then
    echo 'Falta FRONTEND_URL o RENDER_EXTERNAL_URL.' >&2
    exit 1
fi
FRONTEND_URL="${FRONTEND_URL%/}"
export FRONTEND_URL
php /var/www/app/deploy/verificar-configuracion.php
export API_URL="${FRONTEND_URL%/}/api"
export API_INTERNAL_URL=http://127.0.0.1:10000/api
php /var/www/app/deploy/preparar-base.php
# La actualizacion 1.5 se habilita explicitamente desde Environment en Render.
# El actualizador de la entrega aplica 006-008 dentro de una transaccion
# y no repite las migraciones que ya esten instaladas.
if [ "${UPGRADE_V15_DATABASE:-false}" = "true" ]; then
    php /var/www/app/backend/scripts/actualizar-consultas-bitacora.php
fi
# La clave inicial solo se necesita en el proceso de inicializacion.
unset INITIAL_ADMIN_PASSWORD
exec apache2-foreground
