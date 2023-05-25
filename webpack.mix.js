let mix = require('laravel-mix');

mix.setPublicPath('dist')
    .js('src/resources/js/transmorpher.js', 'dist')
    .sass('src/resources/css/transmorpher.scss', 'dist')
    .version();
