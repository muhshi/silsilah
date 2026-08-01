FROM dunglas/frankenphp:php8.4

ENV SERVER_NAME=":80"

WORKDIR /app

# Install system dependencies & Chromium (for Spatie Browsershot PNG/PDF export)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    chromium \
    fontconfig \
    fonts-dejavu-core \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl gd zip pdo_mysql opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# Copy package files for node dependencies caching
COPY package.json package-lock.json vite.config.js ./

# Install Node.js dependencies
RUN npm ci || npm install

# Copy application code
COPY . /app

# Run composer dump-autoload
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Set permissions for storage & bootstrap/cache
RUN mkdir -p /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Copy custom PHP & Caddy configurations
COPY docker/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443 443/udp
