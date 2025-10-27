#!/bin/bash
# Script untuk memperbaiki masalah CSS/JS tidak muncul di production
# Jalankan di server production: sysadmin@APBDANALYSIS2026

echo "=========================================="
echo "Fixing CSS/JS Assets in Production"
echo "=========================================="
echo ""

echo "🔧 Step 1: Install Node dependencies in container..."
docker exec apbdanalysis2026_app_prod npm install

echo ""
echo "🔨 Step 2: Build Vite assets..."
docker exec apbdanalysis2026_app_prod npm run build

echo ""
echo "🔗 Step 3: Create storage symlink..."
docker exec apbdanalysis2026_app_prod php artisan storage:link

echo ""
echo "🧹 Step 4: Clear and cache views..."
docker exec apbdanalysis2026_app_prod php artisan view:clear
docker exec apbdanalysis2026_app_prod php artisan view:cache

echo ""
echo "✅ Step 5: Set proper permissions..."
docker exec apbdanalysis2026_app_prod chown -R www-data:www-data /var/www/html/public/build
docker exec apbdanalysis2026_app_prod chmod -R 755 /var/www/html/public/build

echo ""
echo "=========================================="
echo "Testing Assets..."
echo "=========================================="
docker exec apbdanalysis2026_app_prod ls -la /var/www/html/public/build/

echo ""
echo "=========================================="
echo "✅ Done! Assets should now be available."
echo "Refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)"
echo "=========================================="

