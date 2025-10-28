#!/bin/bash
# Script untuk fix CSP violations di production
# Jalankan di server production: sysadmin@APBDANALYSIS2026

set -e

echo "=========================================="
echo "🔧 Fixing CSP Violations in Production"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

cd ~/dev/apbdanalysis2026

echo -e "${YELLOW}Step 1: Pulling latest changes from git...${NC}"
git pull origin main

echo ""
echo -e "${YELLOW}Step 2: Verifying nginx config updated...${NC}"
if grep -q "code.jquery.com" docker/nginx/nginx-prod.conf; then
    echo -e "${GREEN}✅ Nginx config contains CDN allowlist${NC}"
else
    echo -e "${RED}❌ Nginx config not updated! Check git pull${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3: Stopping containers...${NC}"
docker compose -f docker-compose.prod.yml down

echo ""
echo -e "${YELLOW}Step 4: Rebuilding container with new config...${NC}"
docker compose -f docker-compose.prod.yml build app

echo ""
echo -e "${YELLOW}Step 5: Starting containers...${NC}"
docker compose -f docker-compose.prod.yml up -d

echo ""
echo -e "${YELLOW}Step 6: Waiting for containers to be healthy (45 seconds)...${NC}"
sleep 45

echo ""
echo -e "${YELLOW}Step 7: Verifying .env file exists...${NC}"
if docker exec apbdanalysis2026_app_prod test -f /var/www/html/.env; then
    echo -e "${GREEN}✅ .env file exists${NC}"
else
    echo -e "${RED}❌ .env file missing! Copy it to container:${NC}"
    echo "   docker cp .env apbdanalysis2026_app_prod:/var/www/html/.env"
fi

echo ""
echo -e "${YELLOW}Step 8: Checking assets...${NC}"
ASSET_COUNT=$(docker exec apbdanalysis2026_app_prod find /var/www/html/public/build -type f 2>/dev/null | wc -l)
echo "Assets found: $ASSET_COUNT files"

if [ "$ASSET_COUNT" -lt 5 ]; then
    echo -e "${YELLOW}⚠️  Assets missing or incomplete, rebuilding...${NC}"
    docker exec apbdanalysis2026_app_prod npm install
    docker exec apbdanalysis2026_app_prod npm run build
    echo -e "${GREEN}✅ Assets rebuilt${NC}"
fi

echo ""
echo -e "${YELLOW}Step 9: Clearing and rebuilding caches...${NC}"
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache
echo -e "${GREEN}✅ Caches rebuilt${NC}"

echo ""
echo -e "${YELLOW}Step 10: Running migrations...${NC}"
docker exec apbdanalysis2026_app_prod php artisan migrate --force
echo -e "${GREEN}✅ Migrations completed${NC}"

echo ""
echo -e "${YELLOW}Step 11: Testing application...${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://103.130.82.202:5560/)
echo "HTTP Response: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ SUCCESS! Application is running${NC}"
else
    echo -e "${RED}❌ ERROR! Application returned HTTP $HTTP_CODE${NC}"
    echo ""
    echo "Check logs with:"
    echo "  docker logs apbdanalysis2026_app_prod --tail 50"
    echo "  docker exec apbdanalysis2026_app_prod tail -50 storage/logs/laravel.log"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 12: Verifying CSP headers...${NC}"
CSP_HEADER=$(curl -s -I http://103.130.82.202:5560/ | grep -i "Content-Security-Policy" || true)
if echo "$CSP_HEADER" | grep -q "code.jquery.com"; then
    echo -e "${GREEN}✅ CSP header includes CDN allowlist${NC}"
    echo "$CSP_HEADER"
else
    echo -e "${RED}⚠️  CSP header might not be updated${NC}"
    echo "$CSP_HEADER"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ Fix Complete!${NC}"
echo "=========================================="
echo ""
echo "Please verify in browser:"
echo "  1. Open: http://103.130.82.202:5560/"
echo "  2. Press F12 to open Developer Console"
echo "  3. Check Console tab for errors"
echo "  4. Verify no CSP violations"
echo "  5. Check all resources loaded (jQuery, DataTables, Google Fonts)"
echo ""
echo "Container status:"
docker ps | grep apbdanalysis2026
echo ""

