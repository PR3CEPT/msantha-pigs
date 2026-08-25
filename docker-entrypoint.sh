#!/bin/bash
set -e

# If no external DB_HOST is configured, start embedded MariaDB server inside container
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "127.0.0.1" ] || [ "$DB_HOST" = "localhost" ]; then
    echo "=========================================================="
    echo "Starting embedded MariaDB database service..."
    echo "=========================================================="
    service mariadb start || service mysql start || true

    # Wait 3 seconds for MariaDB socket to open
    sleep 3

    echo "Configuring MariaDB user permissions for web application..."
    mysql -u root -e "
        ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
        CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    " || mysql -u root -e "
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION;
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY '' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    " || true

    echo "Initializing msantha_pigs database..."
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS msantha_pigs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true

    if [ -f /var/www/html/deploy_database.sql ]; then
        echo "Importing deploy_database.sql into msantha_pigs..."
        mysql -u root msantha_pigs < /var/www/html/deploy_database.sql || true
    fi
fi

echo "=========================================================="
echo "Starting Apache Web Server..."
echo "=========================================================="
exec apache2-foreground
