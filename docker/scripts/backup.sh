#!/bin/bash

# HSAN Analysis Backup Script
# This script creates backups of the database and application files

set -e

echo "🗄️ Starting HSAN Analysis Backup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration
BACKUP_DIR="backups"
DATE=$(date +"%Y%m%d_%H%M%S")
DB_BACKUP_FILE="hsananalysis_db_${DATE}.sql"
FILES_BACKUP_FILE="hsananalysis_files_${DATE}.tar.gz"
FULL_BACKUP_FILE="hsananalysis_full_${DATE}.tar.gz"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

print_status "Creating backup directory: $BACKUP_DIR"

# Function to backup database
backup_database() {
    print_status "Backing up database..."
    
    # Check if we're in development or production
    if docker-compose -f docker-compose.dev.yml ps | grep -q "Up"; then
        COMPOSE_FILE="docker-compose.dev.yml"
        DB_SERVICE="mariadb"
    elif docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
        COMPOSE_FILE="docker-compose.prod.yml"
        DB_SERVICE="mariadb"
    else
        print_error "No running containers found!"
        exit 1
    fi
    
    # Get database credentials from environment
    DB_NAME=${DB_DATABASE:-hsananalysis_dev}
    DB_USER=${DB_USERNAME:-root}
    DB_PASS=${DB_PASSWORD:-password}
    
    # Create database backup
    docker-compose -f "$COMPOSE_FILE" exec -T "$DB_SERVICE" mysqldump \
        -u "$DB_USER" \
        -p"$DB_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        "$DB_NAME" > "$BACKUP_DIR/$DB_BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        print_success "Database backup created: $DB_BACKUP_FILE"
    else
        print_error "Database backup failed!"
        exit 1
    fi
}

# Function to backup application files
backup_files() {
    print_status "Backing up application files..."
    
    # Create tar archive of important directories
    tar -czf "$BACKUP_DIR/$FILES_BACKUP_FILE" \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='.git' \
        --exclude='storage/logs' \
        --exclude='storage/framework/cache' \
        --exclude='storage/framework/sessions' \
        --exclude='storage/framework/views' \
        --exclude='bootstrap/cache' \
        --exclude='backups' \
        .
    
    if [ $? -eq 0 ]; then
        print_success "Application files backup created: $FILES_BACKUP_FILE"
    else
        print_error "Application files backup failed!"
        exit 1
    fi
}

# Function to create full backup
create_full_backup() {
    print_status "Creating full backup archive..."
    
    cd "$BACKUP_DIR"
    tar -czf "$FULL_BACKUP_FILE" \
        "$DB_BACKUP_FILE" \
        "$FILES_BACKUP_FILE"
    cd ..
    
    if [ $? -eq 0 ]; then
        print_success "Full backup created: $FULL_BACKUP_FILE"
        
        # Remove individual files to save space
        rm "$BACKUP_DIR/$DB_BACKUP_FILE"
        rm "$BACKUP_DIR/$FILES_BACKUP_FILE"
        
        print_status "Individual backup files removed to save space"
    else
        print_error "Full backup creation failed!"
        exit 1
    fi
}

# Function to cleanup old backups
cleanup_old_backups() {
    print_status "Cleaning up old backups (keeping last 7 days)..."
    
    find "$BACKUP_DIR" -name "hsananalysis_*.tar.gz" -type f -mtime +7 -delete
    
    print_success "Old backups cleaned up"
}

# Function to show backup info
show_backup_info() {
    print_status "Backup Information:"
    echo "  - Backup Directory: $BACKUP_DIR"
    echo "  - Full Backup File: $FULL_BACKUP_FILE"
    echo "  - Backup Size: $(du -h "$BACKUP_DIR/$FULL_BACKUP_FILE" | cut -f1)"
    echo "  - Created: $(date)"
}

# Main execution
main() {
    # Check if backup directory is writable
    if [ ! -w "$BACKUP_DIR" ]; then
        print_error "Backup directory is not writable: $BACKUP_DIR"
        exit 1
    fi
    
    # Perform backups
    backup_database
    backup_files
    create_full_backup
    cleanup_old_backups
    show_backup_info
    
    print_success "🎉 Backup completed successfully!"
    print_status "Backup location: $BACKUP_DIR/$FULL_BACKUP_FILE"
}

# Run main function
main "$@"