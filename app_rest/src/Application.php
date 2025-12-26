<?php

declare(strict_types = 1);

namespace App;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        /* bootstrapCli not needed after upgrade
        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        }
        //*/

        $pluginsToLoad = [
            'Migrations',
            \RadioRelay\RadioRelayPlugin::class,
            \Rankings\RankingsPlugin::class,
            \RestOauth\RestOauthPlugin::class,
            \Results\ResultsPlugin::class,
        ];
        foreach ($pluginsToLoad as $plugin) {
            if (!$this->getPlugins()->has($plugin)) {
                try {
                    $this->addPlugin($plugin);
                } catch (CakeException $e) {
                    if (!str_contains($e->getMessage(), 'is already loaded')) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers and render error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))
            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime')
            ]))
            ->add(new BodyParserMiddleware([
                'xml' => false,
                'json' => true,
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance. For that when
            // creating the middleware instance specify the cache config name by
            // using it's second constructor argument:
            // `new RoutingMiddleware($this, '_cake_routes_')`
            ->add(new RoutingMiddleware($this));

        return $middlewareQueue;
    }
}
