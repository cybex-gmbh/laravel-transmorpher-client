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

Run 

```bash
composer update
```

## resources/views/welcome.blade.php

Replace with:

```bladehtml
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>
</head>

<body style="padding:50px; display:flex; gap:50px; flex-wrap:wrap">
@foreach(App\Models\User::first()->images as $image)
<x-transmorpher::dropzone :media="$image" width="300px"></x-transmorpher::dropzone>
@endforeach

@foreach(App\Models\User::first()->documents as $document)
<x-transmorpher::dropzone :media="$document" width="300px"></x-transmorpher::dropzone>
@endforeach

@foreach(App\Models\User::first()->videos as $video)
<x-transmorpher::dropzone :media="$video" width="300px"></x-transmorpher::dropzone>
@endforeach
</body>

</html>
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

Implement the interface:

```php
... implements \Transmorpher\HasTransmorpherMediaInterface
```

Add the `HasTransmorpherMedia` trait:

```php
use \Transmorpher\HasTransmorpherMedia;
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
