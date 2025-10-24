let mix = require('laravel-mix')

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'js')
  .vue({ version: 3 })
  .sass('resources/sass/field.scss', 'css')
  .webpackConfig({
    externals: {
      vue: 'Vue',
      'laravel-nova': 'LaravelNova',
      'laravel-nova-ui': 'LaravelNovaUi',
      clipboard: 'Clipboard',
      // vuedraggable: removed from externals - now bundled with the package
      // vue-cropperjs: removed from externals - now bundled with the package
      // cropperjs: removed from externals - now bundled with the package
    },
    output: {
      uniqueName: 'pcrcard/nova-medialibrary-bounding-box-field',
    },
  })
  .version()
