<?php
namespace Citrus\Cluster\Orm;

abstract class ModelDispatcher {

    const MODEL 			= 'model';
    const SCHEMA 			= 'schema';

    static private $elements = array(
    	self::MODEL => '\Citrus\Cluster\Orm\Doctrine\Model',
    	self::SCHEMA => '\Citrus\Cluster\Orm\Doctrine\Schema',
    );

    static public function add( $ident, $args ) 
    {
        self::$elements[ $ident ] = $args;
    }

    static public function get( $ident ) 
    {
        return self::$elements[ $ident ];
    }

}