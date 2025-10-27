#!/bin/bash
# Script untuk fix credential issues di production
# Jalankan di server production: sysadmin@APBDANALYSIS2026

echo "=========================================="
echo "🔧 Fixing Credentials Issues"
echo "=========================================="
echo ""

echo "Step 1: Check current .env database credentials..."
docker exec apbdanalysis2026_app_prod cat .env | grep -E "DB_|REDIS_|CACHE_|SESSION_"

echo ""
echo "Step 2: Check docker-compose environment variables..."
echo "Looking for DB_PASSWORD and REDIS_PASSWORD in docker-compose.prod.yml..."

echo ""
echo "Step 3: Test database connection..."
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB Connection: OK'; } catch (Exception \$e) { echo 'DB Connection: FAILED - ' . \$e->getMessage(); }"

echo ""
echo "Step 4: Test Redis connection..."
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="try { Redis::connection()->ping(); echo 'Redis Connection: OK'; } catch (Exception \$e) { echo 'Redis Connection: FAILED - ' . \$e->getMessage(); }"

echo ""
echo "=========================================="
echo "If connections failed, you need to:"
echo "1. Check .env file has correct passwords"
echo "2. Match passwords with docker-compose.prod.yml"
echo "3. Restart containers after fixing"
echo "=========================================="

