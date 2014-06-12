<?php

namespace Yvelines\Citrus\View\Twig;
use Yvelines\Citrus\View\TemplateEngineInterface;
use Assetic\AssetWriter;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\LazyAssetManager;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\Yui;
use Assetic\FilterManager;
use Yvelines\Citrus\TObject;

class TwigTemplateEngine extends \Twig_Environment implements TemplateEngineInterface {

    protected $template;
    protected $base;
    protected $assetFactory;
    static public $extension = ".twig.html";

    public function __construct($app) {
        $this->base = $app->getContext('dir');
        $loader = new \Twig_Loader_Filesystem( $this->base );
        foreach ($this->getTemplatesPath() as $id=>$path)
            $loader->addPath($path, $id);

        parent::__construct( $loader , array(
            'cache' => $app->getContext('cache') . '/twig',
            'auto_reload' => true
        ));
        $this->assetFactory = new AssetFactory( $app->getContext('dir') );
        $this->assetFactory->setDefaultOutput( $app->getContext('cache') .'/js-inline' );
        $ext = new AsseticExtension($this->assetFactory);
        $this->addExtension($ext);

        $fm = new FilterManager();
        $fm->set('yui_js', new Yui\JsCompressorFilter($app->getContext('vendor') .'/nervo/yuicompressor/yuicompressor.jar'));

        $this->assetFactory->setFilterManager($fm);

    }

    public function render($args) {
        if (!is_array( $args )) $args = array();
        $render =  $this->template->render($args);
        $loader = $this->getLoader();

        $cachePath = TObject::getProtected( $this->assetFactory, 'output');

        $am = new LazyAssetManager($this->assetFactory);
        $am->setLoader('twig', new TwigFormulaLoader($this));

        $arrList = TObject::getProtected($loader, 'cache');

        foreach ($arrList as $slug=>$file) {
            $resource = new TwigResource($loader, $slug);
            $am->addResource($resource, 'twig');
        }
        $writer = new AssetWriter($cachePath);
        $writer->writeManagerAssets($am);
        return $render;

    }

    public function loadTemplate($name) {
        $this->template = parent::loadTemplate( $name . static::$extension );
        return $this->template;
    }

    private function getTemplatesPath( $path = false, $res = array() ) {
        if (!$path) $path = $this->base;
        $lstDir = glob ( $path . '/*', GLOB_ONLYDIR );
        foreach ( $lstDir as $dir ) {
            if (basename($dir) == 'templates') {
                $lst = explode(DIRECTORY_SEPARATOR, substr( dirname($dir), strlen($this->base)));
                $id = implode('', array_map('ucfirst', $lst) );
                $res[$id] = $dir;
            }
            $res = $this->getTemplatesPath( $dir, $res );
        }
        return $res;
    }
}
