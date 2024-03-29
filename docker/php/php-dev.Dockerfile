FROM php:7.4-fpm-alpine

RUN apk add --no-cache \
    autoconf \
    curl \
    dpkg-dev \
    dpkg \
    freetype-dev \
    file \
    g++ \
    gcc \
    git \
    icu-dev \
    jpeg-dev \
    libc-dev \
    libmcrypt-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libxml2-dev \
    libzip-dev \
    make \
    mariadb-dev \
    postgresql-dev \
    pkgconf \
    php7-dev \
    re2c \
    rsync \
    unzip \
    wget \
    zlib-dev

RUN docker-php-ext-install \
    zip \
    iconv \
    soap \
    sockets \
    intl \
    pdo_mysql \
    pdo_pgsql \
    exif \
    pcntl

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug \
    && echo xdebug.client_port=9001 >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo xdebug.client_host=host.docker.internal >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo xdebug.mode=debug >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo xdebug.start_with_request=yes >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --chown=www-data:www-data . /var/www/app
RUN chmod -R 775 /var/www/app
COPY --chown=www-data:www-data ./docker/php/php.ini /usr/local/etc/php/php.ini
