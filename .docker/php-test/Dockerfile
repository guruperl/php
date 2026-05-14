FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libonig-dev default-mysql-client \
    && docker-php-ext-install mbstring pdo_mysql \
    && echo 'pdo_mysql.default_socket=/var/run/mysqld/mysqld.sock' > /usr/local/etc/php/conf.d/pdo_mysql_socket.ini \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
