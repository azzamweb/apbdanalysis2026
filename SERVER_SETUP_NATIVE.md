# 🖥️ Panduan Instalasi Server Native (Ubuntu 24.04)

Panduan ini mencakup langkah-langkah instalasi manual (tanpa Docker) untuk stack LEMP (Linux, Nginx, MySQL/MariaDB, PHP) beserta Redis dan Supervisor.

## 📋 Spesifikasi Software
- **OS**: Ubuntu 24.04 LTS
- **Web Server**: Nginx
- **Database**: MariaDB 10.11+
- **PHP**: PHP 8.2 (via PPA Ondrej)
- **Cache**: Redis
- **Process Monitor**: Supervisor
- **DB Management**: phpMyAdmin

---

## 🚀 Langkah 1: Initial Setup & Security

Login sebagai root, lalu update sistem.

```bash
apt update && apt upgrade -y
timedatectl set-timezone Asia/Jakarta
```

### Buat User Sysadmin
```bash
adduser sysadmin
usermod -aG sudo sysadmin
su - sysadmin
```

### Konfigurasi Firewall (UFW)
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## 🌐 Langkah 2: Install Nginx (Web Server)

```bash
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

Verifikasi dengan mengakses IP server di browser.

---

## 🐘 Langkah 3: Install PHP 8.2

Ubuntu 24.04 defaultnya PHP 8.3, tapi aplikasi kita butuh PHP 8.2. Kita gunakan PPA Ondrej.

```bash
# Install dependencies
sudo apt install -y software-properties-common ca-certificates lsb-release apt-transport-https

# Add PHP PPA
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 dan ekstensi yang dibutuhkan Laravel
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring \
    php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl \
    php8.2-redis php8.2-tokenizer php8.2-fileinfo

# Cek versi
php -v
```

### Konfigurasi PHP-FPM
Edit `php.ini` untuk optimasi upload size.
```bash
sudo nano /etc/php/8.2/fpm/php.ini
```
Ubah nilai berikut:
```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```
Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

---

## 🗄️ Langkah 4: Install MariaDB (Database)

```bash
sudo apt install -y mariadb-server
sudo systemctl enable --now mariadb
```

### Secure Installation
Jalankan script keamanan untuk set root password dan hapus user anonymous.
```bash
sudo mysql_secure_installation
```
- Switch to unix_socket authentication? **Y**
- Change the root password? **Y** (Set password kuat)
- Remove anonymous users? **Y**
- Disallow root login remotely? **Y**
- Remove test database? **Y**
- Reload privilege tables? **Y**

### Buat Database & User Aplikasi
Login ke MariaDB:
```bash
sudo mariadb -u root -p
```
Jalankan query SQL:
```sql
CREATE DATABASE apbdanalysis2026_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'apbd_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT_ANDA';
GRANT ALL PRIVILEGES ON apbdanalysis2026_prod.* TO 'apbd_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🚀 Langkah 5: Install Redis & Supervisor

### Redis
```bash
sudo apt install -y redis-server
sudo nano /etc/redis/redis.conf
```
Ubah `supervised no` menjadi `supervised systemd`.
Set password (optional tapi recommended):
```conf
requirepass PASSWORD_REDIS_ANDA
```
Restart Redis:
```bash
sudo systemctl restart redis-server
```

### Supervisor
Untuk menjalankan queue worker Laravel.
```bash
sudo apt install -y supervisor
```

---

## 🛠️ Langkah 6: Install Composer & Node.js

### Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Node.js (LTS)
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 📊 Langkah 7: Install phpMyAdmin

Kita install manual agar bisa menggunakan PHP 8.2 dan Nginx.

```bash
cd /var/www/html
sudo wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip
sudo apt install unzip
sudo unzip phpMyAdmin-latest-all-languages.zip
sudo mv phpMyAdmin-*-all-languages pma
sudo rm phpMyAdmin-latest-all-languages.zip
```

### Konfigurasi phpMyAdmin
```bash
sudo cp /var/www/html/pma/config.sample.inc.php /var/www/html/pma/config.inc.php
sudo nano /var/www/html/pma/config.inc.php
```
Isi `blowfish_secret` dengan string random 32 karakter.

### Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/pma
sudo chmod -R 755 /var/www/html/pma
```

Sekarang server sudah siap untuk deployment aplikasi! Lanjut ke **DEPLOYMENT_NATIVE.md**.
