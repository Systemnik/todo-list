FROM php:8.3-cli-alpine AS test_image

RUN apk add --no-cache postgresql-dev zip bash \
    && docker-php-ext-install pdo_pgsql opcache

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
