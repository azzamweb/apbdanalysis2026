#!/bin/bash

# APBD Analysis 2026 Production Setup Script
# This script sets up the production environment

set -e

echo "🚀 Setting up APBD Analysis 2026 Production Environment..."

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
check_docker() {
    print_status "Checking Docker installation..."
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed!"
        print_status "Please install Docker first: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed!"
        print_status "Please install Docker Compose first: https://docs.docker.com/compose/install/"
        exit 1
    fi
    
    print_success "Docker and Docker Compose are installed"
}

# Check if .env file exists
check_env_file() {
    print_status "Checking environment configuration..."
    
    if [ ! -f ".env" ]; then
        print_warning ".env file not found!"
        print_status "Creating .env file from production template..."
        
        if [ -f "production.env.example" ]; then
            cp production.env.example .env
            print_success ".env file created from template"
            print_warning "Please edit .env file with your production settings!"
        else
            print_error "production.env.example not found!"
            exit 1
        fi
    else
        print_success ".env file found"
    fi
}

# Generate application key
generate_app_key() {
    print_status "Generating application key..."
    
    # Check if APP_KEY is empty
    if grep -q "APP_KEY=$" .env; then
        print_status "Generating new application key..."
        APP_KEY=$(openssl rand -base64 32)
        sed -i "s/APP_KEY=/APP_KEY=$APP_KEY/" .env
        print_success "Application key generated"
    else
        print_success "Application key already exists"
    fi
}

# Create necessary directories
create_directories() {
    print_status "Creating necessary directories..."
    
    mkdir -p storage/app/public
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    mkdir -p docker/nginx/ssl
    mkdir -p backups
    
    print_success "Directories created"
}

# Set proper permissions
set_permissions() {
    print_status "Setting proper permissions..."
    
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    chmod +x docker/scripts/*.sh
    
    print_success "Permissions set"
}

# Install Composer dependencies
install_dependencies() {
    print_status "Installing Composer dependencies..."
    
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader --no-interaction
        print_success "Composer dependencies installed"
    else
        print_warning "Composer not found, dependencies will be installed in container"
    fi
}

# Create SSL certificates
create_ssl_certificates() {
    print_status "Creating SSL certificates..."
    
    if [ ! -f "docker/nginx/ssl/cert.pem" ] || [ ! -f "docker/nginx/ssl/key.pem" ]; then
        print_status "Generating self-signed SSL certificates..."
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout docker/nginx/ssl/key.pem \
            -out docker/nginx/ssl/cert.pem \
            -subj "/C=ID/ST=Jakarta/L=Jakarta/O=APBD Analysis 2026/OU=IT Department/CN=localhost"
        print_success "SSL certificates generated"
    else
        print_success "SSL certificates already exist"
    fi
}

# Validate configuration
validate_config() {
    print_status "Validating configuration..."
    
    # Check required environment variables
    required_vars=("DB_DATABASE" "DB_USERNAME" "DB_PASSWORD" "REDIS_PASSWORD")
    
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" .env || grep -q "^$var=$" .env; then
            print_error "Required environment variable $var is not set!"
            print_status "Please edit .env file and set $var"
            exit 1
        fi
    done
    
    print_success "Configuration validated"
}

# Main setup function
main() {
    print_status "Starting production setup..."
    
    check_docker
    check_env_file
    generate_app_key
    create_directories
    set_permissions
    install_dependencies
    create_ssl_certificates
    validate_config
    
    print_success "🎉 Production setup completed!"
    print_status ""
    print_status "Next steps:"
    print_status "1. Review and edit .env file with your production settings"
    print_status "2. Run 'make prod' to deploy to production"
    print_status "3. Access your application at https://localhost"
    print_status ""
    print_status "Useful commands:"
    print_status "  - Deploy: make prod"
    print_status "  - View logs: make logs-prod"
    print_status "  - Check status: make status-prod"
    print_status "  - Access shell: make shell-prod"
}

# Run main function
main "$@"
