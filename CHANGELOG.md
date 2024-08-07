# Release Notes

## [v0.4.0](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)

> [!NOTE]
> Overview of potentially breaking changes: 
> 
> - published configs won't have `web` middleware added
> - API responses for media URLs have changed
> 
> See below for more information.

### Features

> [!WARNING]
> Breaking changes!

- [BREAKING] Route middlewares are now fully configurable
  - the `SubstituteBindings` middleware is now always applied as it is necessary to resolve route model bindings
  - when `transmorpher.routeMiddleware` is not set, the `web` and `auth` middlewares are applied
  - already published config files have to be adjusted accordingly


- [BREAKING] Responses which included media URLs now include media type specific URLs
  - for videos, the MP4 URL, HLS URL, DASH URL and thumbnail URL are now included in the response
  - for images, the fullsize URL and thumbnail URL are now included in the response
  - for deleted media, the placeholder URL is included in the response

### Bug Fixes

- fixed a bug where overwriting uploads would fail when the dropzone was not in a reset (e.g., freshly loaded page) state
- fixed a bug where unintentional requests for upload slots where made when dropping a wrong mimetype into the video dropzone


