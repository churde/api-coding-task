<?php

use App\Middleware\RateLimitMiddleware;
use Slim\Factory\AppFactory;
use DI\Container;
use App\Services\Cache;

require __DIR__ . '/../vendor/autoload.php';

// Create Container
$container = new Container();

// Set up dependencies
$container->set(Cache::class, function() {
    return new Cache();
});

$app = AppFactory::createFromContainer($container);

// Add routing middleware
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Include routes
require __DIR__ . '/../src/routes.php';

// Run the application
$app->run();
