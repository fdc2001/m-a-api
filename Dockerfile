# Use a imagem base do PHP 7.4
FROM php:7.4-fpm

# Instalar dependências do sistema, incluindo o Git
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    curl \
    && docker-php-ext-install pdo_mysql

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www
