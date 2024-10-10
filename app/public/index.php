<?php
require '../vendor/autoload.php';


use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Include routes
require __DIR__ . '/../src/routes.php';

// Run the application
$app->run();
