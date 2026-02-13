#!/bin/bash
echo "🔴 ATTENTION : Reset complet de la base de données !"
read -p "Continuer ? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

echo "📦 Arrêt des containers..."
docker-compose down

echo "🗑️  Suppression du volume..."
docker volume rm followup-back_db_data 2>/dev/null || true

echo "🗂️  Nettoyage des migrations..."
# Sauvegarde avant suppression
mkdir -p .migrations-backup
cp -r migrations/* .migrations-backup/ 2>/dev/null || true
rm -rf migrations/*

echo "🚀 Redémarrage..."
docker-compose up -d
sleep 15

echo "✨ Création de la base..."
docker exec -it followup-php php bin/console doctrine:database:create
docker exec -it followup-php php bin/console doctrine:database:create --env=test

echo "📝 Génération de la migration initiale..."
docker exec -it followup-php php bin/console make:migration

echo "🚀 Application..."
docker exec -it followup-php php bin/console doctrine:migrations:migrate --no-interaction
docker exec -it followup-php php bin/console doctrine:migrations:migrate --env=test --no-interaction

echo "✅ Vérification..."
./bin/check-db.sh

echo ""
echo "✅ Reset terminé !"
echo "💾 Anciens migrations sauvegardées dans .migrations-backup/"