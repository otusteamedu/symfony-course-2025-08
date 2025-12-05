FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer \
    && docker-php-ext-install zip \
        pdo \
        pdo_mysql

WORKDIR /var/www/app