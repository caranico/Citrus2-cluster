<?php

namespace Citrus\Cluster\Controller;

use Symfony\Component\HttpKernel\Controller\ControllerResolver as SfControllerResolver,
    Symfony\Component\HttpFoundation\Request,
    Psr\Log\LoggerInterface,
    Citrus\Cluster\TArray;

class ControllerResolver extends SfControllerResolver
{

    protected $app;

    public function __construct(LoggerInterface $logger = null, $app)
    {
        parent::__construct($logger);
        $this->app = $app;
    }

    public function getController(Request $request) {
        $ctrl = $request->get('_controller', false);
        $app = array_shift( explode('/', $ctrl, 3) );
        $arrRight = (array) TArray::indexedByUnique($this->app['routes'], 'target');
        if (!isset($arrRight[$ctrl]['cookieFree']) && isset($this->app['session']))
            $this->app['session']->boot($this->app);
        if (!$this->app->isUserLogged() && !isset($arrRight[$ctrl]['safe'])) 
        {
            $request->attributes->set('_controller', $app . '/' . $this->app['config']['default_ctrl'] . '/'. $this->app['config']['layout']['login']);
        }
        else if ( $ctrl && false !== strpos($ctrl, 'AutoClasse') ) 
        {
            $url = array_shift(explode('?', $request->getRequestUri()));
            $totalUri = array_pop( split('/classes/', $url ) );
            @list( $className, $id, $action ) = split('/', $totalUri);
            if (!$action) $action='list';
            if (is_numeric($id))
            {
                $request->attributes->set('id', $id);
                if (!$action) $action='view';
            }
            else if (!empty($id)) $action= $id;
            if ($action) $action = array_shift(explode('.', $action));

            $className = ObjectController::getUnSlug($className);
            $ctrlName = ucfirst( $className ) . "Controller";
            $class = implode( '\\', Array(
                '',
                $this->app['config']['current_app'],
                $ctrlName
            ) );
            $request->attributes->set('_controller', $app . '/' . $class.'/'.$action);
            $request->attributes->set('_object', preg_replace('/Controller$/', '', $class));
            $request->attributes->set('_method', $action);
        }

        $res = parent::getController( $request );
        if ($res == array('Citrus\Core\Controller\ErrorController', 'doException'))
        {
            $request->attributes->set('_controller', $app . '/' . $this->app['config']['default_ctrl'] . '/'. $this->app['config']['layout']['404']);
            $request->attributes->remove('_object');
            $request->attributes->remove('_method');
            $res = parent::getController( $request );
        }

        return $res;
    }

    protected function createController($controller)
    {
        if (false !== strpos($controller, '::')) {
            list($class, $method) = explode('::', $controller, 2);
            if (!class_exists($class)) {
                throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
            }
            return Array(new $class(), $method);
        }

        if (false === strpos($controller, '/')) {
            throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
        }

        list($app, $class, $method) = explode('/', $controller, 3);

        if (substr($class,0,1) != '\\')
        {
            $class = implode( '\\', Array(
                $app,
                "Apps",
                $this->app['config']['current_app'],
                ucfirst( $class ) . "Controller"
            ) );
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }
        $method = "do" . ucfirst( $method );


        $inst = new $class( $class );

        if (is_a($inst, "Citrus\Core\Controller\ControllerInterface")) {
            $inst->setContainer($this->app);
        }

        return array($inst, $method);
    }
}
