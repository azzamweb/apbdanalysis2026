## APBD Analysis 2026 – Panduan Instalasi & Deploy

Panduan ini merangkum satu alur kerja berbasis Docker untuk development maupun production. Ikuti langkah sesuai lingkungan yang dibutuhkan.

---

## 1. Prasyarat

- Docker 24+ dan Docker Compose Plugin
- Git
- RAM minimal 4 GB untuk stack lengkap
- Port yang tersedia:
  - Development: `5560`, `5561`, `6380`, `3307`, `5173-5174`
  - Production (default): `5560`, `5561`, `8443`, `6380`, `3307`

---

## 2. Siapkan Repository & Environment

```bash
git clone https://github.com/azzamweb/apbdanalysis2026.git
cd apbdanalysis2026

# Salin file env sesuai kebutuhan
cp .env.example .env
```

Lengkapi variabel penting di `.env`:

| Variabel | Nilai contoh | Catatan |
| --- | --- | --- |
| `APP_NAME` | `APBD Analysis 2026` | Opsional |
| `APP_KEY` | (kosong) | Akan diisi via `php artisan key:generate` |
| `APP_URL` | `http://localhost:5560` | Untuk development |
| `APP_ENV` | `local` / `production` | Sesuaikan |
| `APP_DEBUG` | `true` (dev) / `false` (prod) | — |
| `DB_HOST` | `mariadb` (dev) / `127.0.0.1` atau host DB | Sesuaikan deployment |
| `DB_DATABASE` | `apbdanalysis2026_dev` | Sudah tersedia di docker-compose.dev |
| `DB_USERNAME` | `apbdanalysis2026` | — |
| `DB_PASSWORD` | `apbdanalysis2026_password` | — |
| `REDIS_HOST` | `redis` (dev) | Sesuaikan jika eksternal |
| `REDIS_PASSWORD` | `redis_password` | Default docker-compose.dev |

---

## 3. Menjalankan Lingkungan Development (Docker Compose)

1. **Bangun & jalankan stack**
   ```bash
   docker compose -f docker-compose.dev.yml up -d --build
   ```

2. **Masuk ke kontainer aplikasi**
   ```bash
   docker compose -f docker-compose.dev.yml exec app sh
   ```

   Jalankan di dalam kontainer (sekali setelah clone):
   ```bash
   composer install
   npm install
   php artisan key:generate
   php artisan migrate --seed
   npm run build    # atau npm run dev untuk hot-reload
   exit             # keluar dari kontainer
   ```

3. **Akses layanan**
   - Aplikasi: http://localhost:5560
   - phpMyAdmin: http://localhost:5561 (user `apbdanalysis2026` / password `apbdanalysis2026_password`)
   - Redis: `localhost:6380`, password `redis_password`
   - Database host ke luar kontainer: `localhost:3307`

4. **Perintah berguna**
   ```bash
   docker compose -f docker-compose.dev.yml logs -f app
   docker compose -f docker-compose.dev.yml exec app php artisan migrate
   docker compose -f docker-compose.dev.yml exec app npm run dev
   docker compose -f docker-compose.dev.yml down    # hentikan semua layanan
   ```

---

## 4. Deploy Production dengan Docker

### 4.1. Persiapan Server
- OS: Ubuntu 22.04 LTS atau kompatibel
- Docker & Docker Compose terinstal
- Domain & SSL (opsional, Let’s Encrypt didukung)
- Port terseleksi:
  - Aplikasi: `80` / `443` (dipetakan ke `5560` / `8443` jika mengikuti contoh)
  - phpMyAdmin: `5561`
  - Database: `3307` (port host) → `3306` (kontainer)
  - Redis: `6380`

### 4.2. Proses Deploy
1. Clone repo di server dan masuk direktori.

2. Salin `production.env.example` menjadi `.env`, lalu isi nilai produksi (`APP_URL`, kredensial DB/Redis eksternal, dsb.).

3. Jalankan skrip deploy:
   ```bash
   chmod +x deploy-to-production.sh
   ./deploy-to-production.sh
   ```
   Skrip akan:
   - `git pull` branch `main`
   - Build image `app` tanpa cache
   - Menjalankan stack `docker-compose.prod.yml`
   - Menjalankan `php artisan migrate --force`
   - Membangun ulang cache & memverifikasi health

4. **Endpoint default**
   - Aplikasi: `http://<IP-server>:5560` (gunakan reverse proxy atau map port 80/443 sesuai kebutuhan)
   - phpMyAdmin: `http://<IP-server>:5561`

### 4.3. Operasional Rutin
```bash
# Status kontainer
docker compose -f docker-compose.prod.yml ps

# Log aplikasi produksi
docker compose -f docker-compose.prod.yml logs -f app

# Masuk ke kontainer
docker compose -f docker-compose.prod.yml exec app sh

# Migrasi & cache
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

---

## 5. Tips & Troubleshooting

| Gejala | Solusi |
| --- | --- |
| `No application encryption key has been specified.` | Jalankan `php artisan key:generate` di dalam kontainer aplikasi. |
| Tidak bisa konek DB/Redis | Pastikan `DB_HOST` & `REDIS_HOST` sesuai jaringan Docker, jalankan `docker compose ... ps` untuk memastikan kontainer hidup. |
| Perubahan kode tidak terlihat | Karena volume ter-mount, pastikan file tersimpan. Jika perlu rebuild aset: `npm run build`. |
| Artisan error karena dependency | Pastikan `composer install` & `npm install` dijalankan di kontainer setelah build awal. |
| Gagal deploy karena cache rute | Jalankan `php artisan route:clear` dan pastikan tidak ada nama rute duplikat sebelum `route:cache`. |

---

## 6. Referensi Perintah Singkat

```bash
# Development
docker compose -f docker-compose.dev.yml up -d --build
docker compose -f docker-compose.dev.yml exec app sh
docker compose -f docker-compose.dev.yml logs -f

# Production
./deploy-to-production.sh
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml exec app php artisan optimize

# Shutdown
docker compose -f docker-compose.dev.yml down
docker compose -f docker-compose.prod.yml down
```

---

Gunakan panduan ini sebagai sumber tunggal untuk setup dan deployment. Jika alur berubah, cukup perbarui satu dokumen ini dan tautkan dari README.


