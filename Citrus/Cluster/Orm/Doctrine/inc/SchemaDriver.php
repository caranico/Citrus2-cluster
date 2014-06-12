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

use Doctrine\Common\Persistence\Mapping,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\Builder\EntityListenerBuilder,
    Yvelines\Citrus\Orm\Doctrine\Schema,
    Yvelines\Citrus\Orm\Doctrine\Adapter,
    Yvelines\Citrus\Orm\Doctrine\Enum;

class SchemaDriver extends Mapping\Driver\FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.schema.php';

    /**
     * Initializes a new FileDriver that looks in the given path(s) for mapping
     * documents and operates in the specified operating mode.
     *
     * @param string|array|FileLocator $locator       A FileLocator or one/multiple paths
     *                                                where mapping documents can be found.
     * @param string|null              $fileExtension
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     * 
     * @param string $className
     * @param ClassMetadataInfo $metadata
     */
    public function loadMetadataForClass($className, Mapping\ClassMetadata $metadata)
    {
        $sh = Adapter::getSchema( $className );
        $arrInformations = $sh->getInformations();
        $arrProperties = $sh->getProperties();
        $primarys = $sh->getPrimaryKeys();
        $metadata->setTableName( $arrInformations['table'] );
        //$metadata->setPrimaryTable( $arrInformations['table'] );

        if (isset($arrInformations['enum'])) {
            if (is_array( $arrInformations['enum'] )) {
                $metadata->setInheritanceType( ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE );
                $metadata->setDiscriminatorColumn(array(
                    'name' => $arrInformations['enum']['critere'],
                    'type' => null,
                    'length' => null,
                    'columnDefinition' => null
                ));
                $map = array();
                $map[(string) $arrInformations['enum']['value'] ] = $className;
                $metadata->setDiscriminatorMap($map);
            }
        }
            

        $idGenerator = false;
        if (is_array($arrProperties)) foreach ( $arrProperties as $propName => $propValue ) {
            $prop = isset($propValue['definition']) ? $propValue['definition'] : array();
            $mapping = array();
            if (isset($propValue['relation'])) {
                $relation = $propValue['relation'];
                switch ($relation['type']) {
                    case Schema::ONE_TO_ONE :
                        /**
                        Relation ONE_TO_ONE

                            foreign->class *     : classe de l'objet
                            foreign->property    : propriété liée de l'objet cible               (string)
                            foreign->pointer     : propriété virtuelle liée de l'objet cible     (string)
                            foreign->joinColumn  : Colonne de jointure                           (array)
                                ex : 
                                    array(
                                        'name' => 'nom de la colonne',                                  (obligatoire)
                                        'referencedColumnName' => 'nom de la colonne de reference'      (obligatoire)
                                        'unique' => true,                                               (true/false; optionnel)
                                        'nullable' => true,                                             (true/false; optionnel)
                                        'onDelete' => '',                                               ( ? ; optionnel)
                                        'columnDefinition' => ''                                        ( ? ; optionnel)
                                    );
                            foreign->joinColumns : Tableau de colonnes de jointures              (array)
                            cascade              : tableau d'action en cascade                   (array; default: array('persist', 'merge', 'detach'))
                                persist : persistence des entitées associées en cascade.
                                remove : suppression des entitées associées en cascade.
                                merge : Cascades merge operations to the associated entities.
                                detach : detache les entitées associées en cascade.
                                all : persist, remove, merge et detach.
                            fetch                : gestion de remplissage                        (constantes Objet Schema)
                            orphanRemoval        : Suppression des orphelins                     (true/false)

                        - * champs obligatoires
                        */
                        $mapping = array(
                            'fieldName' => $propName,
                            'targetEntity' => $relation['foreign']['class']
                        );

                        if (in_array($propName, $primarys)) {
                            $mapping['id'] = true;
                        }

                        $mapping['fetch'] = isset($relation['fetch']) ? $relation['fetch'] : Schema::FETCH_LAZY;


                        if (isset($relation['foreign']['property'])) {
                            $mapping['mappedBy'] = $relation['foreign']['property'];
                        } else {
                            if (isset($relation['foreign']['pointer'])) {
                                $mapping['inversedBy'] = $relation['foreign']['pointer'];
                            }

                            $joinColumns = array();

                            if (isset($relation['foreign']['joinColumn']))
                                $joinColumns[] = $relation['foreign']['joinColumn'];
                            else if (isset($relation['foreign']['joinColumns']))
                                $joinColumns = $relation['foreign']['joinColumns'];

                            $mapping['joinColumns'] = $joinColumns;
                        }

                        $mapping['cascade'] = isset($relation['cascade']) ? $relation['cascade'] : array('persist', 'merge', 'detach');
                        if (isset($relation['orphanRemoval'])) 
                            $mapping['orphanRemoval'] = $relation['orphanRemoval'];

                        $metadata->mapOneToOne($mapping);
                    break;
                    case Schema::MANY_TO_ONE :
                        /**
                        Relation MANY_TO_ONE

                            foreign->class *        : classe de l'objet
                            foreign->pointer  *     : propriété virtuelle liée de l'objet cible     (string)
                            foreign->joinColumn (?) : Colonne de jointure                           (array)
                                ex : 
                                    array(
                                        'name' => 'nom de la colonne',                                  (obligatoire)
                                        'referencedColumnName' => 'nom de la colonne de reference'      (obligatoire)
                                        'unique' => true,                                               (true/false; optionnel)
                                        'nullable' => true,                                             (true/false; optionnel)
                                        'onDelete' => '',                                               ( ? ; optionnel)
                                        'columnDefinition' => ''                                        ( ? ; optionnel)
                                    );
                            foreign->joinColumns (?) : Tableau de colonnes de jointures             (array)
                            cascade                  : tableau d'action en cascade                  (array; default: array('persist', 'merge', 'detach'))
                                persist : persistence des entitées associées en cascade.
                                remove : suppression des entitées associées en cascade.
                                merge : Cascades merge operations to the associated entities.
                                detach : detache les entitées associées en cascade.
                                all : persist, remove, merge et detach.
                            fetch                    : gestion de remplissage                       (constantes Objet Schema)

                        - * champs obligatoires
                        */
                        $mapping = array(
                            'fieldName' => $propName,
                            'targetEntity' => $relation['foreign']['class'],
                        );

                        if (isset($relation['foreign']['pointer'])) {
                            $mapping['inversedBy'] = $relation['foreign']['pointer'];
                        }
                        if (in_array($propName, $primarys)) {
                            $mapping['id'] = true;
                        }

                        $joinColumns = array();

                        if (isset($relation['foreign']['joinColumn']))
                            $joinColumns[] = $relation['foreign']['joinColumn'];
                        else if (isset($relation['foreign']['joinColumns']))
                            $joinColumns = $relation['foreign']['joinColumns'];

                        $mapping['joinColumns'] = $joinColumns;

                        $mapping['cascade'] = isset($relation['cascade']) ? $relation['cascade'] : array('persist', 'merge', 'detach');
                        $mapping['fetch'] = isset($relation['fetch']) ? $relation['fetch'] : Schema::FETCH_LAZY;

                        $metadata->mapManyToOne($mapping);

                    break;
                    case Schema::ONE_TO_MANY :
                        /**
                        Relation ONE_TO_MANY

                            foreign->class *    : classe de l'objet
                            foreign->property   : propriété liée de l'objet cible               (string; default: id)
                            orderBy             : tri de la collection selon un champs          (string)
                            indexBy             : indexation des collection selon un champs     (string)
                            cascade             : tableau d'action en cascade                   (array; default: array('persist', 'merge', 'detach'))
                                persist : persistence des entitées associées en cascade.
                                remove : suppression des entitées associées en cascade.
                                merge : Cascades merge operations to the associated entities.
                                detach : detache les entitées associées en cascade.
                                all : persist, remove, merge et detach.
                            orphanRemoval       : Suppression des orphelins                     (true/false)
                            fetch               : gestion de remplissage                        (constantes Objet Schema)

                        - * champs obligatoires
                        */
                        $mapping = array(
                            'fieldName' => $propName,
                            'targetEntity' => $relation['foreign']['class'],
                            'mappedBy' => isset($relation['foreign']['property']) ? $relation['foreign']['property'] : 'id'
                        );
                        if (isset($relation['orderBy'])) 
                            $mapping['orderBy'] = $relation['orderBy'];
                        if (isset($relation['indexBy'])) 
                            $mapping['indexBy'] = $relation['indexBy'];
                        if (isset($relation['cascade'])) 
                            $mapping['cascade'] = $relation['cascade'];
                        $mapping['cascade'] = isset($relation['cascade']) ? $relation['cascade'] : array('persist', 'merge', 'detach');
                        if (isset($relation['orphanRemoval'])) 
                            $mapping['orphanRemoval'] = $relation['orphanRemoval'];
                        $joinColumns = array();

                        if (isset($relation['foreign']['joinColumn']))
                            $joinColumns[] = $relation['foreign']['joinColumn'];
                        else if (isset($relation['foreign']['joinColumns']))
                            $joinColumns = $relation['foreign']['joinColumns'];

                        $mapping['joinColumns'] = $joinColumns;

                        $mapping['fetch'] = isset($relation['fetch']) ? $relation['fetch'] : Schema::FETCH_LAZY;
                        $metadata->mapOneToMany($mapping);
                    break;
                    case MANY_TO_MANY :
                        /**
                        Relation MANY_TO_MANY

                            foreign->class *    : classe de l'objet
                            foreign->property   : propriété liée de l'objet cible               (string)
                            foreign->pointer    : propriété virtuelle liée de l'objet cible     (string)
                            foreign->joinTable  : Table de jointure                             (array / string)
                                ex :
                                    array(
                                        'name' => 'nom de la table',
                                        'schema' => 'schema de la table'
                                    );
                                ou : 'nom de la table' (si schema vide)
                            foreign->joinColumns : Tableau de colonnes de jointures             (array)
                            foreign->inverseJoinColumns : Tableau de colonnes de jointures inverse  (array)
                            orderBy             : tri de la collection selon un champs          (string)
                            indexBy             : indexation des collection selon un champs     (string)
                            cascade             : tableau d'action en cascade                  (array; default: array('persist', 'merge', 'detach'))
                                persist : persistence des entitées associées en cascade.
                                remove : suppression des entitées associées en cascade.
                                merge : Cascades merge operations to the associated entities.
                                detach : detache les entitées associées en cascade.
                                all : persist, remove, merge et detach.
                            fetch               : gestion de remplissage                       (constantes Objet Schema)

                        - * champs obligatoires
                        */

                        $mapping = array(
                            'fieldName' => $propName,
                            'targetEntity' => $relation['foreign']['class']
                        );
                        $mapping['fetch'] = isset($relation['fetch']) ? $relation['fetch'] : Schema::FETCH_LAZY;
                        if (isset($relation['orphanRemoval'])) 
                            $mapping['orphanRemoval'] = $relation['orphanRemoval'];

                        if (isset($relation['foreign']['property'])) {
                            $mapping['mappedBy'] = $relation['foreign']['property'];
                        } else {
                            if (isset($relation['foreign']['pointer'])) {
                                $mapping['inversedBy'] = $relation['foreign']['pointer'];
                            }

                            $joinTable = array(
                                'name' => is_array($relation['foreign']['joinTable']) ? $relation['foreign']['joinTable']['name'] : $relation['foreign']['joinTable']
                            );

                            if (isset($relation['foreign']['joinTable']['schema'])) {
                                $joinTable['schema'] = $relation['foreign']['joinTable']['schema'];
                            }

                            $joinColumns = array();

                            if (isset($relation['foreign']['joinColumns']))
                                $mapping['joinColumns'] = $relation['foreign']['joinColumns'];
                            if (isset($relation['foreign']['inverseJoinColumns']))
                                $mapping['inverseJoinColumns'] = $relation['foreign']['inverseJoinColumns'];

                        }

                        if (isset($relation['orderBy'])) 
                            $mapping['orderBy'] = $relation['orderBy'];
                        if (isset($relation['indexBy'])) 
                            $mapping['indexBy'] = $relation['indexBy'];
                        if (isset($relation['cascade'])) 
                            $mapping['cascade'] = $relation['cascade'];
                        $metadata->mapManyToMany($mapping);
                    break;
                }

            } else {
                /**
                Champ de donnée

                    type *              : type de variable                                  (constantes Objet Schema)
                    size                : taille du champs                                  (integer)
                    scale               : échelle décimale du champs                        (integer)
                    precision           : précision décimale du champs                      (integer)
                    primary             : Clé primaire                                      (true/false; default: false)
                    manual              : a true si la clé primaire n'est pas auto généré   (true/false; default: false)
                    unique              : défini si le champs est unique                    (true/false; default: false)
                    notnull             : défini le caractère non null du champs            (true/false; default: false)
                    column              : nom du champs objet si différent de fieldName     (string)
                    columnDefinition    : définition du champs                              (string)

                - * champs obligatoires
                */
                $mapping = array(
                    'fieldName' => $propName,
                    'type'      => $prop['type']
                );

                if (isset($prop['columnDefinition'])) 
                    $mapping['columnDefinition'] = $prop['columnDefinition'];
                if (isset($prop['column'])) 
                    $mapping['columnName'] = $prop['column'];
                if (isset($prop['size'])) 
                    $mapping['length'] = $prop['size'];                

                if (isset($prop['primary'])) {
                    $mapping['id'] = true;
                    if ( !isset($prop['manual']) && !$idGenerator ) {
                        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
                        $idGenerator = true;
                    }
                } else {

                    if (isset($prop['scale'])) 
                        $mapping['scale'] = $prop['scale'];
                    if (isset($prop['precision'])) 
                        $mapping['precision'] = $prop['precision'];
                    if (isset($prop['size'])) 
                        $mapping['length'] = $prop['size'];
                    if (isset($prop['unique'])) 
                        $mapping['unique'] = $prop['unique'];
                    $mapping['nullable'] = !isset($prop['notnull']) || (isset($prop['notnull']) && !$prop['notnull']);
                }
                $metadata->mapField( $mapping );
            }
        }
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding file driver elements.
     *
     * @param string $file The mapping file to load.
     *
     * @return array
     */
    protected function loadMappingFile($file)
    {
        // parse contents of $file and return php data structure
    }
}