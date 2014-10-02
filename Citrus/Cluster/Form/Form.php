<?php

namespace Citrus\Cluster\Form;

use Citrus\Cluster\Controller\ObjectController,
    Citrus\Cluster\TObject,
    Citrus\Cluster\TArray;

class Form extends Element {
    protected $properties;
    protected $elements;
    protected $values;
    protected $marker;
    protected $classSlug = '';
    protected $jsSchema;
    protected $jsMethod = 'post';

    public function __construct( Array $params = array())
    {
        $this->properties = $params['properties'];
        $this->marker = md5(uniqid(rand(), true));
        parent::__construct( $params['attributes'] );
    }

    public function __toString()
    {
        return $this->getHead() . 
            implode(chr(10), $this->elements) . 
            $this->getFoot();
    }

    public function attachObject( $mixed, $render = false ) 
    {        
        if (is_array( $mixed ))
            $this->values = (object) $mixed;
        else if (is_object( $mixed ) && is_a( $mixed, '\Citrus\Cluster\Orm\ModelInterface')) 
        {
            $this->values = $mixed;
            $info = $mixed->getSchema()->getInformations();
            $this->classSlug = ObjectController::getSlug(substr($info['class'], strpos($info['class'], '\\', 1)));
        }
        if ($render) $this->render();
    }

    public function setMethod( $method )
    {
        $this->jsMethod = $method;
    }

    public function getTransfert( $name )
    {
        return array(
            $name,
            $this->elements[ $name ],
            $this->jsSchema[ $name ]
        );
    }

    public function addTransfert( Array $params, $newName = false )
    {
        $this->elements[ $newName ? $newName : $params[0] ] = $params[1];
        $this->jsSchema[ $newName ? $newName : $params[0] ] = $params[2];
    }

    public function addProperties( $params, $value = false )
    {
        if (is_array( $params ))
            $this->properties = TArray::merge( $this->properties, $params );
        else $this->properties[ $params ] = $value;
    }

    public function getProperties( $key = false )
    {
        if ($key === false) return $this->properties;
        else if (isset($this->properties[ $key ])) return $this->properties[ $key ];
        else return false;
    }

    public function render()
    {
        $this->elements = array();
        foreach ( $this->properties as $id => $props ) {
            if (!isset($props['definition']['primary']) && (!isset($props['constraint']['display']) || $props['constraint']['display'])) 
            {
                $constructor = Input::objFromProperties($id, $props, $this->values ? $this->values->$id : null);
                $this->elements[ $id ] = Input::create( $constructor );
                unset($constructor['options']);
                unset($constructor['properties']['value']);
                unset($constructor['targetClass']);
                $this->jsSchema[ $id ] = $constructor;
            }
        }
    }

    public function getElement( $name = false )
    {
        if (!$name) return $this->elements;
        else if (substr($name, -1, 1) == '*')
        {
            $ret = array();
            foreach ($this->elements as $id => $el) {
                if (substr( $id, 0, strlen($name)-1) == substr( $name, 0, strlen($name)-1)) {
                    $ret[ $id ] = $el;
                }
            }
            return $ret;
        }
        else return $this->elements[ $name ];
    }

    public static function fromObject( $object, $action, $generate = true ) {

        $view = call_user_func(array($object, 'getView'));
        $sch = call_user_func(array($object, 'getSchema'));
        $propView = $view->getProperties();
        $propSchema = $sch->getProperties();
        foreach ($propView as $id => &$props)
            if (!is_array($props)) $props = array('libelle' => $props);

        $f = new self(array(
            "attributes" => array(
                "method" => "POST",
                "action" => "/classes/" . ObjectController::getSlug( $object->getClass() ) . ( $object->id ? '/' . $object->id : '') .  "/" . $action . ".json"
            ),
            "properties" => TArray::merge( $propSchema, $propView )
        ));

        $f->attachObject( $object, $generate );
        return $f;
    }

    public function getHead( $callback = array(), $class=array() ) {
        $this->mergeAttribute('class', array_merge($class, array('jform')));
        return $this->generateValidationScript($callback) . $this->getMarker() . '<form ' . $this->renderAttributes().'>';       
    }

    public function getMarker() {
        return '<span class="jformMarker" id="' . $this->marker . '"></span>';       
    }

    public function getFoot( $param = array() ) {
        $p = array_merge( array(
            "reset" => 'Annuler',
            "obligatoire" => 'Champs de saisie<br />obligatoire',
            "submit" => "Valider",
            "button" => false,
            "addEl" => false
        ), $param);
        $res='';
        if ( $p['addEl'] && is_array($p['addEl']) ) foreach($p['addEl'] as $k) $res .= isset( $this->elements[$k] ) ? $this->elements[$k] : $k;
        return '
        <div class="form_ctrl">'.
            ($p['obligatoire'] ? '<span class="validInfo">* ' . $p['obligatoire'] . '</span>':'') .
            ($p['reset'] ? '<button type="reset" class="red"><span>' . $p['reset'] . '</span></button>':'') .
            $res .
            ($p['submit'] ? '<button type="submit" class="green"><span>' . $p['submit'] . '</span></button>':'') .
            ($p['button'] ? '<button type="button"><span>' . $p['button'] . '</span></button>':'') .
        '</div></form>'.chr(10);
    }

    private function generateValidationScript( $arrCallBack = array()) {

        $params = array(
            'options' => array(
                'method' => $this->jsMethod
            ),
            'schema' => $this->jsSchema,
            'objectSlug' => $this->classSlug,
            'callbacks' => $arrCallBack
        );


        return "<script type='text/javascript'>".chr(10).
            "setTimeout(function () { $('#" . $this->marker . "').next().jform(" . TObject::jsonize( $params ) . "); });" .
        "</script>".chr(10);

    }


}