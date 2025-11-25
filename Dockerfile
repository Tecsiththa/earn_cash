FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
  && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
