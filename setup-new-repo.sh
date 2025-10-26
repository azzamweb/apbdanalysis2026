#!/bin/bash

# APBD Analysis 2026 - Repository Setup Script
# Script untuk memudahkan setup repository baru di GitHub

set -e

echo "🚀 APBD Analysis 2026 - Repository Setup"
echo "========================================"

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

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -f "README.md" ]; then
    print_error "Please run this script from the project root directory"
    exit 1
fi

# Get GitHub username
print_status "Enter your GitHub username:"
read -r GITHUB_USERNAME

if [ -z "$GITHUB_USERNAME" ]; then
    print_error "GitHub username is required"
    exit 1
fi

# Repository name
REPO_NAME="apbdanalysis2026"
REPO_URL="https://github.com/$GITHUB_USERNAME/$REPO_NAME.git"

print_status "Repository will be created at: $REPO_URL"

# Check if remote already exists
if git remote get-url apbd2026 >/dev/null 2>&1; then
    print_warning "Remote 'apbd2026' already exists"
    print_status "Removing existing remote..."
    git remote remove apbd2026
fi

# Add new remote
print_status "Adding remote repository..."
git remote add apbd2026 "$REPO_URL"

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
print_status "Current branch: $CURRENT_BRANCH"

# Push to new repository
print_status "Pushing to new repository..."
git push -u apbd2026 "$CURRENT_BRANCH"

# Set main branch
print_status "Setting main branch..."
git push apbd2026 "$CURRENT_BRANCH:main"

print_success "Repository setup completed!"
print_status ""
print_status "Next steps:"
print_status "1. Go to https://github.com/$GITHUB_USERNAME/$REPO_NAME"
print_status "2. Set 'apbd-analysis-2026' as default branch in repository settings"
print_status "3. Add repository description and topics"
print_status "4. Configure branch protection rules"
print_status ""
print_status "Repository URL: $REPO_URL"
print_status "Clone URL: git clone $REPO_URL"
print_status ""
print_success "🎉 APBD Analysis 2026 repository is ready!"

# Show repository information
echo ""
echo "📋 Repository Information:"
echo "=========================="
echo "Name: $REPO_NAME"
echo "URL: $REPO_URL"
echo "Description: APBD Analysis 2026 - Aplikasi Pengolah Data APBD dengan konfigurasi Docker yang dioptimalkan"
echo "Topics: laravel, apbd, anggaran, data-analysis, docker, php, mysql, redis, nginx, php-fpm"
echo ""
echo "📚 Documentation:"
echo "================="
echo "- README.md: Dokumentasi utama"
echo "- PRODUCTION.md: Panduan deployment production"
echo "- UPLOAD_CONFIG.md: Konfigurasi upload file besar"
echo "- DOCKER.md: Dokumentasi Docker"
echo "- CHANGELOG.md: Riwayat perubahan"
echo "- CONTRIBUTING.md: Panduan kontribusi"
echo "- SECURITY.md: Kebijakan keamanan"
echo "- LICENSE: Lisensi MIT"
echo ""
echo "🚀 Quick Start:"
echo "==============="
echo "git clone $REPO_URL"
echo "cd $REPO_NAME"
echo "make setup"
echo "make dev"
echo ""
echo "🔧 Development Commands:"
echo "========================"
echo "make dev              # Start development environment"
echo "make prod             # Deploy to production"
echo "make logs             # View logs"
echo "make backup           # Create backup"
echo "make test             # Run tests"
echo ""
echo "📖 For more information, check the documentation files."
