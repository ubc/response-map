FROM php:8.2-apache

ENV DB_HOST localhost
ENV DB_PORT 3306
ENV DB_NAME response_map
ENV DB_USERNAME rmap_user
ENV DB_PASSWORD rmap_pass
ENV ADMIN_PASSWORD randompass
ENV OAUTH_CONSUMER {"key": "secret"}

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libmcrypt-dev \
        libmagickwand-dev libmagickcore-dev \
    && docker-php-ext-install -j$(nproc) mysqli opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && pecl install imagick \
    && docker-php-ext-enable imagick

COPY . /var/www/html/
