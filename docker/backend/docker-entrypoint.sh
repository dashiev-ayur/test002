#!/bin/sh
set -e

if [ -z "${SKIP_DB_MIGRATIONS:-}" ]; then
  runuser -u www-data -- php bin/console doctrine:migrations:migrate --no-interaction
fi

if [ -z "${SKIP_DB_SEEDS:-}" ]; then
  runuser -u www-data -- php bin/console app:seed-demo --no-interaction
fi

chown -R www-data:www-data var 2>/dev/null || true

php-fpm --daemonize

exec nginx -g 'daemon off;'
