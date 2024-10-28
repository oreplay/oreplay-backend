<?php

declare(strict_types = 1);

use App\Controller\ApiController;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->scope(ApiController::ROUTE_PREFIX, function (RouteBuilder $builder) {
        // Register scoped middleware for in scopes.
        //$builder->registerMiddleware('csrf', new CsrfProtectionMiddleware([
        //    'httponly' => true,
        //]));
        $builder->connect('/ping/*', \App\Controller\PingController::route());
        $builder->connect('/users/*', \App\Controller\UsersController::route());
        // TODO remove duplicated /authentication/*
        $builder->connect('/authentication/*', \RestOauth\Controller\OauthTokenController::route());
        $builder->connect('/me/*', \App\Controller\MeController::route());
        $builder->connect('/openapi/*', \App\Controller\SwaggerJsonController::route());
    });

    $routes->setRouteClass(DashedRoute::class);
    $routes->scope('/', function (RouteBuilder $builder) {
        $builder->connect('/api/v1', \App\Controller\RootController::route());
        $builder->connect('/api', \App\Controller\RootController::route());
        $builder->connect('/', \App\Controller\RootController::route());
        $builder->fallbacks();
    });
};
