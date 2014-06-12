<?php

namespace Yvelines\Citrus;


class TArray extends \ArrayObject {
	
	public function __construct( $array = array() ) {
		parent::__construct( $array );
	}
	
	public function __toString() {
		return implode( ',', $this->getArrayCopy() );
	}

	public function __call( $name, $args ) {
		if ( function_exists( $name ) ) {
			array_unshift( $args, $this->getArrayCopy() );
			$res = call_user_func_array( $name, $args );
			return is_array( $res ) ? new self( $res ) : $res;
		}
		return parent::__call( $name, $args );
	}

	public function contains( $elt ) {
		foreach ( $this as $item ) {
			if ( $item === $elt ) return true;
		}
		return false;
	}
	
	static public function indexedBy( $source, $key, $idValue = false ) {
		$target = array();
		foreach ( $source as $item ) {
			if ( !isset( $target[ $item[$key] ] ) )	$target[ $item[$key] ] = array( $item['id'] => $idValue ? $item[$idValue] : $item );
			else $target[ $item[$key] ][$item['id']] = $idValue ? $item[$idValue] : $item ;
		}
		return new self( $target );
	}
	static public function indexedByUnique( $source, $key, $idValue = false ) {
		$target = array();
		foreach ( $source as $item ) {
			if (is_array( $item ))
				$target[ $item[$key] ] = $idValue ? $item[$idValue] : $item;
			else if (is_object( $item )) 
				$target[ $item->$key ] = $idValue ? $item->$idValue : $item;
		}
		return new self( $target );
	}

	static public function indexedFor( $source, $key, $idValue = false ) {
		$target = array();
		foreach ( $source as $item ) {
			if ( !isset( $target[ $item[$key] ] ) )	$target[ $item[$key] ] = array( $idValue ? $item[$idValue] : $item );
			else $target[ $item[$key] ][] = $idValue ? $item[$idValue] : $item ;
		}
		return new self( $target );
	}
}



