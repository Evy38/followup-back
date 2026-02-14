FROM php:8.2-apache

# Installer dépendances système + extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Configuration PHP recommandée pour Symfony
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
} > /usr/local/etc/php/conf.d/symfony.ini

# Configuration Apache pour Symfony
RUN cat > /etc/apache2/sites-available/000-default.conf << 'APACHEEOF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Order Allow,Deny
        Allow from All
        Require all granted
    </Directory>
    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </IfModule>
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
</VirtualHost>
APACHEEOF

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier le projet
COPY . .

# Installer Composer (depuis image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer un .env temporaire pour le build
RUN echo "APP_ENV=prod" > .env.local && \
    echo "APP_DEBUG=0" >> .env.local && \
    echo "APP_SECRET=tmp-build-secret" >> .env.local && \
    echo "DATABASE_URL=postgresql://user:pass@localhost/db" >> .env.local && \
    echo "JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem" >> .env.local && \
    echo "JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem" >> .env.local

# Installer dépendances Symfony
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Nettoyer le .env temporaire
RUN rm -f .env.local

# Copier le script d'entrypoint
COPY docker/scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Configurer les permissions pour www-data
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Point d'entrée et commande par défaut
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
