#!/bin/bash
# ===============================================
# 🚀 Script d'entrypoint Docker - Production
# ===============================================
# Ce script s'exécute au démarrage du container

set -e  # ✅ Arrêter si une commande échoue 

echo "🚀 [FollowUp] Démarrage du container..."

# -----------------------------------------------
# 0️⃣ Créer le fichier .env s'il n'existe pas
# -----------------------------------------------
# Symfony s'attend à ce que le fichier .env existe
# même s'il est vide (les variables viendront de l'environnement du système)
if [ ! -f .env ]; then
    echo "📝 [ENV] Création du fichier .env..."
    touch .env
    echo "✅ [ENV] Fichier .env créé"
fi

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
# 3️⃣ Nettoyer le cache (non-bloquant)
# -----------------------------------------------
echo "🗑️ [Cache] Nettoyage du cache..."
php bin/console cache:clear --no-warmup 2>&1 | grep -v "PDOException" || true
echo "✅ [Cache] Cache nettoyé"

# -----------------------------------------------
# 4️⃣ Attendre que la base de données soit prête
# -----------------------------------------------
echo "🗄️ [Database] Vérification de la connexion..."

max_attempts=30
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
        echo "✅ [Database] Connexion établie !"
        break
    fi
    
    attempt=$((attempt + 1))
    echo "⏳ [Database] Tentative $attempt/$max_attempts - En attente..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ [Database] Impossible de se connecter à la base de données après $max_attempts tentatives"
    echo "⚠️  Le conteneur va démarrer mais les migrations n'ont pas été exécutées"
else
    # -----------------------------------------------
    # 5️⃣ Créer la base si elle n'existe pas
    # -----------------------------------------------
    echo "🗄️ [Database] Création de la base si nécessaire..."
    php bin/console doctrine:database:create --if-not-exists --no-interaction 2>&1 | grep -v "already exists" || true
    
    # -----------------------------------------------
    # 6️⃣ Exécuter les migrations
    # -----------------------------------------------
    echo "🗄️ [Database] Exécution des migrations..."
    
    if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
        echo "✅ [Database] Migrations terminées avec succès !"
        
        # Afficher le statut des migrations
        echo "📊 [Database] Statut des migrations :"
        php bin/console doctrine:migrations:status
    else
        echo "❌ [Database] Échec des migrations !"
        echo "⚠️  Le conteneur va démarrer mais la base peut être incomplète"
    fi
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
# 7️⃣ Démarrer Apache
# -----------------------------------------------
echo "🎉 [FollowUp] Application prête ! Démarrage d'Apache..."

# Exécuter la commande passée en argument (CMD du Dockerfile)
exec "$@"