# Cybex/Transmorpher-Client

A client package for Laravel applications that use the Transmorpher media server.

## Installation

To get the latest version, simply require the project using [Composer](https://getcomposer.org):

```bash
$ composer require cybex/transmorpher-client
```

To create the tables the package provides, you have to migrate.

```bash
$ php artisan migrate
```

## Configuration

### .env keys

Before u can use this package u have to configure some .env keys (or config values).

The `TRANSMORPHER_AUTH_TOKEN` is a Laravel Sanctum token which is used for authentication at the Transmorpher media
server. This token should be provided to you by an admin of the Transmorpher.

```dotenv
TRANSMORPHER_AUTH_TOKEN=
```

The `TRANSMORPHER_API_URL` is the Transmorpher endpoint for making API calls.

```dotenv
TRANSMORPHER_API_URL=
```

The `TRANSMORPHER_PUBLIC_URL` is the Transmorpher endpoint which is used to request image derivatives.

```dotenv
TRANSMORPHER_PUBLIC_URL=
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

To configure your model to be able to have media and make API calls to the transmorpher, you have to define a method for
each image or video you want the model to have.

For images you will have to return an instance of an ImageTransmorpher:

```php
public function image(): ImageTransmorpher
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

The instance of the corresponding `Transmorpher`-class can the be used to make API calls to the Transmorpher media
server.

### Callback Route

If you want to configure the route to which the Transmorpher media server sends information after transcoding a video,
you can do so by publishing the `transmorpher.php` file to your project directory.

```bash
php artisan vendor:publish --tag=transmorpher.config
```

## Usage

To upload media, you will have to call the `upload`-method on an `ImageTransmorpher` or `VideoTransmorpher` and pass a
valid file handle.

## License

This package is licensed under [The MIT License (MIT)](LICENSE).
