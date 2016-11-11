<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
header('Content-Type: text/html; charset=utf8');
require __DIR__ . '/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// set timezone
date_default_timezone_set("UTC");

// Set up globals
require __DIR__ . '/../src/globals.php';

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';
require __DIR__ . '/../src/user-auth-routes.php';
require __DIR__ . '/../src/account.php';

// Get container
$container = $app->getContainer();
// Run app
$app->run();
