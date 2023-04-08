const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Settings
 |--------------------------------------------------------------------------
*/
	mix.options({
      processCssUrls: false
    });

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/gao-connector.js', 'public/js')
    .sass('resources/sass/gao-connector.scss', 'public/css').sourceMaps();

/*
 |--------------------------------------------------------------------------
 | Mix  Copy Files
 |--------------------------------------------------------------------------
*/
   	mix.copyDirectory('resources/img', 'public/img');
   	mix.copyDirectory('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/fonts');
