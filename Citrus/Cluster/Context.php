<?php

namespace Citrus\Cluster;

class Context extends \Pimple
{

    public function __construct($options = array())
    {
        $default_options = array(
            "dir"              => MUFFIN_PATH,
            "asset"            => dirname( MUFFIN_PATH ),
            "classes"          => MUFFIN_PATH . "/classes",
            "config"           => MUFFIN_PATH . "/Citrus/Config",
            "static"           => MUFFIN_PATH . "/Apps/static",
            "var"              => MUFFIN_PATH . "/vars",
            "cache"            => MUFFIN_PATH . "/vars/cache",
            "vendor"           => MUFFIN_PATH . "/../vendor",
            "libs"             => MUFFIN_PATH . "/../libs",
        );

        parent::__construct( array_merge($default_options, $options) );
    }
}
