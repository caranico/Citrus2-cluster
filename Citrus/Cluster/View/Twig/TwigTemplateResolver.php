<?php

namespace Yvelines\Citrus\View\Twig;

class TwigTemplateResolver {
    protected $container;

    public function __construct( $container ) {
    	$this->container = $container;
    }

    public function getTemplate($request) {
        if (!$controller = $request->attributes->get('_controller')) {
            return false;
        }
        if ($obj = $request->get('_object', false) ) {
            $method = $request->get('_method', false);
            return array( 
                '@Classes' . str_replace('\\', '', $obj).'/'.$method ,
                '@CitrusController/' . $method
            );

        } else {
            $type = 'Apps';
            list($app, $class, $method) = explode('/', $controller, 3);
            return '@' . $type . $this->container['config']['current_app'] . '/' . $method;
        }
    }
}
