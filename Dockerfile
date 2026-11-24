FROM php:8.5-fpm-bookworm@sha256:bad1d3498eaa489efe88c1d532e47e2f4111304c3f0b19fbc428842c39ec155c

# Arguments defined in docker-compose.yml
ARG user=monitoria
ARG uid=1000

# Install system dependencies
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
    texlive-latex-base \
    texlive-latex-extra \
    texlive-fonts-recommended \
    texlive-lang-portuguese \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring exif pcntl bcmath zip \
    && ln -sf /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/libsybdb.so \
    && SYBASE=/usr docker-php-ext-install pdo_dblib \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install gosu for running commands as a different user
RUN curl -o /usr/local/bin/gosu -sSL "https://github.com/tianon/gosu/releases/download/1.17/gosu-$(dpkg --print-architecture)" \
    && chmod +x /usr/local/bin/gosu

# Get Composer 2
COPY --from=composer:2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 /usr/bin/composer /usr/bin/composer

# Install Node.js 22 LTS (required for Laravel Mix / webpack)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
