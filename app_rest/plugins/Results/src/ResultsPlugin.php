<?php
declare(strict_types=1);

namespace Results;

use App\Controller\ApiController;
use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Results\Controller\EventsController;

class ResultsPlugin extends BasePlugin
{
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            $this->name,
            ['path' => ApiController::ROUTE_PREFIX],
            function (RouteBuilder $builder) {
                $builder->connect('/events/*', EventsController::route());
            }
        );
        parent::routes($routes);
    }
}
