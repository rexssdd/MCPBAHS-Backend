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
# pcntl: lets queue:work catch SIGTERM so Railway can gracefully shut down workers
# opcache: bytecode cache — significant throughput improvement in production
RUN docker-php-ext-install pdo pdo_pgsql pcntl \
    && docker-php-ext-enable opcache

# Tune OPcache for a read-only, containerised filesystem
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Suppress "don't run composer as root" warning (Railway runs as root)
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy application source (excludes files listed in .dockerignore)
COPY . /app

# Install PHP dependencies (no dev, optimised autoloader)
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Nuke ALL bootstrap caches — they were built with local env vars and
# must be regenerated at container startup against live Railway env vars
RUN rm -f /app/bootstrap/cache/config.php \
           /app/bootstrap/cache/routes-v7.php \
           /app/bootstrap/cache/services.php \
           /app/bootstrap/cache/events.php \
           /app/bootstrap/cache/packages.php

# Remove public/storage if it exists as a real directory so that
# `php artisan storage:link` can create the symlink at runtime.
# (storage:link refuses to overwrite an existing non-symlink path.)
RUN rm -rf /app/public/storage

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache virtual host with a literal placeholder port.
# Railway only assigns the real $PORT at container start (not build time),
# so start.sh replaces __PORT__ with the live value via sed before Apache
# launches. (Relying on Apache's own ${ENV} config interpolation here would
# be version-fragile, so we do the substitution explicitly ourselves.)
RUN echo '<VirtualHost *:__PORT__>\n\
    DocumentRoot /app/public\n\
    <Directory /app/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/Listen 80/Listen __PORT__/' /etc/apache2/ports.conf

# Create storage directories and set permissions
RUN mkdir -p /app/storage/app/private/reports \
             /app/storage/app/public \
             /app/storage/framework/cache/data \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs \
             /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Documentation only — Railway ignores EXPOSE and routes to whatever $PORT
# the container actually listens on (set at runtime by start.sh).
EXPOSE 80

CMD ["sh", "/app/start.sh"]
