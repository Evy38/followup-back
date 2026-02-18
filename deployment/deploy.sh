#!/bin/bash
set -e

ENVIRONMENT=$1
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

if [ -z "$ENVIRONMENT" ]; then
  echo "Usage: ./deploy.sh [sit|uat|prod]"
  exit 1
fi

case $ENVIRONMENT in
  sit|uat|prod) ;;
  *)
    echo "Invalid environment. Use: sit, uat, prod"
    exit 1
    ;;
esac

echo "🚀 Deploy FollowUp Backend → $ENVIRONMENT"
echo "📅 $TIMESTAMP"

echo "Step 1: Pre-checks"
php -v
composer -V

echo "Step 2: Install dependencies (prod)"
composer install --no-interaction --prefer-dist --no-dev

echo "Step 3: Symfony cache warmup"
echo "Suppression du dossier var/cache/prod (sécurité)"
rm -rf var/cache/prod
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup

echo "💾 Step 4: Backup (simulation)"
# pg_dump au lieu de mysqldump
echo "(simulation) pg_dump -U follow_user -h localhost followup_prod > backup_${TIMESTAMP}.sql"

echo "🗂 Vérification de la route google/callback en prod"
APP_ENV=prod php bin/console debug:router --env=prod | grep google/callback || echo "Route google/callback absente en prod"
echo "🚀 Step 5: Deploy (simulation)"
case $ENVIRONMENT in
  sit)
    echo "(simulation) rsync -av . user@sit-server:/var/www/followup-back/"
    ;;
  uat)
    echo "(simulation) rsync -av . user@uat-server:/var/www/followup-back/"
    ;;
  prod)
    echo "⚠️ PRODUCTION deploy"
    read -p "Type 'yes' to confirm: " confirmation
    if [ "$confirmation" != "yes" ]; then
      echo "Deployment cancelled"
      exit 1
    fi
    echo "(simulation) rsync -av . user@prod-server:/var/www/followup-back/"
    echo "(simulation) ssh user@prod-server 'sudo systemctl reload apache2'"
    ;;
esac

echo "🧪 Step 6: Post-deploy checks (simulation)"
echo "(simulation) curl https://api-$ENVIRONMENT.followup.com/health"

echo "🎉 Deployment finished (simulation)"