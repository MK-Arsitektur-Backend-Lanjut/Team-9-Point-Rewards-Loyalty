FROM php:8.1-fpm

ARG COMPOSER_PROCESS_TIMEOUT=2000

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

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

ENV COMPOSER_PROCESS_TIMEOUT=${COMPOSER_PROCESS_TIMEOUT}
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts

COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
