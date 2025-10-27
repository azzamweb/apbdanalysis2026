# phpMyAdmin Login Credentials - Production

## 🔗 URL Akses
- **URL**: http://[IP-SERVER]:5561
- Atau jika dari komputer yang sama: http://localhost:5561

## 🔐 Login Credentials

### Option 1: Root User (Full Access)
- **Server**: `mariadb` atau kosongkan (auto-detect)
- **Username**: `root`
- **Password**: `your_secure_password_here`

### Option 2: Application User (Database Specific)
- **Server**: `mariadb` atau kosongkan (auto-detect)
- **Username**: `apbdanalysis2026_user`
- **Password**: `your_secure_password_here`

## 📊 Database Information
- **Database Name**: `apbdanalysis2026_prod`
- **Port**: 3307 (external), 3306 (internal)
- **Host**: mariadb (dalam docker network)

## ⚠️ Catatan Keamanan

### Password Production Asli
Kredensial di atas menggunakan password default dari template. Di production server asli, password-nya harus sudah diganti!

Untuk melihat password production yang sebenarnya, jalankan di server:

```bash
# Di server production: sysadmin@APBDANALYSIS2026
docker exec apbdanalysis2026_app_prod cat .env | grep -E "DB_USERNAME|DB_PASSWORD"
```

Outputnya akan menampilkan username dan password yang benar.

## 🔒 Rekomendasi Keamanan Production

1. **Ganti Password Default** jika masih menggunakan `your_secure_password_here`
   ```bash
   # Generate strong password
   openssl rand -base64 32
   ```

2. **Batasi Akses phpMyAdmin** dengan firewall
   ```bash
   # Hanya izinkan dari IP tertentu
   sudo ufw allow from [YOUR_IP] to any port 5561
   ```

3. **Pertimbangkan Disable phpMyAdmin** di production setelah setup selesai
   ```bash
   docker stop apbdanalysis2026_phpmyadmin_prod
   docker rm apbdanalysis2026_phpmyadmin_prod
   ```

4. **Atau gunakan SSH Tunnel** untuk akses phpMyAdmin
   ```bash
   # Dari komputer local
   ssh -L 5561:localhost:5561 sysadmin@APBDANALYSIS2026
   # Lalu akses: http://localhost:5561
   ```

## 📝 Troubleshooting

### Jika tidak bisa login:
```bash
# Cek password di .env production
docker exec apbdanalysis2026_app_prod cat .env | grep DB_PASSWORD

# Test koneksi database
docker exec apbdanalysis2026_app_prod php artisan tinker --execute="DB::connection()->getPdo();"

# Cek phpMyAdmin logs
docker logs apbdanalysis2026_phpmyadmin_prod --tail 50
```

### Reset password database jika lupa:
```bash
# Login ke MariaDB container
docker exec -it apbdanalysis2026_mariadb_prod mysql -u root -p

# Di MySQL prompt:
ALTER USER 'root'@'%' IDENTIFIED BY 'new_password_here';
ALTER USER 'apbdanalysis2026_user'@'%' IDENTIFIED BY 'new_password_here';
FLUSH PRIVILEGES;
EXIT;

# Jangan lupa update .env juga!
```

