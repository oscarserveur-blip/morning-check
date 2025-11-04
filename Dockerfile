# Étape 0 : build des assets front avec Vite
FROM node:20-alpine AS assets

WORKDIR /var/www

# Copier uniquement ce qui est nécessaire pour le build front
COPY package*.json ./
COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm ci && npm run build

# Étape 1 : base PHP avec Composer
FROM php:8.2-cli

# Installer les extensions nécessaires à Laravel + zip + FreeType pour TTF
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libonig-dev libxml2-dev libzip-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer le dossier de travail
WORKDIR /var/www

# Copier les fichiers de ton projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Copier les assets buildés depuis l'étape Node
COPY --from=assets /var/www/public/build /var/www/public/build

# Donner les bons droits à Laravel
RUN mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Exposer le port PHP
EXPOSE 8000

# Commande par défaut (serveur Laravel)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
