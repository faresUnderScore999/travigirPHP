# Use PHP 8.4 with Apache
FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Allow Composer to run as root for Render
ENV COMPOSER_ALLOW_SUPERUSER=1

# 1. Copy composer files first to cache dependencies
COPY composer.json composer.lock ./

# 2. Install dependencies (without running scripts yet)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 3. Copy the rest of your application code
COPY . .

# 4. THE FIX: Create a temporary .env so composer can "dump" it for production
RUN echo "APP_ENV=prod" > .env && composer dump-env prod

# 5. Ensure the var directory exists for Symfony cache and logs
RUN mkdir -p var/cache var/log

# 6. SET PERMISSIONS: Ensure www-data owns the files before warming up cache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/var

# 7. WARM UP CACHE: Pre-generate cache files as the web user
# This prevents the "Permission Denied" rename error at runtime
RUN su www-data -s /bin/bash -c "php bin/console cache:warmup --env=prod"

# 8. Enable Apache mod_rewrite for Symfony routing
RUN a2enmod rewrite

# 9. Set ServerName to avoid Apache warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 10. Configure Apache VirtualHost
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# 11. FINAL STEP: Run migrations then start Apache
# We use a shell string here so both commands run on startup
CMD php bin/console doctrine:migrations:migrate --no-interaction && apache2-foreground