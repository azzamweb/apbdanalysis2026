# HSAN Analysis Docker Makefile
# This file provides convenient commands for Docker operations

.PHONY: help setup dev prod stop clean logs shell db-shell redis-shell backup restore

# Default target
help: ## Show this help message
	@echo "HSAN Analysis Docker Commands"
	@echo "=============================="
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

setup: ## Initial setup for the project
	@echo "🚀 Setting up HSAN Analysis..."
	@chmod +x docker/scripts/setup.sh
	@./docker/scripts/setup.sh

setup-prod: ## Setup production environment
	@echo "🚀 Setting up HSAN Analysis Production..."
	@chmod +x docker/scripts/setup-prod.sh
	@./docker/scripts/setup-prod.sh

dev: ## Start development environment
	@echo "🚀 Starting development environment..."
	@chmod +x docker/scripts/dev.sh
	@./docker/scripts/dev.sh

prod: ## Deploy to production
	@echo "🚀 Deploying to production..."
	@chmod +x docker/scripts/deploy.sh
	@./docker/scripts/deploy.sh

stop: ## Stop all containers
	@echo "🛑 Stopping all containers..."
	@docker-compose -f docker-compose.yml down
	@docker-compose -f docker-compose.dev.yml down
	@docker-compose -f docker-compose.prod.yml down

clean: ## Clean up containers and volumes
	@echo "🧹 Cleaning up containers and volumes..."
	@docker-compose -f docker-compose.yml down -v --remove-orphans
	@docker-compose -f docker-compose.dev.yml down -v --remove-orphans
	@docker-compose -f docker-compose.prod.yml down -v --remove-orphans
	@docker system prune -f

logs: ## View application logs
	@echo "📝 Viewing application logs..."
	@docker-compose -f docker-compose.dev.yml logs -f app

logs-prod: ## View production logs
	@echo "📝 Viewing production logs..."
	@docker-compose -f docker-compose.prod.yml logs -f app

shell: ## Access application container shell
	@echo "🔧 Accessing application shell..."
	@docker-compose -f docker-compose.dev.yml exec app bash

shell-prod: ## Access production application shell
	@echo "🔧 Accessing production application shell..."
	@docker-compose -f docker-compose.prod.yml exec app bash

db-shell: ## Access database shell
	@echo "🗄️ Accessing database shell..."
	@docker-compose -f docker-compose.dev.yml exec mariadb mysql -u root -p

redis-shell: ## Access Redis shell
	@echo "📊 Accessing Redis shell..."
	@docker-compose -f docker-compose.dev.yml exec redis redis-cli

backup: ## Create backup
	@echo "🗄️ Creating backup..."
	@chmod +x docker/scripts/backup.sh
	@./docker/scripts/backup.sh

restore: ## Restore from backup (requires BACKUP_FILE variable)
	@echo "🔄 Restoring from backup..."
	@if [ -z "$(BACKUP_FILE)" ]; then echo "Please specify BACKUP_FILE=filename"; exit 1; fi
	@docker-compose -f docker-compose.dev.yml exec mariadb mysql -u root -p < backups/$(BACKUP_FILE)

migrate: ## Run database migrations
	@echo "🗄️ Running database migrations..."
	@docker-compose -f docker-compose.dev.yml exec app php artisan migrate

migrate-prod: ## Run production database migrations
	@echo "🗄️ Running production database migrations..."
	@docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

seed: ## Run database seeders
	@echo "🌱 Running database seeders..."
	@docker-compose -f docker-compose.dev.yml exec app php artisan db:seed

seed-prod: ## Run production database seeders
	@echo "🌱 Running production database seeders..."
	@docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force

test: ## Run tests
	@echo "🧪 Running tests..."
	@docker-compose -f docker-compose.dev.yml exec app php artisan test

cache-clear: ## Clear application cache
	@echo "🧹 Clearing application cache..."
	@docker-compose -f docker-compose.dev.yml exec app php artisan cache:clear
	@docker-compose -f docker-compose.dev.yml exec app php artisan config:clear
	@docker-compose -f docker-compose.dev.yml exec app php artisan route:clear
	@docker-compose -f docker-compose.dev.yml exec app php artisan view:clear

cache-clear-prod: ## Clear production application cache
	@echo "🧹 Clearing production application cache..."
	@docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
	@docker-compose -f docker-compose.prod.yml exec app php artisan config:clear
	@docker-compose -f docker-compose.prod.yml exec app php artisan route:clear
	@docker-compose -f docker-compose.prod.yml exec app php artisan view:clear

optimize: ## Optimize application for production
	@echo "⚡ Optimizing application..."
	@docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
	@docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
	@docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
	@docker-compose -f docker-compose.prod.yml exec app php artisan event:cache

status: ## Show container status
	@echo "📊 Container status:"
	@docker-compose -f docker-compose.dev.yml ps

status-prod: ## Show production container status
	@echo "📊 Production container status:"
	@docker-compose -f docker-compose.prod.yml ps
