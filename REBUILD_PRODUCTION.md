# Cara Rebuild Production Container dengan Assets yang Benar

## 🔴 Masalah
Halaman selain welcome page tidak memiliki styling (CSS/JS tidak load) karena assets Vite tidak ter-build saat Docker image dibuat.

## ✅ Solusi yang Sudah Dilakukan
Saya sudah memperbaiki:
1. ✅ `Dockerfile.prod` - Ditambahkan step untuk build Vite assets
2. ✅ `supervisord-prod.conf` - Dihapus vite-build process (tidak diperlukan lagi)

## 🚀 Cara Memperbaiki di Production Server

### Option 1: Quick Fix (Sementara) - 5 Menit ⚡

Jalankan di server production untuk fix langsung tanpa rebuild:

```bash
# Di server: sysadmin@APBDANALYSIS2026

# 1. Install npm dependencies
docker exec apbdanalysis2026_app_prod npm install

# 2. Build assets
docker exec apbdanalysis2026_app_prod npm run build

# 3. Create storage symlink
docker exec apbdanalysis2026_app_prod php artisan storage:link

# 4. Clear cache
docker exec apbdanalysis2026_app_prod php artisan view:clear
docker exec apbdanalysis2026_app_prod php artisan view:cache

# 5. Set permissions
docker exec apbdanalysis2026_app_prod chown -R www-data:www-data /var/www/html/public/build

# 6. Test
docker exec apbdanalysis2026_app_prod ls -la /var/www/html/public/build/
```

Setelah selesai, refresh browser dengan **Ctrl+Shift+R** (Windows/Linux) atau **Cmd+Shift+R** (Mac)

---

### Option 2: Permanent Fix (Rebuild Container) - 15-30 Menit 🔧

Untuk fix permanent, rebuild Docker image dengan kode yang sudah diperbaiki:

#### A. Upload Kode Terbaru ke Server

```bash
# Di komputer local
cd /Users/hermansyah/dev/apbdanalysis2026

# Commit perubahan
git add Dockerfile.prod docker/supervisor/supervisord-prod.conf
git commit -m "Fix: Add Vite assets build to production Dockerfile"
git push origin main

# Di server production
cd ~/dev/apbdanalysis2026
git pull origin main
```

#### B. Rebuild dan Restart Container

```bash
# Di server: sysadmin@APBDANALYSIS2026
cd ~/dev/apbdanalysis2026

# Stop dan remove container lama
docker compose -f docker-compose.prod.yml down

# Rebuild image dengan --no-cache untuk memastikan fresh build
docker compose -f docker-compose.prod.yml build --no-cache app

# Start container baru
docker compose -f docker-compose.prod.yml up -d

# Tunggu container fully started
sleep 30

# Jalankan post-deployment tasks
docker exec apbdanalysis2026_app_prod php artisan storage:link
docker exec apbdanalysis2026_app_prod php artisan migrate --force
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache

# Test
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ | head -5
```

---

## 🧪 Verifikasi Assets Berhasil Di-Build

```bash
# Cek apakah folder build ada dan berisi file
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/public/build/

# Cek manifest.json
docker exec apbdanalysis2026_app_prod cat /var/www/html/public/build/manifest.json

# Cek apakah CSS file ada
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/public/build/assets/*.css
```

Expected output:
```
manifest.json
assets/app-[hash].css
assets/app-[hash].js
```

---

## 🐛 Troubleshooting

### Jika npm install gagal:

```bash
# Cek apakah npm tersedia
docker exec apbdanalysis2026_app_prod which npm

# Cek apakah package.json ada
docker exec apbdanalysis2026_app_prod cat package.json
```

### Jika npm run build gagal:

```bash
# Cek error log
docker exec apbdanalysis2026_app_prod npm run build 2>&1 | tee build-error.log

# Cek vite.config.js
docker exec apbdanalysis2026_app_prod cat vite.config.js
```

### Jika halaman masih tidak ada styling:

```bash
# Hard refresh browser (clear cache)
# Chrome/Firefox: Ctrl+Shift+R atau Cmd+Shift+R
# Safari: Cmd+Option+R

# Cek di browser console (F12) apakah ada error loading CSS/JS

# Cek nginx error log
docker exec apbdanalysis2026_app_prod cat /var/log/nginx/error.log | tail -50
```

---

## 📝 Catatan

1. **Option 1 (Quick Fix)** bagus untuk fix cepat, tapi akan hilang jika container di-restart
2. **Option 2 (Rebuild)** adalah solusi permanent yang direkomendasikan
3. Setelah rebuild, worker dan scheduler akan otomatis berjalan dengan benar
4. File `fix-assets-production.sh` bisa digunakan untuk automasi Option 1

---

## ✅ Checklist Setelah Fix

- [ ] Halaman welcome menampilkan styling dengan benar
- [ ] Halaman login menampilkan styling dengan benar
- [ ] Halaman register menampilkan styling dengan benar
- [ ] Halaman dashboard menampilkan styling dengan benar
- [ ] Tidak ada error 404 untuk file CSS/JS di browser console
- [ ] Laravel workers berjalan dengan status RUNNING (tidak FATAL)

