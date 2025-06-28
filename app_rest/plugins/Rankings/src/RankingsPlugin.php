<?php

declare(strict_types = 1);

namespace Rankings;

use Cake\Routing\RouteBuilder;
use Rankings\Controller\RankingComputeClassController;
use Rankings\Controller\RankingComputeStageController;
use RestApi\Lib\RestPlugin;

class RankingsPlugin extends RestPlugin
{
    protected function routeConnectors(RouteBuilder $builder): void
    {
        $builder->connect(
            '/rankings/{rankingID}/events/{eventID}/stages/{stageID}/classes/{classID}/compute/*',
            RankingComputeClassController::route()
        );
        $builder->connect(
            '/rankings/{rankingID}/events/{eventID}/stages/{stageID}/compute/*',
            RankingComputeStageController::route()
        );
        $builder->connect(
            '/rankings/{rankingID}/events/{eventID}/stages/{stageID}/runnerResults/*',
            \Rankings\Controller\RankingRunnerManagementController::route()
        );
    }
}
