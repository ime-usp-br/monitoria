# Etapa de build dos assets (Vite).
FROM node:22-alpine@sha256:1314fb30ec37592d5845e5a160d522c0e680972aa534549940b8d73bde88481a AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Imagem da aplicação usada SOMENTE no stack E2E.
# Deriva da mesma base do Dockerfile de produção, mas copia o código e as
# dependências para dentro da imagem (sem bind mount do repositório).
FROM php:8.5-fpm-bookworm@sha256:bad1d3498eaa489efe88c1d532e47e2f4111304c3f0b19fbc428842c39ec155c

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
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    freetds-dev \
    freetds-common \
    python3 \
    python3-pip \
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

COPY app/Scripts/Python/requirements.txt /tmp/python-requirements.txt
RUN pip3 install --break-system-packages --no-cache-dir -r /tmp/python-requirements.txt \
    && rm -f /tmp/python-requirements.txt

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . /var/www

COPY --from=assets /app/public/build /var/www/public/build

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
