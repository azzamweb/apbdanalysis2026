# Fix: Composer Lock File Missing - Production Build Error

## 🔴 Masalah
Build Docker di production server gagal dengan error:
```
No composer.lock file present. Updating dependencies to latest instead of installing from lock file.
curl error 28 while downloading https://repo.packagist.org/packages.json: Connection timed out
```

## 🔍 Root Cause
1. ❌ `composer.lock` dan `package-lock.json` ada di `.gitignore`
2. ❌ File lock tidak di-commit ke git
3. ❌ Di production, composer harus resolve dependencies dari awal (lambat)
4. ❌ Koneksi internet server timeout saat download dari packagist

## ✅ Solusi yang Sudah Dilakukan
1. ✅ Hapus `composer.lock` dari `.gitignore`
2. ✅ Hapus `package-lock.json` dari `.gitignore`  
3. ✅ Update `Dockerfile.prod` dengan timeout lebih lama dan retry logic
4. ✅ Tambahkan konfigurasi npm untuk handle slow network

## 🚀 Langkah-Langkah Deployment di Production Server

### Step 1: Push File Lock ke Git (Di Komputer Local)

```bash
cd /Users/hermansyah/dev/apbdanalysis2026

# Verify lock files exist
ls -lh composer.lock package-lock.json

# Add files to git
git add .gitignore Dockerfile.prod docker/supervisor/supervisord-prod.conf
git add composer.lock package-lock.json

# Commit
git commit -m "Fix: Include lock files for production build

- Remove composer.lock and package-lock.json from .gitignore
- Add retry logic and increased timeouts to Dockerfile.prod
- This ensures deterministic builds in production"

# Push to repository
git push origin main
```

### Step 2: Pull dan Rebuild di Production Server

```bash
# SSH ke production server
ssh sysadmin@APBDANALYSIS2026

# Navigate to project
cd ~/dev/apbdanalysis2026

# Pull latest changes
git pull origin main

# Verify lock files are now present
ls -lh composer.lock package-lock.json

# Stop existing containers
docker compose -f docker-compose.prod.yml down

# Remove old images (optional but recommended)
docker image rm apbdanalysis2026-app || true

# Rebuild with no cache (this will take 10-20 minutes)
docker compose -f docker-compose.prod.yml build --no-cache app

# Start containers
docker compose -f docker-compose.prod.yml up -d

# Wait for containers to be healthy
sleep 45

# Run post-deployment tasks
docker exec apbdanalysis2026_app_prod php artisan storage:link
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache
docker exec apbdanalysis2026_app_prod php artisan migrate --force

# Test application
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ | head -5
```

### Step 3: Verify Build Success

```bash
# Check if build folder exists with assets
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/public/build/

# Check container status
docker ps | grep apbdanalysis2026

# Check logs
docker logs apbdanalysis2026_app_prod --tail 50

# Test from browser
# http://[SERVER-IP]:5560
```

---

## 🐛 Troubleshooting

### Jika Build Masih Timeout di Step Composer Install:

#### Option A: Build dengan Docker Build Args
```bash
# Set longer timeout via build args
docker compose -f docker-compose.prod.yml build --build-arg COMPOSER_PROCESS_TIMEOUT=1200 app
```

#### Option B: Copy Vendor dari Local (Quick Fix)
```bash
# Di komputer local, create vendor archive
cd /Users/hermansyah/dev/apbdanalysis2026
composer install --no-dev --optimize-autoloader
tar czf vendor.tar.gz vendor/

# Upload ke server
scp vendor.tar.gz sysadmin@APBDANALYSIS2026:~/dev/apbdanalysis2026/

# Di server, extract sebelum build
cd ~/dev/apbdanalysis2026
tar xzf vendor.tar.gz

# Modify Dockerfile temporarily to skip composer install
# Comment out composer install line, rebuild
```

#### Option C: Use Composer Cache Mirror
```bash
# Di production server sebelum build, setup composer cache
mkdir -p ~/.composer/cache

# Try build again with cache mounted
docker compose -f docker-compose.prod.yml build app
```

### Jika Build Timeout di Step NPM:

```bash
# Check internet connection from container
docker run --rm alpine ping -c 4 registry.npmjs.org

# If npm is slow, try using yarn instead
# Modify Dockerfile to use yarn:
# RUN npm install -g yarn && yarn install --production --frozen-lockfile
```

### Jika Masih Error Setelah Build Berhasil:

```bash
# Check APP_KEY
docker exec apbdanalysis2026_app_prod cat .env | grep APP_KEY

# Clear all caches
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan cache:clear
docker exec apbdanalysis2026_app_prod php artisan route:clear
docker exec apbdanalysis2026_app_prod php artisan view:clear

# Recache
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache
```

---

## 🔒 Kenapa Lock Files Harus Di-Commit?

### `composer.lock`
- ✅ Memastikan semua environment menggunakan versi package yang sama
- ✅ Menghindari "works on my machine" syndrome
- ✅ Build lebih cepat (tidak perlu resolve dependencies)
- ✅ Security: tahu persis versi yang digunakan

### `package-lock.json`
- ✅ Deterministic builds untuk NPM packages
- ✅ Faster npm ci installation
- ✅ Konsistensi antara dev dan production

**Best Practice:** Lock files HARUS di-commit untuk production apps!

---

## 📊 Perubahan yang Dilakukan

### `.gitignore`
```diff
# Composer
- composer.lock
+ # Note: composer.lock SHOULD be committed for production deployments
+ # composer.lock

# NPM
- package-lock.json
+ # Note: package-lock.json SHOULD be committed for production deployments
+ # package-lock.json
```

### `Dockerfile.prod`
```diff
# Install PHP dependencies and optimize
- RUN composer config --global process-timeout 300 \
-     && composer install --no-dev --optimize-autoloader --no-interaction
+ # Set composer to use IPv4 and increase timeout to handle slow networks
+ RUN composer config --global process-timeout 600 \
+     && composer config --global repo.packagist composer https://repo.packagist.org \
+     && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
+     || (echo "Retrying composer install..." && sleep 5 && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist)

# Install Node dependencies and build assets
- RUN npm ci --only=production \
+ # Increase npm timeout for slow networks
+ RUN npm config set fetch-retry-mintimeout 20000 \
+     && npm config set fetch-retry-maxtimeout 120000 \
+     && npm ci --prefer-offline --no-audit \
```

---

## ✅ Checklist Setelah Deploy

- [ ] `composer.lock` ada di git repository
- [ ] `package-lock.json` ada di git repository
- [ ] Build berhasil tanpa error timeout
- [ ] Container running dengan status "healthy"
- [ ] Web application accessible dan styling tampil benar
- [ ] phpMyAdmin accessible di port 5561
- [ ] Database migrations berjalan sukses
- [ ] No errors di `storage/logs/laravel.log`
- [ ] Assets CSS/JS ter-load dengan benar (check browser console)

---

## 📞 Bantuan Lebih Lanjut

Jika masih mengalami masalah, collect informasi berikut:

```bash
# System info
uname -a
docker --version
docker compose version

# Network test
ping -c 4 repo.packagist.org
ping -c 4 registry.npmjs.org
curl -I https://repo.packagist.org/packages.json

# Docker logs
docker logs apbdanalysis2026_app_prod --tail 100 > docker-logs.txt

# Laravel logs
docker exec apbdanalysis2026_app_prod cat storage/logs/laravel.log | tail -100 > laravel-logs.txt
```

Share output di atas untuk troubleshooting lebih lanjut.

