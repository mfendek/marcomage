let mix = require('laravel-mix').mix;

mix.setPublicPath(__dirname);

mix.js('js/main.js', 'js/dist/main.js')
  .autoload({
    jquery: ['$', 'window.jQuery', 'jQuery'],
  });

mix.sass('styles/scss/main.scss', 'styles/css/main.css');
mix.options({
  processCssUrls: false
});

mix.copy('node_modules/bootstrap-sass/assets/fonts/bootstrap', 'styles/fonts');
