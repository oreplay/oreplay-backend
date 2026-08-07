<?php

declare(strict_types = 1);

namespace HeadlessPdfs;

use Cake\Routing\RouteBuilder;
use RestApi\Lib\RestPlugin;

class HeadlessPdfsPlugin extends RestPlugin
{
    public static function fontsPath(): string
    {
        return dirname(__DIR__) . DS . 'resources' . DS . 'fonts';
    }

    protected function routeConnectors(RouteBuilder $builder): void
    {
        $builder->connect('/pdf/*', \HeadlessPdfs\Controller\PdfController::route());
    }
}
