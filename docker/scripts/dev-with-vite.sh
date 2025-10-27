#!/bin/bash

# APBD Analysis 2026 Development Script with Vite
# This script starts the development environment with Vite dev server

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

print_status "🚀 Starting APBD Analysis 2026 Development Environment with Vite..."

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
if docker-compose -f docker-compose.dev.yml exec app npm install 2>/dev/null; then
    print_success "NPM dependencies installed successfully"
else
    print_warning "Failed to install NPM dependencies, continuing..."
fi

# Set proper permissions
print_status "Setting permissions..."
docker-compose -f docker-compose.dev.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.dev.yml exec app chmod -R 755 storage bootstrap/cache

print_success "🎉 Development environment is ready!"
print_status "Services available:"
echo "🌐 Application: http://localhost:5560"
echo "🗄️ phpMyAdmin: http://localhost:5561"
echo "⚡ Vite Dev Server: http://localhost:5173"
echo "📊 Redis: localhost:6380"
echo "🗃️ MariaDB: localhost:3307"

print_status "Useful commands:"
echo "📝 View logs: docker-compose -f docker-compose.dev.yml logs -f"
echo "🔧 Run artisan: docker-compose -f docker-compose.dev.yml exec app php artisan [command]"
echo "📦 Install package: docker-compose -f docker-compose.dev.yml exec app composer require [package]"
echo "🛑 Stop services: docker-compose -f docker-compose.dev.yml down"
echo "🔄 Restart Vite: docker-compose -f docker-compose.dev.yml exec app npm run dev -- --host 0.0.0.0"
