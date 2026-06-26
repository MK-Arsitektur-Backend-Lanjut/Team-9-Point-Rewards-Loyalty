FROM php:8.1-fpm

ARG COMPOSER_PROCESS_TIMEOUT=2000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    default-libmysqlclient-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    mbstring \
    exif \
    pcntl \
    bcmath

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy project
COPY . .

# Environment
ENV COMPOSER_PROCESS_TIMEOUT=${COMPOSER_PROCESS_TIMEOUT}

# ✅ Laravel folders (dari Modul-3)
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# ✅ Install dependencies (balanced)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# ✅ Permission fix (gabungan terbaik)
RUN chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 755 /app

# ✅ Custom PHP-FPM config (dari main)
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

CMD ["php-fpm"]