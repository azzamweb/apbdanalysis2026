# Perintah untuk Memperbaiki Production Server

## 🚨 Error: "No application encryption key has been specified"

### Copy dan Paste Perintah Berikut di Server Production

```bash
# === STEP 1: CEK APP_KEY ADA ATAU TIDAK ===
docker exec apbdanalysis2026_app_prod cat .env | grep APP_KEY

# Harusnya muncul: APP_KEY=base64:jbtt/BlnWLJ96h/a2zEVG4sIRNGUXRgtitDAnxmSreI=
# Jika kosong atau tidak ada, generate dulu dengan: docker exec apbdanalysis2026_app_prod php artisan key:generate --force


# === STEP 2: CLEAR CONFIG CACHE (INI YANG PALING PENTING!) ===
docker exec apbdanalysis2026_app_prod php artisan config:clear


# === STEP 3: REBUILD CONFIG CACHE ===
docker exec apbdanalysis2026_app_prod php artisan config:cache


# === STEP 4: CEK APAKAH SUDAH BISA ===
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ 2>/dev/null | head -1

# Harusnya muncul: HTTP/1.1 200 OK
# Jika masih 500, lanjut ke step 5


# === STEP 5: CLEAR SEMUA CACHE ===
docker exec apbdanalysis2026_app_prod php artisan cache:clear
docker exec apbdanalysis2026_app_prod php artisan route:clear
docker exec apbdanalysis2026_app_prod php artisan view:clear


# === STEP 6: OPTIMIZE ===
docker exec apbdanalysis2026_app_prod php artisan optimize


# === STEP 7: TEST LAGI ===
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ 2>/dev/null | head -5


# === STEP 8: JALANKAN MIGRATIONS (Jika database masih kosong) ===
docker exec apbdanalysis2026_app_prod php artisan migrate --force


# === STEP 9: RESTART CONTAINER (Jika masih belum berhasil) ===
docker restart apbdanalysis2026_app_prod

# Tunggu 30 detik sampai container fully started
sleep 30

docker exec apbdanalysis2026_app_prod curl -I http://localhost/ 2>/dev/null | head -5
```

## 📊 Troubleshooting Tambahan

### Jika APP_KEY tidak ada atau kosong:

```bash
# Generate APP_KEY baru
docker exec apbdanalysis2026_app_prod php artisan key:generate --force --show

# Copy output dan edit .env di container
docker exec -it apbdanalysis2026_app_prod sh
vi .env  # atau nano .env
# Paste APP_KEY yang baru di-generate
# Save dan exit

# Clear dan cache lagi
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan config:cache
```

### Jika masih error setelah semua step:

```bash
# Cek bootstrap/cache apakah ada file config.php
docker exec apbdanalysis2026_app_prod ls -la bootstrap/cache/

# Hapus manual jika ada
docker exec apbdanalysis2026_app_prod rm -f bootstrap/cache/config.php
docker exec apbdanalysis2026_app_prod rm -f bootstrap/cache/routes.php
docker exec apbdanalysis2026_app_prod rm -f bootstrap/cache/packages.php
docker exec apbdanalysis2026_app_prod rm -f bootstrap/cache/services.php

# Clear dan cache lagi
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
```

## ✅ Hasil yang Benar

Setelah berhasil, Anda akan melihat:

```
HTTP/1.1 200 OK
Server: nginx
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
Cache-Control: no-cache, private
```

Dan aplikasi bisa diakses melalui browser!

