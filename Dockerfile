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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install Composer dependencies
ENV COMPOSER_PROCESS_TIMEOUT=${COMPOSER_PROCESS_TIMEOUT}
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts

# Tune PHP-FPM pool for higher concurrency
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set permissions
RUN chown -R www-data:www-data /app

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
