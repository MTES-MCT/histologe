FROM php:8.3-fpm
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        wget \
        zlib1g-dev \
        libmagickwand-dev \
        libxml2-dev \
        libzip-dev \
        zip \
        unzip \
        libsqlite3-dev \
        sqlite3 \
        libxslt1-dev \
        && curl -fsSL https://deb.nodesource.com/setup_lts.x | bash \
        && apt-get install -y  nodejs \
         # Install intl
        && docker-php-ext-configure intl \
        && docker-php-ext-install -j2 intl \
        # Install apcu
        && pecl bundle -d /usr/src/php/ext apcu \
        && docker-php-ext-install -j2 apcu \
        # Install Xdebug
        && pecl bundle -d /usr/src/php/ext xdebug-3.4.2 \
        && docker-php-ext-install xdebug \
        # Install Redis
        && pecl install redis \
        && docker-php-ext-enable redis \
        # PHP GD
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        # Install others
        && docker-php-ext-install -j2 bcmath ctype iconv pdo pdo_mysql pdo_sqlite zip xsl gd sockets\
        && docker-php-ext-enable opcache \
        && rm /usr/src/php/ext/*.tgz \
        && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer  \
        && mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN sed -E -i -e 's/memory_limit = 128M/memory_limit = 512M/' "$PHP_INI_DIR/php.ini" \
    && sed -E -i -e 's/post_max_size = 8M/post_max_size = 75M/' "$PHP_INI_DIR/php.ini" \
    && sed -E -i -e 's/upload_max_filesize = 2M/upload_max_filesize = 75M/' "$PHP_INI_DIR/php.ini"

WORKDIR /app/