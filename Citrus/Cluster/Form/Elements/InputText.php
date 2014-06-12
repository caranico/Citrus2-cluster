<?php

namespace Citrus\Cluster\Form\Elements;

use Citrus\Cluster\Form\Input;

class InputText extends Input {

    public function __construct( Array $params = array() ) {
        $this->params = $params;
        parent::__construct( isset($params['properties']) ? array_merge( array( 'type' => 'text' ), $params['properties'] ) : array());
    }

    public function __toString()
    {
        return '<label>' .
        		'<span class="label">' . $this->params['label']['libelle'] . '</span>' .
        		'<input ' . $this->renderAttributes().' />' .
			'</label>';
    }

}