FROM php:8.2-apache

# ============================================
# 1. SYSTEM DEPENDENCIES & PHP EXTENSIONS
# ============================================

# Update packages and install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for PostgreSQL and Laravel
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# ============================================
# 2. COMPOSER & APACHE CONFIG
# ============================================

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# ============================================
# 3. APPLICATION SETUP
# ============================================

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies (production only)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# ============================================
# 4. PERMISSIONS & STORAGE
# ============================================

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create storage directories
RUN mkdir -p storage/framework/{cache,sessions,views}

# ============================================
# 5. APACHE CONFIGURATION
# ============================================

# Copy Apache virtual host configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ============================================
# 6. DATABASE MIGRATIONS
# ============================================

# Try to run migrations during build (will work if DB credentials are available)
# If DB not ready during build, it will fail gracefully
RUN php artisan migrate --force --no-interaction 2>/dev/null || true

# ============================================
# 7. PRODUCTION OPTIMIZATION
# ============================================

# Cache configuration for better performance
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ============================================
# 8. FINAL CONFIGURATION
# ============================================

# Expose port 80 for Apache
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]