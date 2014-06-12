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

use \Doctrine\Common\Persistence\Mapping;

class Reflection extends Mapping\RuntimeReflectionService {

    /**
     * Returns an accessible property (setAccessible(true)) or null.
     *
     * @param string $class
     * @param string $property
     *
     * @return \ReflectionProperty|null
     */
    public function getAccessibleProperty($class, $property)
    {
        $property = new ReflectionProperty($class, $property);
        $property->setAccessible(true);
        return $property;
    }
}