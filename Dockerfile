FROM php:8.4-apache

WORKDIR /app

# Install system packages
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy project
COPY . .

# Laravel directories
RUN mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Install dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Remove cached config
RUN rm -f bootstrap/cache/*.php

# Remove storage symlink
RUN rm -rf public/storage

# ONLY enable rewrite and headers
RUN a2enmod rewrite headers

# Apache VirtualHost
RUN cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:__PORT__>
    ServerName localhost

    DocumentRoot /app/public

    <Directory /app/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Dynamic Railway port
RUN sed -i 's/Listen 80/Listen __PORT__/g' /etc/apache2/ports.conf

EXPOSE 80

CMD ["sh","/app/start.sh"]