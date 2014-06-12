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
*/

/**
 * @package Citrus
 * @author Rémi Cazalet <remi@caramia.fr>
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace Yvelines\Citrus\Orm\Doctrine\inc\type;

class OArray implements \Countable, \IteratorAggregate, \ArrayAccess
{	
    /**
     * @access private
     * @var array
     */
	private $_;
    /**
     * @access private
     * @var string
     */
	private $hash;

    /**
     * Constructor
     *
     * @param array     $elements         Content of the array.
     * @param boolean   $isChild          Define the first parent to hash content
     */
    public function __construct( array $elements = array(), $isChild = false ) {
		$this->_ = $this->toObject( $elements );
		if ( !$isChild ) $this->hash = md5(serialize($elements));
	}

    /**
     * Interface method
     *
     * @param mixed     $offset         offset
     *
     * @return bool
     */
    public function offsetExists($offset) {	
		return isset($this->_[$offset]); 
	}

    /**
     * Interface method
     *
     * @param mixed     $offset         offset
     *
     * @return mixed
     */
    public function offsetGet($offset) {
    	if ( !isset($this->_[$offset]) ) $this->_[$offset] = new OArray( array(), true );
        return $this->_[$offset];
	}
    
    /**
     * Interface method
     *
     * @param mixed     $offset         offset
     * @param mixed     $value          value
     *
     * @return bool
     */
	public function offsetSet($offset, $value) {
		if ( is_array($value) ) $value = new OArray( $value, true );
       	if ( is_null($offset) ) return $this->_[]=$value;
       	return $this->_[$offset] =$value;
    }

    /**
     * Interface method
     *
     * @param mixed     $offset         offset
     */
    public function offsetUnset($offset) {
        unset($this->_[$offset]);
	}

    /**
     * Interface method
     *
     * @return integer
     */	
	public function count() {
		return count($this->_);
	}

    /**
     * Interface method
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_);
    }

    /**
     * Convert OArray to array
     *
     * @return array
     */
    public function toArray() {
    	$copy = $this->_;
    	foreach ( $copy as $key => $var ) 
    		$copy[ $key ] = ( is_object( $var ) && get_class( $var ) == get_class() ) ? $var->toArray() : $var;
    	return $copy;
    }

    /**
     * Convert array to OArray
     *
     * @param array     $copy         array to transform
     *
     * @return OArray
     */
    public function toObject( array $copy ) {
    	foreach ( $copy as $key => $var ) 
    		$copy[ $key ] = is_array( $var ) ? new OArray( $var, true ) : $var;
    	return $copy;
    }

    /**
     * Indicate if the current object as changed
     *
     * @return bool
     */
    public function hasChanged() {
    	return md5(serialize($this->toArray())) !== $this->hash;
    }
}
