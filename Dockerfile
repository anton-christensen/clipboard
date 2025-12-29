FROM node:24-alpine AS app-build

WORKDIR /usr/local/app
COPY ./app /usr/local/app

RUN npm ci --quiet
RUN npm run build
RUN mv /usr/local/app/dist/index.html /usr/local/app/dist/app.html

FROM php:8.3-apache

RUN a2enmod rewrite
RUN mkdir /var/www/data
RUN chown www-data:www-data /var/www/data

COPY src /var/www/html

COPY --from=app-build /usr/local/app/dist /var/www/html
