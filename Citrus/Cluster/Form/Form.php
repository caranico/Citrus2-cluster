<?php

namespace Citrus\Cluster\Form;

use Citrus\Cluster\Controller\ObjectController,
    Citrus\Cluster\TObject;

class Form extends Element {
    protected $properties;
    protected $elements;
    protected $values;
    protected $marker;
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
            $this->values = $mixed;
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

    public function addTransfert( Array $params )
    {
        $this->elements[ $params[0] ] = $params[1];
        $this->jsSchema[ $params[0] ] = $params[2];
    }

    public function render()
    {
        foreach ( $this->properties as $id => $props ) {
            if (!isset($props['definition']['primary']) && (!isset($props['constraint']['display']) || $props['constraint']['display'])) 
            {
                $constructor = Input::objFromProperties($id, $props, $this->values->$id);
                $this->elements[ $id ] = Input::create( $constructor );
                unset($constructor['options']);
                $this->jsSchema[ $id ] = $constructor;
            }
        }
    }

    public function getElement( $name = false )
    {
        if (!$name) return $this->elements;
        return $this->elements[ $name ];
    }

    public static function fromObject( $object, $action ) {

        $view = call_user_func(array($object, 'getView'));
        $sch = call_user_func(array($object, 'getSchema'));
        $propView = $view->getProperties();
        $propSchema = $sch->getProperties();
        foreach ($propView as $id => &$props)
            if (!is_array($props)) $props = array('libelle' => $props);

        $f = new self(array(
            "attributes" => array(
                "method" => "POST",
                "action" => "/classes/" . ObjectController::getSlug( get_class($object) ) . ( $object->id ? '/' . $object->id : '') .  "/" . $action . ".json"
            ),
            "properties" => array_merge_recursive( $propView, $propSchema)
        ));

        $f->attachObject( $object, true );
        return $f;
    }

    public function getHead( $callback = array(), $class=array() ) {
        $this->mergeAttribute('class', array_merge($class, array('jform')));
        return $this->generateValidationScript($callback) . '<span class="jformMarker" id="' . $this->marker . '"></span><form ' . $this->renderAttributes().'>';       
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
            $res .
            ($p['obligatoire'] ? '<span class="validInfo">* ' . $p['obligatoire'] . '</span>':'') .
            ($p['reset'] ? '<button type="reset" class="red"><span>' . $p['reset'] . '</span></button>':'') .
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
            'callbacks' => $arrCallBack
        );


        return "<script type='text/javascript'>".chr(10).
            "setTimeout(function () { $('#" . $this->marker . "').next().jform(" . TObject::jsonize( $params ) . "); });" .
        "</script>".chr(10);

    }


}