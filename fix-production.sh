#!/bin/bash
# Script untuk memperbaiki production server
# Jalankan di server production: sysadmin@APBDANALYSIS2026

echo "=========================================="
echo "Fixing Production Server Issues"
echo "=========================================="
echo ""

echo "1. Clear configuration cache..."
docker exec apbdanalysis2026_app_prod php artisan config:clear

echo ""
echo "2. Rebuild configuration cache..."
docker exec apbdanalysis2026_app_prod php artisan config:cache

echo ""
echo "3. Clear route cache..."
docker exec apbdanalysis2026_app_prod php artisan route:clear

echo ""
echo "4. Rebuild route cache..."
docker exec apbdanalysis2026_app_prod php artisan route:cache

echo ""
echo "5. Clear view cache..."
docker exec apbdanalysis2026_app_prod php artisan view:clear

echo ""
echo "6. Clear application cache..."
docker exec apbdanalysis2026_app_prod php artisan cache:clear

echo ""
echo "7. Run database migrations..."
docker exec apbdanalysis2026_app_prod php artisan migrate --force

echo ""
echo "8. Optimize application..."
docker exec apbdanalysis2026_app_prod php artisan optimize

echo ""
echo "=========================================="
echo "Testing Application..."
echo "=========================================="
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ 2>/dev/null | head -5

echo ""
echo "=========================================="
echo "Done! Check the HTTP response above."
echo "If you see 'HTTP/1.1 200 OK', the fix was successful!"
echo "=========================================="

