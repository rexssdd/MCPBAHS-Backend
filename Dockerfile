FROM php:8.4-apache

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/views \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data storage bootstrap/cache

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

RUN find bootstrap/cache -name "*.php" -delete

RUN rm -rf public/storage

RUN a2dismod mpm_event \
 && a2enmod mpm_prefork \
 && a2enmod rewrite headers

RUN printf '%s\n' \
'<VirtualHost *:__PORT__>' \
'    ServerName localhost' \
'    DocumentRoot /app/public' \
'    <Directory /app/public>' \
'        AllowOverride All' \
'        Require all granted' \
'    </Directory>' \
'</VirtualHost>' \
> /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/Listen 80/Listen __PORT__/' /etc/apache2/ports.conf

EXPOSE 80

CMD ["sh","/app/start.sh"]