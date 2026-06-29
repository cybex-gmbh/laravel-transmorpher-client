# syntax=docker/dockerfile:1.7

FROM cybexwebdev/php-laravel:8.4-alpine-devtools as build

WORKDIR /var/www

COPY --chown=www-data:www-data ./amigor /var/www
COPY --chown=www-data:www-data . /var/package

RUN composer install --no-interaction --no-dev --no-scripts

RUN chmod +x /var/www/docker/prod/pullpreview.initialize.sh

#########################

FROM cybexwebdev/php-laravel:8.4-alpine as app

WORKDIR /var/www
ENV WEB_DOCUMENT_ROOT /var/www/public

COPY --from=build /var/www /var/www
COPY --from=build /var/package /var/package
