FROM php:8.2-fpm

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Installer Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier le code source
COPY . .

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Exposer le port
EXPOSE 8000

# Commande pour démarrer Laravel
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
