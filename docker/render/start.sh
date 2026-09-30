#!/bin/sh
set -e

# Render informa a porta em $PORT (padrão 10000).
PORTA="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORTA}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORTA}>/" /etc/apache2/sites-available/000-default.conf

# Sem APP_KEY configurada, gera uma por inicialização (sessões não precisam sobreviver a reinícios).
export APP_KEY="${APP_KEY:-base64:$(head -c 32 /dev/urandom | base64)}"
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Banco novo a cada início: dados fictícios sempre consistentes.
touch "${DB_DATABASE:-database/database.sqlite}"
php artisan migrate:fresh --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache database

exec apache2-foreground
