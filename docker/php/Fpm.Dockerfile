FROM php:8.3-fpm

# Устанавливаем зависимости для MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    default-mysql-client \
    && docker-php-ext-install zip pdo pdo_mysql

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/app