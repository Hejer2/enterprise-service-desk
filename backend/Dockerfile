# Stage 1: Vendor Dependencies
FROM php:8.3-fpm-alpine AS vendor
WORKDIR /app

RUN apk add --no-cache unzip git icu-dev libzip-dev
RUN docker-php-ext-install pdo_mysql intl zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Stage 2: Production Runtime
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache icu-dev libzip-dev
RUN docker-php-ext-install pdo_mysql intl zip opcache

COPY --from=vendor /app /var/www/html

# Production OPCache Config
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
 && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
 && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
