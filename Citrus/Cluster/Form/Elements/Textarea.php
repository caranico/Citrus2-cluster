<?php

namespace Citrus\Cluster\Form\Elements;

use Citrus\Cluster\Form\Input;

class Textarea extends Input {

    private $value;

    public function __construct( Array $params = array() ) {
        $this->params = $params;
        if (isset($params['properties']['value']))
        {
            $this->value = $params['properties']['value'];
            unset( $params['properties']['value'] );
        }
        parent::__construct( isset($params['properties']) ? array_merge( array( 'type' => 'text' ), $params['properties'] ) : array());
    }

    public function __toString()
    {
        return '<label ' . $this->renderLabelAttributes().'>' .
                '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                '<textarea ' . $this->renderAttributes().'>' . $this->value . '</textarea>' .
            '</label>';
    }

}