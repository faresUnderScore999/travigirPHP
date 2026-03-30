# Use PHP 8.4 with Apache to match your local environment and composer requirements
FROM php:8.4-apache

# Install system dependencies for Symfony, PostgreSQL, and Intl
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

# Allow Composer to run as root/superuser for Render
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files first to leverage Docker layer caching
COPY composer.json composer.lock ./

# Install dependencies without running scripts (scripts require DB connection)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy the rest of the application code
COPY . .

# Ensure var directory exists for Symfony cache and logs
RUN mkdir -p var/cache var/log

# Set correct permissions for the Apache user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/var

# Enable Apache mod_rewrite for Symfony routing
RUN a2enmod rewrite

# Configure Apache VirtualHost to point to /public
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

# Start Apache
CMD ["apache2-foreground"]