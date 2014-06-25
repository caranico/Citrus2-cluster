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

namespace Citrus\Cluster\Orm\Doctrine;

use Doctrine\ORM,
    Citrus\Cluster\Orm\Doctrine\Adapter,
    Citrus\Cluster\Orm\ModelInterface;

Class Model extends inc\Synapse implements ModelInterface {

	/**
	magics
	*/

    /**
     * Constructor.
     */
	public function __construct( $params = array() ) {
		parent::__construct( $params );
		self::getSchema()->init( $this );
	}

    /**
     * Magic getter
     * @param string $prop propertie name
     */
	public function __get( $prop ) { 
        $x = new \ReflectionClass( self::getClass() );
        if ( $x->hasProperty( $prop ) ) return $this->$prop;
		else if (self::getSchema()->control( $prop )) 
			return parent::__get( $prop );
	}

    /**
     * Magic setter
     * @param string $prop propertie name
     * @param mixed $value propertie value
     */
	public function __set( $prop, $value ) { 
        $x = new \ReflectionClass( self::getClass() );
        if ( $x->hasProperty( $prop ) ) $this->$prop = $value;
        else if (self::getSchema()->control( $prop, $value )) 
			self::getSchema()->update( $this, $prop, $value ); 
	}

    /**
     * Magic toString
     */
    public function __toString() {
        return $this->getView()->execIdent( $this );
    }

	/**
	Publics
	*/

    /**
     * return an array of datas
     * @param bool $deep Target object
     */
	public function toArray( $deep = true ) {
		return Schema::toArray( $this, $deep );
	}

    /**
     * Save to bdd
     */
	public function save() {
		self::getSchema()->save( $this );
	}

    /**
     * Delete to bdd
     */
	public function delete() {
		self::getSchema()->delete( $this );
	}

    /**
     * Hydrate current object with an array of arguments
     * @param array $args array of properties
     */
	public function hydrate( Array $args ) {
		return self::getSchema()->hydrate( $this, $args );
	}
	
    /**
     * Return a JSON representation
     */
	public function toJSON() {
		return json_encode( $this->toArray( false ) );
	}

    /**
        Static
    */

    /**
     * Return the doctrine entity manager
     */
	static public function getEntitymanager() {
		return Adapter::getInstance()->getEntitymanager();
	}

    /**
     * Return the class schema
     */
    static public function getSchema() {
        return Adapter::getSchema( self::getClass() );
    }

    /**
     * Return the class view
     */
    static public function getView() {
        return Adapter::getView( self::getClass() );
    }

    /**
     * Return the class metadatas
     */
	static public function getMetadata() {
        if (!is_object(self::getEntitymanager())) debug_print_backtrace();
		return self::getEntitymanager()->getClassMetadata(get_called_class());
	}

    /**
     * Return the class name
     */
	static public function getClass() {
		return self::getMetadata()->name;
	}

    /**
     * Return a collection of object
     * @param string $where condition of the selection
     *
     * @return Collection
     */
 	static public function selectAll( $where = false, $param = false ) {
        $query = self::getEntitymanager()->createQuery( 'SELECT self FROM ' . self::getClass() . ' self ' . ( $where ? $where : '' ) );
        if ($param) {
            if (isset($param['offset'])) $query->setFirstResult( (int) $param['offset'] );
            if (isset($param['limit'])) $query->setMaxResults( (int) $param['limit'] );
        }
		return $query->getResult();
 	}

    /**
     * Return an integer
     * @param string $where condition of the selection
     *
     * @return integer
     */
    static public function count( $where = false, $param = false ) {
		$query = self::getEntitymanager()->createQuery( 'SELECT self FROM ' . self::getClass() . ' self ' . ( $where ? $where : '' ) );
        if ($param) {
            if (isset($param['offset'])) $query->setFirstResult( (int) $param['offset'] );
            if (isset($param['limit'])) $query->setMaxResults( (int) $param['limit'] );
        }
		try { $count = $query->getSingleScalarResult(); }
		catch (ORM\NonUniqueResultException $e) 	{ $count = count( $query->getResult() ); }
		catch (ORM\NoResultException $e) 			{ $count = 0; }
		return $count;
    }

    /**
     * Delete an object
     * @param integer $id ident of the object to delete
     */
	static public function deleteOne( $id ) {
		self::selectOne( $id )->delete();
    }
    
    /**
     * Delete somes objects
     * @param array $ids array of idents to delete
     */
    static public function deleteMultiple( Array $ids ) {
    	$lst = self::selectAll( 'WHERE self.id IN ( ' . implode(',', $ids) . ' )' );
    	foreach ($lst as $obj) $obj->delete();
    }

    /**
     * Select an object
     * @param integer $id ident of the object to select
     *
     * @return entity
     */
    static public function selectOne( $id ) {
        if ( count( self::getSchema()->getPrimaryKeys() ) == 1 && is_int( $id ) )
            return self::getEntitymanager()->find( self::getClass() , $id );
        return null;
    }

    /**
     * Select an object
     * @param array $args array of condition
     *
     * @return entity
     */
    static public function selectOneBy( array $args ) {
        return self::getEntitymanager()->getRepository( self::getClass() )->findOneBy( $args );
    }


    /**
     * Return a collection of object
     * @param array $args array of condition
     *
     * @return Collection
     */
    static public function selectAllBy( array $args ) {
        return self::getEntitymanager()->getRepository( self::getClass() )->findBy( $args );
    }
}