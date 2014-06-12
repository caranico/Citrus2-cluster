<?php

namespace Citrus\Cluster;

class Context extends \Pimple
{

    public function __construct($options = array())
    {

        $default_options = array(
            "dir"              => App::$path,
            "asset"            => dirname( App::$path ),
            "classes"          => App::$path . "/classes",
            "config"           => App::$path . "/Citrus/Config",
            "static"           => App::$path . "/Apps/static",
            "var"              => App::$path . "/vars",
            "cache"            => App::$path . "/vars/cache",
            "vendor"           => App::$path . "/../vendor",
            "libs"             => App::$path . "/../libs",
        );

        parent::__construct( array_merge($default_options, $options) );
    }
}
