#!/bin/bash

# Deteksi apakah berjalan di Docker
if [ -f "/.dockerenv" ] || [ -f "/.dockerinit" ]; then
    echo "Running in Docker environment"
    cp .env.docker .env
    echo "Using .env.docker configuration"
else
    echo "Running in Laragon environment"
    # .env sudah ada, tidak perlu mengcopy
    echo "Using existing .env for Laragon"
fi

# Generate key jika diperlukan
if [ ! -f "storage/oauth-private.key" ]; then
    php artisan key:generate
    php artisan jwt:secret
fi