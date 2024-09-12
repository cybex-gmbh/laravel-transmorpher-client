# Upgrade Guide

## v0.3.0 to v0.4.0
- [Release Notes](CHANGELOG.md#v040)
- [GitHub diff](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)


### Medium impact changes
- [Route middleware](#route-middleware)

### Low impact changes
- [API responses](#api-responses)

### Route middleware

>![NOTE]
> Likelihood Of Impact: Medium

Route middlewares are now fully configurable.

When `transmorpher.routeMiddleware` is not set, the `web` and `auth` middlewares are now applied. `SubstituteBindings` is now always applied.

If you previously published the config file, you will no longer have the `web` middleware applied by default. 
To restore the default, uncomment the `transmorpher.routeMiddleware` config key:

```php
    // The middlewares applied to routes provided by this package:
    // - the "SubstituteBindings" middleware will be applied additionally.
    // - "web" and "auth" middlewares will be applied when this is not set.
//    'routeMiddleware' => ['web', 'auth'],
```

### API responses

>![NOTE]
> Likelihood Of Impact: Low

Responses which included media URLs now include media type specific URLs.

If you use a custom frontend, you will need to adjust to the new response format:
- for videos, the MP4 URL, HLS URL, DASH URL and thumbnail URL are now included in the response
- for images, the fullsize URL and thumbnail URL are now included in the response
- for deleted media, the placeholder URL is included in the response





