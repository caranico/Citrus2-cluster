<?php

namespace Citrus\Cluster\Form\Elements;

use Citrus\Cluster\Form\Input;

class SelectOne extends Input {

    private $value;

    public function __construct( Array $params = array() ) {
        $this->params = $params;
        if (isset($params['properties']['value']))
        {
            $this->value = $params['properties']['value'];
            unset( $params['properties']['value'] );
        }
        parent::__construct( isset($params['properties']) ? $params['properties'] : array());
    }

    public function __toString()
    {
        switch ($this->params['appearence'])
        {
            case 'minimal':
                return '<span class="choice">' .
                        '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                        $this->renderRadios().
                    '</span>';
            break;
            default:
                return '<label>' .
                        '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                        '<select ' . $this->renderAttributes().'>' .
                        $this->renderOptions().
                        '</select>' .
                        '<span class="select">' . $this->value . '</span>' .
                    '</label>';
            break;
        }
    }

    private function renderOptions() {
        if (!isset( $this->params['options'])) return;
        $reel = is_a( $this->value, '\Citrus\Cluster\Orm\ModelInterface') ? $this->value->id : $this->value;
        $res = array();
        foreach ($this->params['options'] as $id=>$el)
            $res [] = '<option value="' . $id . '"' . ($id == $reel ? ' selected="selected"':''). '>' . $el . '</option>';
        return implode('', $res);
    }

    private function renderRadios() {
        if (!isset( $this->params['options'])) return;
        $reel = is_a( $this->value, '\Citrus\Cluster\Orm\ModelInterface') ? $this->value->id : $this->value;
        $res = array();
        $first = true;
        foreach ($this->params['options'] as $id=>$el)
        {
            $res [] = '<label>' .
                    '<input type="radio" ' . $this->renderAttributes() . ' value="' . $id . '"' . ($id == $reel || (!isset($reel) && $first) ? ' checked="checked"':''). ' />' .
                    '<span class="radio"></span>' .
                    '<span class="label">' . $el . '</span>' .
                '</label>';
            $first = false;
        }
        return implode('', $res);
    }

}