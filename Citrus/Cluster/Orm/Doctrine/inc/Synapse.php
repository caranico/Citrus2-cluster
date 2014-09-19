<?php
/*
.---------------------------------------------------------------------------.
|  Software: Citrus PHP Framework                                           |
|   Version: 1.0                                                            |
|   Contact: devs@citrus-project.net                                        |
|      Info: http://citrus-project.net                                      |
|   Support: http://citrus-project.net/documentation/                       |
| ------------------------------------------------------------------------- |
|   Authors: Rémi Cazalet                                                   |
|          : Nicolas Mouret                                                 |
|   Founder: Studio Caramia                                                 |
|  Copyright (c) 2008-2012, Studio Caramia. All Rights Reserved.            |
| ------------------------------------------------------------------------- |
|   For the full copyright and license information, please view the LICENSE |
|   file that was distributed with this source code.                        |
'---------------------------------------------------------------------------'
This is an abstract-only class of synaptic object which can be modeled as you whish

	$obj = new Synapse();

virtual methods :
	$obj->set{name}( $value )  			- To set a property
	$obj->set{name}( $prop, $value )  	- To set a property's associative array
	$obj->add{name}( $value )		  	- To add a value to an indexed Array
	$obj->add{name}( $prop, $value )	- To add a value to an indexed Array in a associative array
	$obj->remove{name}( $value )		- To del a value to an indexed Array
	$obj->remove{name}( $prop, $value )	- To del a value to an indexed Array in a associative array
	$obj->get{name}()					- To get a property	
	$obj->get{name}( $prop, $value )  	- To get a property's associative array
	$obj->exec{name}( $args )			- To execute a closure value in an indexed Array

virtual properties (datas) :
	$obj->property = 'value';  	is equivalent to  $obj->setData('property', 'value'); but method setData is forbidden
	echo $obj->property;  		is equivalent to  echo $obj->getData('property'); but method getData is forbidden

other function :
	isset( $obj->property );
	unset( $obj->property );


The constructor can fill all properties of the object :

	$obj = new Synapse( array(
		"string" => "my string",
		"array" => array(
			"content" => "Cool"
		),
		"function" => function ( $param ) {
			return $param . " Done";
		}
	));
	
	$obj->getString(); 					return "my string"
	$obj->getArray(); 					return array( "content" => "Cool" )
	$obj->getArray('content');			return "Cool"
	$obj->getFunction();				return Closure function () { return "Done"; }
	$obj->execFunction('test');			return "test Done"

Setter of a closure :

	$obj->setNewfunction( function ( $prop, $value ) use ( $self ) {
		$self->setData( $prop, $value );
		return "Done";
	});

Execution of a closure :
	$obj->execNewfunction( 'propName', 'propValue' );		return "Done";
	$obj->getData('propName');								return "PropValue";


Other

	$obj();								return the integral content of the object

*/

/**
 * @package Citrus
 * @author Rémi Cazalet <remi@caramia.fr>
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace Citrus\Cluster\Orm\Doctrine\inc;

define('EMPTYDATA',  md5(date("U")) ) ;

abstract class Synapse {
    private $content;

	/**
	magics
	*/

	public function __toString() { return get_called_class(); }

	public function __construct( $params = array() ) { $this->content = $params; }

	public function __destruct() { unset($this->content); }

	public function __sleep() { return array('content'); }

	public function __wakeup() { }

	public function __call($name, Array $arguments) {
		$x = new \ReflectionClass( get_called_class() );
		if ( $x->hasMethod( $name ) ) return call_user_func_array( array( $this, $name ), $arguments );
		// getter / setter / exec / add / remove auto
		else if (preg_match('/^\_?(set|get|exec|add|remove)([A-Z][A-Za-z0-9]*)/', $name, $matches)) {
			switch ( $matches[1] ) {
				case 'set' : 
					return call_user_func_array( array( $this, '_set'), array( strtolower($matches[2]), $arguments[0], count($arguments) > 1 ? $arguments[1] : EMPTYDATA ) );
					break;
				case 'get' : 
					return call_user_func_array( array( $this, '_get'), array( strtolower($matches[2]), count($arguments) > 0 ? $arguments[0] : false ) );
					break;
				case 'add' :
					return call_user_func_array( array( $this, '_add'), array( strtolower($matches[2]), $arguments[0], count($arguments) > 1 ? $arguments[1] : EMPTYDATA ) );
					break; 
				case 'remove' :
					return call_user_func_array( array( $this, '_remove'), array( strtolower($matches[2]), $arguments[0], count($arguments) > 1 ? $arguments[1] : EMPTYDATA ) );
					break;
				case 'exec' :
					$closure = 	call_user_func_array( array( $this, '_get'), array( strtolower($matches[2]) ) );
					if ( $closure && get_class( $closure ) == 'Closure') return call_user_func_array( $closure, $arguments );
					break; 
			}
		}
	}

	public static function __callStatic($name, $arguments) {
		$x = new \ReflectionClass( get_called_class() );
		if ( $x->hasMethod( $name ) ) return call_user_func_array( array( get_called_class(), $name ), $arguments );
	}

	public function __get( $prop ) { return $this->_get( 'data', $prop ); }

	public function __set( $prop, $value ) { $this->_set( 'data', $prop, $value ); }

	public function __isset( $prop ) { return $this->_isset( 'data', $prop ); }

	public function __unset( $prop ) { $this->_unset( 'data', $prop ); }

	public function __invoke() {
		return $this->content;
	}

	/**
	privates
	*/

	private function _get( $type, $prop = false ) {
		return ( $prop && is_array($this->content[ $type ]) && isset( $this->content[ $type ][ $prop ] ) ? $this->content[ $type ][ $prop ] : ( !$prop && isset( $this->content[ $type ] ) ? $this->content[ $type ] : NULL ) );
	}

	private function _set( $type, $propOrValue , $value = EMPTYDATA ) {
		$self = $this;
		if ($value !== EMPTYDATA) $this->content[ $type ][ $propOrValue ] = $value;
		else $this->content[ $type ] = $propOrValue;
	}

	private function _add( $type, $propOrValue , $value = EMPTYDATA ) {
		if ($value !== EMPTYDATA) $this->content[ $type ][ $propOrValue ][] = $value;
		else $this->content[ $type ][] = $propOrValue;
	}

	private function _remove( $type, $propOrValue , $value = EMPTYDATA ) {
		$self = $this;
		if ($value !== EMPTYDATA) 
			if (is_array($this->content[ $type ][ $propOrValue ]) && in_array($value, $this->content[ $type ][ $propOrValue ])) 
				array_splice( $this->content[ $type ][ $propOrValue ], array_search($value, $this->content[ $type ][ $propOrValue ]) , 1);
		else 
			if (is_array($this->content[ $type ]) && in_array($propOrValue, $this->content[ $type ])) 
				array_splice( $this->content[ $type ], array_search($propOrValue, $this->content[ $type ]), 1);
	}

	private function _isset( $type, $prop = false ) {
		return ( ( $prop && isset( $this->content[ $type ][ $prop ] )) || ( !$prop && isset( $this->content[ $type ] )) );
	}

	private function _unset( $type, $prop = false ) {
		if ($prop) unset( $this->content[ $type ][ $prop ] );
		else unset( $this->content[ $type ] );
	}
}



?>