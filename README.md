# Laravel Transmorpher Client

A client package for Laravel applications that use the Transmorpher media server.

## Installation

To get the latest version, simply require the project using [Composer](https://getcomposer.org):

```bash
$ composer require cybex/laravel-transmorpher-client
```

To create the tables the package provides, you have to migrate.

```bash
$ php artisan migrate
```

## Configuration

### .env keys

Before u can use this package u have to configure some .env keys (or config values).

The `TRANSMORPHER_AUTH_TOKEN` is a Laravel Sanctum token which is used for authentication at the Transmorpher media
server. This token should be provided to you by an admin of the Transmorpher media server.

```dotenv
TRANSMORPHER_AUTH_TOKEN=
```

The `TRANSMORPHER_API_URL` is the server endpoint for making API calls.

```dotenv
TRANSMORPHER_API_URL=https://example.com/api
```

The `TRANSMORPHER_PUBLIC_URL` is the Transmorpher endpoint which is used to request image derivatives.

```dotenv
TRANSMORPHER_PUBLIC_URL=https://example.com
```

### Allow models to have images and videos

All Models which should be able to have media, have to implement the `HasTransmorpherMediaInterface` and use
the `HasTransmorpherMedia` trait. The trait provides the implementation for the relation to `TransmorpherMedia`, the
model which stores the information about uploaded images and videos.

```php
class YourModel extends Model implements HasTransmorpherMediaInterface
{
    use HasTransmorpherMedia
   
    ...
}
```

To configure your model to be able to have media and make API calls to the Transmorpher media server, you have to define
a method for each image or video you want the model to have.

**_NOTE:_** This package uses polymorphic relations. You will have to set a morph alias without any special characters
(e.g. slashes), as it will be used for passing a unique identifier to the Transmorpher media server.

For images you will have to return an instance of an ImageTransmorpher:

```php
public function imageFrontView(): ImageTransmorpher
{
    return ImageTransmorpher::getInstanceFor($this, __FUNCTION__);
}

public function imageSideView(): ImageTransmorpher
{
    return ImageTransmorpher::getInstanceFor($this, __FUNCTION__);
}
```

For videos you will have to return an instance of a VideoTransmorpher:

```php
public function video(): VideoTransmorpher
{
    return VideoTransmorpher::getInstanceFor($this, __FUNCTION__);
}
```

#### Dynamic images & videos

If you need a more dynamic approach to defining images or videos for a model, you can instead use an array and a single method:

```php
public $transmorpherImages = [
    'frontView',
    'sideView',
];

public function image($motif): ImageTransmorpher
{
    return ImageTransmorpher::getInstanceFor($this, $motif);
}
```

This can be used to iterate over all images for a model for example.

The instance of the corresponding `Transmorpher`-class can then be used to make API calls to the Transmorpher media
server.

```php
$imageTransmorpher = $yourModel->$imageFrontView();

// Upload an image to the media server.
$imageTransmorpher->upload($fileHandle);

// Get the public URL of the image for retrieving a derivative.
// Transformations are optional and will be included in the URL. 
$imageTransmorpher->getUrl(['width' => 1920, 'height' => 1080, 'format' => 'jpg', 'quality' => 80]);
```

### Callback Route

If you want to configure the route to which the Transmorpher media server sends information after transcoding a video,
you can do so by publishing the `transmorpher.php` file to your project directory.

```bash
php artisan vendor:publish --tag=transmorpher.config
```

## License

This package is licensed under [The MIT License (MIT)](LICENSE).
