.DEFAULT_GOAL := help
COMPOSE_PROD := docker compose -f docker-compose.prod.yml

# ── Help ───────────────────────────────────────────────────────────────────────
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

# ── Setup ──────────────────────────────────────────────────────────────────────
setup: ## First-time setup: copy env files
	@[ -f .env ] || (cp .env.example .env && echo "Created .env — fill in DB credentials")
	@[ -f backend/.env ] || (cp backend/.env.example backend/.env && echo "Created backend/.env — fill in APP_KEY etc.")
	@[ -f frontend/.env ] || (cp frontend/.env.example frontend/.env && echo "Created frontend/.env")
	@echo "Setup done. Edit the .env files, then run: make dev"

# ── Local development ──────────────────────────────────────────────────────────
dev: ## Start local dev environment (API :8000, frontend :5173)
	docker compose up -d
	@echo ""
	@echo "  API:      http://localhost:8000"
	@echo "  Frontend: http://localhost:5173"
	@echo ""
	@echo "Run 'make dev-logs' to tail logs, 'make dev-down' to stop."

dev-down: ## Stop local dev environment
	docker compose down

dev-logs: ## Tail local dev logs
	docker compose logs -f

dev-fresh: ## Wipe DB and re-migrate + seed (local only)
	docker compose exec api php artisan migrate:fresh --seed

migrate: ## Run migrations (local)
	docker compose exec api php artisan migrate

shell: ## Open bash shell in the API container (local)
	docker compose exec api sh

shell-db: ## Open MySQL shell (local)
	docker compose exec db mysql -u$${DB_USERNAME:-app} -p$${DB_PASSWORD:-secret} $${DB_DATABASE:-myfitnesspal}

# ── Production ─────────────────────────────────────────────────────────────────
prod-setup: ## First-time production setup: copy and edit env files
	@[ -f .env ] || (cp .env.example .env && echo "Created .env — edit DB passwords before continuing")
	@[ -f backend/.env ] || (cp backend/.env.production.example backend/.env && echo "Created backend/.env — fill in APP_KEY and passwords")
	@echo ""
	@echo "Next steps:"
	@echo "  1. Edit .env and backend/.env with your real values"
	@echo "  2. make prod-build"
	@echo "  3. make prod-up"
	@echo "  4. make prod-migrate  (first deploy only)"

prod-build: ## Build production Docker images
	$(COMPOSE_PROD) build --no-cache

prod-up: ## Start production environment
	$(COMPOSE_PROD) up -d
	@echo "Running at http://your-server-ip"

prod-down: ## Stop production environment
	$(COMPOSE_PROD) down

prod-restart: ## Restart production containers (after config change)
	$(COMPOSE_PROD) restart

prod-deploy: ## Pull latest code and redeploy (zero-downtime rebuild)
	git pull
	$(COMPOSE_PROD) build api web
	$(COMPOSE_PROD) up -d --no-deps api web
	$(COMPOSE_PROD) exec api php artisan migrate --force

prod-migrate: ## Run migrations on production
	$(COMPOSE_PROD) exec api php artisan migrate --force

prod-seed-usda: ## Populate food database from USDA (production)
	$(COMPOSE_PROD) exec api php artisan db:seed --class=UsdaFoodSeeder

prod-logs: ## Tail production logs
	$(COMPOSE_PROD) logs -f

prod-shell: ## Open shell in production API container
	$(COMPOSE_PROD) exec api sh

prod-artisan: ## Run an artisan command on production (usage: make prod-artisan CMD="cache:clear")
	$(COMPOSE_PROD) exec api php artisan $(CMD)

.PHONY: help setup dev dev-down dev-logs dev-fresh migrate shell shell-db \
        prod-setup prod-build prod-up prod-down prod-restart prod-deploy \
        prod-migrate prod-seed-usda prod-logs prod-shell prod-artisan
