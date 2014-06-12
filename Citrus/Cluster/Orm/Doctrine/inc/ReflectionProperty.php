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

namespace Yvelines\Citrus\Orm\Doctrine\inc;

class ReflectionProperty {
	/**
     * @access private
     * @var string
     */
	private $nameProp;

    /**
     * Constructor
     *
     * @param string $class className
     * @param string $property
     */
	public function __construct($class, $property)
    {
    	$this->nameProp = $property;
    }

    /**
     * Setter
     *
     * @param Entity $object
     * @param mixed $value
     */
	public function setValue( $object , $value ) {

        if (is_a($object, 'Doctrine\Common\Proxy\Proxy')) {
            $class = get_class($object);
            $class::$lazyPropertiesDefaults[$this->nameProp]=$value;
        }
		$object->setData( $this->nameProp, $value );
	}

    /**
     * Acessibility modifier, empty but be call ( no impact on the new structure )
     *
     * @param bool $accessible
     */
	public function setAccessible( $accessible ) {
	}

    /**
     * Getter
     *
     * @param Entity $object
     *
     * @return mixed
     */
	public function getValue( $object ) {
		return $object->getData( $this->nameProp );
	}
}