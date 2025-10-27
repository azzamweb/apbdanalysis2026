#!/bin/bash
# Script untuk deploy ke production server
# Jalankan di server production: sysadmin@APBDANALYSIS2026

set -e  # Exit on error

echo "=========================================="
echo "🚀 Production Deployment Script"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "docker-compose.prod.yml" ]; then
    print_error "docker-compose.prod.yml not found! Are you in the project directory?"
    exit 1
fi

print_info "Step 1: Pulling latest code from git..."
git pull origin main || {
    print_error "Git pull failed! Please resolve conflicts manually."
    exit 1
}
print_success "Code updated"

print_info "Step 2: Verifying lock files exist..."
if [ ! -f "composer.lock" ]; then
    print_error "composer.lock not found! Please commit it to git."
    exit 1
fi
if [ ! -f "package-lock.json" ]; then
    print_error "package-lock.json not found! Please commit it to git."
    exit 1
fi
print_success "Lock files verified"

print_info "Step 3: Stopping existing containers..."
docker compose -f docker-compose.prod.yml down
print_success "Containers stopped"

print_info "Step 4: Removing old images (optional)..."
docker image rm apbdanalysis2026-app 2>/dev/null || true
print_success "Old images cleaned"

print_info "Step 5: Building new Docker image (this may take 10-20 minutes)..."
docker compose -f docker-compose.prod.yml build --no-cache app || {
    print_error "Docker build failed! Check error messages above."
    print_info "Common issues:"
    print_info "  - Network timeout: Try again or use vendor archive method"
    print_info "  - Insufficient memory: Increase Docker memory limit"
    print_info "  - Disk space: Run 'docker system prune -a' to free space"
    exit 1
}
print_success "Docker image built successfully"

print_info "Step 6: Starting containers..."
docker compose -f docker-compose.prod.yml up -d
print_success "Containers started"

print_info "Step 7: Waiting for containers to be healthy (45 seconds)..."
sleep 45

print_info "Step 8: Running post-deployment tasks..."

# Storage link
docker exec apbdanalysis2026_app_prod php artisan storage:link 2>/dev/null || print_info "Storage link already exists"

# Run migrations
docker exec apbdanalysis2026_app_prod php artisan migrate --force || {
    print_error "Migration failed!"
    exit 1
}
print_success "Migrations completed"

# Clear caches
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan cache:clear
docker exec apbdanalysis2026_app_prod php artisan route:clear
docker exec apbdanalysis2026_app_prod php artisan view:clear
print_success "Caches cleared"

# Rebuild caches
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache
print_success "Caches rebuilt"

print_info "Step 9: Verifying deployment..."

# Check if app is responding
HTTP_STATUS=$(docker exec apbdanalysis2026_app_prod curl -s -o /dev/null -w "%{http_code}" http://localhost/)

if [ "$HTTP_STATUS" = "200" ]; then
    print_success "Application is responding with HTTP 200 OK"
else
    print_error "Application returned HTTP $HTTP_STATUS"
    print_info "Checking logs..."
    docker exec apbdanalysis2026_app_prod tail -20 storage/logs/laravel.log
    exit 1
fi

# Check if assets exist
ASSETS_COUNT=$(docker exec apbdanalysis2026_app_prod find /var/www/html/public/build -type f 2>/dev/null | wc -l)
if [ "$ASSETS_COUNT" -gt 0 ]; then
    print_success "Assets built successfully ($ASSETS_COUNT files)"
else
    print_error "No assets found in public/build/"
    exit 1
fi

echo ""
echo "=========================================="
echo "🎉 Deployment completed successfully!"
echo "=========================================="
echo ""
echo "Services are running at:"
echo "  - Application: http://$(hostname -I | awk '{print $1}'):5560"
echo "  - phpMyAdmin:  http://$(hostname -I | awk '{print $1}'):5561"
echo ""
echo "Container status:"
docker ps | grep apbdanalysis2026
echo ""
print_info "To check logs: docker logs apbdanalysis2026_app_prod --tail 50"
print_info "To check health: docker exec apbdanalysis2026_app_prod curl http://localhost/health"
echo ""

