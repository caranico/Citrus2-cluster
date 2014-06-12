<?php

namespace Yvelines\Citrus\Form\Elements;

use Yvelines\Citrus\Form\Input;

class InputHidden extends Input {

    public function __construct( Array $params = array() ) {
    	$this->params = $params;
        parent::__construct( isset($params['properties']) ? array_merge( array( 'type' => 'hidden', $params['properties'] )) :array());
    }

}