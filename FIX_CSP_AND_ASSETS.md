# Fix: CSP Violations & Missing Assets di Production

## 🔴 Masalah yang Ditemukan

Dari browser console error terlihat 2 masalah:

### 1. Content Security Policy (CSP) Violations
```
Refused to load the script 'https://code.jquery.com/jquery-3.6.0.min.js'
Refused to load the stylesheet 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700...'
Refused to load the stylesheet 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css'
Refused to load the stylesheet 'https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css'
```

**Penyebab**: CSP di nginx terlalu strict, memblokir external CDN resources.

### 2. Assets 404 Not Found
```
Failed to load resource: the server responded with a status of 404 (Not Found) aos.css:1
```

**Penyebab**: Assets tidak ter-build atau path tidak benar.

---

## ✅ Solusi

### Fix 1: Update CSP Policy (Sudah Dilakukan)

File `docker/nginx/nginx-prod.conf` sudah diupdate untuk allow CDN resources:

**Before:**
```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';" always;
```

**After:**
```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.datatables.net https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.datatables.net https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net; connect-src 'self';" always;
```

**Yang Ditambahkan:**
- ✅ `code.jquery.com` - jQuery library
- ✅ `cdn.datatables.net` - DataTables plugin
- ✅ `cdn.jsdelivr.net` - Bootstrap Icons & other CDN
- ✅ `fonts.googleapis.com` - Google Fonts CSS
- ✅ `fonts.gstatic.com` - Google Fonts files

---

## 🚀 Cara Apply Fix di Production

### Option 1: Rebuild Container (Recommended - Permanent Fix)

```bash
# Di server production (sysadmin@APBDANALYSIS2026)
cd ~/dev/apbdanalysis2026

# Pull latest changes
git pull origin main

# Verify nginx config updated
grep -A 1 "Updated CSP" docker/nginx/nginx-prod.conf

# Rebuild container dengan nginx config baru
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build --no-cache app
docker compose -f docker-compose.prod.yml up -d

# Wait for containers
sleep 45

# Test
curl -I http://103.130.82.202:5560/ | grep "200 OK"
```

### Option 2: Update Config Tanpa Rebuild (Quick Fix)

```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Pull latest changes
git pull origin main

# Copy nginx config ke running container
docker cp docker/nginx/nginx-prod.conf apbdanalysis2026_app_prod:/etc/nginx/http.d/default.conf

# Reload nginx inside container
docker exec apbdanalysis2026_app_prod nginx -t
docker exec apbdanalysis2026_app_prod nginx -s reload

# Test
curl -I http://103.130.82.202:5560/ | grep "Content-Security-Policy"
```

---

## 🔧 Fix Missing Assets (aos.css 404)

### Check Missing Assets

```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Check if build folder exists in container
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/public/build/

# Check manifest.json
docker exec apbdanalysis2026_app_prod cat /var/www/html/public/build/manifest.json

# List all assets
docker exec apbdanalysis2026_app_prod find /var/www/html/public/build -type f
```

### If Assets Missing - Rebuild Assets

```bash
# Install dependencies and build assets
docker exec apbdanalysis2026_app_prod npm install
docker exec apbdanalysis2026_app_prod npm run build

# Verify assets built
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/public/build/assets/

# Clear view cache
docker exec apbdanalysis2026_app_prod php artisan view:clear
docker exec apbdanalysis2026_app_prod php artisan view:cache
```

---

## 📋 Complete Fix Script (All-in-One)

Jalankan ini untuk fix semua masalah sekaligus:

```bash
#!/bin/bash
# Di server production
cd ~/dev/apbdanalysis2026

echo "=== Step 1: Pull latest changes ==="
git pull origin main

echo ""
echo "=== Step 2: Rebuild container with new nginx config ==="
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml up -d

echo ""
echo "=== Step 3: Wait for containers to be ready ==="
sleep 45

echo ""
echo "=== Step 4: Verify .env exists ==="
docker exec apbdanalysis2026_app_prod cat /var/www/html/.env | head -5

echo ""
echo "=== Step 5: Check if assets exist ==="
ASSET_COUNT=$(docker exec apbdanalysis2026_app_prod find /var/www/html/public/build -type f 2>/dev/null | wc -l)
echo "Assets found: $ASSET_COUNT files"

if [ "$ASSET_COUNT" -lt 5 ]; then
    echo "⚠️  Assets missing or incomplete, rebuilding..."
    docker exec apbdanalysis2026_app_prod npm install
    docker exec apbdanalysis2026_app_prod npm run build
fi

echo ""
echo "=== Step 6: Clear and rebuild caches ==="
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache

echo ""
echo "=== Step 7: Run migrations ==="
docker exec apbdanalysis2026_app_prod php artisan migrate --force

echo ""
echo "=== Step 8: Test application ==="
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://103.130.82.202:5560/)
echo "HTTP Response: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ SUCCESS! Application is running"
else
    echo "❌ ERROR! Application returned HTTP $HTTP_CODE"
    echo ""
    echo "Check logs:"
    echo "  docker logs apbdanalysis2026_app_prod --tail 50"
    echo "  docker exec apbdanalysis2026_app_prod tail -50 storage/logs/laravel.log"
fi

echo ""
echo "=== Step 9: Check CSP header ==="
curl -I http://103.130.82.202:5560/ | grep "Content-Security-Policy"

echo ""
echo "=========================================="
echo "✅ Deployment complete!"
echo "=========================================="
echo ""
echo "Please test in browser and check console:"
echo "  1. Open http://103.130.82.202:5560/"
echo "  2. Press F12 to open Developer Console"
echo "  3. Check if CSP errors are gone"
echo "  4. Verify all CSS/JS loaded correctly"
echo ""
```

---

## ✅ Verification Checklist

Setelah apply fix, verify di browser:

### 1. Check Browser Console (F12)
- [ ] Tidak ada CSP violation errors
- [ ] Tidak ada 404 errors untuk assets
- [ ] Semua external CDN resources loaded (jQuery, DataTables, Google Fonts, Bootstrap Icons)

### 2. Check Visual
- [ ] Halaman tampil dengan styling yang benar
- [ ] Font Inter dari Google Fonts loaded
- [ ] Icons tampil dengan benar
- [ ] DataTables berfungsi dengan benar

### 3. Check Headers
```bash
curl -I http://103.130.82.202:5560/ | grep "Content-Security-Policy"
```

Harus menampilkan CSP yang include CDN domains:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.datatables.net https://cdn.jsdelivr.net; ...
```

### 4. Check Assets
```bash
# Should return multiple files
docker exec apbdanalysis2026_app_prod find /var/www/html/public/build -type f
```

---

## 🐛 Troubleshooting

### Jika Masih Ada CSP Errors

**Check nginx config applied:**
```bash
docker exec apbdanalysis2026_app_prod cat /etc/nginx/http.d/default.conf | grep "Content-Security-Policy"
```

**Rebuild container:**
```bash
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build --no-cache app
docker compose -f docker-compose.prod.yml up -d
```

### Jika Assets 404

**Check if build folder exists:**
```bash
docker exec apbdanalysis2026_app_prod ls -la /var/www/html/public/
```

**Rebuild assets:**
```bash
docker exec apbdanalysis2026_app_prod npm install
docker exec apbdanalysis2026_app_prod npm run build
docker exec apbdanalysis2026_app_prod ls -la /var/www/html/public/build/
```

### Jika Browser Cache Issue

**Clear browser cache:**
- Chrome/Firefox: `Ctrl+Shift+Delete` atau `Cmd+Shift+Delete`
- Hard refresh: `Ctrl+Shift+R` atau `Cmd+Shift+R`
- Or use Incognito/Private mode

---

## 📝 Summary

### Changes Made:
1. ✅ Updated nginx CSP policy to allow external CDN resources
2. ✅ Committed and pushed to git repository
3. ✅ Ready for deployment to production

### Next Steps:
1. Pull latest changes di production server
2. Rebuild container dengan nginx config baru
3. Verify di browser tidak ada CSP errors lagi
4. Check assets loaded dengan benar

### Expected Result:
- ✅ No CSP violations in browser console
- ✅ All external resources (jQuery, DataTables, Google Fonts, Bootstrap Icons) load successfully
- ✅ Application functions normally with all styling and JavaScript working

Good luck! 🚀

