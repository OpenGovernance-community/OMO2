FROM php:8.2-apache

RUN apt-get update \
    && if apt-cache show libcurl4-openssl-dev >/dev/null 2>&1; then \
        curl_dev_pkg='libcurl4-openssl-dev'; \
    else \
        curl_dev_pkg='libcurl4t64-openssl-dev'; \
    fi \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        "$curl_dev_pkg" \
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
    && mkdir -p /etc/apache2/ssl \
    && openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/dev-localhost.key \
        -out /etc/apache2/ssl/dev-localhost.crt \
        -subj "/C=CH/ST=Zurich/L=Zurich/O=SystemDD/OU=Dev/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,DNS:*.localhost,DNS:omo.test,DNS:*.omo.test" \
        -addext "basicConstraints=critical,CA:FALSE" \
        -addext "keyUsage=critical,digitalSignature,keyEncipherment" \
        -addext "extendedKeyUsage=serverAuth" \
    && a2enmod headers rewrite expires ssl socache_shmcb \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

WORKDIR /var/www/html
