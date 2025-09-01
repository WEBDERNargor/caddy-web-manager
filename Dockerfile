# PHP Apache image running on port 8000
FROM php:8.2-apache

# Bring in Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libzip-dev \
       libsqlite3-0 libsqlite3-dev \
       libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
       git unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
       gd \
       zip \
       pdo \
       pdo_mysql \
       pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules and set Apache to listen on 8000
RUN a2enmod rewrite \
    && sed -ri 's/Listen 80/Listen 8000/' /etc/apache2/ports.conf \
    && sed -ri 's#<VirtualHost \*:80>#<VirtualHost *:8000>#' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html#' /etc/apache2/sites-available/000-default.conf

# Recommended: set AllowOverride for .htaccess rewrites
RUN sed -ri 's#<Directory /var/www/>#<Directory /var/www/>\n    AllowOverride All#' /etc/apache2/apache2.conf \
    && sed -ri 's#<Directory /var/www/html/>#<Directory /var/www/html/>\n    AllowOverride All#' /etc/apache2/apache2.conf

# App source (use bind mount in docker-compose; keeping path consistent)
WORKDIR /var/www/html

EXPOSE 8000

# Copy entrypoint
COPY docker/entrypoint.sh /docker/entrypoint.sh
RUN chmod +x /docker/entrypoint.sh

ENTRYPOINT ["/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
