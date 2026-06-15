FROM php:8.3-apache

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js for the Vite frontend build
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get update && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy entire application
COPY . /app

# Install backend dependencies
WORKDIR /app/backend
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Build frontend
WORKDIR /app/frontend
RUN npm ci && VITE_BASE_PATH=/app/ npm run build && cp -r dist ../backend/public/app

# Configure Apache
WORKDIR /app/backend
RUN a2enmod rewrite
RUN a2enmod headers

# Set permissions
RUN chown -R www-data:www-data /app

# Copy Apache configuration
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /app/backend/public\n\
    <Directory /app/backend/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

# Run migrations, cache config/routes/views, then start Apache (production server)
CMD ["sh", "-c", "cd /app/backend && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && apache2-foreground"]
