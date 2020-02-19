let mix = require('laravel-mix')

mix.setPublicPath('dist')
    .js('resources/js/gantt-metric.js', 'js')
    .sass('resources/sass/gantt-metric.scss', 'css')
