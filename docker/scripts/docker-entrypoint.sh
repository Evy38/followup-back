#!/bin/bash
# ===============================================
# 🚀 Script d'entrypoint Docker - Production
# ===============================================
# Ce script s'exécute au démarrage du container

set -e  # ✅ Arrêter si une commande échoue 

echo "🚀 [FollowUp] Démarrage du container..."

# -----------------------------------------------
# 0️⃣ NETTOYER LE CACHE COMPLÈTEMENT AVANT TOUT (!!!!)
# -----------------------------------------------
echo "🗑️  [Cache] Suppression agressive du cache avant initialisation..."
rm -rf var/cache/* var/log/* 2>/dev/null || true
rm -rf /tmp/sf_* 2>/dev/null || true
echo "✅ [Cache] Cache supprimé"


# Régénérer le cache Symfony avec les vraies variables d'environnement
echo "🔄 [Cache] Régénération du cache Symfony..."
rm -rf var/cache/prod 2>/dev/null || true
php bin/console cache:clear --env=prod --no-warmup || true
php bin/console cache:warmup --env=prod || true
chown -R www-data:www-data var/cache
echo "✅ [Cache] Cache régénéré"

# -----------------------------------------------
# 2️⃣ Configurer Apache AVANT tout (fix port)
# -----------------------------------------------
echo "🌐 [Apache] Configuration du port ${PORT:-80}..."
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/g" /etc/apache2/sites-available/000-default.conf
echo "✅ [Apache] Port configuré sur ${PORT:-80}"

# -----------------------------------------------
# 3️⃣ Générer les clés JWT si elles n'existent pas
# -----------------------------------------------
if [ ! -f config/jwt/private.pem ]; then
    echo "🔐 [JWT] Génération des clés JWT..."
    
    mkdir -p config/jwt
    
    # Générer la clé privée
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
    
    # Générer la clé publique
    openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem
    
    # Permissions correctes
    chown www-data:www-data config/jwt/*.pem
    chmod 600 config/jwt/private.pem
    chmod 644 config/jwt/public.pem
    
    echo "✅ [JWT] Clés générées avec succès"
else
    echo "✅ [JWT] Clés JWT déjà présentes"
fi

# -----------------------------------------------
# 4️⃣ Créer répertoires de cache avec bonnes permissions
# -----------------------------------------------
mkdir -p var/cache var/log
chown -R www-data:www-data var/cache var/log
chmod -R 775 var/cache var/log
echo "✅ [Permissions] Répertoires cache préparés"

# -----------------------------------------------
# 5️⃣ Attendre la base de données (max 30 sec, non-bloquant après)
# -----------------------------------------------
echo "🗄️ [Database] Tentative de connexion..."

max_attempts=15
attempt=0
DB_CONNECTED=false

while [ $attempt -lt $max_attempts ]; do
    if php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
        echo "✅ [Database] Connexion établie !"
        DB_CONNECTED=true
        break
    fi
    
    attempt=$((attempt + 1))
    echo "⏳ [Database] Tentative $attempt/$max_attempts..."
    sleep 2
done

if [ "$DB_CONNECTED" = "true" ]; then
    # -----------------------------------------------
    # 6️⃣ Créer la base si elle n'existe pas
    # -----------------------------------------------
    echo "🚀 [Database] Création de la base si nécessaire..."
    php bin/console doctrine:database:create --if-not-exists --no-interaction 2>&1 | grep -v "already exists" || true
    
    # -----------------------------------------------
    # 7️⃣ Exécuter les migrations
    # -----------------------------------------------
    echo "📊 [Database] Exécution des migrations..."
    php bin/console doctrine:migrations:sync-metadata-storage --no-interaction
    if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1; then
        echo "✅ [Database] Migrations complétées avec succès"
    else
        MIGRATION_ERROR=$?
        echo "❌ [Database] ERREUR migrations (code: $MIGRATION_ERROR)" >&2
        echo "⚠️  [Database] Continuant malgré l'erreur..." >&2
    fi
    
    # -----------------------------------------------
    # 8️⃣ Charger les fixtures (données initiales) - DÉSACTIVÉ EN PROD
    # -----------------------------------------------
    if [ "$APP_ENV" != "prod" ] && [ -d "src/DataFixtures" ] && [ "$(ls -A src/DataFixtures)" ]; then
        echo "📦 [Database] Chargement des fixtures..."
        php bin/console doctrine:fixtures:load --no-interaction 2>&1 || true
        echo "✅ [Database] Fixtures chargées"
    fi
else
    echo "❌ [Database] ATTENTION - Base de données non accessible!" >&2
    echo "⚠️  L'application démarrera mais les endpoints nécessitant la DB échoueront" >&2
    echo "Vérifier que PostgreSQL tourne sur Render et que DATABASE_URL est correcte" >&2
fi

echo ""
echo "✅ [FollowUp] Conteneur prêt, démarrage d'Apache..."
echo ""
# -----------------------------------------------
echo "🔐 [Permissions] Configuration des permissions..."

chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

echo "✅ [Permissions] Permissions configurées"

# -----------------------------------------------
# 9️⃣ Démarrer Apache
# -----------------------------------------------
echo "🎉 [FollowUp] Application prête ! Démarrage d'Apache..."

# Forcer le bon MPM Apache au runtime (Railway restaure les fichiers de l'image de base)
echo "🔧 [Apache] Correction des modules MPM..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf
echo "✅ [Apache] MPM prefork seul actif"

# Exécuter la commande passée en argument (CMD du Dockerfile)
exec "$@"