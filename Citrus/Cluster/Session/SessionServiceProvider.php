<?php

namespace Yvelines\Citrus\Session;
use Citrus\Core\System\ServiceProviderInterface;

class SessionServiceProvider implements ServiceProviderInterface {
    public function register($app) {
        $app['session'] = function ($app) {
            $path = $app->getContext('var'). '/session';
            if (!is_dir($path)) mkdir( $path, 0777, true );
            session_save_path ( $path );
            session_cache_limiter ( 'private_no_expire' );
            session_start();
            $app->retrieveUser();
        };

    }

    public function boot($app) {

    }

}