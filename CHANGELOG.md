# Release Notes

## [v0.5.1](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.5.0...v0.5.1)

- Add support for Laravel 12

## [v0.5.0](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.4.0...v0.5.0)

### Features
- PDF uploads are now supported
  - PDFs can be uploaded and previewed
  - Requesting a PDF without a format will return the document
  - Requesting a PDF with an image format will return the document as an image
  - Available transformations:
    - ppi: The ppi will be multiplied with the document dimensions, which results in the image resolution
    - p: The page to be displayed as image
    - all regular image transformations

- When defining media via methods, the name of the calling method will now automatically be used as media name.
See the according [readme section](README.md#allow-models-to-have-images-and-videos) for more information
  
## [v0.4.0](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)

> [!WARNING]
> Breaking changes! 
> 
> For more information, see the [upgrade guide](UPGRADE-GUIDE.md#v030-to-v040).

### Features

- Route middlewares are now fully configurable
  - the `SubstituteBindings` middleware is now always applied as it is necessary to resolve route model bindings
  - when `transmorpher.routeMiddleware` is not set, the `web` and `auth` middlewares are applied
  - already published config files have to be adjusted accordingly

- Responses which included media URLs now include media type specific URLs
  - for videos, the MP4 URL, HLS URL, DASH URL and thumbnail URL are now included in the response
  - for images, the fullsize URL and thumbnail URL are now included in the response
  - for deleted media, the placeholder URL is included in the response

### Bug Fixes

- fixed a bug where overwriting uploads would fail when the dropzone was not in the initial state (e.g., freshly loaded page)
- fixed a bug where unintentional requests for upload slots where made when dropping a wrong mimetype into the video dropzone
