# 1) Compila il front-end Vue
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# 2) Dipendenze PHP
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# 3) Immagine finale: PHP + Apache
FROM php:8.3-apache
# L'immagine ufficiale include già pdo_sqlite, mbstring, dom e OPcache
RUN a2enmod rewrite headers && echo 'ServerName localhost' > /etc/apache2/conf-enabled/servername.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod +x docker/start.sh

EXPOSE 80
CMD ["docker/start.sh"]
