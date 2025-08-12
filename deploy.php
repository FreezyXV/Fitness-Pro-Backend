<?php
namespace Deployer;

require 'recipe/laravel.php';

// Configuration du projet
set('application', 'Fitness Pro Backend');
set('repository', 'https://github.com/FreezyXV/Fitness-Pro-Backend.git');

// Configuration pour Fly.io
host('production')
    ->set('remote_user', 'root')
    ->set('hostname', 'fitness-pro.fly.dev')
    ->set('port', 22)
    ->set('deploy_path', '/app')
    ->set('labels', ['stage' => 'production']);

// Si vous avez un environnement de staging sur Fly.io
host('staging')
    ->set('remote_user', 'root') 
    ->set('hostname', 'fitness-pro-staging.fly.dev')
    ->set('port', 22)
    ->set('deploy_path', '/app')
    ->set('labels', ['stage' => 'staging']);

// Variables d'environnement
set('php_fpm_version', '8.2'); // Ajustez selon votre version PHP
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

// Dossiers partagés (persistent entre les déploiements)
add('shared_files', [
    '.env',
]);

add('shared_dirs', [
    'storage',
]);

// Dossiers en écriture
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// Tâches personnalisées
task('artisan:storage:link', artisan('storage:link'));
task('artisan:config:cache', artisan('config:cache'));
task('artisan:route:cache', artisan('route:cache'));
task('artisan:view:cache', artisan('view:cache'));
task('artisan:queue:restart', artisan('queue:restart'));

// Migrations (optionnel, attention en production!)
task('artisan:migrate', artisan('migrate --force'));

// Redémarrer PHP-FPM
task('php-fpm:reload', function () {
    run('sudo service php{{php_fpm_version}}-fpm reload');
});

// Hook après déploiement
after('artisan:optimize:clear', 'artisan:storage:link');
after('artisan:optimize:clear', 'artisan:config:cache');
after('artisan:optimize:clear', 'artisan:route:cache');
after('artisan:optimize:clear', 'artisan:view:cache');
after('deploy:symlink', 'php-fpm:reload');
after('deploy:symlink', 'artisan:queue:restart');

// Optionnel : exécuter les migrations automatiquement
// after('artisan:optimize:clear', 'artisan:migrate');

// Échec de déploiement
after('deploy:failed', 'deploy:unlock');