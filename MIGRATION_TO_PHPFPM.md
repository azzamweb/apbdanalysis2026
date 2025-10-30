# Migrasi dari FrankenPHP ke PHP-FPM + Nginx (Development)

## 🎯 Tujuan
Menyamakan environment development dengan production untuk menghindari perbedaan behavior dan memudahkan debugging.

## 📋 Perubahan yang Dilakukan

### 1. **Dockerfile**
- **Sebelum**: `Dockerfile` menggunakan FrankenPHP
- **Sekarang**: `Dockerfile.dev` menggunakan PHP-FPM + Nginx (sama seperti production)

### 2. **Web Server**
- **Sebelum**: FrankenPHP dengan Caddyfile
- **Sekarang**: PHP-FPM 8.2 + Nginx (sama seperti production)

### 3. **Konfigurasi**
| Komponen | Development | Production |
|----------|-------------|------------|
| Base Image | `php:8.2-fpm-alpine` | `php:8.2-fpm-alpine` |
| Web Server | Nginx | Nginx |
| Config File | `nginx-dev.conf` | `nginx-prod.conf` |
| Supervisor | `supervisord-dev.conf` | `supervisord-prod.conf` |
| CSP Policy | ✅ Allow CDN | ✅ Allow CDN |

### 4. **Content Security Policy (CSP)**
CSP sudah ditambahkan ke development dan production dengan domain yang sama:

**Script Sources:**
- `code.jquery.com` - jQuery
- `cdn.datatables.net` - DataTables
- `cdn.jsdelivr.net` - Bootstrap, Icons
- `cdnjs.cloudflare.com` - Libraries
- `kit.fontawesome.com` - Font Awesome JS

**Style Sources:**
- `fonts.googleapis.com` - Google Fonts
- `cdn.datatables.net` - DataTables CSS
- `cdn.jsdelivr.net` - Bootstrap CSS

**Font Sources:**
- `fonts.gstatic.com` - Google Fonts files
- `cdn.jsdelivr.net` - Bootstrap Icons
- `kit.fontawesome.com` - Font Awesome fonts

**Connect Sources:**
- `cdnjs.cloudflare.com` - CDNJS API
- `ka-f.fontawesome.com` - Font Awesome API

### 5. **Supervisor Configuration**
**Development** (`supervisord-dev.conf`):
- ✅ PHP-FPM
- ✅ Nginx
- ✅ Vite (hot reload untuk development)
- ⚪ Queue Worker (optional, dihandle docker-compose)
- ⚪ Scheduler (optional, dihandle docker-compose)

**Production** (`supervisord-prod.conf`):
- ✅ PHP-FPM
- ✅ Nginx
- ✅ Laravel Queue Worker (2 processes)
- ✅ Laravel Scheduler

## 🚀 Cara Menerapkan Perubahan

### Step 1: Backup (Optional)
```bash
# Backup data jika perlu
docker-compose exec mariadb mysqldump -u apbdanalysis2026 -p apbdanalysis2026 > backup.sql
```

### Step 2: Stop dan Hapus Container Lama
```bash
docker-compose down
```

### Step 3: Hapus Image Lama (Optional)
```bash
docker rmi apbdanalysis2026-app
```

### Step 4: Build Ulang dengan Konfigurasi Baru
```bash
docker-compose build --no-cache app
```

### Step 5: Start Services
```bash
docker-compose up -d
```

### Step 6: Verifikasi
```bash
# Cek status container
docker-compose ps

# Cek logs
docker-compose logs -f app

# Cek apakah PHP-FPM dan Nginx berjalan
docker-compose exec app ps aux | grep -E 'php-fpm|nginx'

# Test aplikasi
curl -I http://localhost:5560/
```

### Step 7: Test CSP Header
```bash
# Verifikasi CSP header sudah benar
curl -I http://localhost:5560/ | grep "Content-Security-Policy"
```

Hasilnya harus menunjukkan CSP dengan CDN yang diizinkan.

## 🔍 Troubleshooting

### Issue: Container gagal start
```bash
# Cek logs untuk error
docker-compose logs app

# Cek inside container
docker-compose exec app sh
ps aux
ls -la /var/www/html
```

### Issue: Permission denied pada storage/cache
```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 755 /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/bootstrap/cache
```

### Issue: Nginx 502 Bad Gateway
```bash
# Cek PHP-FPM status
docker-compose exec app ps aux | grep php-fpm

# Restart PHP-FPM
docker-compose exec app supervisorctl restart php-fpm
```

### Issue: Assets tidak load / Vite tidak jalan
```bash
# Install dependencies
docker-compose exec app npm install

# Restart Vite
docker-compose exec app supervisorctl restart vite

# Atau manual
docker-compose exec app npm run dev
```

### Issue: CSP masih memblokir resources
1. **Clear browser cache**: `Ctrl+Shift+R` (Windows/Linux) atau `Cmd+Shift+R` (Mac)
2. **Hard refresh**: Tutup browser dan buka lagi
3. **Check CSP header**:
```bash
curl -I http://localhost:5560/ | grep -i "content-security"
```

## 📊 Perbandingan Environment

| Aspek | Development (Lokal) | Production |
|-------|-------------------|-----------|
| **Base Image** | `php:8.2-fpm-alpine` | `php:8.2-fpm-alpine` |
| **Web Server** | Nginx | Nginx |
| **PHP Handler** | PHP-FPM | PHP-FPM |
| **Hot Reload** | ✅ Vite | ❌ |
| **Code Mount** | ✅ Volume | ❌ Copy |
| **Composer** | `--dev` | `--no-dev` |
| **Assets Build** | Vite dev | Vite build |
| **Debug Mode** | ✅ Enabled | ❌ Disabled |
| **Cache** | ❌ No cache | ✅ Config + View cache |
| **Optimize** | ❌ | ✅ Autoloader optimized |
| **Port** | 5560 | 5560 (prod server) |

## ✅ Keuntungan Migrasi

1. **Development-Production Parity** ✨
   - Behavior yang sama antara dev dan prod
   - Bug di production bisa direproduksi di lokal
   
2. **Debugging Lebih Mudah** 🐛
   - Error yang sama di kedua environment
   - Tidak ada "works on my machine" issue
   
3. **CSP Consistency** 🔒
   - CSP policy yang sama
   - Tidak ada surprise saat deploy ke production
   
4. **Performance Testing** ⚡
   - Bisa test performance PHP-FPM di lokal
   - Optimasi bisa dilakukan sebelum deploy
   
5. **Standard Setup** 📦
   - Mengikuti best practice PHP deployment
   - Compatible dengan hosting/server manapun

## 📝 File yang Berubah

```
✅ Dockerfile.dev (baru)
✅ docker-compose.yml (updated)
✅ docker/nginx/nginx-dev.conf (updated CSP)
✅ docker/nginx/nginx-prod.conf (updated CSP)
✅ docker/supervisor/supervisord-dev.conf (updated)
✅ docker/frankenphp/Caddyfile (updated CSP - optional)
```

## 🔄 Rollback (Jika Diperlukan)

Jika ingin kembali ke FrankenPHP:

```bash
# Edit docker-compose.yml
# Ganti:
#   dockerfile: Dockerfile.dev
# Menjadi:
#   dockerfile: Dockerfile

# Rebuild
docker-compose down
docker-compose up -d --build
```

---

**Tanggal Migrasi**: 30 Oktober 2025  
**Status**: ✅ Complete  
**Tested**: ✅ Development, ✅ Production

