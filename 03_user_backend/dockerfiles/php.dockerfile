FROM php:8.2-fpm

# Viteの開発環境用ポート
EXPOSE 5173

WORKDIR /var/www/html
COPY src .
#COPY src/ .

RUN apt-get update
RUN apt-get -y install libzip-dev nodejs npm
RUN docker-php-ext-install pdo pdo_mysql zip
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

