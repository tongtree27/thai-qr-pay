<?php
ini_set('session.gc_maxlifetime', 1 * (3600 * 24));
// Memcached workaround
if (extension_loaded('memcached')) {
    ini_set('session.save_handler', 'memcached');
    ini_set('session.save_path', 'memcached-01:11211');
    ini_set('session.lazy_write', 0);
}
date_default_timezone_set('Asia/Bangkok');
$_SERVER['DOCUMENT_ROOT'] = '/srv/thai-qr-pay';

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

// Instantiate the app
$settings = require $_SERVER['DOCUMENT_ROOT'] . '/src/settings.php';
$app = new \Slim\App($settings);
$container = $app->getContainer();

if (empty(session_id())) {
  session_start();
}

// Set up dependencies
require $_SERVER['DOCUMENT_ROOT'] . '/src/dependencies.php';

// Register middleware
require $_SERVER['DOCUMENT_ROOT'] . '/src/middleware.php';

// Register pre routes
require $_SERVER['DOCUMENT_ROOT'] . '/src/preRoutes.php';

// Register routes
require $_SERVER['DOCUMENT_ROOT'] . '/src/routes.php';

// Run app
$app->run();
