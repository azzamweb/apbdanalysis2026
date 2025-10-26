# Changelog

All notable changes to APBD Analysis 2026 will be documented in this file.

## [2026.1.0] - 2025-10-26

### Added
- **Docker Configuration**: Complete Docker setup with PHP-FPM + Nginx
- **Large File Upload Support**: Support for uploading Excel files up to 500MB
- **Production Ready**: Optimized configuration for production deployment
- **Security Enhancements**: SSL/TLS, security headers, rate limiting
- **Performance Optimizations**: OPcache, Redis caching, database optimization
- **Monitoring & Logging**: Health checks, comprehensive logging
- **Backup System**: Automated backup and restore functionality
- **Documentation**: Complete documentation for development and production

### Changed
- **Web Server**: Migrated from FrankenPHP to PHP-FPM + Nginx for better stability
- **Upload Limits**: Increased from 100MB to 500MB for large Excel files
- **Memory Limits**: Increased PHP memory limit to 1024MB
- **Timeout Settings**: Extended timeouts for large file processing
- **Database Configuration**: Optimized MariaDB settings for production

### Technical Improvements
- **PHP Configuration**: Optimized for both development and production
- **Nginx Configuration**: Enhanced with security headers and performance tuning
- **Redis Configuration**: Optimized for caching and session management
- **Docker Compose**: Separate configurations for development and production
- **Makefile**: Convenient commands for all operations
- **Scripts**: Automated setup, deployment, and backup scripts

### Fixed
- **413 Request Entity Too Large**: Resolved upload issues for large files
- **Container Stability**: Improved container startup and health checks
- **Database Migrations**: Fixed migration order issues
- **Route Conflicts**: Resolved duplicate route names
- **Configuration Consistency**: Ensured consistency between dev and prod

### Security
- **SSL/TLS**: Full SSL support with Let's Encrypt compatibility
- **Security Headers**: XSS, CSRF, and other security protections
- **Rate Limiting**: API and login rate limiting
- **Password Protection**: Redis and database password protection
- **Input Validation**: Enhanced input validation and sanitization

### Performance
- **Caching**: Redis-based caching and session management
- **Compression**: Gzip compression for static files
- **Database Optimization**: Query cache and connection pooling
- **Static File Caching**: Long-term caching for static assets
- **Memory Management**: Optimized memory usage and garbage collection

## [2025.1.0] - 2025-02-08 (Original Version)

### Added
- **Laravel Application**: Basic Laravel 11 application structure
- **Data Anggaran Management**: CRUD operations for budget data
- **Excel Upload**: Basic Excel file upload functionality
- **Database Schema**: Initial database structure for APBD data
- **Basic UI**: Simple web interface for data management

### Technical Stack
- **Laravel 11**: PHP framework
- **MariaDB**: Database
- **Bootstrap**: Frontend framework
- **Excel Processing**: Maatwebsite Excel package

---

## Migration Notes

### From Original Version to 2026.1.0

If you're migrating from the original version:

1. **Backup your data**:
   ```bash
   # Export database
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Update environment**:
   ```bash
   # Copy new environment template
   cp production.env.example .env
   # Update with your settings
   ```

3. **Deploy new version**:
   ```bash
   # Setup new environment
   make setup-prod
   # Deploy
   make prod
   ```

4. **Import data**:
   ```bash
   # Import your backup
   mysql -u username -p database_name < backup.sql
   ```

### Breaking Changes

- **Web Server**: Changed from FrankenPHP to PHP-FPM + Nginx
- **Configuration**: New Docker-based configuration
- **Upload Limits**: Increased file size limits
- **Environment**: New environment variable structure

### Compatibility

- **PHP**: Requires PHP 8.2+
- **Docker**: Requires Docker and Docker Compose
- **Database**: MariaDB 10.11+ or MySQL 8.0+
- **Redis**: Redis 7+ for caching and sessions

---

## Support

For support and questions:
- Check the documentation in `README.md` and `PRODUCTION.md`
- Review the upload configuration in `UPLOAD_CONFIG.md`
- Check GitHub issues for known problems
- Contact the development team

## License

This project is licensed under the MIT License.
