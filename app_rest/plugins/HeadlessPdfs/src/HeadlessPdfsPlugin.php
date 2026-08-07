<?php

declare(strict_types = 1);

namespace HeadlessPdfs;

use Cake\Routing\RouteBuilder;
use RestApi\Lib\RestPlugin;

class HeadlessPdfsPlugin extends RestPlugin
{
    /**
     * Where the font data generated for tc-lib-pdf-font lives.
     *
     * Kept next to the code that needs it, rather than in the application's resources, so
     * that removing this plugin takes its several megabytes of font data with it.
     */
    public static function fontsPath(): string
    {
        return dirname(__DIR__) . DS . 'resources' . DS . 'fonts';
    }

    protected function routeConnectors(RouteBuilder $builder): void
    {
        $builder->connect('/pdf/*', \HeadlessPdfs\Controller\PdfController::route());
    }
}
