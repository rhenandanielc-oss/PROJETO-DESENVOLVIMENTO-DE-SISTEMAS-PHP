# Imagem do sistema: PHP 8.3 + Apache com a extensão PDO MySQL
FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql && a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
COPY . /var/www/html/
