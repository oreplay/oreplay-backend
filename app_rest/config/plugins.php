<?php

declare(strict_types = 1);

return [
    'Migrations',
    \Bake\BakePlugin::class => [
        'onlyDebug' => true,
    ],
    \RadioRelay\RadioRelayPlugin::class,
    \Rankings\RankingsPlugin::class,
    \RestOauth\RestOauthPlugin::class,
    \Results\ResultsPlugin::class,
];
