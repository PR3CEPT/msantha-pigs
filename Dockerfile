FROM php:8.2-apache

# Install MariaDB server, MariaDB client, and PHP PDO extensions
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure Apache for clean URL redirects & AllowOverride
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "DirectoryIndex index.php dashboard.php login.php" >> /etc/apache2/apache2.conf \
    && echo "<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>" >> /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Copy entrypoint startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
