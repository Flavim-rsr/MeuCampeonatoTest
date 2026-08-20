FROM php:8.4-cli-alpine

RUN apk add --no-cache python3 \
    && docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize
RUN test -f .env || cp .env.example .env
RUN php artisan key:generate --force

EXPOSE 8000
ENTRYPOINT ["sh", "docker/entrypoint.sh"]
