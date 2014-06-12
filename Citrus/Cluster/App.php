<?php

namespace Citrus\Cluster;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;

use Citrus\Core\Event\EventServiceProvider;
use Citrus\Core\Routing\RoutingServiceProvider;
use Citrus\Core\KernelServiceProvider;
use Citrus\Core\App as CitrusApp;

use Citrus\Cluster\Loader\JsonLoader;
use Citrus\Cluster\Controller\ControllerResolverServiceProvider;
use Citrus\Cluster\View;
use Citrus\Cluster\Session\SessionServiceProvider;
use Citrus\Cluster\Context;
use Citrus\Cluster\Orm;
use Citrus\Cluster\TArray;
use Citrus\Cluster\TObject;
use Citrus\Cluster\Response\ResponseCached;
use Citrus\Cluster\Response\ResponseCachedJsonEnv;

abstract class App extends CitrusApp
{
    const VERSION = '2.0a-DEV';

    protected $paths     = Array();

    protected $container = Array();

    protected $providers = Array();

    protected $context;

    public $user;

    public function __construct(Request $request, $debug = false)
    {
        $this['request'] = $request;
        $this['debug']   = $debug;
        $this->registerAutoload();
        $this->context = new Context(array(
            'config' => __DIR__ . '/Config'
        ));
        $this->loadConfig();
    }

    private function loadConfig()
    {
        $this['routes'] = JsonLoader::get( $this->context['config'] . '/routing.json');
        $config = JsonLoader::get( $this->context['config'] . '/config.json');
        $config['current_app'] = $config['default_app'];
        $this['config'] = $config;
    }

    public function run( $standAlone = false )
    {
        ob_start();

        $this->boot();
        /*
            Autodetection selon le hostname

        if ($this['request']->server->get('HTTP_HOST') != $this['config']['default_host'])
        {
            die('Modification non faites');
        }
        */

        if (!$standAlone)
        {
            if ($this['debug'] === false) {
                $this['http_cache'] = new HttpCache($this['kernel'], new Store( $this->context['cache'] ), null, array(
                    "debug" => $this['debug']
                ));
                $kernel = $this['http_cache'];
            } else {
                $kernel = $this['kernel'];
            }
            $response = $kernel->handle($this['request']);
            $potential_error = ob_get_clean();

            if ($potential_error) {
                return $this->loadError($this['request'], $potential_error)->send();
            }

            return $response->send();
        }
    }

    private function loadError( $request, $error )
    {
        $template = "@Apps" . $this['config']['default_app'] . "/" . $this['config']['layout']['error'];
        $template_engine = $this->get('template_engine');
        $template_engine->loadTemplate($template);

        $attributes = TObject::getProtected($request->attributes, 'parameters');
        $query = TObject::getProtected($request->query, 'parameters');
        $server = TObject::getProtected($request->server, 'parameters');

        $content = $template_engine->render( array(
            'layout'=> $this->defaultLayout( $request ),
            'url' => $request->getRequestUri(),
            'app' => $this,
            'error' => $error,
            'attributes' => print_r( $attributes, true ),
            'query' => print_r( $query, true ),
            'server' => print_r( $server, true )
        ) );

        if ($request->isXmlHttpRequest()) 
            return ResponseCachedJsonEnv::get($request, $content, 500);
        else
            return ResponseCached::get($request, $content, 500);
    }


    public function defaultLayout( Request $request )
    {
        if ($request->isXmlHttpRequest())
            return "@Apps" . $this['config']['default_app'] . "/" . $this['config']['layout']['default_ajax'];
        else
            return "@Apps" . $this['config']['default_app'] . "/" . $this['config']['layout']['default'];
    }

    public function registerCoreProviders()
    {

        $this->registerProvider(new SessionServiceProvider());
        $this->registerProvider(new EventServiceProvider());
        $this->registerProvider(new RoutingServiceProvider());
        $this->registerProvider(new ControllerResolverServiceProvider());
        $this->registerProvider(new KernelServiceProvider());
        $this->registerProvider(new Orm\Doctrine\OrmServiceProvider());
        $this->registerProvider(new View\Twig\TwigTemplateEngineServiceProvider());
    }

    public function getContext( $type = false ) {
        if (!$type) return $this->context;
        else return $this->context[ $type ];
    }

    private function registerAutoload() {
        spl_autoload_register( array( 'Citrus\Cluster', 'loader' ) );
    }

    public static function start( $debug = false ) {
        $app = new App( Request::createFromGlobals() , $debug );
        $app->run();
    }

    public static function standAlone( $debug = false ) {
        $app = new App( Request::createFromGlobals() , $debug );
        $app->run( true );
        return $app;
    }

    public static function loader( $class ) {
        $file = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
        if ( file_exists( MUFFIN_PATH . "/../$file.php" ) )
            include_once( MUFFIN_PATH . "/../$file.php" );
    }

    public function getDebug() {
        return '<div class="debug"></div>';
    }


    public function retrieveUser() {
        $this->user = \Main\users\User::retrieveUser();
    }

    public function isUserLogged() {
        return \Main\users\User::isLogged();
    }
}
