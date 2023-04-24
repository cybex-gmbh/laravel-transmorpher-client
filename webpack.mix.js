let mix = require('laravel-mix');

mix.js('src/resources/js/transmorpher.js', 'dist')
    .setPublicPath('dist')
    .sass('src/resources/css/transmorpher.scss', 'dist')
    .setPublicPath('dist')
    .version();
