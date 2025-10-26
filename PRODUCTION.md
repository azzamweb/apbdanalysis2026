# HSAN Analysis - Production Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying HSAN Analysis to production using Docker.

## Prerequisites

- Docker and Docker Compose installed
- SSL certificates (or use self-signed for testing)
- Domain name configured (optional)
- Server with at least 2GB RAM and 20GB storage

## Quick Start

### 1. Setup Production Environment

```bash
# Clone the repository
git clone https://github.com/azzamweb/hsananalysis.git
cd hsananalysis

# Setup production environment
make setup-prod
```

### 2. Configure Environment

Edit the `.env` file with your production settings:

```bash
# Copy production template
cp production.env.example .env

# Edit with your settings
nano .env
```

Required environment variables:
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `REDIS_PASSWORD`: Redis password
- `APP_URL`: Your application URL

### 3. Deploy to Production

```bash
# Deploy to production
make prod
```

### 4. Access Your Application

- **Application**: https://localhost (or your domain)
- **phpMyAdmin**: http://localhost:8080
- **Database**: localhost:3306
- **Redis**: localhost:6379

## Detailed Configuration

### Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_ENV` | Application environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `APP_URL` | Application URL | `https://yourdomain.com` |
| `DB_DATABASE` | Database name | `hsananalysis_prod` |
| `DB_USERNAME` | Database username | `hsananalysis_user` |
| `DB_PASSWORD` | Database password | `secure_password` |
| `REDIS_PASSWORD` | Redis password | `redis_secure_password` |

### SSL Certificates

#### Using Self-Signed Certificates (Testing)

The setup script automatically generates self-signed certificates for testing.

#### Using Let's Encrypt (Production)

1. Install Certbot:
```bash
sudo apt-get install certbot
```

2. Generate certificates:
```bash
sudo certbot certonly --standalone -d yourdomain.com
```

3. Copy certificates to the project:
```bash
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem docker/nginx/ssl/key.pem
```

### Database Configuration

The production setup uses MariaDB with optimized settings:

- **Buffer Pool Size**: 256MB
- **Query Cache**: 32MB
- **Max Connections**: 200
- **Character Set**: utf8mb4

### Redis Configuration

Redis is configured for production with:

- **Memory Limit**: 256MB
- **Persistence**: RDB + AOF
- **Password Protection**: Enabled
- **Max Clients**: 10,000

### Nginx Configuration

Nginx is configured with:

- **Gzip Compression**: Enabled
- **Security Headers**: Configured
- **Rate Limiting**: Enabled
- **SSL/TLS**: TLS 1.2+ only
- **Static File Caching**: 1 year

## Production Commands

### Basic Operations

```bash
# Start production environment
make prod

# Stop all containers
make stop

# View production logs
make logs-prod

# Check container status
make status-prod
```

### Database Operations

```bash
# Run migrations
make migrate-prod

# Run seeders
make seed-prod

# Access database shell
make db-shell
```

### Application Operations

```bash
# Access application shell
make shell-prod

# Clear application cache
make cache-clear-prod

# Optimize application
make optimize
```

### Backup Operations

```bash
# Create backup
make backup

# Restore from backup
make restore BACKUP_FILE=filename.tar.gz
```

## Monitoring and Maintenance

### Health Checks

The application includes health checks for all services:

- **Application**: HTTP endpoint `/health`
- **Database**: MariaDB ping
- **Redis**: Redis ping

### Logs

Logs are available in the following locations:

- **Application**: `docker-compose -f docker-compose.prod.yml logs app`
- **Database**: `docker-compose -f docker-compose.prod.yml logs mariadb`
- **Redis**: `docker-compose -f docker-compose.prod.yml logs redis`
- **Nginx**: `docker-compose -f docker-compose.prod.yml logs nginx`

### Performance Monitoring

Monitor the following metrics:

- **CPU Usage**: `docker stats`
- **Memory Usage**: `docker stats`
- **Disk Usage**: `df -h`
- **Database Performance**: MariaDB slow query log

### Backup Strategy

1. **Daily Backups**: Automated via cron job
2. **Weekly Cleanup**: Remove backups older than 7 days
3. **Offsite Storage**: Copy backups to remote location

Example cron job:
```bash
# Add to crontab
0 2 * * * cd /path/to/hsananalysis && make backup
```

## Security Considerations

### Network Security

- Use firewall to restrict access
- Enable fail2ban for brute force protection
- Use strong passwords for all services

### Application Security

- Keep dependencies updated
- Regular security audits
- Monitor logs for suspicious activity

### Database Security

- Use strong database passwords
- Limit database user privileges
- Enable SSL for database connections

## Troubleshooting

### Common Issues

#### Container Won't Start

1. Check logs: `make logs-prod`
2. Verify environment variables
3. Check disk space: `df -h`
4. Check memory: `free -h`

#### Database Connection Issues

1. Verify database credentials in `.env`
2. Check if MariaDB container is running
3. Test connection: `make db-shell`

#### SSL Certificate Issues

1. Verify certificate files exist
2. Check certificate validity: `openssl x509 -in docker/nginx/ssl/cert.pem -text -noout`
3. Regenerate certificates if needed

#### Performance Issues

1. Check resource usage: `docker stats`
2. Review slow query log
3. Optimize database queries
4. Increase container resources

### Getting Help

1. Check application logs
2. Review Docker logs
3. Verify configuration files
4. Test individual services

## Scaling

### Horizontal Scaling

To scale the application:

1. Use load balancer (HAProxy, Nginx)
2. Deploy multiple application instances
3. Use shared Redis for sessions
4. Use shared database

### Vertical Scaling

To increase performance:

1. Increase container memory limits
2. Optimize database configuration
3. Use SSD storage
4. Increase CPU cores

## Updates and Maintenance

### Application Updates

1. Pull latest changes: `git pull`
2. Rebuild containers: `make prod`
3. Run migrations: `make migrate-prod`
4. Clear cache: `make cache-clear-prod`

### System Updates

1. Update Docker: `sudo apt update && sudo apt upgrade docker.io`
2. Update system packages: `sudo apt update && sudo apt upgrade`
3. Reboot if necessary: `sudo reboot`

## Support

For support and questions:

1. Check this documentation
2. Review application logs
3. Check GitHub issues
4. Contact the development team
