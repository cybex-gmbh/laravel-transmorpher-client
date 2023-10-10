# Laravel Transmorpher Client

A client package for Laravel applications that use the Transmorpher media server.

The client package provides a Dropzone component, which supports chunked uploads, out of the box.

## Installation

To get the latest version, simply require the project using [Composer](https://getcomposer.org):

```bash
composer require cybex/laravel-transmorpher-client
```

To create the tables the package provides, you have to migrate.

```bash
php artisan migrate
```

## Configuration

### Config values

If you want to configure certain configuration values used by the package, you can do so by publishing the `transmorpher.php` file to your project directory.

```bash
php artisan vendor:publish --tag=transmorpher.config
```

There you can configure values such as the route to which the Transmorpher media server sends information after transcoding a video.

### .env keys

Before you can use this package you have to configure some .env keys (or config values).

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

**_NOTE:_** In case you are in a docker environment or similar, you might want to set specific URLs for server to server communication. You can do so by using the following .env
keys:

```dotenv
TRANSMORPHER_S2S_API_BASE_URL=http://example/api
TRANSMORPHER_S2S_CALLBACK_BASE_URL=http://example
```

A placeholder image can be displayed for media without uploads.

```dotenv
TRANSMORPHER_WEB_PLACEHOLDER_URL=http://example.com/placeholder.jpg
```

You can specify the Transmorpher API version which should be used. Make sure the Transmorpher media server supports the configured version.

For versions supported by the client, check the SupportedApiVersion enum.

```dotenv
TRANSMORPHER_API_VERSION=1
```

### Allow models to have images and videos

All Models which should be able to have media, have to implement the `HasTransmorpherMediaInterface` and use
the `HasTransmorpherMedia` trait. The trait provides the implementation for the relation to `TransmorpherMedia`, the
model which stores the information about uploaded images and videos, as well as convenient methods for dynamically accessing your images and videos.

```php
class YourModel extends Model implements HasTransmorpherMediaInterface
{
    use HasTransmorpherMedia
   
    ...
}
```

To configure your model to be able to have media and make API calls to the Transmorpher media server, you have to define specific array properties.
Images and Videos are registered in those arrays by a media name.

```php
protected array $transmorpherImages = [
    'front',
    'back'
];

protected array $transmorpherVideos = [
    'teaser'
];
```

**NOTE:** These property names are not replaceable since they are expected by the `HasTransmorpherMedia` trait. 

The trait `HasTransmorpherMedia` provides convenient methods to access your media.

```php
// Retrieve all images as collection, with media name as key and the Image object as value.
$yourModel->images;

// Retrieve all videos as collection, with media name as key and the Video object as value.
$yourModel->videos;

// Retrieve a single Image object.
$yourModel->image('front');

// Retrieve a single Video object.
$yourModel->video('teaser');
```
The instance of the corresponding `Media`-class can then be used to make API calls to the Transmorpher media
server.

```php
// Retrieve the 'back' Image instance.
$image = $yourModel->image('back');

// Upload an image to the media server.
$image->upload($fileHandle);

// Get the public URL of the image for retrieving a derivative.
// Transformations are optional and will be included in the URL.
$image->getUrl(['width' => 1920, 'height' => 1080, 'format' => 'jpg', 'quality' => 80]);
```
You can iterate over all your media for a model with `images` and `videos`:

```html
@foreach($yourModel->images as $image)
    <img src="{{ $image->getUrl() }}"></img>
@endforeach
```

#### Identifier

To uniquely identify media, an identifier is passed to the Transmorpher media server. This identifier consists of the following:
 - media name: name for the media in the model
 - model id
 - an alias (by default the morph alias is used)

Instead of using the morph alias, you can add the property `$transmorpherAlias` to your model, which will then be used for the identifier.

```php
class YourModel extends Model implements HasTransmorpherMediaInterface
{
    use HasTransmorpherMedia;
   
    protected string $transmorpherAlias = 'yourAlias';
    
    ...
}
```

**_NOTE:_** As the identifier is used in filenames and URLs, your chosen alias may not contain special characters (e.g. slashes).

> **Warning**
> 
> The alias is not intended to be ever changed, as you change the identifier and therefore lose the access to your version history.
> The images of the old identifier will still be accessible from the public, but the client cannot associate them to its model.

## Dropzone Blade component & assets

For using the client package in the frontend you are provided with a convenient Dropzone component. In order to use the component, you will have to publish the necessary assets to
your public folder.

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
<x-transmorpher::dropzone :media="$yourModel->image('front')"></x-transmorpher::dropzone>
```

**_NOTE:_** You can optionally define a fixed width by setting the width attribute (e.g. `width="300px"`).

Depending on whether you pass a `Transmorpher\Image` or a `Transmorpher\Video`, the component will function as your upload form for images or videos.

#### Dynamic usage

If you want a more dynamic approach, to display a dropzone for each available image or video, you can use the provided functions for retrieving all media mentioned above.

```html
@foreach($yourModel->images as $image)
    <x-transmorpher::dropzone :media="$image"></x-transmorpher::dropzone>
@endforeach
```

#### Validation

**NOTE:** This is no security feature and will only be checked in the frontend.

There are some validation rules which can be applied for the dropzone component:
- accepted file types
- max file size
- min/max width *
- min/max height *
- ratio *

All those validation rules can be configured in the `transmorpher.php` config file and will be applied to all dropzones.

Additionally, you have the option to specify the validation rules marked with a '*' for a specific dropzone, which will take priority over the rules specified in the config file.

```html
<x-transmorpher::dropzone :media="$image" acceptedMinWidth="1920" acceptedMinHeight="1080" :acceptedRatio="16/9"></x-transmorpher::dropzone>
```

## Development

### Frontend Assets
For installing frontend dependencies you will have to run:
```bash
npm install
```

For compiling assets you can use the following command in the project root:
```bash
npx mix
```

### Transformations

To show derivatives on a webpage, you can use an HTML image tag.

**NOTE**: These examples use Blade syntax and assume you have a valid `Media`-class instance in your template.

```html
<img src="{{ $media->getUrl() }}"></img>
```

You also have the possibility to apply transformations.

```html
<img src="{{ $media->getUrl(['width' => 300, 'format' => 'png']) }}"></img>
```

List of available transformations:
- width
- height
- format
- quality

## License

This package is licensed under [The MIT License (MIT)](LICENSE).
