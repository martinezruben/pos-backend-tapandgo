#!/bin/sh
set -e
cd /var/www/html

# Asegurar APP_KEY (definido en build, pero verifica)
if ! grep -q '^APP_KEY=base64:' .env; then
    echo "WARN: APP_KEY no encontrado -> generando"
    php artisan key:generate --force --show 2>&1 || true
fi

# Limpiar cualquier config cache (los tests dependen de APP_ENV=testing del .env/phpunit)
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan optimize:clear --no-interaction 2>/dev/null || true

# Descubrir paquetes (necesario tras composer install --no-scripts)
php artisan package:discover --ansi 2>&1 || echo "WARN: package:discover falló"

echo "Laravel listo."
exec "$@"
