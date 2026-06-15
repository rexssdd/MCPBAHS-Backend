FROM php:8.4-apache

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (pgsql only — MySQL not needed in production)
RUN docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Suppress "don't run composer as root" warning (Railway runs as root)
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy application source
COPY . /app

# Install PHP dependencies (no dev, optimised autoloader)
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache virtual host pointing to Laravel's public/ directory
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /app/public\n\
    <Directory /app/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Create storage directories and set permissions
RUN mkdir -p /app/storage/app/private/reports \
             /app/storage/app/public \
             /app/storage/framework/cache \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs \
             /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80

# start.sh: waits for DB, runs migrations, caches config/routes/views, starts Apache
CMD ["sh", "/app/start.sh"]
