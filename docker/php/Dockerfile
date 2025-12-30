FROM php:7.2-cli

# Fix Debian Buster EOL repositories
RUN sed -i 's|deb.debian.org|archive.debian.org|g' /etc/apt/sources.list \
 && sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list \
 && sed -i '/buster-updates/d' /etc/apt/sources.list \
 && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid

# Install system deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libaio1 \
    wget \
 && docker-php-ext-install \
    intl \
    zip \
    xml \
    mbstring \
 && pecl install xdebug-2.9.8 \
 && docker-php-ext-enable xdebug

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --2.2 \
 && mv composer.phar /usr/local/bin/composer \
 && rm composer-setup.php

