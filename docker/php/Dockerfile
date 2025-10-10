FROM php:8.2-apache

# Installer dépendances système + extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Copier la config Apache (VirtualHost)
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf

# Définir le dossier de travail
WORKDIR /var/www/html

# Installer Composer (depuis image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
