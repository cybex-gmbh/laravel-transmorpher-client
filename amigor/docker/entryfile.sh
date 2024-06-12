#!/usr/bin/env bash

if ${PULLPREVIEW:-false}; then
    php /var/www/html/amigor/artisan migrate --force

    if ${PULLPREVIEW_FIRST_RUN:-false}; then
        php /var/www/html/amigor/artisan db:seed --class=PullpreviewSeeder --force
    fi
fi

exec /entrypoint supervisord "$@"
