# HSAN Analysis - Docker Setup

This document provides comprehensive instructions for setting up and running the HSAN Analysis application using Docker with FrankenPHP, MariaDB, and phpMyAdmin.

## 🏗️ Architecture

The Docker setup includes:

- **FrankenPHP**: Modern PHP application server with built-in HTTP/2 and WebSocket support
- **MariaDB**: High-performance database server
- **phpMyAdmin**: Web-based database management interface
- **Redis**: In-memory data store for caching and sessions
- **Nginx**: Reverse proxy for production (optional)

## 📋 Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- Git
- Make (optional, for convenience commands)

## 🚀 Quick Start

### 1. Initial Setup

```bash
# Clone the repository
git clone https://github.com/azzamweb/hsananalysis.git
cd hsananalysis

# Run initial setup
make setup
# or
chmod +x docker/scripts/setup.sh
./docker/scripts/setup.sh
```

### 2. Development Environment

```bash
# Start development environment
make dev
# or
chmod +x docker/scripts/dev.sh
./docker/scripts/dev.sh
```

### 3. Production Deployment

```bash
# Deploy to production
make prod
# or
chmod +x docker/scripts/deploy.sh
./docker/scripts/deploy.sh
```

## 🔧 Available Commands

### Development Commands

```bash
make dev          # Start development environment
make stop         # Stop all containers
make logs         # View application logs
make shell        # Access application shell
make db-shell     # Access database shell
make redis-shell  # Access Redis shell
make migrate      # Run database migrations
make seed         # Run database seeders
make test         # Run tests
make cache-clear  # Clear application cache
make status       # Show container status
```

### Production Commands

```bash
make prod              # Deploy to production
make logs-prod         # View production logs
make shell-prod        # Access production shell
make migrate-prod      # Run production migrations
make seed-prod         # Run production seeders
make cache-clear-prod  # Clear production cache
make optimize          # Optimize for production
make status-prod       # Show production status
```

### Maintenance Commands

```bash
make backup    # Create backup
make restore   # Restore from backup
make clean      # Clean up containers and volumes
```

## 🌐 Service URLs

### Development
- **Application**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8081
- **Redis**: localhost:6380
- **MariaDB**: localhost:3307

### Production
- **Application**: http://localhost:8000 (or your domain)
- **phpMyAdmin**: Internal access only
- **Redis**: Internal access only
- **MariaDB**: Internal access only

## ⚙️ Configuration

### Environment Variables

The application uses the following environment variables:

```bash
# Application
APP_NAME="HSAN Analysis"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=hsananalysis
DB_USERNAME=hsananalysis
DB_PASSWORD=hsananalysis_password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Docker Compose Files

- `docker-compose.yml` - Base configuration
- `docker-compose.dev.yml` - Development environment
- `docker-compose.prod.yml` - Production environment

## 🗄️ Database Management

### Accessing phpMyAdmin

1. Open http://localhost:8081 (development) or http://localhost:8080 (production)
2. Use the following credentials:
   - **Server**: mariadb
   - **Username**: hsananalysis
   - **Password**: hsananalysis_password

### Database Backup

```bash
# Create backup
make backup

# Restore from backup
make restore BACKUP_FILE=hsananalysis_db_20240101_120000.sql.gz
```

## 🔧 Troubleshooting

### Common Issues

1. **Port conflicts**: Make sure ports 8000, 8080, 3306, 6379 are not in use
2. **Permission issues**: Run `chmod +x docker/scripts/*.sh`
3. **Container not starting**: Check logs with `make logs`

### Logs

```bash
# View all logs
docker-compose -f docker-compose.dev.yml logs

# View specific service logs
docker-compose -f docker-compose.dev.yml logs app
docker-compose -f docker-compose.dev.yml logs mariadb
docker-compose -f docker-compose.dev.yml logs redis
```

### Performance Optimization

The Docker setup includes several performance optimizations:

- **OPcache**: Enabled with aggressive settings for production
- **Redis**: Used for caching and sessions
- **MariaDB**: Optimized configuration for different environments
- **FrankenPHP**: Modern PHP server with HTTP/2 support
- **Nginx**: Reverse proxy with compression and caching

## 📊 Monitoring

### Health Checks

- Application: http://localhost:8000/health
- Database: Check via phpMyAdmin
- Redis: Use `make redis-shell`

### Resource Usage

```bash
# View resource usage
docker stats

# View container resource usage
docker-compose -f docker-compose.dev.yml top
```

## 🔒 Security

### Production Security Features

- Security headers configured
- Rate limiting enabled
- Input validation
- SQL injection protection
- XSS protection
- CSRF protection

### Environment Security

- Separate development and production configurations
- Secure default passwords (change in production)
- Network isolation between services
- Volume encryption support

## 📚 Additional Resources

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [MariaDB Documentation](https://mariadb.org/documentation/)
- [Redis Documentation](https://redis.io/documentation)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

## 🤝 Support

For issues and questions:

1. Check the logs: `make logs`
2. Verify container status: `make status`
3. Check the troubleshooting section above
4. Create an issue in the repository

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.
