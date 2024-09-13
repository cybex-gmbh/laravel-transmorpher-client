# Upgrade Guide

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
