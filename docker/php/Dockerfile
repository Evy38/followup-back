FROM php:8.2-apache

# Installer dépendances système + extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache \
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

# Copier la config Apache (VirtualHost)
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf

# Définir le dossier de travail
WORKDIR /var/www/html

# Installer Composer (depuis image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
