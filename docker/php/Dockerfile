FROM php:8.3-fpm

ARG user=developer
ARG uid=1000

# Outils système de base
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    gnupg

# Installation de Node.js 20 LTS & NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installateur officiel d'extensions PHP
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    bcmath \
    pcntl \
    opcache \
    intl \
    zip

# Installation de Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur non-root
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

WORKDIR /var/www/html

USER $user
