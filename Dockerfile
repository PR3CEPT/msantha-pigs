FROM php:8.2-apache

# Install MariaDB server, MariaDB client, and PHP PDO extensions
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Copy entrypoint startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
