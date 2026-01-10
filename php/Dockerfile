FROM php:8.3-cli

ENV DEBIAN_FRONTEND=noninteractive
ENV PATH="$HOME/.composer/vendor/bin:$PATH"

# Update and install only stable, verified packages
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    wget \
    locales \
    php-mbstring \
    php-xml \
    php-curl \
    php-zip \
    php-common \
    php-dev \
    libzip-dev \
    libxml2-dev \
    libsqlite3-dev \
    sqlite3 \
    libtinfo6 \
    libuv1 \
    && apt-get clean

# Install PHP extensions including SQLite
RUN docker-php-ext-install pdo pdo_sqlite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Install Phpactor (PHP LSP)
RUN composer global require phpactor/phpactor

# Install Neovim from Debian unstable (minimal addition)

RUN echo "deb http://deb.debian.org/debian unstable main" > /etc/apt/sources.list.d/unstable.list \
    && apt-get update \
    && apt-get install -y -t unstable neovim \
    && apt-get clean

WORKDIR /app

CMD ["/bin/bash"]
