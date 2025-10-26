#!/bin/bash

# HSAN Analysis Docker Setup Script
# This script sets up the Docker environment for the application

set -e

echo "🚀 Setting up HSAN Analysis Docker Environment..."

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

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create necessary directories
print_status "Creating necessary directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p docker/logs/{nginx,mysql,redis,php}

# Set permissions
print_status "Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    print_status "Creating .env file from docker.env..."
    cp docker.env .env
else
    print_warning ".env file already exists. Skipping..."
fi

# Generate application key if not set
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    print_status "Generating application key..."
    # We'll generate this after the container is running
    print_warning "Application key will be generated after container startup"
fi

print_success "Setup completed successfully!"
print_status "Next steps:"
echo "1. Run 'docker-compose up -d' to start the services"
echo "2. Run 'docker-compose exec app php artisan key:generate' to generate app key"
echo "3. Run 'docker-compose exec app php artisan migrate' to run migrations"
echo "4. Access the application at http://localhost:8000"
echo "5. Access phpMyAdmin at http://localhost:8080"
