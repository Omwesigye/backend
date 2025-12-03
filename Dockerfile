FROM php:8.2-apache

# 1. Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions for PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Enable Apache modules
RUN a2enmod rewrite

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy application files
COPY . .

# 7. Install PHP dependencies
RUN composer install --no-dev --no-interaction --optimize-autoloader

# 8. Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# 9. Create storage directories
RUN mkdir -p storage/framework/{cache,sessions,views}

# 10. Copy Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# 11. Expose port
EXPOSE 80

# 12. Start Apache
CMD ["apache2-foreground"]