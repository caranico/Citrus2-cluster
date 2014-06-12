<?php

namespace Citrus\Cluster\Orm\Doctrine;
use Citrus\Core\System\ServiceProviderInterface;

class OrmServiceProvider implements ServiceProviderInterface {

    public function register($app) {
    	Adapter::setDebug( $app['debug'] );
        Adapter::$classPath = $app->getContext('classes');
        Adapter::$cachePath = $app->getContext('cache') . '/doctrine';

    	if (isset($app['config']['doctrine']) && is_array($app['config']['doctrine'])) {

    		spl_autoload_register( array( '\Citrus\Cluster\Orm\Doctrine\Adapter', 'autoload' ) );
    		foreach ($app['config']['doctrine'] as $id => $conf )
    			Adapter::addBdd( $id, $conf );
   		}
    }

    public function boot($app) {
    }
}