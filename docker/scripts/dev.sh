#!/bin/bash

# HSAN Analysis Docker Development Script
# This script starts the development environment

set -e

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

print_status "🚀 Starting HSAN Analysis Development Environment..."

# Stop existing containers
print_status "Stopping existing containers..."
docker-compose -f docker-compose.dev.yml down

# Start development containers
print_status "Starting development containers..."
docker-compose -f docker-compose.dev.yml up -d --build

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 20

# Generate application key
print_status "Generating application key..."
docker-compose -f docker-compose.dev.yml exec app php artisan key:generate --force

# Run database migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.dev.yml exec app php artisan migrate --force

# Install Composer dependencies
print_status "Installing Composer dependencies..."
docker-compose -f docker-compose.dev.yml exec app composer install

# Install NPM dependencies
print_status "Installing NPM dependencies..."
docker-compose -f docker-compose.dev.yml exec app npm install

# Build frontend assets for development
print_status "Building frontend assets..."
docker-compose -f docker-compose.dev.yml exec app npm run dev

# Set proper permissions
print_status "Setting permissions..."
docker-compose -f docker-compose.dev.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.dev.yml exec app chmod -R 755 storage bootstrap/cache

print_success "🎉 Development environment is ready!"
print_status "Services available:"
echo "🌐 Application: http://localhost:8000"
echo "🗄️ phpMyAdmin: http://localhost:8081"
echo "📊 Redis: localhost:6380"
echo "🗃️ MariaDB: localhost:3307"

print_status "Useful commands:"
echo "📝 View logs: docker-compose -f docker-compose.dev.yml logs -f"
echo "🔧 Run artisan: docker-compose -f docker-compose.dev.yml exec app php artisan [command]"
echo "📦 Install package: docker-compose -f docker-compose.dev.yml exec app composer require [package]"
echo "🛑 Stop services: docker-compose -f docker-compose.dev.yml down"
