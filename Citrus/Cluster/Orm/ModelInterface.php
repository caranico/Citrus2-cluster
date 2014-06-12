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

namespace Citrus\Cluster\Orm;

Interface ModelInterface {

	/**
	Publics
	*/

    /**
     * return an array of datas
     * @param bool $deep Target object
     */
	public function toArray( $deep = true );

    /**
     * Save to bdd
     */
	public function save();

    /**
     * Delete to bdd
     */
	public function delete();

    /**
     * Hydrate current object with an array of arguments
     * @param array $args array of properties
     */
	public function hydrate( Array $args );
	
    /**
     * Return a JSON representation
     */
	public function toJSON();

    /**
     * Return a collection of object
     * @param string $where condition of the selection
     *
     * @return Collection
     */
 	static public function selectAll( $where = false, $param = false );

    /**
     * Return an integer
     * @param string $where condition of the selection
     *
     * @return integer
     */
    static public function count( $where = false, $param = false );

    /**
     * Delete an object
     * @param integer $id ident of the object to delete
     */
	static public function deleteOne( $id );
    
    /**
     * Delete somes objects
     * @param array $ids array of idents to delete
     */
    static public function deleteMultiple( Array $ids );

    /**
     * Select an object
     * @param integer $id ident of the object to select
     *
     * @return entity
     */
    static public function selectOne( $id );

    /**
     * Select an object
     * @param array $args array of condition
     *
     * @return entity
     */
    static public function selectOneBy( array $args );


    /**
     * Return a collection of object
     * @param array $args array of condition
     *
     * @return Collection
     */
    static public function selectAllBy( array $args );

    /**
     * Return the class schema
     */
    static public function getSchema();

    /**
     * Return the class view
     */
    static public function getView();
}