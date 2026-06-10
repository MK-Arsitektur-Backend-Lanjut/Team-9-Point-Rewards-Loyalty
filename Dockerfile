FROM php:8.1-fpm

# Accept build argument
ARG COMPOSER_PROCESS_TIMEOUT=2000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    default-libmysqlclient-dev \
    libmcrypt-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql
    g++ \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql mysqli zip gd mbstring exif pcntl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install Composer dependencies
ENV COMPOSER_PROCESS_TIMEOUT=${COMPOSER_PROCESS_TIMEOUT}
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts

# Set permissions
RUN chown -R www-data:www-data /app

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
