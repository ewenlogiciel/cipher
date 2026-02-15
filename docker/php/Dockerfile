FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    openssh-client \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    opcache \
    zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/backend

# Copy composer files first for better caching
COPY backend/composer.json backend/composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application
COPY backend/ .

# Generate autoloader
RUN composer dump-autoload --optimize

# Permissions
RUN mkdir -p var && chown -R www-data:www-data var/

EXPOSE 8000

CMD ["symfony", "server:start", "--no-tls", "--port=8000", "--allow-all-ip"]
