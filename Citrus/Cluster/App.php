<?php

namespace Citrus\Cluster;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpCache\HttpCache,
    Symfony\Component\HttpKernel\HttpCache\Store,
    Citrus\Core\Event\EventServiceProvider,
    Citrus\Core\Routing\RoutingServiceProvider,
    Citrus\Core\KernelServiceProvider,
    Citrus\Core\App as CitrusApp,
    Citrus\Cluster\Loader\JsonLoader,
    Citrus\Cluster\Controller\ControllerResolverServiceProvider,
    Citrus\Cluster\View,
    Citrus\Cluster\Session\SessionServiceProvider,
    Citrus\Cluster\Context,
    Citrus\Cluster\Orm,
    Citrus\Cluster\TArray,
    Citrus\Cluster\TObject,
    Citrus\Cluster\Response\ResponseCached,
    Citrus\Cluster\Response\ResponseCachedJsonEnv;

class App extends CitrusApp
{
    const VERSION = '2.0a-DEV';

    static public $path;

    protected $container = Array();

    protected $providers = Array();

    protected $context;

    public $user;

    public function __construct(Request $request, $debug = false)
    {
        parent::__construct($request, $debug);
        $this->registerAutoload();
    }

    protected function loadConfig()
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

        $this['session'] = $this->registerProvider(new SessionServiceProvider());


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

    protected function registerAutoload() {
        spl_autoload_register( array( 'Citrus\Cluster\App', 'loader' ) );
    }

    public function init() {

    }

    public static function start( $debug = false ) {
        $class= get_called_class();
        $app = new $class( Request::createFromGlobals() , $debug );
        $app->init();
        $app->run();
    }

    public static function standAlone( $debug = false ) {
        $class= get_called_class();
        $app = new $class( Request::createFromGlobals() , $debug );
        $app->init();
        $app->run( true );
        return $app;
    }

    public static function loader( $class ) {
        $file = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
        if ( file_exists( App::$path . "/../$file.php" ) )
            include_once( App::$path . "/../$file.php" );
    }

    public function getDebug() {
        return '<div class="debug"></div>';
    }


    public function retrieveUser() {}

    public function isUserLogged() {}
}
