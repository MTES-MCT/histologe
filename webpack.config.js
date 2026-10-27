import Encore from '@symfony/webpack-encore';
import Dotenv from 'dotenv-webpack';
import webpack from 'webpack';
import path from 'path';
import { readFileSync } from 'fs';

const isDev = process.env.NODE_ENV !== 'production';

// Les fichiers copiés depuis node_modules ne sont pas hashés (TinyMCE résout ses plugins par chemin fixe) :
// on versionne leur URL dans le manifest pour invalider le cache navigateur lors des montées de version.
const readVersion = (pkg) => JSON.parse(readFileSync(`./node_modules/${pkg}/package.json`, 'utf8')).version;
const vendorVersions = {
    'build/dsfr/': readVersion('@gouvfr/dsfr'),
    'build/popper/': readVersion('@popperjs/core'),
    'build/leaflet/': readVersion('leaflet'),
    'build/leaflet.vectorgrid/': readVersion('leaflet.vectorgrid'),
    'build/tinymce/': `${readVersion('tinymce')}-${readVersion('tinymce-i18n')}`,
};
const maplibreVersion = readVersion('maplibre-gl');

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
        from: './node_modules/tinymce-i18n/langs8/',
        to: 'tinymce/langs/[name].[ext]',
        pattern: /fr-FR\.js$/
    })

    .copyFiles({
        from: './node_modules/leaflet/dist/images',
        to: 'images/leaflet/[name].[ext]'
    })
    // maplibre-gl est chargé comme module ES natif depuis ce dossier (voir addExternals plus bas) :
    // il y retrouve son worker via import.meta.url, et le worker partage le même maplibre-gl-shared.mjs.
    // Le numéro de version dans le chemin invalide le cache navigateur, y compris pour les imports relatifs entre ces fichiers.
    .copyFiles({
        from: './node_modules/maplibre-gl/dist/',
        to: `maplibre-gl/${maplibreVersion}/[name].[ext]`,
        pattern: /maplibre-gl(-worker|-shared)?\.mjs$/
    })
    .addExternals({
        'maplibre-gl': `import /build/maplibre-gl/${maplibreVersion}/maplibre-gl.mjs`,
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

    .configureManifestPlugin(options => {
        options.map = file => {
            const prefix = Object.keys(vendorVersions).find(p => file.name.startsWith(p));
            return prefix ? { ...file, path: `${file.path}?v=${vendorVersions[prefix]}` } : file;
        };
    })
    // Suffixe appliqué par TinyMCE aux plugins, thèmes, skins et langues qu'il charge dynamiquement
    .configureDefinePlugin(options => {
        options.TINYMCE_CACHE_SUFFIX = JSON.stringify(`?v=${vendorVersions['build/tinymce/']}`);
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

const config = await Encore.getWebpackConfig();
// Sans browserslist, Webpack suppose un environnement ES5 : on déclare le support de import(),
// nécessaire pour charger maplibre-gl comme module ES natif (external de type "import").
config.output.environment = { ...config.output.environment, dynamicImport: true };

export default config;
