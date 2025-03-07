<?php

declare(strict_types = 1);

namespace RadioRelay;

use Cake\Routing\RouteBuilder;
use RestApi\Lib\RestPlugin;

class RadioRelayPlugin extends RestPlugin
{
    protected function routeConnectors(RouteBuilder $builder): void
    {
        $builder->connect('/cpi/*', \RadioRelay\Controller\CpiServerController::route());
    }
}
