# Release Notes

## [v0.4.0](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)

> [!NOTE]
> Overview of potentially breaking changes: 
> 
> - route middleware no longer applies `web` by default
> - API responses for media URLs have changed
> 
> See below for more information.

### Features

> [!WARNING]
> Breaking changes!

- [BREAKING] Route middleware is now fully configurable
  - the `web` middleware is no longer applied by default and has to be set in already published config files

- [POTENTIALLY BREAKING (if the client's API is used)] Responses which included URLs for media now include media type specific media URLs
  - for videos, the MP4 URL, HLS Url, DASH URL and thumbnail URL are now included in the response
  - for images, the fullsize URL and thumbnail URL are now included in the response
  - for deleted media, the placeholder URL is included in the response

### Bug Fixes

- fixed a bug where overwriting uploads would fail when the dropzone was not in a reset (e.g. freshly loaded page) state
- fixed a bug where unintentional requests for upload slots where made when dropping a wrong mimetype into the video dropzone



