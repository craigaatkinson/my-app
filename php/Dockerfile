FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive
ENV PATH="$HOME/.composer/vendor/bin:$PATH"

# Update and install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    wget \
    locales \
    libzip-dev \
    libxml2-dev \
    libsqlite3-dev \
    sqlite3 \
    libonig-dev \
    libcurl4-openssl-dev \
    libtinfo6 \
    libuv1 \
    && apt-get clean

# Install PHP extensions using docker-php-ext-install
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring xml curl zip pcntl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Install Phpactor (PHP LSP)
RUN composer global config minimum-stability dev \
    && composer global config prefer-stable true \
    && composer global config audit.block-insecure false \
    && composer global require phpactor/phpactor:2023.12.03.0

# Install Neovim from Debian unstable (minimal addition)

RUN echo "deb http://deb.debian.org/debian unstable main" > /etc/apt/sources.list.d/unstable.list \
    && apt-get update \
    && apt-get install -y -t unstable neovim \
    && apt-get clean

WORKDIR /var/www/html

CMD ["php-fpm"]
