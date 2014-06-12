<?php

namespace Yvelines\Citrus\Form\Elements;

use Yvelines\Citrus\Form\Input;

class InputPassword extends Input {

    public function __construct( Array $params = array() ) {
        $this->params = $params;
        parent::__construct( isset($params['properties']) ? array_merge( array( 'type' => 'password' ), $params['properties'] ) : array());
    }

    public function __toString()
    {
        return '<label>' .
        		'<span class="label">' . $this->params['label']['libelle'] . '</span>' .
        		'<input ' . $this->renderAttributes().' />' .
			'</label>';
    }

}