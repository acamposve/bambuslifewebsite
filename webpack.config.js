var Encore = require('@symfony/webpack-encore');
var CopyWebpackPlugin = require('copy-webpack-plugin');

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if you JavaScript imports CSS.
     */
    .addEntry('app',                      './assets/js/app.js')
    .addEntry('home',                     './assets/js/home.js')
    .addEntry('products',                 './assets/js/products.js')
    .addEntry('product',                  './assets/js/products/product.js')
    .addEntry('cs-orgs',                  './assets/js/products/cs-orgs.js')
    .addEntry('cs-pro',                   './assets/js/products/cs-pro.js')
    .addEntry('cs-active',                './assets/js/products/cs-active.js')
    .addEntry('accessories',              './assets/js/products/accessories.js')
    .addEntry('order-csactive',           './assets/js/order/order-csactive.js')
    .addEntry('order-cspro',              './assets/js/order/order-cspro.js')
    .addEntry('order-view',               './assets/js/order/order-view.js')
    .addEntry('cart',                     './assets/js/order/cart.js')
    .addEntry('invitation'      ,         './assets/js/invitation/accept-invitation.js')
    .addEntry('checkout-s1-user',         './assets/js/checkout/checkout-s1-user.js')
    .addEntry('checkout-s2-patient',      './assets/js/checkout/checkout-s2-patient.js')
    .addEntry('checkout-s4-payment',      './assets/js/checkout/checkout-s4-payment.js')
    .addEntry('checkout-s3-shippinginfo', './assets/js/checkout/checkout-s3-shippinginfo.js')
    .addEntry('change-password',          './assets/js/user/change-password.js')
    .addEntry('cancel-subscription',      './assets/js/user/cancel-subscription.js')
    .addEntry('resetPassword',            './assets/js/user/resetPassword.js')
    .addEntry('contact-form',             './assets/js/user/contact-form.js')
    .addEntry('sub-config',               './assets/js/user/sub-config.js')
    .addEntry('profile',                  './assets/js/user/profile.js')
    .addEntry('profile-clinic',           './assets/js/user/profile-clinic.js')
    .addEntry('billing-details',          './assets/js/user/billing-details.js')
    .addEntry('faq',                      './assets/js/faq.js')
    .addEntry('invoice',                  './assets/js/user/invoice.js')
    .addEntry('practitioner-register',    './assets/js/user/practitioner-register.js')
    .addEntry('organization-register',    './assets/js/user/organization-register.js')
    .addEntry('patient-practitioner',     './assets/js/user/patient-practitioner.js')
    .addEntry('observation-list',         './assets/js/user/observation-list.js')
    .addEntry('request-diag-rep',         './assets/js/observations/request-diag-rep.js')
    .addEntry('callus',                   './assets/js/components/callus.js')
    .addEntry('landing-casmu',            './assets/js/landing/landing-casmu.js')
    .addEntry('landing-covid',            './assets/js/landing/landing-covid.js')
    .addEntry('landing-portal-medico',    './assets/js/landing/landing-portal-medico.js')
    .addEntry('appointment-list',         './assets/js/user/appointment-list.js')
    .addEntry('appointment',              './assets/js/user/appointment.js')
    .addEntry('appointment-virtual',      './assets/js/user/appointment-virtual.js')
    .addEntry('resources-search',         './assets/js/user/resources-search.js')
    .addEntry('resources-select',         './assets/js/user/resources-select.js')
    .addEntry('select-date',              './assets/js/user/select-date.js')
    .addEntry('complete-registration',    './assets/js/user/complete-registration.js')
    .addEntry('register',                 './assets/js/user/register.js')
    .addEntry('pressrelease',             './assets/js/pressrelease/pressrelease.js')
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

    // enables Sass/SCSS support
    .enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // uncomment if you use API Platform Admin (composer req api-admin)
    //.enableReactPreset()
    //.addEntry('admin', './assets/js/admin.js')

    .addPlugin(new CopyWebpackPlugin([
        { from: './assets/images', to: 'images' },
        { from: { glob: './assets/css/payment-methods*' }, to: 'css', flatten: 1}
    ]))
;

module.exports = Encore.getWebpackConfig();
