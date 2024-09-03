# Release Notes

## [v0.4.0](https://github.com/cybex-gmbh/laravel-transmorpher-client/compare/v0.3.0...v0.4.0)

> [!WARNING]
> Breaking change!

- Route middlewares are now fully configurable
  - the `SubstituteBindings` middleware is now always applied as it is necessary to resolve route model bindings
  - when `transmorpher.routeMiddleware` is not set, the `web` and `auth` middlewares are applied
  - already published config files have to be adjusted accordingly
