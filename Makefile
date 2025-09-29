.PHONY: help build up down shell install test lint clean example

# Colors for output
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

help: ## Show this help message
	@echo "$(BLUE)Cake Calculator - Docker Commands$(NC)"
	@echo "================================="
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker containers
	@echo "$(YELLOW)Building Docker containers...$(NC)"
	docker compose build --no-cache

up: ## Start the development environment
	@echo "$(YELLOW)Starting development environment...$(NC)"
	docker compose up -d
	@echo "$(GREEN)Development environment is running!$(NC)"

down: ## Stop the development environment
	@echo "$(YELLOW)Stopping development environment...$(NC)"
	docker compose down
	@echo "$(GREEN)Environment stopped.$(NC)"

shell: ## Access the development container shell
	@echo "$(YELLOW)Accessing development container...$(NC)"
	docker compose exec cake-dev sh

shell-app: ## Access the app container shell
	@echo "$(YELLOW)Accessing app container...$(NC)"
	docker compose exec cake-dev sh

install: ## Install PHP dependencies
	@echo "$(YELLOW)Installing dependencies...$(NC)"
	docker compose exec cake-dev composer install
	@echo "$(GREEN)Dependencies installed!$(NC)"

update: ## Update PHP dependencies
	@echo "$(YELLOW)Updating dependencies...$(NC)"
	docker compose exec cake-dev composer update
	@echo "$(GREEN)Dependencies updated!$(NC)"

test: ## Run PHPUnit tests
	@echo "$(YELLOW)Running tests...$(NC)"
	docker compose exec cake-dev composer test
	@echo "$(GREEN)Tests completed!$(NC)"

test-coverage: ## Run tests with coverage report
	@echo "$(YELLOW)Running tests with coverage...$(NC)"
	docker compose exec cake-dev ./vendor/bin/phpunit --coverage-html coverage
	@echo "$(GREEN)Coverage report generated in ./coverage/$(NC)"

lint: ## Run code quality checks
	@echo "$(YELLOW)Running code quality checks...$(NC)"
	docker compose exec cake-dev composer psalm
	docker compose exec cake-dev composer phpstan
	@echo "$(GREEN)Code quality checks completed!$(NC)"

example: ## Run example calculation
	@echo "$(YELLOW)Running example calculation...$(NC)"
	docker compose exec cake-dev php bin/cake-calculator examples/employees.txt output/results.csv --year=2025 -v
	@echo "$(GREEN)Example completed! Check output/results.csv$(NC)"

interactive: ## Run calculator interactively
	@echo "$(YELLOW)Running calculator interactively...$(NC)"
	docker compose exec -it cake-dev php bin/cake-calculator examples/employees.txt output/interactive-results.csv --year=2025 -vv

clean: ## Clean up Docker containers and volumes
	@echo "$(YELLOW)Cleaning up Docker environment...$(NC)"
	docker compose down -v --rmi local --remove-orphans
	docker system prune -f
	@echo "$(GREEN)Cleanup completed!$(NC)"

logs: ## Show container logs
	docker compose logs -f

restart: down up ## Restart the development environment

setup: build up install ## Complete setup for new developers
	@echo "$(GREEN)"
	@echo "🎉 Setup completed successfully!"
	@echo ""
	@echo "Available commands:"
	@echo "  make example     - Run example calculation"
	@echo "  make test        - Run tests"
	@echo "  make shell       - Access development container"
	@echo "  make help        - Show all available commands"
	@echo "$(NC)"
