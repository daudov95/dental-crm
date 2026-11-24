FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip intl bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000

RUN groupmod -o -g ${GID} www-data \
    && usermod -o -u ${UID} -g ${GID} www-data \
    && mkdir -p /var/www/.composer /var/www/.cache \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www/html
