# 🖥️ Panduan Persiapan Server Production (Ubuntu 24.04)

Panduan ini mencakup langkah-langkah persiapan server Ubuntu 24.04 LTS untuk men-deploy aplikasi APBD Analysis 2026 menggunakan Docker.

## 📋 Spesifikasi Server
- **OS**: Ubuntu 24.04 LTS
- **CPU**: 4 Core
- **RAM**: 8 GB
- **Storage**: 200 GB

---

## 🚀 Langkah 1: Initial Server Setup

Login ke server sebagai `root` via SSH.

### 1. Update System
Pastikan sistem menggunakan paket terbaru.
```bash
apt update && apt upgrade -y
```

### 2. Setup Timezone
Set timezone sesuai lokasi (WIB/Jakarta).
```bash
timedatectl set-timezone Asia/Jakarta
```

### 3. Buat User Non-Root (Recommended)
Hindari menjalankan operasional sehari-hari sebagai root.
```bash
# Buat user baru (ganti 'sysadmin' dengan username yang diinginkan)
adduser sysadmin

# Berikan akses sudo
usermod -aG sudo sysadmin

# Login sebagai user baru
su - sysadmin
```

---

## 🛡️ Langkah 2: Security Hardening (Basic)

### 1. Konfigurasi Firewall (UFW)
Aktifkan firewall dan hanya izinkan port yang diperlukan.
```bash
# Izinkan SSH (PENTING: Jangan sampai terlockout)
sudo ufw allow OpenSSH

# Izinkan HTTP & HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Aktifkan firewall
sudo ufw enable
```

### 2. Setup Swap (Optional tapi Recommended)
Dengan RAM 8GB, swap file 4GB cukup untuk safety net.
```bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 🐳 Langkah 3: Install Docker & Docker Compose

Ubuntu 24.04 memiliki paket Docker yang cukup baru, tapi disarankan menggunakan repository resmi Docker.

### 1. Install Dependencies
```bash
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common
```

### 2. Tambahkan Docker Repository
```bash
# Add Docker's official GPG key:
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
```

### 3. Install Docker Packages
```bash
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

### 4. Konfigurasi User Docker
Agar user `sysadmin` bisa menjalankan docker tanpa `sudo`.
```bash
sudo usermod -aG docker $USER
newgrp docker
```

### 5. Verifikasi Instalasi
```bash
docker --version
docker compose version
```

---

## 🌐 Langkah 4: Setup Reverse Proxy (Nginx Host)

Kita akan menggunakan Nginx di host server untuk mem-proxy request ke container Docker. Ini lebih aman dan fleksibel daripada mengekspos port container langsung.

### 1. Install Nginx
```bash
sudo apt install -y nginx
```

### 2. Buat Konfigurasi Nginx
Buat file config baru untuk aplikasi.
```bash
sudo nano /etc/nginx/sites-available/apbdanalysis
```

Isi dengan konfigurasi berikut (Sesuaikan `server_name` jika sudah ada domain, jika belum gunakan IP):

```nginx
server {
    listen 80;
    server_name _; # Ganti dengan domain anda atau IP public

    # Proxy ke Aplikasi Utama (Port 5560)
    location / {
        proxy_pass http://127.0.0.1:5560;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Proxy ke phpMyAdmin (Optional - Port 5561)
    # Akses via http://IP/pma/
    location /pma/ {
        rewrite ^/pma(/.*)$ $1 break;
        proxy_pass http://127.0.0.1:5561;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 3. Aktifkan Site
```bash
sudo ln -s /etc/nginx/sites-available/apbdanalysis /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Hapus default config jika tidak dipakai
sudo nginx -t
sudo systemctl reload nginx
```

---

## 📂 Langkah 5: Persiapan Direktori Aplikasi

```bash
# Buat direktori project
mkdir -p ~/dev/apbdanalysis2026
cd ~/dev/apbdanalysis2026

# Clone repository (Anda perlu setup SSH key git terlebih dahulu atau gunakan HTTPS)
# git clone <REPO_URL> .
```

Server sekarang siap untuk proses deployment aplikasi! 🚀
