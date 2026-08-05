<?php

declare(strict_types = 1);

namespace Rankings;

use App\Lib\Rbac\ScopeRegistry;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;
use Rankings\Controller\RankingComputeClassController;
use Rankings\Controller\RankingComputeStageController;
use Rankings\Lib\Rbac\RankingsScopeContributor;
use Rankings\Lib\Rbac\StubRankingMembership;
use RestApi\Lib\RestPlugin;

class RankingsPlugin extends RestPlugin
{
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        ScopeRegistry::instance()->add(new RankingsScopeContributor(new StubRankingMembership()));
    }

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
        $builder->connect(
            '/rankings/{rankingID}/events/{eventID}/stages/{stageID}/stageOrders/{stageOrderID}/organizers/*',
            \Rankings\Controller\RankingOrganizersController::route()
        );
        $builder->connect(
            '/rankings/{rankingID}/classMerger/*',
            \Rankings\Controller\RankingClassMergerController::route()
        );
        $builder->connect(
            '/rankings/*',
            \Rankings\Controller\RankingSettingsController::route()
        );
    }
}
