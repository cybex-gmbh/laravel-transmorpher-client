# Upgrade Guide

## v0.5.1 to v0.6.0

- [Release Notes](CHANGELOG.md#v060)
- [GitHub diff](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.5.1...v0.6.0)

> [!WARNING]
> Breaking changes!
> 
> If you have published the config file, you should re-publish it or compare changes.

- Transmorpher Media Server API v1 is no longer supported
- To use this version of the package, you need a Transmorpher Media Server v0.9.0 or higher
- If you have published the config, or set the TRANSMORPHER_API_VERSION env key, you will need to update the default or value to 2
- The default config now uses a chunk size of 5 MiB to support S3-Multi-Part uploads.
  - You can still set it to a lower value, but when the Media Server is configured to use S3-Multi-Part uploads, it will automatically be set to 5 MiB when uploading.

## v0.3.0 to v0.4.0

- [Release Notes](CHANGELOG.md#v040)
- [GitHub diff](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)

### Route middleware

> [!NOTE]
> Impact: API routes may not be available

Route middlewares are now fully configurable.

When `transmorpher.routeMiddleware` is not set, the `web` and `auth` middlewares are now applied. `SubstituteBindings` is now always added.

If you previously published the config file, only the `auth` middleware is configured.
Now, you will no longer have `web` added to the configured middlewares.
Using `auth` without `web` will not work.

If you need `auth`, add the `web` middleware (sessions and CSRF protection) to the `transmorpher.routeMiddleware` config key or comment out the line.

```php
'routeMiddleware' => ['web', 'auth'],
```

If you don't want `auth`, remove it from the configuration.

```php
'routeMiddleware' => [],
```

### API responses

> [!NOTE]
> Impact: Custom frontend implementations will stop working

Responses which included media URLs now include media type specific URLs.

If you use a custom frontend, you will need to adjust to the new response format:

- for videos, the MP4 URL, HLS URL, DASH URL and thumbnail URL are now included in the response

```json5
{
  // ...
  "mp4Url": "https://example.com/video.mp4",
  "hlsUrl": "https://example.com/video.m3u8",
  "dashUrl": "https://example.com/video.mpd",
  "thumbnailUrl": "https.//example.com/thumbnail.jpg"
}
```

- for deleted media, the placeholder URL is included in the response

```json5
{
  // ...
  "placeholderUrl": "https://example.com/placeholder.jpg"
}
```
