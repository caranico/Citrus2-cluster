<?php

namespace Citrus\Cluster\View\Twig;
use Citrus\Core\System\ServiceProviderInterface;

class TwigTemplateEngineServiceProvider implements ServiceProviderInterface {
    public function register($app) {
        $app['template_engine'] = function() {
            return new TwigTemplateEngine($app);
        };
    }

    public function boot($app) {
        $app['event_dispatcher']->addSubscriber(new TwigTemplateListener($app));
    }

}