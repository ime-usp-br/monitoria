# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# Monitoria — imagem única de aplicação (Laravel 13 / PHP 8.5)
#
# Estágios:
#   base      — PHP-FPM + extensões + runtime (Python/LaTeX/Sybase)
#   assets    — build dos assets Vite
#   vendor    — dependências de produção
#   vendor-dev— dependências de teste
#   runtime   — imagem de deploy (php-fpm), usada por app e worker
#   web       — nginx dedicado (proxy HTTP) com os assets públicos
#   test      — imagem isolada para PHPUnit
#   final     — alias do runtime para `docker build` sem --target
# ---------------------------------------------------------------------------

FROM php:8.5-fpm-bookworm@sha256:bad1d3498eaa489efe88c1d532e47e2f4111304c3f0b19fbc428842c39ec155c AS base

ENV TZ=America/Sao_Paulo \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        util-linux \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpq-dev \
        freetds-dev \
        freetds-common \
        default-mysql-client \
        postgresql-client \
        python3 \
        python3-pip \
        texlive-latex-base \
        texlive-latex-extra \
        texlive-fonts-recommended \
        texlive-lang-portuguese \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql pdo_pgsql mbstring exif pcntl bcmath zip \
    && ln -sf /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/libsybdb.so \
    && SYBASE=/usr docker-php-ext-install pdo_dblib \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY app/Scripts/Python/requirements.txt /tmp/python-requirements.txt
RUN pip3 install --break-system-packages --no-cache-dir -r /tmp/python-requirements.txt \
    && rm -f /tmp/python-requirements.txt

WORKDIR /var/www

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# ---------------------------------------------------------------------------
# Assets (Vite)
# ---------------------------------------------------------------------------
FROM node:22-alpine@sha256:1314fb30ec37592d5845e5a160d522c0e680972aa534549940b8d73bde88481a AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------------------------------------------------------------------------
# Dependências de produção
# ---------------------------------------------------------------------------
FROM composer:2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader \
        --ignore-platform-req=ext-pdo_dblib

# ---------------------------------------------------------------------------
# Dependências de teste
# ---------------------------------------------------------------------------
FROM composer:2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 AS vendor-dev

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader \
        --ignore-platform-req=ext-pdo_dblib

# ---------------------------------------------------------------------------
# Runtime de deploy
# ---------------------------------------------------------------------------
FROM base AS runtime

COPY . /var/www
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=assets /app/public/build /var/www/public/build

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage \
    && php artisan package:discover --ansi \
    && php artisan vendor:publish --provider="Uspdev\UspTheme\ServiceProvider" --tag=assets --force \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R u+rwX,g+rwX storage bootstrap/cache public \
    && chmod +x docker/entrypoint.sh scripts/*.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["/var/www/docker/entrypoint.sh"]

# ---------------------------------------------------------------------------
# Nginx dedicado (proxy HTTP)
# ---------------------------------------------------------------------------
FROM nginx:1.30.7-alpine@sha256:caf4e1e032a20f9c3c5e28fc7f25ffd768ad8677b4975c54193075997b3a97e3 AS web

ENV WEB_PORT=8080

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY scripts/healthcheck.sh /usr/local/bin/healthcheck.sh
COPY --from=runtime /var/www/public /var/www/public

RUN chmod +x /usr/local/bin/healthcheck.sh

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --retries=5 --start-period=60s \
    CMD ["sh", "/usr/local/bin/healthcheck.sh"]

# ---------------------------------------------------------------------------
# Imagem de testes (PHPUnit)
# ---------------------------------------------------------------------------
FROM base AS test

COPY . /var/www
COPY --from=vendor-dev /app/vendor /var/www/vendor
COPY --from=assets /app/public/build /var/www/public/build

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage \
    && php artisan package:discover --ansi \
    && php artisan vendor:publish --provider="Uspdev\UspTheme\ServiceProvider" --tag=assets --force \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R u+rwX,g+rwX storage bootstrap/cache public

USER www-data

CMD ["vendor/bin/phpunit", "--colors=never", "--do-not-cache-result"]

# ---------------------------------------------------------------------------
# Alvo padrão = runtime (evita publicar a imagem de teste por engano)
# ---------------------------------------------------------------------------
FROM runtime AS final
