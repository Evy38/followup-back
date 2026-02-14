#!/bin/bash
# ===============================================
# 🚀 Script d'entrypoint Docker - Production
# ===============================================
# Ce script s'exécute au démarrage du container

set +e  # Continuer même si une commande échoue (ne pas bloquer le démarrage)

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
# 4️⃣ ⚠️ Migrations optionnelles
# -----------------------------------------------
echo "💡 [Database] Les migrations seront exécutées via Render post-deploy hook"
echo "💡 [Instructions] Pour migrer manuellement : php bin/console doctrine:migrations:migrate"

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