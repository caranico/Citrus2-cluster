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

namespace Yvelines\Citrus\Orm\Doctrine;

use \Doctrine\DBAL\Types\Type,
	\Doctrine\ORM\Mapping\ClassMetadataInfo,
	\Doctrine\Common\Collections\ArrayCollection;

Class Schema extends inc\Synapse {

    const ONE_TO_ONE 	= ClassMetadataInfo::ONE_TO_ONE;
    const MANY_TO_ONE 	= ClassMetadataInfo::MANY_TO_ONE;
    const ONE_TO_MANY 	= ClassMetadataInfo::ONE_TO_MANY;
    const MANY_TO_MANY 	= ClassMetadataInfo::MANY_TO_MANY;

    const SIMPLE_ARRAY 	= Type::SIMPLE_ARRAY;
    const BIGINT 		= Type::BIGINT;
    const BOOLEAN 		= Type::BOOLEAN;
    const DATETIME 		= Type::DATETIME;
    const DATETIMETZ 	= Type::DATETIMETZ;
    const DATE 			= Type::DATE;
    const TIME 			= Type::TIME;
    const DECIMAL 		= Type::DECIMAL;
    const INTEGER 		= Type::INTEGER;
    const OBJECT 		= Type::OBJECT;
    const SMALLINT 		= Type::SMALLINT;
    const STRING 		= Type::STRING;
    const TEXT 			= Type::TEXT;
    const BLOB 			= Type::BLOB;
    const FLOAT 		= Type::FLOAT;
    const GUID 			= Type::GUID;
    /* override */
    const TARRAY        = 'muffin_array';
    const JSON_ARRAY    = 'muffin_json_array';

    /**
     * FETCH_LAZY : Specifies that an association is to be fetched when it is first accessed.
     * FETCH_EAGER : Specifies that an association is to be fetched when the owner of the
     * association is fetched.
     * FETCH_EXTRA_LAZY : Specifies that an association is to be fetched lazy (on first access) and that
     * commands such as Collection#count, Collection#slice are issued directly against
     * the database if the collection is not yet initialized.
     */
    const FETCH_LAZY        = ClassMetadataInfo::FETCH_LAZY;
    const FETCH_EAGER       = ClassMetadataInfo::FETCH_EAGER;
    const FETCH_EXTRA_LAZY  = ClassMetadataInfo::FETCH_EXTRA_LAZY;

    /**
    Publics
    */


    /**
     * Control the validity and the format of a value for a property
     *
     * @param string        $propName
     * @param mixed         $propValue
     *
     * @return bool
     */
	public function control( $propName, $propValue = EMPTYDATA ) {
        $properties = $this->getProperties();
		if (in_array($propName, array_keys($properties))) {
            if ( $propValue != EMPTYDATA ) {
                if (isset($properties[ $propName ]['relation'])) {
                    $rel = $properties[ $propName ]['relation'];
                    switch ($rel['type'])
                    {
                        case self::MANY_TO_ONE :
                            if (is_object( $propValue ) && is_a( $propValue , '\Muffin\Citrus\Orm\ModelInterface'))
                                return '\\' . $propValue->getClass() == $rel['foreign']['class'];
                            else if (isset($rel['foreign']['class']))
                                return is_numeric( $propValue );
                            else return true;
                        break;
                        case self::ONE_TO_MANY :
                            return is_string(  $propValue  );
                        break;
                    }

                } else if (isset($properties[ $propName ]['definition'])) {
                    $def = $properties[ $propName ]['definition'];
                    switch ( $def['type'] ){
                        case Schema::BOOLEAN:       
                            return is_bool( $propValue ) || in_array( $propValue, '0', '1', 0, 1 );     
                        break;
                        case Schema::TARRAY:
                        case Schema::JSON_ARRAY:
                            return is_array( $propValue );                      
                        break;
                        case Schema::SIMPLE_ARRAY: 
                            // test tableau non associatif
                            return is_array( $propValue ) && array_keys($propValue) === range(0, count($propValue) - 1);
                        break;
                        # TODO
                        case Schema::DATETIME:
                        case Schema::DATE:
                        case Schema::TIME:
                        case Schema::DECIMAL:
                        case Schema::INTEGER:
                        case Schema::OBJECT:
                        case Schema::SMALLINT:
                        case Schema::STRING:
                        case Schema::BIGINT:
                        case Schema::TEXT:
                        case Schema::BLOB:
                        case Schema::FLOAT:
                        case Schema::GUID:
                            return true;
                        break;
                        // case Schema::DATETIMETZ: non suporté pour mysql
                    }
                }
            } 
            else return true;
		}
		else return false;
	}

    /**
     * Update a value for a property
     *
     * @param Entity        $obj
     * @param string        $propName
     * @param mixed         $propValue
     */
    public function update( $obj, $propName, $propValue ) {
        $properties = $this->getProperties();
        if (isset($properties[ $propName ]['relation'])) {
            $rel = $properties[ $propName ]['relation'];
            switch ($rel['type'])
            {
                case self::MANY_TO_ONE :
                    if (is_object( $propValue ) && is_a( $propValue , '\Yvelines\Citrus\Orm\ModelInterface'))
                        $obj->setData($propName,$propValue);
                    else if (isset($rel['foreign']['class']) && is_numeric( $propValue ))
                    {
                        $obj->setData($propName, call_user_func(array($rel['foreign']['class'], 'selectOne'), (int)$propValue));
                    }
                    else 
                        $obj->setData($propName, $propValue);
                break;
                case self::ONE_TO_MANY :

                    $lst = call_user_func(array($rel['foreign']['class'], 'selectAll'), 'WHERE self.id IN (' . $propValue . ')');

                    foreach( $obj->getData($propName) as $el )
                        if (!in_array( $el, $lst)) {
                            $obj->getData($propName)->removeElement( $el );
                            if (isset($rel['foreign']['property'])) 
                                $el->setData($rel['foreign']['property'], null);
                        }


                    foreach ( $lst as $el )
                        if (!$obj->getData($propName)->contains( $el ))
                            $obj->getData($propName)->add( $el );
                break;
            }

        } else if (isset($properties[ $propName ]['definition'])) {
            $def = $properties[ $propName ]['definition'];
            switch ( $def['type'] ){
                case Schema::BOOLEAN:
                    $obj->setData( $propName, $propValue );
                break;
                case Schema::TARRAY:
                case Schema::JSON_ARRAY:
                    $ref = $obj->getData( $propName )->toArray();
                    $obj->setData( $propName, new override\type\OArray( array_replace_recursive( $ref, $propValue )) );
                case Schema::SIMPLE_ARRAY:
                # TODO
                case Schema::STRING:
                    if (isset($def['enctype'])) $propValue = call_user_func($def['enctype'], $propValue);
                case Schema::DATETIME:
                case Schema::DATE:
                case Schema::TIME:
                case Schema::DECIMAL:
                case Schema::INTEGER:
                case Schema::OBJECT:
                case Schema::SMALLINT:
                case Schema::BIGINT:
                case Schema::TEXT:
                case Schema::BLOB:
                case Schema::FLOAT:
                case Schema::GUID:
                    $obj->setData( $propName, $propValue );
                break;
                // case Schema::DATETIMETZ: non supporté par mysql
            }
        }
    }

    /**
     * Return the primarys keys associate to the current schema
     *
     * @return array
     */
    public function getPrimaryKeys() {
        static $lst = null;
        if ( $lst === null ) {
            $infos = $this->getInformations();
            $props = $this->getProperties();
            /*
            if ( !is_array($props) ) $props = array();
            if ( is_array( $infos['extend'] ) ) {
                $sh = Adapter::getSchema( $infos['extend']['class'] );
                $props = array_merge( $sh->getProperties(), $props );
            }
            */
            foreach ( $props as $name=>$prop )
                if ( isset( $prop['definition']['primary'] ) ) $lst[]=$name;
        }
        return $lst;
    }

    /**
     * Initialize an Entity
     *
     * @param Entity        $obj
     */
    public function init( $obj ) {
        $obj->setData( $this->_getDefaultValues() );
        if ($this->control( 'datecreated' ) && !isset($obj->datecreated))
            $obj->datecreated = $obj->datemodified = new \DateTime();
    }

    /**
     * Hydrate an entity
     *
     * @param Entity        $obj
     * @param array        $args
     */
    public function hydrate( $obj, $args ) {
        $properties = $this->getProperties();
        foreach ( $args as $propName => $propValue )
            if ($this->control( $propName, $propValue )) 
                $this->update( $obj, $propName, $propValue );
    }

    /**
     * Save an entity
     *
     * @param Entity        $obj
     */
    public function save( $obj ) {
        $this->_bilaterralAutoAffect( $obj );
        $em = $obj->getEntitymanager();
        if ($this->control( 'datemodified' ) ) $obj->datemodified = new \DateTime();
        $em->persist($obj);
        $em->flush();
    }

    /**
     * Delete an entity
     *
     * @param Entity        $obj
     */
    public function delete( $obj ) {
        $em = $obj->getEntitymanager();
        $em->remove($obj);
        $em->flush();
    }

    /**
        Private
    */

    /**
     * Auto affect bilaterral relation (Doctrine dont do this)
     *
     * @param Entity        $obj
     */
    private function _bilaterralAutoAffect( $obj ) {
        $meta = $obj->getMetadata();
        foreach ( $meta->associationMappings as $ref => $prop ){
            if ( $prop['type'] == Schema::ONE_TO_ONE || $prop['type'] == Schema::MANY_TO_ONE) {
                $inversed = $prop['inversedBy'];
                $id = $obj->getData( $ref );
                if ($id && $inversed && isset( $id->$inversed )) 
                    $id->$inversed->add($obj);
            }
            else if ( $prop['type'] == Schema::ONE_TO_MANY || $prop['type'] == Schema::MANY_TO_MANY) {
                $inversed = $prop['mappedBy'];
                $id = $obj->getData( $ref );
                if ($id && $inversed) {
                    foreach ($id as $object) {
                        $object->setData($inversed, $obj);
                    }
                }
            }
        }
    }

    /**
     * Return defaults value for object associate to the current schema
     *
     * @return array
     */
    private function _getDefaultValues() {
        static $lst = array();
        $infos = $this->getInformations();
        foreach ( $this->getProperties() as $name=>$prop ) {
            if ( isset($prop['relation']) && ( $prop['relation']['type'] == Schema::ONE_TO_MANY || $prop['relation']['type'] == Schema::MANY_TO_MANY ) ) 
                $lst[ $name ] = new ArrayCollection();
            else {
                switch ( $prop['definition']['type'] ){
                    case Schema::TARRAY:
                    case Schema::JSON_ARRAY:
                        $lst[ $name ] = new override\type\OArray();
                    break;
                    case Schema::SIMPLE_ARRAY:
                        $lst[ $name ] = array();
                    break;
                    default : 
                        $lst[ $name ] = isset($prop['definition']['notnull']) && (bool) $prop['definition']['notnull'] && isset($prop['definition']['default']) ? $prop['definition']['default'] : null;
                    break;
                }
            }
        }
        return $lst;
    }

    /**
        Static
    */

    /**
     * Simulate class object, depend of schema definition
     *
     * @return bool
     */
    static function simuleObject( $class ) {
        $isController = false;
        if (preg_match ('/Controller$/', $class )) {
            $reel = preg_replace (array('#Yvelines\\\\Classes#', '/Controller$/'), '', $class );
            $isController = true;
        }
        else $reel = $class;
        $sh = Adapter::getSchema( $reel );

        if ($sh) {
            if ($isController) {
                $extend = '\Yvelines\Citrus\Controller\ObjectController';
                $namespace = substr($class, 0, strrpos($class,'\\'));
                $className = substr($class, strlen($namespace) + 1 );
            } else {
                $arrExt = $sh->getInformations('extend');
                $extend = is_array($arrExt) && isset($arrExt['class']) ? $arrExt['class'] : ( is_string( $arrExt ) ? $arrExt : '\Yvelines\Citrus\Orm\Doctrine\Model' );
                $namespace = substr($class, 0, strrpos($class,'\\'));
                $className = strlen($namespace) > 0 ? substr($class, strlen($namespace) + 1 ) : $class;
            }
            eval( ( $namespace ? "namespace $namespace;".chr(10) : '' ) . "class $className extends $extend {}" );
            return true;
        }
        return false;
    }

    /**
     * Transform entity to an array of datas
     *
     * @param Entity        $obj
     * @param boolean       $deep
     *
     * @return array
     */
    static function toArray( $obj, $deep = true ) {
        $meta = $obj->getMetadata();
        $res = array();
        foreach ( $meta->fieldNames as $ref => $field ){
            $id = $obj->getData( $ref );
            if (is_object( $id ) && get_class( $id ) == 'DateTime') $res[ $field ] = $id->format("d/m/Y H:i:s");
            else if (is_object( $id ) && get_class( $id ) == 'core\Citrus\doctrine\override\type\OArray') $res[ $field ] = $id->toArray();   
            else $res[ $field ] = $id;
        }
        foreach ( $meta->associationMappings as $ref => $prop ){
            $id = $obj->getData( $ref );
            if ( $prop['type'] == Schema::ONE_TO_ONE || $prop['type'] == Schema::MANY_TO_ONE) {
                if (count($prop['joinColumns']) > 0) {
                    $name = $prop['joinColumns'][0]['name'];
                    $refName = $prop['joinColumns'][0]['referencedColumnName'];
                    $res[ $name ] = $id ? $id->$refName : '';
                }
            }
            if ($deep) {
                if ( $prop['type'] == Schema::ONE_TO_ONE || $prop['type'] == Schema::MANY_TO_ONE)
                    $res[ $ref ] = $id ? $id->toArray( false ) : '';
                else if ( $prop['type'] == Schema::ONE_TO_MANY || $prop['type'] == Schema::MANY_TO_MANY) {
                    if ($id) foreach ( $id as $object ) $res[ $ref ][] = $object->toArray( false );
                    else $res[ $ref ] = array();
                }
            }
        }
        return $res;
    }

}