FROM php:8.2-fpm
RUN apt-get update && apt-get install -y libpq-dev zip unzip git
RUN docker-php-ext-install pdo pdo_pgsql opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -u 1000 -m www-data || true

USER www-data
WORKDIR /var/www

CMD ["php-fpm"]
