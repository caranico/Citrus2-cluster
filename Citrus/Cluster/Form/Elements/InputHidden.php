<?php

namespace Citrus\Cluster\Form\Elements;

use Citrus\Cluster\Form\Input;

class InputHidden extends Input {

    public function __construct( Array $params = array() ) {
    	$this->params = $params;
        parent::__construct( isset($params['properties']) ? array_merge( array( 'type' => 'hidden' ), $params['properties']) :array( 'type' => 'hidden'));
    }

}