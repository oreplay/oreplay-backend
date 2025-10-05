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
            '/events/{eventID}/stages/{stageID}/results/*',
            \Results\Controller\ResultsController::route()
        );
        $builder->connect(
            '/events/{eventID}/stages/{stageID}/fedo-stats/*',
            \Results\Controller\FedoStatsController::route()
        );
        $builder->connect(
            '/events/{eventID}/stages/{stageID}/stats/*',
            \Results\Controller\StatsController::route()
        );
        $builder->connect(
            '/events/{eventID}/uploads/*',
            \Results\Controller\UploadsController::route()
        );
        $builder->connect(
            '/events/{eventID}/rawUploads/*',
            \Results\Controller\RawUploadsController::route()
        );
        $builder->connect(
            '/events/{eventID}/stages/{stageID}/clubs/*',
            \Results\Controller\StageClubsController::route()
        );
        $builder->connect(
            '/events/{eventID}/stages/{stageID}/classes/*',
            \Results\Controller\StageClassesController::route()
        );
        $builder->connect('/events/{eventID}/stages/*', \Results\Controller\StagesController::route());
        $builder->connect('/events/{eventID}/tokens/*', \Results\Controller\EventTokensController::route());
        $builder->connect('/events/*', \Results\Controller\EventsController::route());
        $builder->connect('/organizers/*', \Results\Controller\OrganizersController::route());
    }
}
