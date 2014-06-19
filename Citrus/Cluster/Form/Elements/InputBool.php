<?php

namespace Citrus\Cluster\Form\Elements;

use Citrus\Cluster\Form\Input;

class InputBool extends Input {

    private $value;
    static $defaultAppearance = 'default';

    public function __construct( Array $params = array() ) {
        $this->params = $params;
        if (!isset($this->params['value']))
            $this->params['value'] = false;


        if (!isset($this->params['appearence'])) $this->params['appearence']= InputBool::$defaultAppearance;
        parent::__construct( isset($params['properties']) ? array_merge( array( 'type' => 'checkbox' ), $params['properties']) : array());
    }

    public function __toString()
    {
        switch ($this->params['appearence'])
        {
            case 'switch' :
                return '<label>' .
                        '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                        '<span class="switch ' . ($this->params['value'] ? 'oui' : 'non') . '"><span></span></span>' .
                    '</label>' .
                    '<input type="hidden" name="' . $this->params['properties']['name'] . '" value="' . ($this->params['value'] ? '1' : '0') . '" />';
            break;
            default:
                return '<label class="bool">' .
                        '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                        parent::__toString() .
                        '<span class="checkbox"></span>' .
                        '<span class="label">' . ($this->params['value'] ? 'Oui' : 'Non') . '</span>' .
                    '</label>';
            break;
        }
    }
}