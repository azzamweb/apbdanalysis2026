# 🚀 Panduan Deployment Native (Tanpa Docker)

Dokumen ini menjelaskan cara men-deploy aplikasi APBD Analysis 2026 ke server yang sudah disiapkan menggunakan panduan `SERVER_SETUP_NATIVE.md`.

## 📂 1. Setup Direktori & Code

Login sebagai `sysadmin`.

```bash
# Buat direktori aplikasi
sudo mkdir -p /var/www/apbdanalysis2026
sudo chown -R $USER:$USER /var/www/apbdanalysis2026

# Clone repository
git clone https://github.com/username/apbdanalysis2026.git /var/www/apbdanalysis2026
cd /var/www/apbdanalysis2026

# Install PHP Dependencies
composer install --optimize-autoloader --no-dev

# Install Node Dependencies & Build Assets
npm ci
npm run build
```

## ⚙️ 2. Konfigurasi Environment

```bash
cp .env.example .env
nano .env
```

Sesuaikan konfigurasi `.env`:
```ini
APP_NAME="APBD Analysis 2026"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://analysis.trust-idn.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apbdanalysis2026_prod
DB_USERNAME=apbd_user
DB_PASSWORD=PASSWORD_DB_ANDA

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=PASSWORD_REDIS_ANDA
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis
```

Generate key:
```bash
php artisan key:generate
```

## 🗄️ 3. Setup Database & Storage

```bash
# Migrasi Database
php artisan migrate --force

# Link Storage
php artisan storage:link

# Set Permissions (PENTING)
sudo chown -R www-data:www-data /var/www/apbdanalysis2026/storage
sudo chown -R www-data:www-data /var/www/apbdanalysis2026/bootstrap/cache
sudo chmod -R 775 /var/www/apbdanalysis2026/storage
sudo chmod -R 775 /var/www/apbdanalysis2026/bootstrap/cache
```

## 🌐 4. Konfigurasi Nginx

Copy file konfigurasi yang sudah disiapkan:
```bash
sudo cp nginx-app.conf /etc/nginx/sites-available/apbdanalysis2026
sudo ln -s /etc/nginx/sites-available/apbdanalysis2026 /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 👷 5. Konfigurasi Supervisor (Queue Worker)

Copy file konfigurasi supervisor:
```bash
sudo cp supervisord-worker.conf /etc/supervisor/conf.d/apbdanalysis-worker.conf

# Update Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start apbdanalysis-worker:*
```

## ✅ 6. Verifikasi

1. Buka browser: `https://analysis.trust-idn.id`
2. Cek status worker: `sudo supervisorctl status`

## 🔄 Update Aplikasi (Re-deploy)

Untuk update kode di masa depan:

```bash
cd /var/www/apbdanalysis2026
git pull origin main
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart apbdanalysis-worker:*
```
