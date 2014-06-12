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

namespace Citrus\Cluster\Orm\Doctrine\inc;

use Citrus\Cluster\Orm\Doctrine\Adapter;

class Generator {

    /**
        Static
    */

    /**
     * Load and create shemas from BDD
     * @param  string $prefix prefix to classes
     */
    static function loadDatabase( $prefix = false ) {
        $cnx = Adapter::getInstance()->getConnection();
        $listTable = $cnx->query('SHOW TABLES');
        if ( $prefix != '' && substr($prefix, 0, 1) != '\\' ) $prefix = '\\' . $prefix;

        $constraints = self::_getConstraintsFromDB( $prefix );

        while ($row = $listTable->fetch(\PDO::FETCH_NUM)) {
            
            $tableName = $row[0];
        
            $classDef = self::classeNameFromTable( $tableName, $prefix );
            
            $sh = array(
                'informations' => array(
                    'table' => $tableName,
                    'class' => $classDef,
                )
            );

            $desc = $cnx->query('DESCRIBE ' . $tableName )->fetchAll( \PDO::FETCH_ASSOC );
            $arrField = array();
            $first = 0;

            foreach ( $desc as $propertie ) {
                $field = array( 'definition' => self::_getDefsFromDb( $propertie ) );
                $name = $propertie['Field'];
                if (isset( $constraints['ONE'][ $classDef ][ preg_replace('/\_id$/', '', $name) ] )) {
                    $name = preg_replace('/\_id$/', '', $name);
                    $field['relation'] = $constraints['ONE'][ $classDef ][ $name ];
                }

                $sh['properties'][ $name ] = $field;
                if ( isset( $field['definition']['primary'] ) ) $primary = $name;
                $arrField[ $name ] =  $first > 0 ? ':::Forms "' . $name . '" libelle:::' : array(
                    'libelle' => ':::Forms "' . $name . '" libelle:::',
                    '#combegin#constraint:::Form view constraints:::' => array(
                        'readonly:::Direct condition:::'=>true,
                        'disabled:::Conditionnal condition:::'=>'[name="check_id"]:checked',
                        'visible:::Hidden by default:::'=>'[name="check_id"]:checked',
                        'hidden:::Visible by default:::'=>'[name="check_id"]:checked',
                        'depend:::other field condition to reload:::'=>'[name="check_id"]:checked'
                    ),
                    'field:::Override field type:::'=>array(
                        'type:::Override type:::'=>'className',
                        'options:::Override options:::'=> ''
                    ),
                    'fieldset'=>':::Fieldset name:::#comend#',
                );
                $first ++;
            }

            $sh2 = array(
                'informations' => array(
                    'name' => '::: Entity name:::',
                    'description' => '::: Entity description :::',
                    'gender' => 'm:::Genre m/f:::',
                ),
                'ident' => 'function ( $self ) { return $self->' . $primary . '; }::: __toString function :::'
            );

            if (isset( $constraints['MANY'][ $classDef ])) 
                foreach ($constraints['MANY'][ $classDef ] as $propName => $relation) {
                    $sh['properties'][ $propName ] = array( 'relation' => $relation );
                }
            $arr = array_keys( $arrField );
            $sh2[ 'list:::List options:::' ] = array(
                'list::: Define columns to list, by default list all :::' => array_splice($arr,0,2),
                'search::: Define columns to search :::' => array_splice($arr,0,1),
                'order' => array_shift($arr) . ' ASC',
                'link' => array_splice($arr,0,1),
            );
            $sh2[ 'properties:::Form options:::' ] = $arrField;

            self::_writeSchema( $sh, $sh2, substr($classDef,1) );
        }


    }

    /**
     * Generate className from tableName
     * @param  string $tableName initial table name
     * @param  string $prefix prefix to classes
     *
     * @return string
     */
    static function classeNameFromTable( $tableName, $prefix = false ) {
        $classDef = ( $prefix ? $prefix : '' ) . '\\' . preg_replace('/\_?\_/', "\\", $tableName);
        
        $namespace = substr($classDef, 0, strrpos($classDef,'\\'));
        $className = substr($classDef, strlen($namespace) + 1 );
        
        return  $namespace . '\\' . ucfirst( $className );
    }

    /**
        Privates - static
    */


    /**
     * Get constraints from BDD
     * @param  string $prefix prefix to classes
     * 
     * @return array
     */
    static private function _getConstraintsFromDB( $prefix ) {
        $cnx = Adapter::getInstance()->getConnection();


        $innoQuery = 'SELECT tc.CONSTRAINT_NAME, '.
            'tc.CONSTRAINT_TYPE, '.
            'kcu.TABLE_SCHEMA, '.
            'kcu.TABLE_NAME , '.
            'kcu.COLUMN_NAME, '.
            'kcu.REFERENCED_TABLE_SCHEMA, '.
            'kcu.REFERENCED_TABLE_NAME , '.
            'kcu.REFERENCED_COLUMN_NAME, ' .
            'rc.UPDATE_RULE, ' .
            'rc.DELETE_RULE ' .
        'FROM '.
            'INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc, '.
            'INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu, '.
            'INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS rc '.
        'WHERE tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA' .
        '  AND tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME' .  
        '  AND tc.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA' .
        '  AND tc.CONSTRAINT_NAME = rc.CONSTRAINT_NAME' .
        '  AND tc.TABLE_SCHEMA = "' . Adapter::getInstance()->getCurrent() . '"' .
        '  AND tc.CONSTRAINT_TYPE = "FOREIGN KEY"';

        $constraints = $cnx->query( $innoQuery )->fetchAll(\PDO::FETCH_ASSOC);
        $reelConstraints = array();
        foreach ( $constraints as $cont ) {
            $initClass =self::classeNameFromTable($cont['TABLE_NAME'], $prefix);
            $initField = $cont['COLUMN_NAME'];
            $refClass =self::classeNameFromTable($cont['REFERENCED_TABLE_NAME'], $prefix);
            $refField = $cont['REFERENCED_COLUMN_NAME'];
            $reelConstraints['ONE'][ $initClass ][ preg_replace('/\_id$/', '', $initField) ] = $refField != 'id' ? array(
                "type"      => 'REF::MANY_TO_ONE',
                "foreign"   => array( "class" => $refClass, "pointer"  => $refField )
            ) : array(
                "type"      => 'REF::MANY_TO_ONE',
                "foreign"   => array( "class" => $refClass )
            );
            $reelConstraints['MANY'][ $refClass ][ strtolower(substr($initClass, strrpos($initClass,'\\') + 1 )) . 's' ] = array(
                "type"      => 'REF::ONE_TO_MANY',
                "foreign"   => array( "class" => $initClass,"property"  => preg_replace('/\_id$/', '', $initField) )
            );
        }
        return $reelConstraints;
    }

    /**
     * Convert Mysql properties to Doctrine properties
     * @param  array $props array of properties
     *
     * @return array
     */
    static private function _getDefsFromDb( $props ) {        
        $ret = array();

        switch ( $props['Type'] ) {
            case 'float' :      $ret['type'] = 'REF::FLOAT';        break;
            case 'double' :     $ret['type'] = 'REF::BIGINT';       break;
            case 'date' :       $ret['type'] = 'REF::DATE';         break;
            case 'datetime' :   $ret['type'] = 'REF::DATETIME';     break;
            case 'time' :       $ret['type'] = 'REF::TIME';         break;
            case 'timestamp' :  $ret['type'] = 'REF::STRING';       break;
            case 'text' :       $ret['type'] = 'REF::TEXT';         break;
            case 'tinytext' :   $ret['type'] = 'REF::TEXT';         break;
            case 'mediumtext' : $ret['type'] = 'REF::TEXT';         break;
            case 'longtext' :   $ret['type'] = 'REF::TEXT';         break;
            case 'blob' :       $ret['type'] = 'REF::BLOB';         break;
            case 'tinyblob' :   $ret['type'] = 'REF::BLOB';         break;
            case 'mediumblob' : $ret['type'] = 'REF::BLOB';         break;
            case 'longblob' :   $ret['type'] = 'REF::BLOB';         break;
            default : 
                if (preg_match("/^int\(([0-9]+)\)$/", $props['Type'], $matches))                    $ret = array( 'type' => 'REF::INTEGER',     'size' => $matches[1]);
                elseif (preg_match("/^decimal\(([0-9]+),([0-9]+)\)$/", $props['Type'], $matches))   $ret = array( 'type' => 'REF::DECIMAL',     'size' => ($matches[1] + $matches[2] + 1) , 'precision' => $matches[1], 'scale' => $matches[2]);
                elseif (preg_match("/^tinyint\(1\)$/", $props['Type'], $matches))                   $ret = array( 'type' => 'REF::BOOLEAN');
                elseif (preg_match("/^tinyint\(([0-9]+)\)$/", $props['Type'], $matches))            $ret = array( 'type' => 'REF::INTEGER',     'size' => $matches[1]);
                elseif (preg_match("/^smallint\(([0-9]+)\)$/", $props['Type'], $matches))           $ret = array( 'type' => 'REF::INTEGER',     'size' => $matches[1]);
                elseif (preg_match("/^mediumint\(([0-9]+)\)$/", $props['Type'], $matches))          $ret = array( 'type' => 'REF::BIGINT',      'size' => $matches[1]);
                elseif (preg_match("/^bigint\(([0-9]+)\)$/", $props['Type'], $matches))             $ret = array( 'type' => 'REF::BIGINT',      'size' => $matches[1]);
                elseif (preg_match("/^bigint\(([0-9]+)\) unsigned$/", $props['Type'], $matches))    $ret = array( 'type' => 'REF::DECIMAL',     'size' => 21 , 'precision' => 20, 'scale' => 0);
                elseif (preg_match("/^char\(([0-9]+)\)$/", $props['Type'], $matches))               $ret = array( 'type' => 'REF::STRING',      'size' => $matches[1]);
                elseif (preg_match("/^varchar\(([0-9]+)\)$/", $props['Type'], $matches))            $ret = array( 'type' => 'REF::STRING',      'size' => $matches[1]);
                elseif (preg_match("/^binary\(([0-9]+)\)$/", $props['Type'], $matches))             $ret = array( 'type' => 'REF::STRING',      'size' => $matches[1]);
                elseif (preg_match("/^varbinary\(([0-9]+)\)$/", $props['Type'], $matches))          $ret = array( 'type' => 'REF::STRING',      'size' => $matches[1]);
                elseif (preg_match("/^year\(4\)$/", $props['Type'], $matches))                      $ret = array( 'type' => 'REF::STRING',      'size' => 4);
                elseif (preg_match("/^bit\(([0-9]+)\)$/", $props['Type'], $matches))                $ret = array( 'type' => 'REF::BOOLEAN');
                elseif (preg_match("/^enum\(([^\)]*)\)$/", $props['Type'], $matches))               $ret = array( 'type' => 'REF::STRING',      'options' => eval("return array(" . $matches[1] . ");"));
                elseif (preg_match("/^set\(([^\)]*)\)$/", $props['Type'], $matches))                $ret = array( 'type' => 'REF::SIMPLE_ARRAY','options' => eval("return array(" . $matches[1] . ");"));
                else  echo $props['Type']. chr(10);
            break;
        }
                
        if ( $props['Default'] && $props['Default'] == 'YES' ) $ret['default'] = $props['Default'];
        if ( $props['Null'] && $props['Null'] == 'YES' ) $ret['notnull'] = true;
        if ( $props['Key'] == 'PRI' ) $ret['primary'] = true;
        
        return $ret;
    }

    /**
     * Write schemas
     * @param  array $dataschema array of data properties
     * @param  array $viewschema array of view properties
     * @param  string $class class name
     */
    static private function _writeSchema( $dataschema, $viewschema, $class ) {

        $arr = explode('\\', $class);
        $name = end($arr);
        $dir =  Adapter::$classPath . "/" . strtolower( implode( DIRECTORY_SEPARATOR, $arr) );
        $dataSchemaFile = $dir . "/$name.data.php";
        $viewSchemaFile = $dir . "/$name.view.php";
        $reg = array(
            '/\'type\' => \'(REF::[A-Z_]*)\'/i' => '\'type\' => $1',
            '/\\\\\\\\/i' => '\\',
            '/  /i' => '    ',
            '/\\n *array \(/i' => 'array (',
            '/array \(/i' => 'array(',
            '/(.*)#combegin#/' => '/*' . chr(10) . '$1',
            '/#comend#(.*)/' => '$1' . chr(10) . '*/',
            '/:::(.*):::(.*)/' => '$2    // $1',
            '/\'function(.*)\}\',/'=>'function$1},',
        );

        if (!is_file($dataSchemaFile)) {
            $dataContent ='<?php'.chr(10).chr(10).
                'use \Citrus\Cluster\Orm\Doctrine\Schema as REF;'.chr(10).
                'return ' . preg_replace( array_keys($reg), array_values($reg), var_export( $dataschema, true ) ).';' .chr(10).chr(10).
                '?>'; 
            try {
                self::writeContent( $dataSchemaFile, $dataContent );
            }
            catch( \Exception $e ){
                print_r( $e );
            }
            
        }
        if (!is_file($viewSchemaFile)) {
            $dataView ='<?php'.chr(10).chr(10).
                'return ' . preg_replace( array_keys($reg), array_values($reg), var_export( $viewschema, true ) ).';' .chr(10).chr(10).
                '?>';  
            try {
                self::writeContent( $viewSchemaFile, $dataView );
            }
            catch( \Exception $e ){
                print_r( $e );
            }
        }
    }

    /**
     * Write files
     * @param  string $filename file name
     * @param  string $content content
     */
    private static function writeContent( $filename, $content ) {
        echo '<br />Ecriture : ' . $filename;
        $dir = dirname( $filename );        
        $old = umask(0);
        if (!is_dir( $dir )) 
            if (!mkdir( $dir, 0777, true )) throw new \Exception( 'Le répertoire ' . $dir . ' ne peut être crée ( problèmes de droits )' );
        $fp = fopen( $filename, 'w' );
        if (!$fp) throw new \Exception( 'Le fichier ' . $filename . ' ne peut être crée ( problèmes de droits )' );
        fwrite($fp, $content);
        fclose($fp);
        umask($old);
    }
}