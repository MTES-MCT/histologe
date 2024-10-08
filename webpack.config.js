const Encore = require('@symfony/webpack-encore');
const Dotenv = require('dotenv-webpack');
const isDev = Encore.isDev();

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

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
    .addEntry('app', './assets/scripts/app.ts')
    .addEntry('app-back-bo', './assets/scripts/app-back-bo.ts')
    .addEntry('app-front-signalement-form', './assets/scripts/app-front-signalement-form.ts')
    .addEntry('app-front-stats', './assets/scripts/app-front-stats.ts')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

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

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

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

module.exports = Encore.getWebpackConfig();
