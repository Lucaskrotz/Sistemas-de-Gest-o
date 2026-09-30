# Versão interativa no Render (plano gratuito): um container só, Apache + mod_php + SQLite.
# O disco é efêmero: a cada início o banco é recriado com dados fictícios (a demo "reseta" sozinha).
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install intl zip bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# DocumentRoot em public/ com .htaccess do Laravel habilitado.
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
# ponytail: inclui require-dev porque os seeders da demo usam Faker (fakerphp/faker é dev); imagem maior, mas é só demo.
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader --no-progress

COPY . .
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/render/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start
CMD ["start"]
