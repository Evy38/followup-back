#!/bin/bash
# ===============================================
# 🚀 Script d'entrypoint Docker - Production
# ===============================================
# Ce script s'exécute au démarrage du container

set -e  # Arrêter si une commande échoue

echo "🚀 [FollowUp] Démarrage du container en production..."

# -----------------------------------------------
# 1️⃣ Générer les clés JWT si elles n'existent pas
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
# 2️⃣ Attendre que la base de données soit prête
# -----------------------------------------------
echo "⏳ [DB] Attente de la base de données..."

# Extraire l'host de DATABASE_URL
DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:]*\):.*/\1/p')

# Attendre que PostgreSQL soit accessible (max 30 secondes)
timeout=30
while ! nc -z $DB_HOST 5432 2>/dev/null; do
    timeout=$((timeout - 1))
    if [ $timeout -le 0 ]; then
        echo "❌ [DB] Timeout: impossible de se connecter à la base de données"
        exit 1
    fi
    echo "⏳ [DB] En attente... ($timeout secondes restantes)"
    sleep 1
done

echo "✅ [DB] Base de données accessible"

# -----------------------------------------------
# 3️⃣ Lancer les migrations Doctrine
# -----------------------------------------------
echo "📦 [Migrations] Exécution des migrations..."

# Créer la base si elle n'existe pas
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "✅ [Migrations] Migrations exécutées avec succès"

# -----------------------------------------------
# 4️⃣ Optimiser le cache Symfony
# -----------------------------------------------
echo "🗑️ [Cache] Nettoyage et optimisation du cache..."

php bin/console cache:clear --no-warmup
php bin/console cache:warmup

echo "✅ [Cache] Cache optimisé"

# -----------------------------------------------
# 5️⃣ Configurer Apache pour le port dynamique Render
# -----------------------------------------------
echo "🌐 [Apache] Configuration du port ${PORT:-80}..."

# Remplacer le port par défaut par la variable $PORT de Render
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf

echo "✅ [Apache] Port configuré sur ${PORT:-80}"

# -----------------------------------------------
# 6️⃣ Permissions finales
# -----------------------------------------------
echo "🔐 [Permissions] Configuration des permissions..."

chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

echo "✅ [Permissions] Permissions configurées"

# -----------------------------------------------
# 7️⃣ Démarrer Apache
# -----------------------------------------------
echo "🎉 [FollowUp] Application prête ! Démarrage d'Apache..."

# Exécuter la commande passée en argument (CMD du Dockerfile)
exec "$@"