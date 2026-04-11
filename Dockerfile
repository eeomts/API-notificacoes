FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html