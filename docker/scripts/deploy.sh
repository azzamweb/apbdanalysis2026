#!/bin/bash

# HSAN Analysis Production Deployment Script
# This script handles the deployment of the application to production

set -e

echo "🚀 Starting HSAN Analysis Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env file exists
if [ ! -f ".env" ]; then
    print_error ".env file not found!"
    print_status "Please copy production.env.example to .env and configure it"
    exit 1
fi

# Check if required environment variables are set
print_status "Checking environment variables..."
if [ -z "$DB_PASSWORD" ] || [ -z "$REDIS_PASSWORD" ]; then
    print_error "Required environment variables not set!"
    print_status "Please set DB_PASSWORD and REDIS_PASSWORD in your .env file"
    exit 1
fi

# Create SSL directory if it doesn't exist
print_status "Setting up SSL directory..."
mkdir -p docker/nginx/ssl

# Generate self-signed certificate if it doesn't exist
if [ ! -f "docker/nginx/ssl/cert.pem" ] || [ ! -f "docker/nginx/ssl/key.pem" ]; then
    print_warning "SSL certificates not found. Generating self-signed certificates..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout docker/nginx/ssl/key.pem \
        -out docker/nginx/ssl/cert.pem \
        -subj "/C=ID/ST=Jakarta/L=Jakarta/O=HSAN Analysis/OU=IT Department/CN=localhost"
    print_success "Self-signed SSL certificates generated"
fi

# Stop existing containers
print_status "Stopping existing containers..."
docker-compose -f docker-compose.prod.yml down --remove-orphans

# Build and start production containers
print_status "Building and starting production containers..."
docker-compose -f docker-compose.prod.yml up --build -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Check if containers are running
print_status "Checking container status..."
if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
    print_error "Some containers failed to start!"
    docker-compose -f docker-compose.prod.yml logs
    exit 1
fi

# Run database migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# Clear and cache application
print_status "Optimizing application..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan event:cache

# Set proper permissions
print_status "Setting proper permissions..."
docker-compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/storage
docker-compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose -f docker-compose.prod.yml exec -T app chmod -R 755 /var/www/html/storage
docker-compose -f docker-compose.prod.yml exec -T app chmod -R 755 /var/www/html/bootstrap/cache

# Health check
print_status "Performing health check..."
sleep 10

if curl -f -s http://localhost/health > /dev/null; then
    print_success "Application is healthy and responding!"
else
    print_warning "Health check failed, but deployment completed"
fi

# Show final status
print_status "Deployment completed! Container status:"
docker-compose -f docker-compose.prod.yml ps

print_success "🎉 HSAN Analysis Production Deployment Completed!"
print_status "Application is available at:"
print_status "  - HTTP: http://localhost"
print_status "  - HTTPS: https://localhost"
print_status "  - phpMyAdmin: http://localhost:8080"
print_status ""
print_status "Useful commands:"
print_status "  - View logs: make logs-prod"
print_status "  - Access shell: make shell-prod"
print_status "  - Check status: make status-prod"
print_status "  - Stop services: make stop"