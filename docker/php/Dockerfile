FROM php:8.2.1-zts
RUN apt-get update \
    && apt-get install -y nano vim wget git htop zlib1g-dev libpng-dev libzip-dev libpq-dev \
    && pecl install xdebug-3.2.1 parallel \
    && docker-php-ext-enable xdebug parallel \
    && printf "\n" | pecl install apcu-5.1.21 \
    && docker-php-ext-enable apcu

RUN docker-php-ext-install pdo pdo_mysql bcmath zip gd sockets pdo_pgsql pgsql opcache

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN curl --remote-name ./php/cacert.pem https://curl.se/ca/cacert.pem

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /var/www