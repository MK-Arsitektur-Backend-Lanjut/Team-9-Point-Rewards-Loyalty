#!/bin/bash

echo "=========================================="
echo "Team 9 Loyalty Rewards - Setup Script"
echo "=========================================="
echo ""

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not found. Please install Docker."
    exit 1
fi

echo "✅ Docker found"

# Stop existing containers
echo ""
echo "Stopping existing containers..."
docker-compose down

# Build & start containers
echo ""
echo "Building and starting Docker containers..."
docker-compose up -d --build

echo ""
echo "Waiting for containers to be ready..."
sleep 10

# Install composer dependencies
echo ""
echo "Installing Composer dependencies..."
docker-compose exec -T app composer install

# Generate app key
echo ""
echo "Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo ""
echo "Running database migrations..."
docker-compose exec -T app php artisan migrate

# Seed database
echo ""
echo "Seeding database with test data..."
echo "This may take a few minutes..."
docker-compose exec -T app php artisan db:seed

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "API Base URL: http://localhost:8000/api"
echo "Database: localhost:5432"
echo "Username: postgres"
echo "Password: secret"
echo "Database: loyalty_db"
echo ""
echo "Next steps:"
echo "1. Import postman_collection.json to Postman"
echo "2. Start testing API endpoints"
echo ""
echo "View logs: docker-compose logs -f app"
echo "SSH to app: docker-compose exec app bash"
echo ""
