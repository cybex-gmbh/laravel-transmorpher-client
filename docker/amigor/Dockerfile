# syntax=docker/dockerfile:1.7

FROM cybexwebdev/php-laravel:8.5-alpine-devtools as build

WORKDIR /var/www

COPY --chown=www-data:www-data ./amigor /var/www
COPY --chown=www-data:www-data .env.transmorpher /var/www/.env.transmorpher
COPY --chown=www-data:www-data ./docker/amigor/pullpreview.initialize.sh /var/www/docker/amigor/
COPY --chown=www-data:www-data . /var/package

RUN composer install --no-interaction --no-dev --no-scripts

RUN chmod +x /var/www/docker/amigor/pullpreview.initialize.sh

## App

FROM cybexwebdev/php-laravel:8.5-alpine as app

WORKDIR /var/www
ENV WEB_DOCUMENT_ROOT /var/www/public

COPY --from=build /var/www /var/www
COPY --from=build /var/package /var/package
