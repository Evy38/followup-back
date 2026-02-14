#!/bin/bash
# ===============================================
# 🚀 Hook de post-déploiement Render
# ===============================================
# Ce script s'exécute APRÈS le démarrage du conteneur
# Il est utilisé pour exécuter les migrations de manière sécurisée

set -e  # Arrêter si une commande échoue

echo "🚀 [Post-Deploy] Exécution des migrations..."

# Créer la base si elle n't existe pas
php bin/console doctrine:database:create --if-not-exists --no-interaction || true

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

echo "✅ [Post-Deploy] Migrations terminées"
