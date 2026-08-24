FROM php:8.5-apache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        openssl \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip \
    && mkdir -p /etc/apache2/ssl \
    && openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/dev-localhost.key \
        -out /etc/apache2/ssl/dev-localhost.crt \
        -subj "/C=CH/ST=Zurich/L=Zurich/O=SystemDD/OU=Dev/CN=localtest.me" \
        -addext "subjectAltName=DNS:localhost,DNS:*.localhost,DNS:localtest.me,DNS:*.localtest.me,DNS:omo.test,DNS:*.omo.test" \
        -addext "basicConstraints=critical,CA:FALSE" \
        -addext "keyUsage=critical,digitalSignature,keyEncipherment" \
        -addext "extendedKeyUsage=serverAuth" \
    && a2enmod headers rewrite expires ssl socache_shmcb proxy proxy_http proxy_wstunnel \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

WORKDIR /var/www/html
