<?php

namespace App\Controller;

use App\Lib\FullBaseUrl;
use App\Lib\I18n\LegacyI18n;
use Cake\Cache\Cache;
use Cake\Routing\Router;

class RootController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getMandatoryParams(): array
    {
        return [];
    }

    protected function getList()
    {
        Cache::write('testingCachePing', 'hello-cache-ping');
        $title = 'cake-rest-ct';
        $toRet = [
            'title' => $title,
            'lang' => LegacyI18n::getLocale(),
            'version' => '',
            '_links' => [
                'self' => [
                    'title' => $title,
                    'href' => explode('?', Router::url(null, true))[0]
                ],
                'events' => [
                    'href' => FullBaseUrl::host() . ApiController::ROUTE_PREFIX . '/events/'
                ],
                'checks' => [
                    'href' => FullBaseUrl::host() . ApiController::ROUTE_PREFIX . '/ping/pong/'
                ],
                'documentation' => [
                    'title' => 'API docs',
                    'href' => 'https://github.com/oreplay/oreplay'
                ]
            ],
        ];
        $this->return = $toRet;
    }
}
