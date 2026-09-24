import Encore from '@symfony/webpack-encore';
import Dotenv from 'dotenv-webpack';
import webpack from 'webpack';
import path from 'path';

const isDev = process.env.NODE_ENV !== 'production';

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')


    .copyFiles({
        from: './assets/files',
        to: 'files/[path][name].[ext]'
    })
    .copyFiles({
        from: './assets/json/Signalement',
        to: 'json/Signalement/[path][name].[ext]'
    })
    .copyFiles({
        from: './node_modules/@gouvfr/dsfr/dist/',
        to: 'dsfr/[path][name].[ext]'
    })
    .copyFiles({
        from: './node_modules/@popperjs/core/dist/',
        to: 'popper/[path][name].[ext]'
    })
    .copyFiles({
        from: './node_modules/leaflet/dist/',
        to: 'leaflet/[path][name].[ext]'
    })
    .copyFiles({
        from: './node_modules/leaflet.vectorgrid/dist/',
        to: 'leaflet.vectorgrid/[path][name].[ext]'
    })
    .copyFiles({
        from: './node_modules/tinymce/',
        to: 'tinymce/[path][name].[ext]'
    })

    .copyFiles({
        from: './node_modules/leaflet/dist/images',
        to: 'images/leaflet/[name].[ext]'
    })
    // Voir setWorkerUrl() dans vanilla/services/component/maplibre-worker.js.
    .copyFiles({
        from: './node_modules/maplibre-gl/dist/',
        to: 'maplibre-gl/[name].[ext]',
        pattern: /maplibre-gl-(worker|shared)\.mjs$/
    })

    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    Encore.addEntry('app', './assets/scripts/app.ts')
if (process.env.npm_lifecycle_event !== 'watch-base' && process.env.npm_lifecycle_event !== 'watch-bo' && process.env.npm_lifecycle_event !== 'watch-form') {
    Encore.addEntry('app-front-stats', './assets/scripts/app-front-stats.ts')
}
if (process.env.npm_lifecycle_event !== 'watch-base' && process.env.npm_lifecycle_event !== 'watch-front-stats' && process.env.npm_lifecycle_event !== 'watch-form') {
    Encore.addEntry('app-back-bo', './assets/scripts/app-back-bo.ts')
}
if (process.env.npm_lifecycle_event !== 'watch-base' && process.env.npm_lifecycle_event !== 'watch-front-stats' && process.env.npm_lifecycle_event !== 'watch-bo') {
    Encore.addEntry('app-front-signalement-form', './assets/scripts/app-front-signalement-form.ts')
}

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
Encore.enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .enableSassLoader()

    .enableTypeScriptLoader(function(tsConfig) {
        tsConfig.appendTsSuffixTo = ['\\.vue$']
    })

    .enableVueLoader(() => {}, {
        version: 3,
        runtimeCompilerBuild: false
    })

    .configureDevServerOptions(options => {
        // ... Autres configurations du serveur de développement ...
    
        options.proxy = {
          '/api': {
            target: 'http://localhost:8082',
            changeOrigin: true,
          },
        };
      })
    .addPlugin(new Dotenv({
        path: isDev ? '.env.local' : '.env'
    }))
;

export default await Encore.getWebpackConfig();
