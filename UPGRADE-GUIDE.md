# Upgrade Guide

## v0.3.0 to v0.4.0

- [Release Notes](CHANGELOG.md#v040)
- [GitHub diff](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)

### Route middleware

> [!NOTE]
> Impact: API routes may not be available

Route middlewares are now fully configurable.

When `transmorpher.routeMiddleware` is not set, the `web` and `auth` middlewares are now applied. `SubstituteBindings` is now always applied.

If you previously published the config file, you will no longer have the `web` middleware applied by default.
To re-add the `web` middleware, add it to the `transmorpher.routeMiddleware` config key or comment out the line.

In the example below, we have added the `web` middleware.

```php
'routeMiddleware' => ['web', 'auth'],
```

### API responses

> [!NOTE]
> Impact: Custom frontend implementations will stop working

Responses which included media URLs now include media type specific URLs.

If you use a custom frontend, you will need to adjust to the new response format:

- for videos, the MP4 URL, HLS URL, DASH URL and thumbnail URL are now included in the response
- for deleted media, the placeholder URL is included in the response
