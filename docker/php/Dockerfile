# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-fpm-bookworm AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    PATH="/var/www/html/vendor/bin:${PATH}"

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'expose_php=Off' \
        'memory_limit=256M' \
        'upload_max_filesize=20M' \
        'post_max_size=20M' \
        'max_execution_time=60' \
        'date.timezone=UTC' \
        > "$PHP_INI_DIR/conf.d/99-sentinel.ini"

EXPOSE 9000

CMD ["php-fpm"]


FROM base AS development

ARG INSTALL_XDEBUG=false

ENV APP_ENV=local

RUN mv "$PHP_INI_DIR/php.ini" "$PHP_INI_DIR/php.ini.production" \
    && mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'display_errors=On' \
        'display_startup_errors=On' \
        'error_reporting=E_ALL' \
        'opcache.validate_timestamps=1' \
        'opcache.revalidate_freq=0' \
        > "$PHP_INI_DIR/conf.d/98-development.ini" \
    && if [ "$INSTALL_XDEBUG" = "true" ]; then \
        pecl install xdebug \
        && docker-php-ext-enable xdebug \
        && printf '%s\n' \
            'xdebug.mode=develop,debug' \
            'xdebug.start_with_request=yes' \
            'xdebug.client_host=host.docker.internal' \
            'xdebug.client_port=9003' \
            > "$PHP_INI_DIR/conf.d/97-xdebug.ini"; \
    fi

CMD ["php-fpm"]


FROM base AS production-build

COPY apps/platform/composer.json apps/platform/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY apps/platform/ ./

RUN composer dump-autoload \
        --no-dev \
        --classmap-authoritative \
    && php artisan package:discover --ansi \
    && mkdir -p \
        storage/app \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache


FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --from=production-build --chown=www-data:www-data \
    /var/www/html /var/www/html

RUN printf '%s\n' \
        'display_errors=Off' \
        'display_startup_errors=Off' \
        'log_errors=On' \
        'opcache.enable=1' \
        'opcache.validate_timestamps=0' \
        'opcache.max_accelerated_files=20000' \
        'opcache.memory_consumption=192' \
        'opcache.interned_strings_buffer=16' \
        > "$PHP_INI_DIR/conf.d/98-production.ini"

USER www-data

CMD ["php-fpm"]

