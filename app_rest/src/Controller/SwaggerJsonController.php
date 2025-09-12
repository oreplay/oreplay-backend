<?php

declare(strict_types = 1);

namespace App\Controller;

use RestApi\Lib\Swagger\SwaggerReader;

class SwaggerJsonController extends \RestApi\Controller\SwaggerJsonController
{
    public static function version(): string
    {
        return '0.3.6';
    }

    protected function getContent(SwaggerReader $reader, array $paths): array
    {
        $serverUrl = ($_SERVER['HTTP_HOST'] ?? '');
        if ($serverUrl) {
            $serverUrl = 'https://' . $serverUrl;
        }
        return [
            'openapi' => '3.0.0',
            'info' => [
                'version' => SwaggerJsonController::version(),
                'title' => 'O-replay - OpenAPI 3.0',
                'description' => 'O-replay Rest API',
                'termsOfService' => 'https://github.com/oreplay',
                'contact' => [
                    'email' => 'support@oreplay.es'
                ],
            ],
            'servers' => [
                ['url' => $serverUrl]
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
