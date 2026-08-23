#!/bin/sh
set -e
cd /var/www/html

# El APP_KEY debe venir del .env (definido en build). Verificar:
if grep -q '^APP_KEY=$' .env || ! grep -q '^APP_KEY=' .env; then
    echo "WARN: APP_KEY vacío en .env -> generando"
    php artisan key:generate --force --show 2>&1 || true
fi

# Si la BD está disponible, migrar (no falla el contenedor si la BD tarda)
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan package:discover --ansi 2>&1 || echo "WARN: package:discover omitido"

# Cacheo de producción (solo si config OK)
php artisan optimize --no-interaction 2>&1 || echo "WARN: optimize omitido"

echo "Laravel listo."
exec "$@"
