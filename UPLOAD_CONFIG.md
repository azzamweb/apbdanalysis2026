# Upload File Besar - Konfigurasi

## Overview

Dokumentasi ini menjelaskan konfigurasi yang telah diatur untuk mengatasi error **413 Request Entity Too Large** saat upload file Excel yang besar.

## Konfigurasi yang Telah Diupdate

### 1. PHP Configuration

#### Development (`docker/php/php-dev.ini`)
```ini
memory_limit = 1024M
max_execution_time = 0
max_input_time = -1
post_max_size = 500M
upload_max_filesize = 500M
max_file_uploads = 100
```

#### Production (`docker/php/php-prod.ini`)
```ini
memory_limit = 1024M
max_execution_time = 600
max_input_time = 600
post_max_size = 500M
upload_max_filesize = 500M
max_file_uploads = 100
```

### 2. Nginx Configuration

#### Development (`docker/nginx/nginx-dev.conf`)
```nginx
# Client settings for large file uploads
client_max_body_size 500M;
client_body_timeout 300s;
client_header_timeout 60s;
client_body_buffer_size 128k;
client_body_temp_path /tmp/nginx_upload;

# FastCGI settings for large uploads
fastcgi_connect_timeout 300s;
fastcgi_send_timeout 300s;
fastcgi_read_timeout 300s;
fastcgi_buffer_size 128k;
fastcgi_buffers 4 256k;
fastcgi_busy_buffers_size 256k;
```

#### Production (`docker/nginx/nginx-prod.conf`)
```nginx
# Client settings for large file uploads
client_max_body_size 500M;
client_body_timeout 300s;
client_header_timeout 60s;
client_body_buffer_size 128k;
client_body_temp_path /tmp/nginx_upload;

# FastCGI settings for large uploads
fastcgi_connect_timeout 300s;
fastcgi_send_timeout 300s;
fastcgi_read_timeout 300s;
fastcgi_buffer_size 128k;
fastcgi_buffers 4 256k;
fastcgi_busy_buffers_size 256k;
```

#### Production Reverse Proxy (`docker/nginx/nginx-proxy.conf`)
```nginx
# Buffer Settings for large uploads
client_body_buffer_size 128k;
client_max_body_size 500m;
client_header_buffer_size 1k;
large_client_header_buffers 4 4k;
output_buffers 1 32k;
postpone_output 1460;
client_body_timeout 300s;
client_header_timeout 60s;

# Timeouts for large uploads
proxy_connect_timeout 300s;
proxy_send_timeout 300s;
proxy_read_timeout 300s;
proxy_request_buffering off;
```

### 3. Docker Configuration

#### Docker Compose
```yaml
volumes:
  - nginx_upload_temp:/tmp/nginx_upload

volumes:
  nginx_upload_temp:
    driver: local
```

#### Dockerfile
```dockerfile
# Create necessary directories
RUN mkdir -p /var/log/supervisor /run/nginx /tmp/nginx_upload
```

## Limit yang Dikonfigurasi

| Komponen | Limit | Keterangan |
|----------|-------|------------|
| **PHP upload_max_filesize** | 500MB | Maksimal ukuran file yang bisa diupload |
| **PHP post_max_size** | 500MB | Maksimal ukuran POST data |
| **PHP memory_limit** | 1024MB | Memory limit untuk PHP |
| **PHP max_execution_time** | 0/600s | Timeout eksekusi (0=unlimited untuk dev) |
| **Nginx client_max_body_size** | 500MB | Maksimal ukuran body request |
| **Nginx client_body_timeout** | 300s | Timeout untuk upload |
| **Nginx fastcgi_read_timeout** | 300s | Timeout FastCGI |

## Testing

### Test Upload File Besar

```bash
# Buat file test 10MB
dd if=/dev/zero of=test_large_file.xlsx bs=1M count=10

# Test upload
curl -X POST -F "file=@test_large_file.xlsx" http://localhost:8000/data-anggaran/upload -v

# Cleanup
rm test_large_file.xlsx
```

### Verifikasi Konfigurasi

```bash
# Cek konfigurasi PHP
docker-compose -f docker-compose.dev.yml exec app php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time|memory_limit)"

# Cek konfigurasi Nginx
docker-compose -f docker-compose.dev.yml exec app nginx -T | grep -E "(client_max_body_size|client_body_timeout)"
```

## Troubleshooting

### Error 413 Request Entity Too Large

Jika masih mendapat error 413, periksa:

1. **Nginx Configuration**
   ```bash
   # Reload Nginx
   docker-compose -f docker-compose.dev.yml exec app nginx -s reload
   ```

2. **PHP Configuration**
   ```bash
   # Restart PHP-FPM
   docker-compose -f docker-compose.dev.yml restart app
   ```

3. **Docker Volumes**
   ```bash
   # Pastikan volume upload temp ada
   docker volume ls | grep nginx_upload_temp
   ```

### Error 504 Gateway Timeout

Jika mendapat error 504, periksa:

1. **Timeout Settings**
   - Pastikan `fastcgi_read_timeout` cukup besar
   - Pastikan `client_body_timeout` cukup besar

2. **Memory Settings**
   - Pastikan `memory_limit` cukup besar
   - Pastikan `max_execution_time` tidak terlalu kecil

### Error 500 Internal Server Error

Jika mendapat error 500, periksa:

1. **PHP Logs**
   ```bash
   docker-compose -f docker-compose.dev.yml logs app
   ```

2. **Nginx Logs**
   ```bash
   docker-compose -f docker-compose.dev.yml exec app tail -f /var/log/nginx/error.log
   ```

## Production Considerations

### Security

1. **File Type Validation**
   - Pastikan hanya file Excel yang bisa diupload
   - Validasi MIME type dan extension

2. **File Size Monitoring**
   - Monitor penggunaan disk space
   - Set up alert untuk upload yang terlalu besar

3. **Rate Limiting**
   - Implementasi rate limiting untuk upload
   - Prevent abuse dari upload file besar

### Performance

1. **Disk Space**
   - Pastikan ada cukup disk space untuk file upload
   - Monitor penggunaan storage

2. **Memory Usage**
   - Monitor memory usage saat upload file besar
   - Adjust `memory_limit` jika diperlukan

3. **Network**
   - Pastikan bandwidth cukup untuk upload file besar
   - Consider CDN untuk file static

## Monitoring

### Logs to Monitor

1. **Nginx Access Log**
   ```bash
   docker-compose -f docker-compose.dev.yml exec app tail -f /var/log/nginx/access.log
   ```

2. **Nginx Error Log**
   ```bash
   docker-compose -f docker-compose.dev.yml exec app tail -f /var/log/nginx/error.log
   ```

3. **PHP Error Log**
   ```bash
   docker-compose -f docker-compose.dev.yml exec app tail -f /var/log/php_errors.log
   ```

### Metrics to Track

- Upload success rate
- Average upload time
- File size distribution
- Error rate by file size
- Memory usage during upload

## Best Practices

1. **Progressive Upload**
   - Consider chunked upload untuk file sangat besar
   - Implementasi progress bar untuk user experience

2. **Background Processing**
   - Process file upload di background
   - Use queue untuk heavy processing

3. **File Management**
   - Implementasi file cleanup
   - Archive old files
   - Compress files jika memungkinkan

4. **User Experience**
   - Show upload progress
   - Provide clear error messages
   - Allow retry on failure

## Conclusion

Konfigurasi ini memungkinkan upload file Excel hingga 500MB dengan timeout yang cukup untuk processing. Pastikan untuk monitor performance dan adjust sesuai kebutuhan production.
