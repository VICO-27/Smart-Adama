FROM php:8.3-apache

# 1. Environment configuration
ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# 2. Install required OS packages and development libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    poppler-utils \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        exif \
        pcntl \
        zip \
        opcache \
        gd \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 3. Install Composer 2 from official binary
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# 4. Copy Apache and PHP configurations
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/smart_adama.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy dependency manifests first for Docker layer caching
COPY composer.json composer.lock ./

# 7. Install production dependencies without running artisan scripts
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# 8. Copy application code
COPY . .

# 9. Dump optimized autoloader for production
RUN composer dump-autoload --optimize --no-dev

# 10. Configure writable runtime directories
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 11. Port and health check
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-80}/up || exit 1

# 12. Production entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
