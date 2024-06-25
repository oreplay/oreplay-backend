<?php

declare(strict_types = 1);

namespace App\Controller;

use RestApi\Lib\Swagger\SwaggerReader;

class SwaggerJsonController extends \RestApi\Controller\SwaggerJsonController
{
    protected function getContent(SwaggerReader $reader, array $paths): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'version' => '0.'.date('W').'.'.date('dHi'),
                'title' => 'O-replay - OpenAPI 3.0',
                'description' => 'O-replay Rest API',
                'termsOfService' => 'https://github.com/oreplay',
                'contact' => [
                    'email' => 'support@oreplay.es'
                ],
            ],
            'servers' => [
                ['url' => $_SERVER['HTTP_HOST']]
            ],
            'tags' => [],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ]
                ],
            ],
        ];
    }
}
