FROM php:8.4 AS builder
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y libcurl4-openssl-dev unzip
RUN docker-php-ext-install curl

COPY . /app
WORKDIR /app

RUN composer install --no-dev

FROM php:8.4
COPY --from=builder /app /app
WORKDIR /app
RUN apt-get update && apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-install curl
COPY config_env.php config.php

ENV XMLTV_PATH=/xmltv
ENTRYPOINT ["php", "grab.php"]
