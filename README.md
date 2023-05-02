# Laravel Transmorpher Client

A client package for Laravel applications that use the Transmorpher media server.

The client package provides a Dropzone component, which supports chunked uploads, out of the box. 

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

### Config values

If you want to configure certain configuration values used by the package, you can do so by publishing the `transmorpher.php` file to your project directory.

```bash
php artisan vendor:publish --tag=transmorpher.config
```

There you can configure values such as the route to which the Transmorpher media server sends information after transcoding a video.  

### .env keys

Before u can use this package u have to configure some .env keys (or config values).

The `TRANSMORPHER_AUTH_TOKEN` is a Laravel Sanctum token which is used for authentication at the Transmorpher media
server. This token should be provided to you by an admin of the Transmorpher media server.

```dotenv
TRANSMORPHER_AUTH_TOKEN=
```

The `TRANSMORPHER_WEB_API_URL` is the server endpoint for making API calls.

```dotenv
TRANSMORPHER_WEB_API_BASE_URL=https://example.com/api
```

The `TRANSMORPHER_DELIVERY_URL` is the Transmorpher endpoint which is used to request image derivatives.

```dotenv
TRANSMORPHER_WEB_DELIVERY_BASE_URL=https://example.com
```

**_NOTE:_** In case you are in a docker environment or similar, you might want to set specific URLs for server to server communication. You can do so by using the following .env keys:

```dotenv
TRANSMORPHER_S2S_API_BASE_URL=http://example/api
TRANSMORPHER_S2S_CALLBACK_BASE_URL=http://example
```

For cases in which a medium doesn't have an upload yet, you can specify a URL to a placeholder image which will be used. 

```dotenv
TRANSMORPHER_WEB_PLACEHOLDER_URL=http://example.com/placeholder.jpg
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

## Dropzone Blade component & assets

For using the client package in the frontend you are provided with a convenient Dropzone component. In order to use the component, you will have to publish the necessary assets to your public folder.

```bash
php artisan vendor:publish --tag=transmorpher.assets
```

If you want to make sure you will always have the most recent assets, even when updating the package, you can add this to your `composer.json` file:

```json
{
  "scripts": {
    "post-update-cmd": [
      "@php artisan vendor:publish --tag=transmorpher.assets --ansi --force"
    ]
  }  
}
```

If you would like to customize the Dropzone component in any way, you can also publish it to your `resources` directory:

```bash
php artisan vendor:publish --tag=transmorpher.views
```

### Dropzone component usage

To use the dropzone component in a template, you can simply include it like this:

```html
<x-transmorpher-dropzone :transmorpher="$yourModel->imageFrontView()"></x-transmorpher-dropzone>
```

Depending on whether you pass an ImageTransmorpher or a VideoTransmorpher, the component will function as your upload form for images or videos. 

#### Dynamic usage

If you want a more dynamic approach, to display a dropzone for each available image or video, you can use the dynamic way of defining images and videos mentioned above.

```html
@foreach($yourModel->transmorpherImages as $differentiator)
    <x-transmorpher-dropzone :transmorpher="$yourModel->image($differentiator)"></x-transmorpher-dropzone>
@endforeach
```

## License

This package is licensed under [The MIT License (MIT)](LICENSE).
