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

# Set working directory
WORKDIR /app
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
ENV COMPOSER_PROCESS_TIMEOUT=${COMPOSER_PROCESS_TIMEOUT}
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts

# Set permissions
RUN chown -R www-data:www-data /app

# Optimize PHP-FPM for high concurrency
RUN sed -i 's/pm.max_children = 5/pm.max_children = 200/g' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/pm.start_servers = 2/pm.start_servers = 20/g' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 20/g' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 50/g' /usr/local/etc/php-fpm.d/www.conf

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8000

# Start Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
