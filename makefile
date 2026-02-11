.PHONY: help

# Couleurs pour les messages
GREEN=\033[0;32m
YELLOW=\033[1;33m
RED=\033[0;31m
NC=\033[0m # No Color

##
## 🎯 MAKEFILE FOLLOWUP
## ==================
##

help: ## Affiche cette aide
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

##
## 🗄️  Base de données
## ------------------

db-create: ## Crée les bases de données (dev + test)
	@echo "$(GREEN)📦 Création des bases de données...$(NC)"
	docker compose exec php bin/console doctrine:database:create --if-not-exists
	docker compose exec php bin/console doctrine:database:create --if-not-exists --env=test
	@echo "$(GREEN)✅ Bases créées$(NC)"

db-drop: ## Supprime les bases de données (dev + test)
	@echo "$(RED)🗑️  Suppression des bases de données...$(NC)"
	docker compose exec php bin/console doctrine:database:drop --force --if-exists
	docker compose exec php bin/console doctrine:database:drop --force --if-exists --env=test
	@echo "$(GREEN)✅ Bases supprimées$(NC)"

db-reset: db-drop db-create ## Reset complet des bases (drop + create)
	@echo "$(GREEN)✅ Bases réinitialisées$(NC)"

db-validate: ## Valide la synchro entités/BDD (dev + test)
	@echo "$(YELLOW)🔍 Validation dev:$(NC)"
	docker compose exec php bin/console doctrine:schema:validate
	@echo ""
	@echo "$(YELLOW)🔍 Validation test:$(NC)"
	docker compose exec php bin/console doctrine:schema:validate --env=test

##
## 📝 Migrations
## ------------

migration-diff: ## Affiche les différences entités/BDD
	@echo "$(YELLOW)🔍 Différences détectées:$(NC)"
	docker compose exec php bin/console doctrine:schema:update --dump-sql

migration-create: ## Crée une nouvelle migration
	@echo "$(YELLOW)📝 Génération de la migration...$(NC)"
	docker compose exec php bin/console make:migration
	@echo "$(GREEN)✅ Migration créée - VÉRIFIE LE CONTENU avant de migrer !$(NC)"

migration-migrate: ## Applique les migrations (dev + test)
	@echo "$(YELLOW)🚀 Application des migrations...$(NC)"
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction --env=test
	@echo "$(GREEN)✅ Migrations appliquées$(NC)"

migration-status: ## Affiche le statut des migrations
	@echo "$(YELLOW)📊 Statut des migrations (dev):$(NC)"
	docker compose exec php bin/console doctrine:migrations:status
	@echo ""
	@echo "$(YELLOW)📊 Statut des migrations (test):$(NC)"
	docker compose exec php bin/console doctrine:migrations:status --env=test

##
## 🔄 Workflows complets
## --------------------

migration-workflow: migration-diff migration-create ## Workflow standard : diff → create (puis vérifier manuellement)
	@echo ""
	@echo "$(YELLOW)⚠️  IMPORTANT : Vérifie le contenu de la migration générée !$(NC)"
	@echo "$(YELLOW)Puis lance: make migration-migrate$(NC)"

migration-full: db-reset migration-create migration-migrate fixtures db-validate ## Reset complet + migration + fixtures
	@echo "$(GREEN)✅ Environnement complètement réinitialisé$(NC)"

##
## 🌱 Fixtures
## ----------

fixtures: ## Charge les fixtures (dev + test)
	@echo "$(YELLOW)🌱 Chargement des fixtures...$(NC)"
	docker compose exec php bin/console doctrine:fixtures:load --no-interaction
	docker compose exec php bin/console doctrine:fixtures:load --no-interaction --env=test
	@echo "$(GREEN)✅ Fixtures chargées$(NC)"

##
## 🧪 Tests
## -------

test: ## Lance les tests PHPUnit
	@echo "$(YELLOW)🧪 Exécution des tests...$(NC)"
	docker compose exec php ./vendor/bin/phpunit --testdox

test-coverage: ## Lance les tests avec couverture de code
	@echo "$(YELLOW)🧪 Tests avec couverture...$(NC)"
	docker compose exec php ./vendor/bin/phpunit --coverage-html var/coverage

##
## 🧹 Cache
## -------

cache-clear: ## Vide le cache Symfony
	@echo "$(YELLOW)🧹 Nettoyage du cache...$(NC)"
	docker compose exec php bin/console cache:clear
	docker compose exec php bin/console cache:clear --env=test
	@echo "$(GREEN)✅ Cache vidé$(NC)"

cache-warmup: cache-clear ## Vide et réchauffe le cache
	@echo "$(YELLOW)🔥 Réchauffage du cache...$(NC)"
	docker compose exec php bin/console cache:warmup
	docker compose exec php bin/console cache:warmup --env=test
	@echo "$(GREEN)✅ Cache réchauffé$(NC)"

##
## 🐳 Docker
## --------

up: ## Démarre les containers
	@echo "$(GREEN)🐳 Démarrage des containers...$(NC)"
	docker-compose up -d

down: ## Arrête les containers
	@echo "$(YELLOW)🐳 Arrêt des containers...$(NC)"
	docker-compose down

restart: down up ## Redémarre les containers
	@echo "$(GREEN)✅ Containers redémarrés$(NC)"

logs: ## Affiche les logs
	docker-compose logs -f

ps: ## Liste les containers actifs
	docker-compose ps

##
## 🔧 Qualité du code
## -----------------

cs-fix: ## Fix le code style (PHP CS Fixer)
	docker compose exec php ./vendor/bin/php-cs-fixer fix src/

phpstan: ## Analyse statique (PHPStan)
	docker compose exec php ./vendor/bin/phpstan analyse src tests

quality: cs-fix phpstan test ## Lance tous les checks qualité

##
## 🚀 Shortcuts pratiques
## ---------------------

install: up db-create migration-migrate fixtures ## Installation complète du projet
	@echo ""
	@echo "$(GREEN)╔════════════════════════════════════════╗$(NC)"
	@echo "$(GREEN)║  ✅ Installation terminée !            ║$(NC)"
	@echo "$(GREEN)║                                        ║$(NC)"
	@echo "$(GREEN)║  🌐 App:        http://localhost:8080  ║$(NC)"
	@echo "$(GREEN)║  🗄️  PhpMyAdmin: http://localhost:8081 ║$(NC)"
	@echo "$(GREEN)║  📧 MailHog:    http://localhost:8025  ║$(NC)"
	@echo "$(GREEN)╚════════════════════════════════════════╝$(NC)"

fresh: db-drop db-create migration-migrate fixtures ## Fresh install (sans down/up Docker)
	@echo "$(GREEN)✅ Base de données réinitialisée avec succès !$(NC)"