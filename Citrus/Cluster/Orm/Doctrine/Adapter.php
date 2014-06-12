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

use Doctrine\ORM,
	Doctrine\DBAL\Types\Type,
    Doctrine\DBAL\Logging\DebugStack,
	Doctrine\Common\EventManager,
    Doctrine\ORM\Mapping\Driver\DriverChain,
    Doctrine\Common\Proxy\AbstractProxyFactory;

define( 'DOCTRINE_ADAPTER_PATH' , __DIR__ );

Class Adapter extends inc\Synapse {

    /**
     * @access private 
     * @var array
     */
    static private $_schemas = null;

    /**
     * @access private 
     * @var bool
     */
    static private $_debug = false;

    /**
     * @access private 
     * @var array
     */
    static private $_views = null;

    /**
     * @access private 
     * @var array
     */
    static private $_bdds = array();


    /**
     * @access private 
     * @var array
     */
    static public $classPath = null;

    /**
     * @access private 
     * @var array
     */
    static public $cachePath = null;
    
	/**
	magics
	*/

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
		$this->connect();
    }

    /**
    Publics
    */

    /**
     * BDD Connector, return Adapter
     * @param  string $idBdd ident of an occurence of constant DOCTRINE_BDD
     */
    public function connect( $idBdd = false ) {
        $chain = new DriverChain();
    	$bdds = self::$_bdds;
    	$lst = $this->getAllEntitymanagers();
        $arrk = array_keys($bdds);
        if (!$idBdd) $idBdd = array_shift( $arrk );
    	if (!isset( $lst[ $idBdd ] )) {
			$config = ORM\Tools\Setup::createConfiguration( self::$_debug );

            $config->setProxyDir(self::$cachePath);
            //$config->setProxyNamespace( isset( $bdds[ $idBdd ]['type'] ) ? $bdds[ $idBdd ]['type'] : 'Main');
            $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
            $chain->addDriver(new inc\SchemaDriver( self::$classPath ), isset( $bdds[ $idBdd ]['type'] ) ? $bdds[ $idBdd ]['type'] : 'Main');
            //$config->setMetadataDriverImpl( new inc\SchemaDriver( self::$classPath ) );
            $config->setMetadataDriverImpl( $chain );
            if (self::$_debug)
                $config->setSQLLogger(new DebugStack());
			$eventManager = new EventManager();
			$eventManager->addEventListener(array( ORM\Events::preUpdate ), new inc\ModelListener());
			$em = ORM\EntityManager::create( $bdds[ $idBdd ] , $config, $eventManager);
			$em->getMetadataFactory()->setReflectionService( new inc\Reflection() );




			$lst[ $idBdd ] = $em;
    		$this->setAllEntitymanagers( $lst );
    		$this->_addType( $em->getConnection(), Schema::TARRAY , '\Yvelines\Citrus\Orm\Doctrine\inc\type\ArrayType');
    		$this->_addType( $em->getConnection(), Schema::JSON_ARRAY , '\Yvelines\Citrus\Orm\Doctrine\inc\type\JsonArrayType');
    	}
        $this->setCurrent( $idBdd );
        $this->setEntitymanager( $lst[ $idBdd ] );
		$this->setConnection( $lst[ $idBdd ]->getConnection() );
		return $this;
    }



    public function generateSchemas( $prefixe = false ) {
        inc\Generator::loadDatabase( $prefixe );
    }

    /**
        Static
    */
    
    /**
     * Singlotron, return Adapter instance
     */
    static function getInstance() {
        static $instance = null;
        if (is_null( $instance )) $instance = new Adapter();
        return $instance;
    }

    /**
     * autoload
     */
    static function autoload( $class ) {
		return (!self::getClass( $class ) && !Schema::simuleObject( $class ));
	}

    /**
     * Schema getter, return a schema object
     * @param  string $class className
     */
    static function getSchema( $class ) {
        $reel = self::getCacheId( $class );
        if (!isset( self::$_schemas[ $reel ])) {
            $tmp = explode( '\\' , $class );
            $name = end($tmp);
            $arr = self::getFile( self::$classPath . "/" . str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . ".data.php", false );
            if (!$arr) $arr = self::getFile( self::$classPath . "/" . strtolower(str_replace( '\\', DIRECTORY_SEPARATOR, $class )) . "/$name.data.php", false );
            if (!$arr) $arr = Enum::find( $class ); // enumeration
            if (!$arr) return false;
            self::$_schemas[ $reel ] = new Schema( $arr );
        }
        return self::$_schemas[ $reel ];
    }

    /**
     * Schema getter, return a schema object
     * @param  string $class className
     */
    static function getView( $class ) {
        $reel = self::getCacheId( $class );
        if (!isset( self::$_views[ $reel ])) {
            $tmp = explode( '\\' , $class );
            $name = end($tmp);
            $arr = self::getFile( self::$classPath . "/" . strtolower(str_replace( '\\', DIRECTORY_SEPARATOR, $class )) . "/$name.view.php", false );
            $arr[ 'class' ] = $class;
            if (!$arr) return false;
            self::$_views[ $reel ] = new View( $arr );
        }
        return self::$_views[ $reel ];
    }

    /**
     * Add bdd connection
     * @param  string $ident ident of the connection
     * @param  array $args param
     */
    static function addBdd( $ident, array $args ) {
        self::$_bdds[ $ident ] = $args;
    }

    /**
     * set debug mode
     * @param  bool $bool
     */
    static function setDebug( $bool = true ) {
        self::$_debug = $bool;
    }

    static function setApp( \Closure $funct ) {
        self::$_app = $funct;
    }

    /**
    Private
    */

    /**
     * Add a custom type in Doctrine
     * @param  string $conn bdd connexion
     * @param  string $ident ident of the new type
     * @param  string $class className
     */
    private function _addType( $conn, $ident, $class ) {
        if (!Type::hasType($ident)) Type::addType( $ident , $class);
        if (!$conn->getDatabasePlatform()->hasDoctrineTypeMappingFor( $ident )) $conn->getDatabasePlatform()->registerDoctrineTypeMapping( $ident, $ident);
    }

    /**
        Static - private
    */
	
    /**
     * Class includer
     * @param  string $class className
     */
	static private function getClass( $class ) {
        $arr = explode( '\\' , $class );
        $name = end( $arr );
		$path = strtolower( implode( DIRECTORY_SEPARATOR, $arr) );
        $path = preg_replace("/controller$/", "", $path);
		if ( file_exists( self::$classPath  . "/$path/$name.php" ) ) return include self::$classPath . "/$path/$name.php";
		else return false;
	}

    /**
     * File includer
     * @param  string $filename
	 * @param  mixed $return default value to return if the file is not found
     */
	static private function getFile( $filename , $return = false ) {
	    return file_exists( $filename ) ? include $filename : $return;
	}

    static private function getCacheId( $class ) {
        return sha1( self::$classPath . str_replace( '\\', '', $class ) );
    }

}



