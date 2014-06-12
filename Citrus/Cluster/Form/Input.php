<?php

namespace Yvelines\Citrus\Form;
use Yvelines\Citrus\TArray,
    Yvelines\Citrus\Orm\ModelDispatcher;

abstract class Input extends Element {

	public $params;

    const INPUT_TEXT 		= 'text';
    const INPUT_HIDDEN 		= 'hidden';
    const INPUT_PASSWORD	= 'password';
    const INPUT_FILE		= 'file';
    const SELECT_ONE		= 'selectOne';
    const SELECT_MANY		= 'selectMany';
    const TEXTAREA			= 'textarea';
    const WYSIWYG			= 'wysiwyg';
    const CHECKBOX			= 'checkbox';
    const BUTTON			= 'button';

    static private $elements = array(
    	self::INPUT_TEXT => '\Yvelines\Citrus\Form\Elements\InputText',
        self::INPUT_HIDDEN => '\Yvelines\Citrus\Form\Elements\InputHidden',
        self::INPUT_PASSWORD => '\Yvelines\Citrus\Form\Elements\InputPassword',
        self::SELECT_ONE => '\Yvelines\Citrus\Form\Elements\SelectOne',
        self::SELECT_MANY => '\Yvelines\Citrus\Form\Elements\SelectMany',
    );

    static public function addElement( $ident, $args ) 
    {
        self::$elements[ $ident ] = $args;
    }

    static public function create( Array $params = array() ) 
    {
    	$ident = $params['className'];
    	unset($params['className']);
    	$class = self::$elements[ $ident ];
    	return new $class( $params );
    }

    static public function objFromProperties( $id, $props, $value = null )
    {
        $res = array();

        $res['className'] = self::INPUT_TEXT;
        $res['appearence'] = 'default';
        $res['properties']['name'] = $id;

        if (isset($props['appearence']))
            $res['appearence'] = $props['appearence'];


    	if (isset($props['definition'])) 
    	{
            $def = $props['definition'];
            if (isset($def['size']))
                $res['properties']['maxlength'] = $def['size'];
            if (isset($def['type']))
                $res['type'] = $def['type'];

            if (!is_null($value))
            {
                if (is_a($value, '\DateTime')) $value= $value->format("d/m/Y H:i:s");
                $res['properties']['value'] = $value;
            }
            else if (isset($def['default']))
                $res['properties']['value'] = $def['default'];

            if ( isset($def['enctype']) )
                $res['className'] = self::INPUT_PASSWORD;
    	}

        if (isset($props['relation'])) 
        {
            $rel = $props['relation'];
            $schObj = ModelDispatcher::get(ModelDispatcher::SCHEMA);

            switch ($rel['type'])
            {
                case $schObj::ONE_TO_ONE :
                break;
                case $schObj::MANY_TO_ONE :
                    $res['className'] = self::SELECT_ONE;
                    if (isset($rel['foreign']))
                    {
                        $class = $rel['foreign']['class'];
                        $sch = $class::getSchema();
                        $res['options'] = (array) TArray::indexedByUnique( $class::selectAll(), array_shift($sch::getPrimaryKeys()) );
                    }
                    if (!is_null($value))
                        $res['properties']['value'] = $value;

                break;
                case $schObj::ONE_TO_MANY :
                    $res['className'] = self::SELECT_MANY;
                    if (isset($rel['foreign']))
                    {
                        $class = $rel['foreign']['class'];
                        $sch = $class::getSchema();
                        $res['options'] = (array) TArray::indexedByUnique( $class::selectAll(), array_shift($sch::getPrimaryKeys()) );
                    }
                    if (!is_null($value))
                        $res['properties']['value'] = $value;

                break;
                case $schObj::MANY_TO_MANY :
                break;
            }
        }



        if (isset( $props['libelle'] ))
            $res['label']['libelle'] = $props['libelle'];

    	return $res;
    }

    public function __toString()
    {
        return '<input ' . $this->renderAttributes().' />';
    }

}