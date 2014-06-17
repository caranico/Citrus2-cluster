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

use Citrus\Cluster\Controller\ObjectController,
	Citrus\Cluster\TObject;

Class View extends inc\Synapse {

	public function jqgrid() {
		$class = $this->getClass();
		$sh = Adapter::getSchema( $class );
		$list = $this->getList();
		$form = $this->getProperties();

		$allField = $sh->getProperties();
		$listField = array_keys($allField);

		$colNames = array();
		$colModel = array();
		$arr = explode(' ', $list['order']);
		$ordername = $arr[0];
		$ordersort = strtolower($arr[1]);
		foreach ($listField as $field) {
			if (!is_array($form[ $field ])) $form[ $field ] = array('libelle' => $form[ $field ]);

			$fieldProp = array_replace_recursive( array(
				'definition' => array(
					'primary' 	=> false
				),
				'constraint' => array(
					'readonly' 	=> false,
					'hidden' 	=> false
				)
			), $sh->getProperties($field), $form[ $field ]);

			$editable 	= !($fieldProp['constraint']['readonly'] || $fieldProp['definition']['primary']);
			$hidden 	= $fieldProp['constraint']['hidden'] || $fieldProp['definition']['primary'];

			$colNames[] = $fieldProp['libelle'];
			$colModel[] = array(
				"name" 			=> $field,
				"index" 		=> $field,
				"width" 		=> 100,
				'editable' 		=> $editable,
				'hidden' 		=> $hidden,
				'editoptions' 	=> array("size"=>10)
			);
		}

		$id = md5( date("U") * rand( 1, 99 ) );
		$slug = ObjectController::getSlug($class);

		$params = array(
			'url' 			=> '/classes/' . $slug . '/list.json',
			'datatype' 		=> 'json',
			'colNames' 		=> $colNames,
			'colModel' 		=> $colModel,
			'rowNum'		=>10,
			'autowidth'		=> true,
			'multiselect' 	=> true,
			'shrinkToFit' 	=> false,
			'rowList' 		=> array(10,20,30),
			'pager'			=> '#pager_' . $id , 
			'sortname' 		=> $ordername,
			'viewrecords'	=> true,
			'sortorder' 	=> $ordersort,
			'editurl' 		=> '/classes/' . $slug . '/listAction.json',
 			'scroll' 		=> true,
			'jsonReader'	=> array(
                  "root"=> 'function (obj) { var e = $.jsonEnv(obj); if (e.rows) return e.rows;}',
			      "page" => "content.page", 
			      "total" => "content.total", 
			      "records" => "content.records", 
            ),
            /*
	 		'ondblClickRow' => 'function (id) {' .
	            'if (id && id != lastsel) {' .
	                '$(this).restoreRow(lastsel);' .
	                '$(this).editRow(id, {' .
	                    'keys: true,' .
	                    'closeAfterEdit: true' .
	                '});' .
	                'lastsel = id;' .
	            '}' .
	        '}',
	        */
	        'beforeRequest' 	=> 'function () {' .
	            '$(\'.ui-jqgrid, .ui-jqgrid-view, .ui-jqgrid-view\').autoheight();' .
	        '}',
	        'loadError' => 'function (xhr,status,error) {' .
	        	'var content = $.jsonEnv( xhr.responseJSON );' .
	        	'$.fn.modal( content, {}, true );' .
	        '}'
		);

		$paramsPager = array(
			array(
				'edit'		=>false,
				'add'		=>false,
				'del'		=>true, 
				'addfunc'	=>'function () { }'
			),
			array(),
			array(),
			array(),
			array(
				'multipleSearch' 	=> true,
				'multipleGroup' 	=> true,
				'showQuery' 		=> true
			)
		);

		$paramsColumn = array(
			'caption' 			=> '',
			'buttonicon'		=> 'ui-icon-gear',
			'title' 			=> "Choisir les colonnes",
			'onClickButton' 	=> 'function (){ $(\'#' . $id . '\').columnChooser(); }'
		);

		$paramsAdd = array(
			'caption' 			=> '',
			'buttonicon'		=> 'ui-icon-plus',
			'title' 			=> "Ajouter un enregistrement",
			'onClickButton' 	=> 'function (){ ' .
				'$.get("/classes/' . $slug . '/edit.html", function (res) { '.
	        		'$.fn.modal( $.jsonEnv( res ), {} );' .
				'}).fail(function (res) { '.
		        	'var content = $.jsonEnv( res.responseJSON );' .
		        	'$.fn.modal( content, {}, true );' .
				'});' .
			' }'
		);

		$paramsExportCSV = array(
			'caption' 			=> 'Export CSV',
			'buttonicon'		=> 'ui-icon-arrowthickstop-1-s',
			'title' 			=> "Export CSV",
			'onClickButton' 	=> 'function () { $(\'#' . $id . '\').excelExport({\'url\':\'/classes/' . $slug . '/list.csv\'});}'
		);

		$paramsExportXLS = array(
			'caption' 			=> 'Export XLS',
			'buttonicon'		=> 'ui-icon-arrowthickstop-1-s',
			'title' 			=> "Export XLS",
			'onClickButton' 	=> 'function () { $(\'#' . $id . '\').excelExport({\'url\':\'/classes/' . $slug . '/list.xls\'});}'
		);

		$paramsSepar = array(
			'sepclass'			=> "ui-separator",
			'sepcontent'		=> '' 
		);

		return '<table id="' . $id . '"></table>' . chr(10) .
			'<div id="pager_' . $id . '"></div>' . chr(10) .
			'<script type="text/javascript">' . chr(10) .
				'$(document).ready(function () {' . chr(10) .
					'var lastsel;' .
					'$("#' . $id . '").jqGrid(' . TObject::jsonize( $params ) . ')' . chr(10) .
					'.navGrid(' .
						'"#pager_' . $id.'",' .
						TObject::jsonize( $paramsPager[0] ) . ', ' .
						TObject::jsonize( $paramsPager[1] ) . ', ' .
						TObject::jsonize( $paramsPager[2] ) . ', ' .
						TObject::jsonize( $paramsPager[3] ) . ', ' .
						TObject::jsonize( $paramsPager[4] ) . 
					')' . chr(10) .
					'.navButtonAdd("#pager_' . $id . '",' . TObject::jsonize( $paramsAdd ) . ') ' . chr(10) .
					'.navSeparatorAdd("#pager_' . $id . '",' . TObject::jsonize( $paramsSepar ) . ') '.
					'.navButtonAdd("#pager_' . $id . '",' . TObject::jsonize( $paramsExportCSV ) . ') ' . chr(10) .
					'.navButtonAdd("#pager_' . $id . '",' . TObject::jsonize( $paramsExportXLS ) . ') ' . chr(10) .
					'.navSeparatorAdd("#pager_' . $id . '",' . TObject::jsonize( $paramsSepar ) . ') '.
					'.navButtonAdd("#pager_' . $id . '",' . TObject::jsonize( $paramsColumn ) . '); ' . chr(10) .
				'});' . chr(10) .
			'</script>';
	}

	public function jqgridList( $request ) {
		$rows = $request->get( 'rows');
		$page = $request->get( 'page');
		$sidx = $request->get( 'sidx');
		$sord = $request->get( 'sord' );
		$search = $request->get( '_search');
		$term = $request->get( 'term' );
		$filters = false;

		if ($search) $filters = $request->get( 'filters');

		$cond = '';
		if ($filters) $cond = 'WHERE ' .$this->_filter( json_decode( $filters ) );
		else if ( $term ) {
			// gestion de la recherche autocomplete
			if (!$page) $page =1;
			if (!$rows) $rows =10;
			$list = $this->getList();
			$res = array();
			foreach ($list['search'] as $field)
				$res[]=sprintf("self.%1s LIKE '%%%2s%%'", $field, $term);
			$cond = 'WHERE ' . implode(' OR ', $res );
		}

		$class = $this->getClass();
		$sh = Adapter::getSchema( $class );
		$lstOption = Adapter::getView( $class )->getList();
		$allField = $sh->getProperties();
		$listField = array_keys($allField);
		$arrParams = array(
			$cond . ( $sidx && $sord ? ' ORDER BY self.' . $sidx . ' ' . $sord : '' ) , 
			array(
				'offset'=> (($page-1) * $rows), 
				'limit' => $rows 
			)
		);
		$lst = call_user_func_array( array($class, 'selectAll'), $arrParams );
		$max = call_user_func_array( array($class, 'count'), $arrParams );

		$responce = (object) array(
			'page' 		=> $page,
			'total'		=> ceil( $max / $rows ),
			'records' 	=> $max,
			'rows' 		=> array()
		);

		foreach ( $lst as $object ) {
			foreach ( $listField as $col ) {
				$value = $object->$col;
				if (is_array($value)) $cell[ $col ] = implode(',',$value);
				if (is_object($value)) {
					if ( get_class($value) == 'DateTime' ) $cell[ $col ] = $value->format('d/m/Y H:i:s');
					else if ( get_class( $value ) == 'Doctrine\ORM\PersistentCollection' ) $cell[ $col ] = count( $value );
					else if ( is_a( $value, 'Citrus\Cluster\Orm\Doctrine\Model' ) ) $cell[ $col ] = (string) $value;
				}
				else {
					if ( isset($lstOption['link']) && in_array( $col,  $lstOption['link'])) {
						$cell[ $col ] = '<a href="' . $object->id . '/' . (isset($lstOption['linkAction']) ? $lstOption['linkAction'] : 'edit') . '">' . $value . '</a>'; 
					}
					else $cell[ $col ] = $value;
				}
			}
			$responce->rows[] = array('cell' => $cell);
		}
		return $responce;
	}

	public function export( $request ) {
		$class = $this->getClass();
		$sh = Adapter::getSchema( $class );

		$sidx = $request->get( 'sidx' );
		$sord = $request->get( 'sord' );
		$search = $request->get( '_search' );
		$term = $request->get( 'term' );
		$filters = false;

		if ($search) $filters = $request->get( 'filters' );

		$cond = '';
		if ($filters) $cond = 'WHERE ' .$this->_filter( json_decode( $filters ) );

		$arrParams = array(
			$cond . ( $sidx && $sord ? ' ORDER BY self.' . $sidx . ' ' . $sord : '' )
		);

		$lst = call_user_func_array( array($class, 'selectAll'), $arrParams );

		$firstLine = array();
		$result = array();
		foreach ($lst as $obj) {
			$first = empty( $firstLine );
			$res = array();
			foreach ($this->getProperties() as $id => $field) {
				$prop = $sh->getProperties( $id );
				if ( $first ) {
					if (!is_array($field)) $field = array('libelle' => $field);
					if (!isset($prop['definition']['enctype']))
						$firstLine[] = $field['libelle'];
				}
				if ($prop['definition']['type'] == Schema::DATETIME) {
					$res[] = $obj->$id->format("d/m/Y H:i:s");
				}
				else if (!isset($prop['definition']['enctype'])) {
					$res[] = $obj->$id;
				}
			}
			$result[] = $res;
		}
		return array_merge(array($firstLine), $result);
	}


	private function _filter( \stdClass $arr ) {
		$separ = $arr->groupOp;
		$resFilter = array();
		if (isset($arr->groups) && is_array($arr->groups) && count($arr->groups) > 0) 
			foreach ( $arr->groups as $group )
				$resFilter[] = '(' . $this->_filter( $group ) . ')';

		foreach ( $arr->rules as $rule ) {
			$model = false;
			switch ($rule->op) {
				case 'bw' : $model = "self.%1s LIKE '%2s%%'";	break;
				case 'eq' : $model = "self.%1s = '%2s'";		break;
				case 'ne' : $model = "self.%1s <> '%2s'";		break;
				case 'lt' : $model = "self.%1s < '%2s'";		break;
				case 'le' : $model = "self.%1s <= '%2s'";		break;
				case 'gt' : $model = "self.%1s > '%2s'";		break;
				case 'ge' : $model = "self.%1s >= '%2s'";		break;
				case 'ew' : $model = "self.%1s LIKE '%%%2s'";	break;
				case 'cn' : $model = "self.%1s LIKE '%%%2s%%'";	break;
			}
			if ($model) $resFilter[] = sprintf( $model, $rule->field, $rule->data );
		}
		return implode(' ' . $arr->groupOp . ' ', $resFilter );
	}
}