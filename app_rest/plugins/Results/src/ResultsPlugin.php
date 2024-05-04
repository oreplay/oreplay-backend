<?php

declare(strict_types = 1);

namespace Results;

use Cake\Routing\RouteBuilder;
use RestApi\Lib\RestPlugin;

class ResultsPlugin extends RestPlugin
{
    protected function routeConnectors(RouteBuilder $builder): void
    {
        $builder->connect(
            '/events/{eventID}/stages/{stageID}/runners/*',
            \Results\Controller\RunnersController::route()
        );
        $builder->connect(
            '/events/{eventID}/uploads/*',
            \Results\Controller\UploadsController::route()
        );
        $builder->connect(
            '/events/{eventID}/stages/{stageID}/classes/*',
            \Results\Controller\ClassesController::route()
        );
        $builder->connect('/events/{eventID}/stages/*', \Results\Controller\StagesController::route());
        $builder->connect('/events/{eventID}/tokens/*', \Results\Controller\EventTokensController::route());
        $builder->connect('/events/*', \Results\Controller\EventsController::route());
    }
}
