#!/bin/bash
# ===============================================
# 🚀 Script d'entrypoint Docker - Production (CORRIGÉ)
# ===============================================
# Ce script s'exécute au démarrage du container

set -e  # Arrêter si une commande échoue

echo "🚀 [FollowUp] Démarrage du container en production..."

# -----------------------------------------------
# 1️⃣ Configurer Apache AVANT tout (fix port)
# -----------------------------------------------
echo "🌐 [Apache] Configuration du port ${PORT:-80}..."
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf
echo "✅ [Apache] Port configuré sur ${PORT:-80}"

# -----------------------------------------------
# 2️⃣ Générer les clés JWT si elles n'existent pas
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
# 3️⃣ Attendre que la base de données soit prête
# -----------------------------------------------
echo "⏳ [DB] Attente de la base de données..."

# Attendre jusqu'à 60 secondes que la DB soit accessible
max_attempts=60
attempt=0

while [ $attempt -lt $max_attempts ]; do
    # Tester la connexion via une commande Doctrine minimaliste
    if php bin/console doctrine:migrations:status --no-interaction 2>/dev/null | grep -q "Database"; then
        echo "✅ [DB] Base de données accessible"
        break
    fi
    
    attempt=$((attempt + 1))
    remaining=$((max_attempts - attempt))
    
    if [ $attempt -ge $max_attempts ]; then
        echo "❌ [DB] Timeout: impossible de se connecter à la base de données après ${max_attempts}s"
        echo "⚠️ Démarrage d'Apache quand même (les migrations seront faites plus tard)"
        # Ne pas exit 1, laisser Apache démarrer
        break
    fi
    
    echo "⏳ [DB] En attente... ($remaining secondes restantes)"
    sleep 1
done

# -----------------------------------------------
# 4️⃣ Lancer les migrations Doctrine (si DB accessible)
# -----------------------------------------------
if php bin/console doctrine:migrations:status --no-interaction 2>/dev/null | grep -q "Database"; then
    echo "📦 [Migrations] Exécution des migrations..."
    
    # Créer la base si elle n'existe pas
    php bin/console doctrine:database:create --if-not-exists --no-interaction || true
    
    # Lancer les migrations
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
    
    echo "✅ [Migrations] Migrations exécutées"
else
    echo "⚠️ [Migrations] DB non accessible, migrations ignorées"
fi

# -----------------------------------------------
# 5️⃣ Optimiser le cache Symfony
# -----------------------------------------------
echo "🗑️ [Cache] Nettoyage et optimisation du cache..."

php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "✅ [Cache] Cache optimisé"

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