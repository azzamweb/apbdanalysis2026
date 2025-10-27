# Fix: Missing .env File in Production Container

## 🔴 Problem
```
cat: can't open '.env': No such file or directory
Access denied for user 'apbdanalysis2026_user'@'172.20.0.5' (using password: YES)
```

**Root Cause**: File `.env` tidak ada di container karena:
1. `.env` ada di `.gitignore` (tidak di-commit ke git)
2. Docker image tidak include `.env` 
3. Aplikasi menggunakan environment variables dari docker-compose, tapi credentials tidak di-set

## ✅ Solusi

### Step 1: Buat File .env di Production Server

```bash
# Di server production (sysadmin@APBDANALYSIS2026)
cd ~/dev/apbdanalysis2026

# Buat .env file dari template
cat > .env << 'EOF'
APP_NAME="APBD Analysis 2026"
APP_ENV=production
APP_KEY=base64:jbtt/BlnWLJ96h/a2zEVG4sIRNGUXRgtitDAnxmSreI=
APP_DEBUG=false
APP_URL=http://your-domain.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=apbdanalysis2026_prod
DB_USERNAME=apbdanalysis2026_user
DB_PASSWORD=your_secure_password_here

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

CACHE_DRIVER=redis
CACHE_PREFIX=apbdanalysis_cache

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

QUEUE_CONNECTION=redis

BROADCAST_DRIVER=log

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
EOF

# Verify file created
ls -lh .env
```

### Step 2: Update Password di .env

**PENTING**: Ganti password dengan password yang sebenarnya!

```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Edit .env
nano .env
# atau
vi .env

# Ganti baris berikut dengan password ASLI Anda:
# DB_PASSWORD=your_secure_password_here
# REDIS_PASSWORD=your_redis_password_here

# Contoh password yang harus diisi:
# DB_PASSWORD=P@ssw0rd123!SecureDB
# REDIS_PASSWORD=R3d1sP@ss!Secure
```

**Cara cek password yang benar:**
```bash
# Lihat di docker-compose.prod.yml
grep -A 5 "MARIADB_PASSWORD" docker-compose.prod.yml
grep -A 5 "REDIS_PASSWORD" docker-compose.prod.yml
```

### Step 3: Update docker-compose.prod.yml - Mount .env File

```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Backup docker-compose.prod.yml
cp docker-compose.prod.yml docker-compose.prod.yml.backup

# Edit docker-compose.prod.yml
nano docker-compose.prod.yml
```

**Tambahkan di bagian `app` service, di section `volumes`:**

```yaml
services:
  app:
    # ... existing config ...
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
      - nginx_upload_temp:/tmp/nginx_upload
      - ./.env:/var/www/html/.env  # <-- TAMBAHKAN LINE INI
```

### Step 4: Update Environment Variables di docker-compose.prod.yml

**Atau** jika tidak mau mount .env, update environment variables di docker-compose.prod.yml:

```yaml
services:
  app:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_KEY=base64:jbtt/BlnWLJ96h/a2zEVG4sIRNGUXRgtitDAnxmSreI=
      - APP_URL=${APP_URL:-http://localhost:5560}
      - DB_CONNECTION=mysql
      - DB_HOST=mariadb
      - DB_PORT=3306
      - DB_DATABASE=apbdanalysis2026_prod
      - DB_USERNAME=apbdanalysis2026_user
      - DB_PASSWORD=YOUR_ACTUAL_DB_PASSWORD_HERE  # <-- GANTI INI
      - REDIS_HOST=redis
      - REDIS_PASSWORD=YOUR_ACTUAL_REDIS_PASSWORD_HERE  # <-- GANTI INI
      - REDIS_PORT=6379
      - CACHE_DRIVER=redis
      - SESSION_DRIVER=redis
      - QUEUE_CONNECTION=redis
```

### Step 5: Restart Containers

```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Stop containers
docker compose -f docker-compose.prod.yml down

# Start containers (akan mount .env file baru)
docker compose -f docker-compose.prod.yml up -d

# Wait for healthy
sleep 30

# Verify .env now exists in container
docker exec apbdanalysis2026_app_prod cat .env | head -10

# Test database connection
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB: OK';"

# Test Redis connection
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="Cache::put('test', 'ok'); echo 'Redis: OK';"
```

### Step 6: Clear dan Rebuild Caches

```bash
# Clear all caches
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan cache:clear
docker exec apbdanalysis2026_app_prod php artisan route:clear
docker exec apbdanalysis2026_app_prod php artisan view:clear

# Rebuild caches
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache

# Run migrations
docker exec apbdanalysis2026_app_prod php artisan migrate --force

# Test application
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ | head -5
```

---

## 🚀 Quick Setup Script

Atau gunakan script all-in-one ini:

```bash
# Di server production
cd ~/dev/apbdanalysis2026

# === CREATE .ENV FILE ===
cat > .env << 'EOF'
APP_NAME="APBD Analysis 2026"
APP_ENV=production
APP_KEY=base64:jbtt/BlnWLJ96h/a2zEVG4sIRNGUXRgtitDAnxmSreI=
APP_DEBUG=false
APP_URL=http://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=apbdanalysis2026_prod
DB_USERNAME=apbdanalysis2026_user
DB_PASSWORD=your_secure_password_here

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

BROADCAST_DRIVER=log
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

VITE_APP_NAME="${APP_NAME}"
EOF

# === IMPORTANT: EDIT PASSWORDS ===
echo ""
echo "⚠️  PENTING: Edit file .env dan ganti password!"
echo ""
echo "nano .env"
echo ""
echo "Ganti baris berikut:"
echo "  DB_PASSWORD=your_secure_password_here"
echo "  REDIS_PASSWORD=your_redis_password_here"
echo ""
read -p "Tekan ENTER setelah selesai edit .env..."

# === RESTART CONTAINERS ===
echo "Restarting containers..."
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
sleep 30

# === VERIFY ===
echo ""
echo "=== Verifying .env file ==="
docker exec apbdanalysis2026_app_prod ls -lh /var/www/html/.env

echo ""
echo "=== Testing connections ==="
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB: OK'; } catch (Exception \$e) { echo 'DB: FAILED - ' . \$e->getMessage(); }"

docker exec apbdanalysis2026_app_prod php artisan tinker --execute="try { Cache::put('test','ok'); echo 'Redis: OK'; } catch (Exception \$e) { echo 'Redis: FAILED - ' . \$e->getMessage(); }"

# === REBUILD CACHES ===
echo ""
echo "=== Rebuilding caches ==="
docker exec apbdanalysis2026_app_prod php artisan config:clear
docker exec apbdanalysis2026_app_prod php artisan config:cache
docker exec apbdanalysis2026_app_prod php artisan route:cache
docker exec apbdanalysis2026_app_prod php artisan view:cache

# === TEST ===
echo ""
echo "=== Testing application ==="
docker exec apbdanalysis2026_app_prod curl -I http://localhost/ | head -5

echo ""
echo "✅ Setup complete! Check results above."
```

---

## 📝 Catatan Penting

### Password Harus Match!

Pastikan password di `.env` sama dengan yang di `docker-compose.prod.yml`:

**Di docker-compose.prod.yml:**
```yaml
mariadb:
  environment:
    MARIADB_PASSWORD: ${DB_PASSWORD}  # <-- ini harus sama dengan .env
```

**Di .env:**
```
DB_PASSWORD=your_secure_password_here  # <-- ini
```

### Security Best Practice

Setelah setup berhasil:

```bash
# Set proper permissions untuk .env
chmod 600 .env
chown root:root .env  # atau user yang menjalankan docker

# Verify permissions
ls -l .env
# Should show: -rw------- (600)
```

### Jika Masih Error

1. **Cek environment variables di container:**
   ```bash
   docker exec apbdanalysis2026_app_prod env | grep -E "DB_|REDIS_"
   ```

2. **Cek config cache:**
   ```bash
   docker exec apbdanalysis2026_app_prod cat bootstrap/cache/config.php | grep -A 5 "database"
   ```

3. **Test langsung ke database:**
   ```bash
   docker exec apbdanalysis2026_mariadb_prod mysql -u apbdanalysis2026_user -pyour_secure_password_here apbdanalysis2026_prod -e "SELECT 1;"
   ```

4. **Test langsung ke Redis:**
   ```bash
   docker exec apbdanalysis2026_redis_prod redis-cli -a your_redis_password_here PING
   ```

---

## ✅ Expected Result

Setelah berhasil:
- ✅ `.env` file ada di container
- ✅ Database connection berhasil
- ✅ Redis connection berhasil  
- ✅ Application return HTTP 200
- ✅ Halaman web tampil dengan styling

