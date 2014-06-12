<?php

namespace Yvelines\Citrus\View\Twig;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yvelines\Citrus\Response\ResponseCached;
use Yvelines\Citrus\Response\ResponseCachedJson;
use Yvelines\Citrus\Response\ResponseCachedJsonEnv;
use Yvelines\Citrus\Response\ResponseCachedCsv;
use Yvelines\Citrus\Response\ResponseCachedXls;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class TwigTemplateListener implements EventSubscriberInterface
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW     => 'onKernelView'
        );
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();


        $args       = $event->getControllerResult();
        $url = array_shift(explode('?', $request->getRequestUri()));
        $ext = substr($url, strrpos($url, '.')+1);
        $res = null;

        switch ( $ext )
        {
            case 'json':
                $response = ResponseCachedJsonEnv::get($request, $args);
                break;
            case 'csv':
                $response = ResponseCachedCsv::get($request, $args, basename($url));
                break;
            case 'xls':
                $response = ResponseCachedXls::get($request, $args, basename($url));
                break;
            default:
                $args['layout'] = $this->container->defaultLayout( $request );
                $args['app'] = $this->container;
                $tr = new TwigTemplateResolver( $this->container );
                $template = $tr->getTemplate($request);


                $template_engine = $this->container->get('template_engine');

                if (is_array($template))
                {
                    $arrTemplate = $template;
                    $found = false;
                    foreach ( $arrTemplate as $tpl ) {
                        if ($found) continue;
                        else if ( $template_engine->getLoader()->exists( $tpl.TwigTemplateEngine::$extension ) ) {
                            $found = true;
                            $template = $tpl;
                        }
                    }        
                }
                $template_engine->loadTemplate($template);
                $content = $template_engine->render($args);
                if ($request->isXmlHttpRequest()) 
                    $response = ResponseCachedJsonEnv::get($request, $content);
                else
                    $response = ResponseCached::get($request, $content);
                break;
        }

        $event->setResponse($response);
    }
}
