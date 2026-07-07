FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libpng-dev \
        libzip-dev \
        mariadb-client \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl pdo_mysql zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/entrypoint.sh /usr/local/bin/gestiio-entrypoint
COPY docker/php/conf.d/*.ini /usr/local/etc/php/conf.d/

RUN chmod +x /usr/local/bin/gestiio-entrypoint \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

EXPOSE 80

ENTRYPOINT ["gestiio-entrypoint"]
