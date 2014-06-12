<?php
namespace Citrus\Cluster\Controller;

use Citrus\Core\Controller\ControllerResolverServiceProvider as CitrusControllerResolverServiceProvider,
	Citrus\Cluster\Controller\ControllerResolver;

class ControllerResolverServiceProvider extends CitrusControllerResolverServiceProvider {
    public function register($app)
    {
        $app['controller_resolver'] = function ($app) {
            return new ControllerResolver(null, $app);
        };
    }

    public function boot($app)
    {}
}
