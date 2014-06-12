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


Class Enum {
	const TABLE = 'faid_porteur__enumeration';
	const FIELD = 'enumtype';

	/**
     * @access private
     * @var _enumLst
     */
	private static $_enumLst = array(
		'faid\porteur\ConventionCouveuse' 			=> 550,
		'faid\porteur\FormeJuridique' 				=> 53,
		'faid\porteur\MarcheCoupdepouce' 			=> 103,
		'faid\porteur\Mobilisation' 				=> 102,
		'faid\porteur\NiveauEtude' 					=> 2,
		'faid\porteur\NumConvention' 				=> 200,
		'faid\porteur\Prescripteur' 				=> 6,
		'faid\porteur\PrescriptionFormation' 		=> 103,
		'faid\porteur\Reseau' 						=> 56,
		'faid\porteur\SecteurActivite' 				=> 52,
		'faid\porteur\SecteurGeographique' 			=> 54,
		'faid\porteur\SituationEntreprise' 			=> 55,
		'faid\porteur\SituationFamiliale' 			=> 1,
		'faid\porteur\SituationSociale' 			=> 3,
		'faid\porteur\StructureLieTIRSA' 			=> 501,
		'faid\porteur\SuiviApporte' 				=> 5,
		'faid\porteur\SuiviIntervenant' 			=> 102,
		'faid\porteur\TypeAccompagnementAccBen' 	=> 451,
		'faid\porteur\TypeAccompagnementRSA' 		=> 450,
		'faid\porteur\TypeCSP' 						=> 300,
		'faid\porteur\TypeEntrepreneur' 			=> 4,
		'faid\porteur\TypeFormation' 				=> 400,
		'faid\porteur\TypeParrainage' 				=> 101,
		'faid\porteur\TypePIA' 						=> 104,
		'faid\porteur\TypeRevenus' 					=> 7,
		'faid\porteur\TypeSortieTIRSA' 				=> 500,
		'faid\porteur\TypeStructure' 				=> 51,
		'faid\porteur\Ville' 						=> 40
	);

    /**
     * Return Enum schema
     * @param  string $class className
     *
     * @return array
     */
	static function find( $class ) {
		if ( !in_array( $class, array_keys( self::$_enumLst ) ) ) return false;
		$enumClass = inc\Generator::classeNameFromTable( self::TABLE );
		$schemaRef = Adapter::getSchema( $enumClass )->__invoke();
		if ( isset( $schemaRef['properties'] ) && isset( $schemaRef['properties'][ self::FIELD ] ) ) unset($schemaRef['properties'][ self::FIELD ]);
		return array_merge( $schemaRef,
			array(
			    'informations' => array(
			        'table' 	=> self::TABLE,
			        'class' 	=> $class,
			        'extend'	=> $enumClass,
			        'enum' 	=> array(
			        	'critere'	=> self::FIELD,
			        	'value' 	=> self::$_enumLst[ $class ]
			        )
			    )
			)
		);
	}
}