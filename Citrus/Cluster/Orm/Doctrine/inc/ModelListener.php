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

use \Doctrine\ORM\Event,
    \Yvelines\Citrus\Orm\Doctrine\Schema;

class ModelListener
{
    /**
     * preUpdate Event
     * @param  \Doctrine\ORM\Event\PreUpdateEventArgs $event Doctrine event
     */
    public function preUpdate(Event\PreUpdateEventArgs $event) { 
        $this->_isOArrayModified( $event->getEntity() );
    }

    /**
    Private
    */

    /**
     * OArray is the array replacement of TARRAY and JSON_ARRAY type.
     * This object must be interrogate to know if it's value changed
     * @param Entity $obj Doctrine object
     */
    private function _isOArrayModified( $obj ) {
        foreach ( $obj->getMetadata()->fieldMappings as $ref => $prop ){
            switch ( $prop['type'] ) {
                case Schema::TARRAY:
                case Schema::JSON_ARRAY:
                    $value = $obj->getData( $ref );
                    if ( is_object( $value ) && get_class( $value ) == 'Yvelines\Citrus\Orm\Doctrine\inc\type\OArray' && $value->hasChanged() ) 
                        $obj->getEntitymanager()->getUnitOfWork()->propertyChanged( $obj, $ref, null, $obj->getData( $ref ) );
                break;
            }
        }
    }

}