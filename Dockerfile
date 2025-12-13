FROM php:8.3-apache as devenv

RUN a2enmod rewrite && mkdir /var/www/data && chown www-data:www-data /var/www/data

FROM devenv
COPY src /var/www/html
