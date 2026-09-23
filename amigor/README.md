# Companion app for the Transmorpher

Basic Laravel 13 app with the following adjustments:

## composer.json

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../package"
        }
    ],
    ...
    "require": {
        ...
        "cybex/laravel-transmorpher-client": "@dev",
        ...
    }
    ...,
    "scripts": {
        "post-update-cmd": [
            ...,
            "@php artisan vendor:publish --tag=transmorpher.assets --ansi --force"
        ]
    },
    ...

```

## resources/views/welcome.blade.php

Replace `body` with:

```bladehtml
@foreach(App\Models\User::first()->images as $image)
<x-transmorpher::dropzone :media="$image" width="300px"></x-transmorpher::dropzone>
@endforeach

@foreach(App\Models\User::first()->documents as $document)
<x-transmorpher::dropzone :media="$document" width="300px"></x-transmorpher::dropzone>
@endforeach

@foreach(App\Models\User::first()->videos as $video)
<x-transmorpher::dropzone :media="$video" width="300px"></x-transmorpher::dropzone>
@endforeach
```

## database/seeders/PullpreviewSeeder.php

Seed with the following data:

```php
User::create(['name' => 'Transmorpher Amigor', 'email' => 'transmorpher.amigor@example.com', 'password' => 'password']);
```

## Laravel Transmorpher Client config

Publish the routes config file:

```bash
php artisan vendor:publish --tag=transmorpher.config.routes --ansi --force
```

Replace the middleware key with:

```php
'middleware' => [],
```

## app/Models/User.php

Add the `HasTransmorpherMedia` trait:

```php
use HasTransmorpherMedia;
```

add media to the user:

```php
protected array $transmorpherImages = [
    'front',
    'back'
];

protected array $transmorpherDocuments = [
    'document',
    'user-guide'
];

protected array $transmorpherVideos = [
    'teaser',
    'full'
];    
```

## docker/prod/Dockerfile

```dockerfile
# syntax=docker/dockerfile:1.7

FROM cybexwebdev/php-laravel:8.5-alpine-devtools as build

WORKDIR /var/www

COPY --chown=www-data:www-data ./amigor /var/www
COPY --chown=www-data:www-data .env.transmorpher /var/www/.env.transmorpher
COPY --chown=www-data:www-data . /var/package

RUN composer install --no-interaction --no-dev --no-scripts

RUN chmod +x /var/www/docker/prod/pullpreview.initialize.sh

## App

FROM cybexwebdev/php-laravel:8.5-alpine as app

WORKDIR /var/www
ENV WEB_DOCUMENT_ROOT /var/www/public

COPY --from=build /var/www /var/www
COPY --from=build /var/package /var/package
```

## docker/prod/pullpreview.initialize.sh

```bash
#!/bin/sh

# All commands need to be executed with "shell" to preserve www-data permissions!

pullpreview.initialize.sh
if ${PULLPREVIEW:-false}; then
    shell php /var/www/artisan migrate --force

    if ${PULLPREVIEW_FIRST_RUN:-false}; then
        shell php /var/www/artisan db:seed --class=PullpreviewSeeder --force
    fi
fi
```

## bootstrap/app.php

Add the TrustProxies middleware:

```php
...
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_TRAEFIK);
})
...
```

## app/Providers/AppServiceProvider.php

Add morph alias for User:

```php
public function boot(): void
{
    Relation::enforceMorphMap([
        'user' => 'App\Models\User',
    ]);
}
```
