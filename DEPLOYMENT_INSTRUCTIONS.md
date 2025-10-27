# 🚀 Instruksi Deployment Production - APBD Analysis 2026

## ✅ Perubahan yang Sudah Dilakukan

1. ✅ **composer.lock** dan **package-lock.json** dihapus dari `.gitignore`
2. ✅ **Dockerfile.prod** diupdate dengan:
   - Timeout lebih lama (600 detik untuk composer)
   - Retry logic jika composer install gagal
   - Build Vite assets otomatis
   - NPM timeout configuration untuk koneksi lambat
3. ✅ **supervisord-prod.conf** - Dihapus vite-build process yang error
4. ✅ Lock files sudah di-commit ke git repository
5. ✅ Semua perubahan sudah di-push ke `origin/main`

---

## 📋 Deployment di Production Server

### **LANGKAH-LANGKAH DEPLOYMENT**

SSH ke server production Anda dan jalankan command berikut:

```bash
# 1. Login ke server
ssh sysadmin@APBDANALYSIS2026

# 2. Navigate ke project directory
cd ~/dev/apbdanalysis2026

# 3. Pull perubahan terbaru dari git
git pull origin main

# 4. Verifikasi lock files sudah ada
ls -lh composer.lock package-lock.json

# 5. Stop containers yang sedang running
docker compose -f docker-compose.prod.yml down

# 6. Remove old images (optional - recommended)
docker image rm apbdanalysis2026-app || true

# 7. Rebuild Docker image (akan memakan waktu 10-20 menit)
docker compose -f docker-compose.prod.yml build --no-cache app

# 8. Start containers
docker compose -f docker-compose.prod.yml up -d

# 9. Tunggu containers fully started (45 detik)
sleep 45

# 10. Run post-deployment tasks
docker exec apbdanalysis2026_app_prod php artisan storage:link
docker exec apbdanalysis2026_app_prod php artisan migrate --force
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache

# 11. Test aplikasi
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ | head -5
```

---

## 🤖 ATAU Gunakan Script Otomatis

Saya sudah membuat script yang otomatis melakukan semua langkah di atas:

```bash
# Di server production
cd ~/dev/apbdanalysis2026
chmod +x deploy-to-production.sh
./deploy-to-production.sh
```

Script ini akan:
- ✅ Pull code dari git
- ✅ Verify lock files exist
- ✅ Stop & remove old containers
- ✅ Build new Docker image
- ✅ Start containers
- ✅ Run migrations & caches
- ✅ Verify deployment success

---

## ✅ Verifikasi Deployment Berhasil

Setelah deployment selesai, cek:

### 1. Container Status
```bash
docker ps | grep apbdanalysis2026
```

Semua containers harus dalam status `healthy` atau `Up X minutes`

### 2. HTTP Response
```bash
docker exec apbdanalysis2026_app_prod curl -I http://localhost/
```

Harus return: `HTTP/1.1 200 OK`

### 3. Assets Build
```bash
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/public/build/
```

Harus ada folder `assets/` dan file `manifest.json`

### 4. Web Browser
Buka browser dan akses:
- **Aplikasi**: `http://[IP-SERVER]:5560`
- **phpMyAdmin**: `http://[IP-SERVER]:5561`

Halaman harus tampil dengan styling CSS yang benar (tidak plain HTML)

### 5. Check Logs
```bash
# Container logs
docker logs apbdanalysis2026_app_prod --tail 50

# Laravel logs
docker exec apbdanalysis2026_app_prod tail -50 storage/logs/laravel.log
```

Tidak boleh ada error `MissingAppKeyException` atau `Base table or view not found`

---

## 🐛 Troubleshooting

### ❌ Jika Build Gagal dengan Timeout Error

**Problem**: Composer atau NPM timeout saat download packages

**Solution 1**: Retry build
```bash
docker compose -f docker-compose.prod.yml build app
```

**Solution 2**: Increase Docker timeout
```bash
export COMPOSE_HTTP_TIMEOUT=600
export DOCKER_CLIENT_TIMEOUT=600
docker compose -f docker-compose.prod.yml build app
```

**Solution 3**: Use pre-built vendor (if network too slow)
```bash
# Di komputer local
composer install --no-dev --optimize-autoloader
tar czf vendor.tar.gz vendor/
scp vendor.tar.gz sysadmin@APBDANALYSIS2026:~/dev/apbdanalysis2026/

# Di server
cd ~/dev/apbdanalysis2026
tar xzf vendor.tar.gz

# Temporary edit Dockerfile.prod - comment out composer install
# nano Dockerfile.prod
# (comment line 53-56)

# Build again
docker compose -f docker-compose.prod.yml build app
```

---

### ❌ Jika Container Start tapi HTTP 500

**Problem**: Config cache issue atau missing APP_KEY

**Solution**:
```bash
# Check APP_KEY exists
docker exec apbdanalysis2026_app_prod cat .env | grep APP_KEY

# If empty, generate
docker exec apbdanalysis2026_app_prod php artisan key:generate --force

# Clear all caches
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan cache:clear
docker exec apbdanalysis2026_app_prod php artisan route:clear
docker exec apbdanalysis2026_app_prod php artisan view:clear

# Rebuild caches
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache

# Check database connection
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="DB::connection()->getPdo();"

# Test again
docker exec apbdanalysis2026_app_prod curl -I http://localhost/
```

---

### ❌ Jika Halaman Tidak Ada Styling

**Problem**: Assets tidak ter-build atau symlink storage missing

**Solution**:
```bash
# Build assets manually
docker exec apbdanalysis2026_app_prod npm install
docker exec apbdanalysis2026_app_prod npm run build

# Create storage symlink
docker exec apbdanalysis2026_app_prod php artisan storage:link

# Clear view cache
docker exec apbdanalysis2026_app_prod php artisan view:clear
docker exec apbdanalysis2026_app_prod php artisan view:cache

# Check assets exist
docker exec apbdanalysis2026_app_prod ls -la /var/www/html/public/build/

# Hard refresh browser: Ctrl+Shift+R (Windows) atau Cmd+Shift+R (Mac)
```

---

### ❌ Jika Database Tables Tidak Ada

**Problem**: Migrations belum dijalankan

**Solution**:
```bash
# Run migrations
docker exec apbdanalysis2026_app_prod php artisan migrate --force

# Check tables exist
docker exec apbdanalysis2026_mariadb_prod mysql -u apbdanalysis2026_user -pyour_secure_password_here apbdanalysis2026_prod -e "SHOW TABLES;"

# If need to reseed
docker exec apbdanalysis2026_app_prod php artisan db:seed --force
```

---

### ❌ Jika Workers/Scheduler Status FATAL

**Problem**: Config issue saat container pertama kali start

**Solution**:
```bash
# Restart container (workers akan auto-restart)
docker restart apbdanalysis2026_app_prod

# Wait for container ready
sleep 30

# Check logs
docker logs apbdanalysis2026_app_prod --tail 100
```

---

## 📊 Expected Build Output

Build yang berhasil akan menampilkan output seperti ini:

```
[+] Building 650.2s (18/18) FINISHED
 => [internal] load build definition from Dockerfile.prod
 => => transferring dockerfile: 2.1kB
 => [internal] load .dockerignore
 => [stage-0  1/15] FROM docker.io/library/php:8.2-fpm-alpine
 ...
 => [stage-0 11/15] COPY . .
 => [stage-0 12/15] RUN composer install --no-dev --optimize-autoloader --no-interaction
 => [stage-0 13/15] RUN npm ci --prefer-offline --no-audit
 => [stage-0 14/15] RUN npm run build
 => [stage-0 15/15] RUN php artisan config:cache && php artisan view:cache
 => exporting to image
 => => exporting layers
 => => writing image sha256:abc123...
 => => naming to docker.io/library/apbdanalysis2026-app

✅ Build SUCCESS
```

Jika melihat output seperti di atas, berarti build berhasil!

---

## 🔐 Post-Deployment Security Checklist

Setelah deployment berhasil:

- [ ] Ganti password database jika masih menggunakan default
- [ ] Verify `.env` di production tidak di-commit ke git
- [ ] Set proper firewall rules untuk port 5560 dan 5561
- [ ] Disable phpMyAdmin di production jika sudah tidak diperlukan
- [ ] Setup backup otomatis untuk database
- [ ] Monitor disk space (Docker images bisa besar)
- [ ] Setup monitoring/logging (optional: Sentry, New Relic, etc)

---

## 📞 Bantuan & Support

Jika mengalami masalah yang tidak tercantum di troubleshooting:

1. **Collect logs**:
   ```bash
   docker logs apbdanalysis2026_app_prod > app.log
   docker exec apbdanalysis2026_app_prod cat storage/logs/laravel.log > laravel.log
   ```

2. **Check system resources**:
   ```bash
   docker stats
   df -h
   free -m
   ```

3. **Test network**:
   ```bash
   ping -c 4 repo.packagist.org
   ping -c 4 registry.npmjs.org
   curl -I https://repo.packagist.org/packages.json
   ```

Share output dari command di atas untuk troubleshooting lebih lanjut.

---

## 📝 Summary

Sekarang production deployment sudah:
- ✅ Include lock files untuk deterministic builds
- ✅ Auto-build Vite assets saat Docker build
- ✅ Handle network timeout dengan retry logic
- ✅ Ready untuk production deployment

**Total waktu deployment**: ~15-30 menit (tergantung kecepatan internet server)

Good luck! 🚀

