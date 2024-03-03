<?php

declare(strict_types = 1);

namespace Results;

use App\Controller\ApiController;
use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class ResultsPlugin extends BasePlugin
{
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            $this->name,
            ['path' => ApiController::ROUTE_PREFIX],
            function (RouteBuilder $builder) {
                $builder->connect(
                    '/events/{eventID}/stages/{stageID}/runners/*',
                    \Results\Controller\RunnersController::route()
                );
                $builder->connect(
                    '/events/{eventID}/stages/{stageID}/uploads/*',
                    \Results\Controller\UploadsController::route()
                );
                $builder->connect('/events/{eventID}/stages/*', \Results\Controller\StagesController::route());
                $builder->connect('/events/*', \Results\Controller\EventsController::route());
            }
        );
        parent::routes($routes);
    }
}
