# Imagem da aplicação usada SOMENTE no stack E2E.
# Deriva da mesma base do Dockerfile de produção, mas copia o código e as
# dependências para dentro da imagem (sem bind mount do repositório).
FROM php:7.4-fpm-bullseye@sha256:3ac7c8c74b2b047c7cb273469d74fc0d59b857aa44043e6ea6a0084372811d5b

# Debian 11 (bullseye) está no archive; os espelhos regulares removem os
# pacotes de segurança antigos e o apt passa a retornar 404.
RUN rm -f /etc/apt/sources.list \
    && printf '%s\n' \
        'deb http://archive.debian.org/debian bullseye main' \
        'deb http://archive.debian.org/debian-security bullseye-security main' \
        > /etc/apt/sources.list \
    && apt-get -o Acquire::Check-Valid-Until=false update

RUN apt-get install -y \
    git \
    curl \
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
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && ln -s /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/libsybdb.so \
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
