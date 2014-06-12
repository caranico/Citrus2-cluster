<?php
namespace Yvelines\Citrus\Orm;

abstract class ModelDispatcher {

    const MODEL 			= 'model';
    const SCHEMA 			= 'schema';

    static private $elements = array(
    	self::MODEL => '\Yvelines\Citrus\Orm\Doctrine\Model',
    	self::SCHEMA => '\Yvelines\Citrus\Orm\Doctrine\Schema',
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