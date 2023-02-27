<?php
namespace Deployer;

require 'recipe/symfony4.php';

// Project name
set('application', 'bambus-life-web-site');

// Project repository
set('repository', 'git@hulk:tingelmar/bambus-life-web-site.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', []);
set('allow_anonymous_stats', false);

// Hosts
host('production')
    ->set('env', [
        'APP_ENV'                => 'prod',
        'APP_DEBUG'              => '0',
        'HUBCLIENT_URL'          => 'http://hulk:3004',
        'HUBCLIENT_TIMEOUT'      => 3,
        'HUBCLIENT_LONG_TIMEOUT' => 120,
        'HUBCLIENT_APIKEY'       => '1c9184aa-056b-42e5-8977-0ad916699a8b',
        'CONTACT_FORM'           => 'sales@bambus.life',
        'NGAGECLIENT_URL'        => 'http://hulk:7000',
        'NGAGECLIENT_TIMEOUT'    => 15,
        'NGAGECLIENT_APIKEY'     => 'ABC'
    ])
    ->hostname('hulk')
    ->stage('prod')
    ->set('deploy_path', '/opt/tingelmar/apps/bambus-life-web-site')
    ->set('branch', 'master')
    ->set('http_user', 'www-data')
    ->set('keep_releases', 2)
    ->user('operador');

host('staging')
    ->set('env', [
        'APP_ENV'             => 'test',
        // 'BL_MPUY_PUBLIC_KEY'  => 'TEST-1ee4982f-e362-4aa7-b7b9-a72daa6aaae6',
        // 'BL_MPUY_PRIVATE_KEY' => 'TEST-2920429832101828-060309-91475fa8bba08e7f678d6969a22edc8e-434425699',
        'CONTACT_FORM'        => 'dev@tingelnmar.com',
        'NGAGECLIENT_URL'     => 'http://hulk:7000',
        'NGAGECLIENT_TIMEOUT' => 15,
        'NGAGECLIENT_APIKEY'  => 'ABC'
    ])
    ->hostname('hulk')
    ->stage('test')
    ->set('deploy_path', '/opt/tingelmar/apps/bambus-life-web-site_stg')
    ->set('branch', 'master')
    ->set('http_user', 'www-data')
    ->set('keep_releases', -1)
    ->user('operador');

set('http_user', 'www-data');
set('default_timeout', 3000);

// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

// Build CSS and JS
task('buildEncore', function () {
    //run('cd {{release_path}} && yarn add @symfony/webpack-encore@0.22.4 && yarn run encore production');
	run('cd {{release_path}} && yarn add @symfony/webpack-encore@0.22.4 --ignore-engines && yarn run encore production');
});
before('deploy:symlink', 'buildEncore');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'database:migrate');

task('database:migrate', function () {
    desc('SKIP DB');
});
