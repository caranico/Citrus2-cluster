<?php

namespace Citrus\Cluster\Form;
use Citrus\Cluster\TArray,
    Doctrine\DBAL\Types\Type,
    Citrus\Cluster\Orm\ModelDispatcher;

abstract class Input extends Element {

    public $params;

    const INPUT_TEXT        = 'text';
    const INPUT_HIDDEN      = 'hidden';
    const INPUT_PASSWORD    = 'password';
    const INPUT_BOOL        = 'bool';
    const INPUT_FILE        = 'file';
    const SELECT_ONE        = 'selectOne';
    const SELECT_MANY       = 'selectMany';
    const TEXTAREA          = 'textarea';
    const WYSIWYG           = 'wysiwyg';
    const CHECKBOX          = 'checkbox';
    const BUTTON            = 'button';

    static private $elements = array(
        self::INPUT_TEXT => '\Citrus\Cluster\Form\Elements\InputText',
        self::INPUT_HIDDEN => '\Citrus\Cluster\Form\Elements\InputHidden',
        self::INPUT_PASSWORD => '\Citrus\Cluster\Form\Elements\InputPassword',
        self::SELECT_ONE => '\Citrus\Cluster\Form\Elements\SelectOne',
        self::SELECT_MANY => '\Citrus\Cluster\Form\Elements\SelectMany',
        self::TEXTAREA => '\Citrus\Cluster\Form\Elements\Textarea',
        self::INPUT_BOOL => '\Citrus\Cluster\Form\Elements\InputBool',
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

    public function setAttribute( $name, $val )
    {
        parent::setAttribute( $name, $val);
        if ($name == 'disabled')
        {
            $this->params['label']['attributes']['class'][] = 'disabled';
        }
    }

    static public function objFromProperties( $id, $props, $value = null )
    {
        $res = array();

        $res['className'] = self::INPUT_TEXT;
        $res['properties']['name'] = $id;
        if (isset($props['constraint']))
            $res['constraint']=array_map('addslashes', $props['constraint']);

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

            if (isset($def['notnull']))
                $res['notnull'] = $def['notnull'];

            switch ( $def['type'] ) 
            {
                case Type::TEXT:
                    $res['className'] = self::TEXTAREA;
                    break;
                case Type::BOOLEAN:
                    $res['className'] = self::INPUT_BOOL;
                    break;
                case Type::STRING:
                    if (isset($def['options'])) {
                        $res['className'] = self::SELECT_ONE;
                        $res['options'] = $def['options'];
                    }
                    break;
            }


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
                        $res['targetClass'] = $class;
                        $sch = $class::getSchema();
                        $res['objClass'] = \Citrus\Cluster\Controller\ObjectController::getSlug( substr($class, strpos($class, '\\', 1) ));
                        $res['options'] = (array) TArray::indexedByUnique( $class::selectAll(), array_shift($sch::getPrimaryKeys()) );
                        if (isset($rel['foreign']['property']))
                            $res['targetProperty'] = $rel['foreign']['property'];
                        if (isset($props['autocomplete'])) 
                            $res['autocomplete'] = $props['autocomplete'];
                    }
                    if (!is_null($value))
                        $res['properties']['value'] = $value;

                break;
                case $schObj::ONE_TO_MANY :
                case $schObj::MANY_TO_MANY :
                    $res['className'] = self::SELECT_MANY;
                    if (isset($rel['foreign']))
                    {
                        $class = $rel['foreign']['class'];
                        $res['targetClass'] = $class;
                        $sch = $class::getSchema();
                        $res['objClass'] = \Citrus\Cluster\Controller\ObjectController::getSlug( substr($class, strpos($class, '\\', 1) ));
                        $res['options'] = (array) TArray::indexedByUnique( $class::selectAll(), array_shift($sch::getPrimaryKeys()) );
                        if (isset($rel['foreign']['property']))
                            $res['targetProperty'] = $rel['foreign']['property'];
                    }
                    if (!is_null($value))
                        $res['properties']['value'] = $value;

                break;
            }
            if (isset($props['allowUpdate'])) $res['allowUpdate'] = $props['allowUpdate'];
        }

        if (isset($props['appearence']))
            $res['appearence'] = $props['appearence'];
        else if ( property_exists(self::$elements[ $res['className'] ], 'defaultAppearance' )) {
            $class = self::$elements[ $res['className'] ];
            $res['appearence'] = $class::$defaultAppearance;
        }

        if (isset($props['listFields'])) {
            $res['listFields'] = $props['listFields'];
        }

        if (isset( $props['libelle'] ))
            $res['label']['libelle'] = $props['libelle'];
        return $res;
    }

    public function renderLabelAttributes() {
        if (!isset($this->params['label']['attributes'])) return '';
        $arrRender = array();
        foreach ($this->params['label']['attributes'] as $key => $val)
            $arrRender[] = $key . '="' . ( is_array( $val ) ? implode(';', $val) : $val ) . '"';
        return implode(' ', $arrRender);
    }

    public function __toString()
    {
        return '<input ' . $this->renderAttributes().' />';
    }

}