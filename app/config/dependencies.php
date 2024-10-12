<?php

use App\Services\Auth;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;
use App\Services\TokenManager;
use App\Services\PermissionChecker;

return [
    // ... other dependencies ...

    Auth::class => function ($container) {
        return new Auth(
            $container->get(AuthenticationService::class),
            $container->get(AuthorizationService::class)
        );
    },

    AuthenticationService::class => function ($container) {
        return new AuthenticationService(
            $container->get(TokenManager::class)
        );
    },

    AuthorizationService::class => function ($container) {
        return new AuthorizationService(
            $container->get(PermissionChecker::class)
        );
    },

    // ... other dependencies ...
];
