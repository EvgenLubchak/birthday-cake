.PHONY: help build up down shell install test clean example

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

install: ## Install PHP dependencies
	@echo "$(YELLOW)Installing dependencies...$(NC)"
	docker compose exec cake-dev composer install
	@echo "$(GREEN)Dependencies installed!$(NC)"

update: ## Update PHP dependencies
	@echo "$(YELLOW)Updating dependencies...$(NC)"
	docker compose exec cake-dev composer update
	@echo "$(GREEN)Dependencies updated!$(NC)"

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
	@echo "  make generate_test_data - Generate example for calculation"
	@echo "  make process_test_data  - Run example calculation"
	@echo "  make test               - Run tests"
	@echo "  make shell              - Access development container"
	@echo "  make help               - Show all available commands"
	@echo "$(NC)"

test-unit: ## Run only unit tests
	@echo "$(YELLOW)Running unit tests...$(NC)"
	docker compose exec cake-dev ./vendor/bin/phpunit tests/Unit
	@echo "$(GREEN)Unit tests completed!$(NC)"

test-coverage: ## Run tests with coverage report
	@echo "$(YELLOW)Running tests with coverage...$(NC)"
	docker compose exec cake-dev ./vendor/bin/phpunit --coverage-html coverage
	@echo "$(GREEN)Coverage report generated in ./coverage/$(NC)"

generate_test_data:
	@echo "$(YELLOW)Start generating test data file...$(NC)"
	docker compose exec -T cake-dev php bin/generate-test-data examples/example.txt --count=900
	@echo "$(GREEN)Data file generated!$(NC)"

process_test_data:
	@echo "$(YELLOW)Start processing file...$(NC)"
	docker compose exec -T cake-dev php bin/cake-calculator examples/example.txt output/example-output.csv
	@echo "$(GREEN)Success!$(NC)"
