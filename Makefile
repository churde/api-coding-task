.PHONY: all

# CONFIG ---------------------------------------------------------------------------------------------------------------
ifneq (,$(findstring xterm,${TERM}))
    BLACK   := $(shell tput -Txterm setaf 0)
    RED     := $(shell tput -Txterm setaf 1)
    GREEN   := $(shell tput -Txterm setaf 2)
    YELLOW  := $(shell tput -Txterm setaf 3)
    BLUE    := $(shell tput -Txterm setaf 4)
    MAGENTA := $(shell tput -Txterm setaf 5)
    CYAN    := $(shell tput -Txterm setaf 6)
    WHITE   := $(shell tput -Txterm setaf 7)
    RESET   := $(shell tput -Txterm sgr0)
else
    BLACK   := ""
    RED     := ""
    GREEN   := ""
    YELLOW  := ""
    BLUE    := ""
    MAGENTA := ""
    CYAN    := ""
    WHITE   := ""
    RESET   := ""
endif

COMMAND_COLOR := $(GREEN)
HELP_COLOR := $(BLUE)

IMAGE_NAME=graphicresources/itpg-api-coding-task
IMAGE_TAG_BASE=base
IMAGE_TAG_DEV=development

# DEFAULT COMMANDS -----------------------------------------------------------------------------------------------------
all: help

help: ## Listar comandos disponibles en este Makefile
	@echo "╔═════════════════════════════════════════════════════════════════════════╗"
	@echo "║                           ${CYAN}.:${RESET} AVAILABLE COMMANDS ${CYAN}:.${RESET}                           ║"
	@echo "╚══════════════════════════════════════════════════════════════════════════════╝"
	@echo ""
	@grep -E '^[a-zA-Z_0-9%-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "${COMMAND_COLOR}%-40s${RESET} ${HELP_COLOR}%s${RESET}\n", $$1, $$2}'
	@echo ""


# BUILD COMMANDS -------------------------------------------------------------------------------------------------------
build: build-container composer-install ## Construye las dependencias del proyecto

build-container: ## Construye el contenedor de la aplicación
	docker build --no-cache --target development -t $(IMAGE_NAME):$(IMAGE_TAG_DEV) .

composer-install: ## Instala las dependencias via composer
	docker run --rm -v ${PWD}/app:/app -w /app $(IMAGE_NAME):$(IMAGE_TAG_DEV) composer install --verbose

composer-update: ## Actualiza las dependencias via composer
	docker run --rm -v ${PWD}/app:/app -w /app $(IMAGE_NAME):$(IMAGE_TAG_DEV) composer update --verbose

composer-require: ## Añade nuevas dependencias de producción
	docker run --rm -ti -v ${PWD}/app:/app -w /app $(IMAGE_NAME):$(IMAGE_TAG_DEV) composer require --verbose

composer-require-dev: ## Añade nuevas dependencias de desarrollo
	docker run --rm -ti -v ${PWD}/app:/app -w /app $(IMAGE_NAME):$(IMAGE_TAG_DEV) composer require --dev --verbose

# DATABASE COMMANDS --------------------------------------------------------------------------------------------------
wait-for-db:
	@echo "Waiting for database to be ready..."
	@timeout=90; \
	while ! docker-compose exec -T db mysqladmin ping -h localhost -u root -proot --silent; do \
		timeout=$$((timeout - 1)); \
		if [ $$timeout -le 0 ]; then \
			echo "Timed out waiting for database to be ready"; \
			exit 1; \
		fi; \
		sleep 1; \
	done
	@echo "Database is ready."
	@sleep 5  # Add an extra delay to ensure MySQL is fully operational

init-db: wait-for-db ## Initialize the database with schema
	@echo "Initializing database..."
	@docker-compose exec -T db mysql -uroot -proot < app/opt/db/init.sql || \
		(echo "First attempt failed, retrying in 5 seconds..." && sleep 5 && \
		docker-compose exec -T db mysql -uroot -proot < app/opt/db/init.sql)
	@echo "Database initialization completed."

populate-db: ensure-app-running ## Populate the database with fake data
	docker-compose exec -T php php /var/www/opt/db/populate_data.php --factions=43 --equipments=89 --characters=5000

# TEST COMMANDS ------------------------------------------------------------------------------------------------------
run-tests: ## Run all tests
	docker-compose exec -T php vendor/bin/phpunit
	@$(MAKE) generate-tokens
	@$(MAKE) print-swagger-link

generate-tokens: ## Generate JWT tokens for each role
	@echo "Generating JWT tokens for each role..."
	@echo "Admin Token (User ID: 1, Role ID: 1):"
	@docker-compose exec -T php php /var/www/opt/generate_token.php 1 1
	@echo "\nEditor Token (User ID: 2, Role ID: 2):"
	@docker-compose exec -T php php /var/www/opt/generate_token.php 2 2
	@echo "\nViewer Token (User ID: 3, Role ID: 3):"
	@docker-compose exec -T php php /var/www/opt/generate_token.php 3 3

# ALL-IN-ONE COMMAND -------------------------------------------------------------------------------------------------
setup-and-run-tests: build up wait-for-db init-db setup-auth-tables populate-db run-tests ## Set up everything, run tests, and print Swagger link

# DOCKER COMMANDS ----------------------------------------------------------------------------------------------------
up: ## Start the Docker containers
	docker-compose up -d

down: ## Stop the Docker containers
	docker-compose down

ensure-app-running:
	@echo "Ensuring php service is running..."
	@if [ -z "$$(docker-compose ps -q php)" ] || [ -z "$$(docker ps -q --no-trunc | grep $$(docker-compose ps -q php))" ]; then \
		echo "PHP service is not running. Starting it now..."; \
		docker-compose up -d php; \
		echo "Waiting for PHP service to be ready..."; \
		sleep 10; \
	else \
		echo "PHP service is already running."; \
	fi
	@$(MAKE) print-swagger-link

# NEW COMMAND TO PRINT SWAGGER LINK AND TOKENS ----------------------------------------------------------------------------------
print-swagger-link: ## Print the Swagger UI link and authentication tokens
	@echo "╔══════════════════════════════════════════════════════════════════════════════╗"
	@echo "║                       ${CYAN}.:${RESET} SWAGGER UI AND AUTH TOKENS ${CYAN}:.${RESET}                        ║"
	@echo "╚══════════════════════════════════════════════════════════════════════════════╝"
	@echo "${GREEN}Swagger UI is available at: ${BLUE}http://localhost:8080/swagger.php${RESET}"
	@echo ""
	@echo "Authentication Tokens:"
	@echo "---------------------"
	@echo "Admin Token (User ID: 1, Role ID: 1):"
	@docker-compose exec -T php php /var/www/opt/generate_token.php 1 1
	@echo "\nEditor Token (User ID: 2, Role ID: 2):"
	@docker-compose exec -T php php /var/www/opt/generate_token.php 2 2
	@echo "\nViewer Token (User ID: 3, Role ID: 3):"
	@docker-compose exec -T php php /var/www/opt/generate_token.php 3 3
	@echo ""
	@echo "To use these tokens in Swagger UI:"
	@echo "1. Copy a token for the desired role (Admin, Editor, or Viewer)"
	@echo "2. Click on the 'Authorize' button in Swagger UI"
	@echo "3. In the 'Value' field, enter: Bearer <your_token>"
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════════════════╗"
	@echo "║                       ${CYAN}.:${RESET} SWAGGER UI AND AUTH TOKENS ${CYAN}:.${RESET}                        ║"
	@echo "╚══════════════════════════════════════════════════════════════════════════════╝"

# DATABASE COMMANDS --------------------------------------------------------------------------------------------------
setup-auth-tables: wait-for-db ## Set up authentication tables
	@echo "Setting up authentication tables..."
	@docker-compose exec -T db mysql -uroot -proot lotr < app/opt/db/setup_auth_tables.sql || \
		(echo "First attempt failed, retrying in 5 seconds..." && sleep 5 && \
		docker-compose exec -T db mysql -uroot -proot lotr < app/opt/db/setup_auth_tables.sql)
	@echo "Authentication tables setup completed."
