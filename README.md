# APBD Analysis 2026 - Aplikasi Pengolah Data APBD

Aplikasi Laravel untuk pengolahan data APBD (Anggaran Pendapatan dan Belanja Daerah) dengan fitur upload, analisis, dan pelaporan data anggaran. Versi 2026 dengan konfigurasi Docker yang telah dioptimalkan untuk upload file besar dan deployment production.

## 🚀 Quick Start

### Development Environment

```bash
# Clone repository
git clone https://github.com/azzamweb/hsananalysis.git
cd hsananalysis

# Setup development environment
make setup

# Start development server
make dev

# Access application
open http://localhost:8000
```

### Production Environment

```bash
# Setup production environment
make setup-prod

# Deploy to production
make prod

# Access application
open https://localhost
```

## 📋 Prerequisites

- Docker & Docker Compose
- Git
- 2GB+ RAM
- 20GB+ Storage

## 🛠️ Technology Stack

- **Backend**: Laravel 11.x
- **Database**: MariaDB 10.11
- **Cache/Session**: Redis 7
- **Web Server**: Nginx + PHP-FPM 8.2
- **Containerization**: Docker & Docker Compose
- **Database Management**: phpMyAdmin

## 📁 Project Structure

```
hsananalysis/
├── app/                    # Laravel application code
├── database/              # Database migrations & seeders
├── docker/                # Docker configurations
│   ├── nginx/            # Nginx configurations
│   ├── php/              # PHP configurations
│   ├── mysql/            # MariaDB configurations
│   ├── redis/            # Redis configurations
│   ├── supervisor/       # Supervisor configurations
│   └── scripts/          # Deployment scripts
├── public/               # Web accessible files
├── resources/            # Views, assets, etc.
├── storage/              # File storage
├── docker-compose.yml    # Base Docker Compose
├── docker-compose.dev.yml # Development environment
├── docker-compose.prod.yml # Production environment
├── Dockerfile.dev        # Development Dockerfile
├── Dockerfile.prod       # Production Dockerfile
├── Makefile              # Convenient commands
└── README.md             # This file
```

## 🔧 Available Commands

### Development Commands

```bash
make dev              # Start development environment
make stop             # Stop all containers
make logs             # View application logs
make shell            # Access application shell
make db-shell         # Access database shell
make redis-shell      # Access Redis shell
make migrate          # Run database migrations
make seed             # Run database seeders
make test             # Run tests
make cache-clear      # Clear application cache
make status           # Show container status
```

### Production Commands

```bash
make setup-prod       # Setup production environment
make prod             # Deploy to production
make logs-prod        # View production logs
make shell-prod       # Access production shell
make migrate-prod     # Run production migrations
make seed-prod        # Run production seeders
make cache-clear-prod # Clear production cache
make optimize         # Optimize application
make status-prod      # Show production status
```

### Backup Commands

```bash
make backup           # Create backup
make restore          # Restore from backup
```

## 🌐 Access URLs

### Development
- **Application**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8081
- **Database**: localhost:3307
- **Redis**: localhost:6380

### Production
- **Application**: https://localhost (or your domain)
- **phpMyAdmin**: http://localhost:8080
- **Database**: localhost:3306
- **Redis**: localhost:6379

## ⚙️ Configuration

### Environment Variables

Copy the appropriate environment file:

```bash
# Development
cp .env.example .env

# Production
cp production.env.example .env
```

Key environment variables:

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_ENV` | Environment | `local` / `production` |
| `APP_DEBUG` | Debug mode | `true` / `false` |
| `APP_URL` | Application URL | `http://localhost:8000` |
| `DB_DATABASE` | Database name | `hsananalysis_dev` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | `password` |
| `REDIS_PASSWORD` | Redis password | `redis_password` |

### SSL Certificates (Production)

For production, you need SSL certificates:

```bash
# Self-signed (testing)
make setup-prod

# Let's Encrypt (production)
sudo certbot certonly --standalone -d yourdomain.com
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem docker/nginx/ssl/key.pem
```

## 🗄️ Database

### Migrations

```bash
# Development
make migrate

# Production
make migrate-prod
```

### Seeders

```bash
# Development
make seed

# Production
make seed-prod
```

### Backup & Restore

```bash
# Create backup
make backup

# Restore from backup
make restore BACKUP_FILE=hsananalysis_full_20240101_120000.tar.gz
```

## 🔒 Security Features

- **SSL/TLS**: HTTPS encryption
- **Security Headers**: XSS, CSRF protection
- **Rate Limiting**: API and login protection
- **Password Protection**: Redis and database
- **Input Validation**: Laravel validation
- **SQL Injection Protection**: PDO prepared statements

## 📊 Performance Optimizations

### Development
- **OPcache**: Disabled for development
- **Debug Mode**: Enabled
- **Logging**: Verbose logging

### Production
- **OPcache**: Enabled with optimization
- **Debug Mode**: Disabled
- **Caching**: Redis for sessions and cache
- **Compression**: Gzip enabled
- **Static Files**: Long-term caching
- **Database**: Optimized MariaDB settings

## 🐳 Docker Services

### Development Services
- **app**: PHP-FPM + Nginx (port 8000)
- **mariadb**: MariaDB database (port 3307)
- **redis**: Redis cache (port 6380)
- **phpmyadmin**: Database management (port 8081)

### Production Services
- **app**: PHP-FPM + Nginx (port 80/443)
- **mariadb**: MariaDB database (port 3306)
- **redis**: Redis cache (port 6379)
- **phpmyadmin**: Database management (port 8080)
- **nginx**: Reverse proxy with SSL

## 📝 Logs

### View Logs

```bash
# Development
make logs

# Production
make logs-prod

# Specific service
docker-compose -f docker-compose.dev.yml logs mariadb
```

### Log Locations

- **Application**: Laravel logs in `storage/logs/`
- **Nginx**: `/var/log/nginx/`
- **PHP**: `/var/log/php_errors.log`
- **Database**: `/var/log/mysql/`

## 🔧 Troubleshooting

### Common Issues

#### Container Won't Start
```bash
# Check logs
make logs

# Check status
make status

# Restart containers
make stop && make dev
```

#### Database Connection Issues
```bash
# Check database status
docker-compose -f docker-compose.dev.yml ps mariadb

# Test connection
make db-shell

# Check environment variables
cat .env | grep DB_
```

#### Permission Issues
```bash
# Fix storage permissions
docker-compose -f docker-compose.dev.yml exec app chown -R www-data:www-data storage
docker-compose -f docker-compose.dev.yml exec app chmod -R 755 storage
```

#### SSL Certificate Issues
```bash
# Regenerate self-signed certificates
rm docker/nginx/ssl/*
make setup-prod
```

### Performance Issues

1. **Check resource usage**: `docker stats`
2. **Review slow queries**: Check MariaDB slow query log
3. **Optimize database**: Run `make optimize`
4. **Clear cache**: Run `make cache-clear`

## 🚀 Deployment

### Development Deployment

```bash
# Initial setup
make setup

# Start development
make dev

# Run migrations
make migrate

# Access application
open http://localhost:8000
```

### Production Deployment

```bash
# Initial setup
make setup-prod

# Configure environment
nano .env

# Deploy to production
make prod

# Run migrations
make migrate-prod

# Access application
open https://localhost
```

### Automated Deployment

For automated deployment, you can use the provided scripts:

```bash
# Setup script
./docker/scripts/setup-prod.sh

# Deploy script
./docker/scripts/deploy.sh

# Backup script
./docker/scripts/backup.sh
```

## 📚 Documentation

- [Production Deployment Guide](PRODUCTION.md)
- [Docker Configuration](DOCKER.md)
- [Laravel Documentation](https://laravel.com/docs)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:

1. Check this README
2. Review the documentation
3. Check GitHub issues
4. Contact the development team

## 🔄 Updates

### Application Updates

```bash
# Pull latest changes
git pull

# Rebuild containers
make dev  # or make prod

# Run migrations
make migrate  # or make migrate-prod

# Clear cache
make cache-clear  # or make cache-clear-prod
```

### System Updates

```bash
# Update Docker
sudo apt update && sudo apt upgrade docker.io

# Update system
sudo apt update && sudo apt upgrade
```

---

**Happy Coding! 🎉**