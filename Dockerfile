FROM php:8.4-apache

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pcntl \
    && docker-php-ext-enable opcache

# Configure OPcache
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=8"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.revalidate_freq=0"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.fast_shutdown=1"; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy application
COPY . .

# Create Laravel directories BEFORE composer install
RUN mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/app/private/reports \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && touch bootstrap/cache/.gitignore \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

# Remove cached bootstrap files
RUN rm -f \
    bootstrap/cache/config.php \
    bootstrap/cache/packages.php \
    bootstrap/cache/services.php \
    bootstrap/cache/events.php \
    bootstrap/cache/routes-*.php

# Remove existing storage symlink/folder
RUN rm -rf public/storage

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache
RUN printf '%s\n' \
'<VirtualHost *:__PORT__>' \
'    DocumentRoot /app/public' \
'    <Directory /app/public>' \
'        AllowOverride All' \
'        Require all granted' \
'    </Directory>' \
'</VirtualHost>' \
> /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/Listen 80/Listen __PORT__/' /etc/apache2/ports.conf

EXPOSE 80

CMD ["sh", "/app/start.sh"]