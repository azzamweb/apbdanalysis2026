# APBD Analysis 2026

Laravel-based APBD (regional budget) analytics platform with Docker-first workflow for development and production.

## 🚀 Quick Start (Docker)

```bash
# Clone repository
git clone https://github.com/azzamweb/apbdanalysis2026.git
cd apbdanalysis2026

# Copy environment file
cp .env.example .env

# Start development stack
docker compose -f docker-compose.dev.yml up -d --build

# Install dependencies & prepare app (run inside container)
docker compose -f docker-compose.dev.yml exec app sh
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build    # atau npm run dev
exit

# Open application
open http://localhost:5560
```

Panduan lengkap (development & production) tersedia di [`INSTALLATION.md`](INSTALLATION.md).

## 📋 Prasyarat

- Docker 24+ & Docker Compose plugin
- Git
- RAM minimal 4 GB
- Port yang tidak bentrok: `5560`, `5561`, `6380`, `3307`, `5173-5174`

## 🛠️ Tumpukan Teknologi

- **Framework**: Laravel 11
- **Web Server**: Nginx + PHP-FPM 8.2
- **Database**: MariaDB 10.11
- **Cache/Queue**: Redis 7
- **Front-end tooling**: Vite (npm), Tailwind CSS
- **Containerization**: Docker & Docker Compose

## 📁 Struktur Proyek

```
apbdanalysis2026/
├── app/                     # Kode Laravel utama
├── database/                # Migrasi & seeder
├── docker/                  # Konfigurasi Docker (nginx/php/mysql/redis/supervisor)
├── public/                  # Aset publik
├── resources/               # Blade view, JS, CSS
├── storage/                 # File aplikasi
├── docker-compose.dev.yml   # Stack development
├── docker-compose.prod.yml  # Stack production
├── Dockerfile.dev           # Image dev
├── Dockerfile.prod          # Image production
├── deploy-to-production.sh  # Skrip deploy otomatis
├── INSTALLATION.md          # Panduan instalasi & deploy
└── README.md
```

## 🔧 Perintah Utama

```bash
# Development lifecycle
docker compose -f docker-compose.dev.yml up -d --build
docker compose -f docker-compose.dev.yml logs -f app
docker compose -f docker-compose.dev.yml exec app sh
docker compose -f docker-compose.dev.yml down

# Production lifecycle
./deploy-to-production.sh
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml exec app sh
docker compose -f docker-compose.prod.yml down
```

## 🌐 Endpoint Default

### Development
- Aplikasi: http://localhost:5560
- phpMyAdmin: http://localhost:5561 (user `apbdanalysis2026`, password `apbdanalysis2026_password`)
- MariaDB (host): `localhost:3307`
- Redis (host): `localhost:6380`

### Production (mapping standar)
- Aplikasi: http://\<server-ip\>:5560 (mapping ke 80/443 dapat disesuaikan)
- phpMyAdmin: http://\<server-ip\>:5561
- MariaDB (host): `localhost:3307`
- Redis (host): `localhost:6380`

## ⚙️ Konfigurasi Lingkungan

```bash
cp .env.example .env             # Development / staging
cp production.env.example .env   # Production (di server)
```

Isi variabel penting sesuai panduan di [`INSTALLATION.md`](INSTALLATION.md). Setelah mengubah `.env`, jalankan:

```bash
docker compose -f docker-compose.dev.yml exec app php artisan config:clear
docker compose -f docker-compose.dev.yml exec app php artisan cache:clear
```

## 🗄️ Database & Migrasi

```bash
# Development
docker compose -f docker-compose.dev.yml exec app php artisan migrate
docker compose -f docker-compose.dev.yml exec app php artisan db:seed

# Production
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

Backup/restore tersedia via `deploy-to-production.sh` dan perintah Docker standar.

## 🔒 Keamanan & Performa

- SSL/TLS siap untuk Let’s Encrypt (lihat `INSTALLATION.md`)
- Security headers & rate limiting terkonfigurasi di Nginx
- Redis digunakan untuk cache, queue, dan session
- OPcache aktif di production, nonaktif di development
- Upload besar (hingga 500 MB) didukung lewat konfigurasi PHP/Nginx

## 📝 Troubleshooting Singkat

| Masalah | Solusi |
| --- | --- |
| `No application encryption key has been specified.` | `php artisan key:generate --show`, tempelkan ke `.env`, restart kontainer & rebuild cache. |
| Koneksi DB/Redis gagal | Pastikan kontainer `mariadb`/`redis` berjalan, cek `DB_HOST` & `REDIS_HOST`. |
| Cache rute gagal | Jalankan `php artisan route:clear` sebelum `route:cache`; pastikan nama rute unik. |
| Perubahan kode tidak muncul | Pastikan volume tersimpan dan rebuild aset (`npm run build` / `npm run dev`). |
| Domain via Cloudflare 523 | DNS harus ke IP origin, buka port 80/443, siapkan reverse proxy ke `127.0.0.1:5560`. |

## 🤝 Kontribusi

1. Fork repository
2. Buat branch fitur
3. Lakukan perubahan & tambahkan tes bila perlu
4. Pastikan `docker compose ... exec app php artisan test` lulus
5. Ajukan pull request

## 📄 Lisensi

Proyek ini berada di bawah lisensi MIT.

---

**Happy coding!** 🎉