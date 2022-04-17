FROM php:7.4-apache as devenv
LABEL maintainer="Anton Christensen <anton.christensen9700@gmail.com>"

RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite && mkdir /var/www/data && chown www-data:www-data /var/www/data
ENV DEVMODE 1

FROM devenv
ENV DEVMODE 0
COPY src /var/www/html