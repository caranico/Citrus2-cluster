<?php

namespace Citrus\Cluster;

class TObject
{
    public static function getProtected( $object, $attr )
    {
        $reflectedClass = new \ReflectionClass($object);
        $property = $reflectedClass->getProperty($attr);
        $property->setAccessible(true);
        return $property->getValue($object);
    }


	static public function jsonize( $object ) 
	{
		$reg = self::_recurseFunctValide( $object );
        return stripslashes(
        	preg_replace(
        		array_keys($reg), 
        		array_values($reg), 
        		json_encode( $object )
        	)
        );
	}

	static private function _recurseFunctValide( &$object )
	{
		foreach ( $object as $key => &$value ) {
            if (is_array( $value ) || is_object( $value )) self::_recurseFunctValide( $value );
			else if (preg_match('/^function(.*)\}$/', $value ))
				$value = '!!!' . $value . '*!!';
		}
        return array(
            '/"[!]{3}([^\*]*)\*[!]{2}"/u'=>'$1',
            '/u([\da-fA-F]{4})/' => '&#x$1;'
        );
	}

}
