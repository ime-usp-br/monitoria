FROM php:8.5-fpm-bookworm@sha256:bad1d3498eaa489efe88c1d532e47e2f4111304c3f0b19fbc428842c39ec155c AS base

ARG user=monitoria
ARG uid=1000

RUN apt-get update && apt-get install -y \
    ca-certificates \
    util-linux \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
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

COPY app/Scripts/Python/requirements.txt /tmp/python-requirements.txt
RUN pip3 install --break-system-packages --no-cache-dir -r /tmp/python-requirements.txt \
    && rm -f /tmp/python-requirements.txt


RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user \
    && chown -R $user:$user /home/$user

WORKDIR /var/www

EXPOSE 9000

FROM base AS test
COPY --from=composer:2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 /usr/bin/composer /usr/bin/composer

FROM base AS runtime
CMD ["php-fpm"]
