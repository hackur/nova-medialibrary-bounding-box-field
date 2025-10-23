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
      vuedraggable: 'vuedraggable',
      'vue-cropperjs': 'VueCropperjs',
      cropperjs: 'Cropper',
    },
    output: {
      uniqueName: 'pcrcard/nova-medialibrary-bounding-box-field',
    },
  })
  .version()
