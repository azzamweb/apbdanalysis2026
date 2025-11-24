# 🚀 Panduan Lengkap Production - APBD Analysis 2026

Dokumen ini adalah panduan komprehensif untuk men-deploy aplikasi APBD Analysis 2026 ke server production Ubuntu 24.04.

## 📑 Daftar Isi
1. [Persiapan Server](#1-persiapan-server)
2. [Setup Aplikasi](#2-setup-aplikasi)
3. [Deployment](#3-deployment)
4. [Maintenance & Monitoring](#4-maintenance--monitoring)

---

## 1. Persiapan Server

Ikuti panduan detail di file **[SERVER_SETUP.md](SERVER_SETUP.md)** untuk:
- Setup awal Ubuntu 24.04
- Konfigurasi Security (Firewall, User)
- Instalasi Docker & Docker Compose
- Konfigurasi Nginx Reverse Proxy

> **PENTING**: Pastikan Anda sudah menyelesaikan langkah-langkah di `SERVER_SETUP.md` sebelum lanjut ke tahap ini.

---

## 2. Setup Aplikasi

Setelah server siap dan Anda sudah login sebagai user non-root (misal: `sysadmin`), lakukan langkah berikut:

### A. Clone Repository
```bash
mkdir -p ~/dev
cd ~/dev
git clone https://github.com/username/apbdanalysis2026.git
cd apbdanalysis2026
```

### B. Konfigurasi Environment Production
Copy template environment file:
```bash
cp production.env.example .env
```

Edit file `.env` dan sesuaikan konfigurasi:
```bash
nano .env
```
**Yang perlu diubah:**
- `APP_URL`: Isi dengan `https://analysis.trust-idn.id` (Penting: gunakan https jika proxy external menggunakan SSL)
- `DB_PASSWORD`: Ganti dengan password database yang KUAT
- `REDIS_PASSWORD`: Ganti dengan password redis yang KUAT
- `TRUSTED_PROXIES`: Isi dengan `*` atau IP proxy jika diketahui (Default sudah diset `*` di aplikasi)

### C. Setup Nginx Host (Jika belum)
Copy file konfigurasi nginx yang sudah disediakan ke direktori konfigurasi Nginx server:
```bash
sudo cp nginx-host-example.conf /etc/nginx/sites-available/apbdanalysis
sudo ln -s /etc/nginx/sites-available/apbdanalysis /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 3. Deployment

Gunakan script otomatis yang sudah disediakan untuk melakukan deployment.

### Jalankan Deployment Script
```bash
chmod +x deploy-to-production.sh
./deploy-to-production.sh
```

Script ini akan otomatis:
1. Pull code terbaru dari git
2. Build Docker image production (bisa memakan waktu 10-15 menit pertama kali)
3. Menjalankan container
4. Menjalankan migrasi database
5. Membersihkan dan rebuild cache aplikasi

### Verifikasi Deployment
Buka browser dan akses IP atau Domain server Anda.
- Aplikasi Utama: `http://IP_SERVER`
- phpMyAdmin: `http://IP_SERVER/pma/` (Jika dikonfigurasi di Nginx)

---

## 4. Maintenance & Monitoring

### Update Aplikasi
Untuk mengupdate aplikasi dengan kode terbaru:
```bash
cd ~/dev/apbdanalysis2026
./deploy-to-production.sh
```

### Cek Logs
```bash
# Log Aplikasi Laravel
docker exec apbdanalysis2026_app_prod tail -f storage/logs/laravel.log

# Log Container Nginx/PHP
docker logs -f apbdanalysis2026_app_prod
```

### Backup Database
Database tersimpan di volume docker `mariadb_prod_data`.
Untuk melakukan backup manual:
```bash
docker exec apbdanalysis2026_mariadb_prod mysqldump -u root -p[PASSWORD] apbdanalysis2026_prod > backup_$(date +%F).sql
```

---

## 🆘 Troubleshooting Common Issues

### 1. 502 Bad Gateway
Biasanya container belum siap atau mati.
- Cek status container: `docker ps`
- Cek logs: `docker logs apbdanalysis2026_app_prod`

### 2. Permission Denied pada Storage
Jika ada error saat upload file atau log tidak muncul.
```bash
docker exec -it apbdanalysis2026_app_prod chown -R www-data:www-data /var/www/html/storage
```

### 3. Halaman Kosong / Putih
Biasanya error PHP yang tidak tertangkap. Cek log laravel:
```bash
docker exec apbdanalysis2026_app_prod tail -50 storage/logs/laravel.log
```
