FROM php:8.3-fpm-alpine AS test_image

RUN apk add --no-cache postgresql-dev zip bash \
    && docker-php-ext-install pdo_pgsql opcache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
