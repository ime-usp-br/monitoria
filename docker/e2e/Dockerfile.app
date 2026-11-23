# Imagem da aplicação usada SOMENTE no stack E2E.
# Deriva da mesma base do Dockerfile de produção, mas copia o código e as
# dependências para dentro da imagem (sem bind mount do repositório).
FROM php:8.2-fpm-bookworm@sha256:afd39a6125fcb59cbe9ff0e2183bbad56aecc59858f9262c73cf6f13ddb9a9e0

RUN apt-get update && apt-get install -y \
    git \
    curl \
    ca-certificates \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    freetds-dev \
    freetds-common \
    python3 \
    texlive-latex-base \
    texlive-latex-extra \
    texlive-fonts-recommended \
    texlive-lang-portuguese \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring exif pcntl bcmath zip \
    && ln -sf /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/libsybdb.so \
    && SYBASE=/usr docker-php-ext-install pdo_dblib \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . /var/www

RUN mkdir -p /opt/e2e-storage \
    && cp -a storage/app/images /opt/e2e-storage/images

RUN composer dump-autoload --no-dev --optimize --no-interaction

RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/framework/testing \
             storage/logs \
             storage/app/public \
             bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R u+rwX storage bootstrap/cache public

EXPOSE 9000
CMD ["php-fpm"]
