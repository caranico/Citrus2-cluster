<?php

namespace Citrus\Cluster\Form\Elements;

use Citrus\Cluster\Form\Input;

class SelectMany extends Input {

    private $value;
    static $defaultAppearance = 'default';

    public function __construct( Array $params = array() ) {
        $this->params = $params;
        if (!isset($this->params['appearence'])) $this->params['appearence']= SelectOne::$defaultAppearance;
        if (isset($params['properties']['value']))
        {
            $this->value = $params['properties']['value'];
            unset( $params['properties']['value'] );
        }
        $params['properties']['name'] .= '-Ctrl';
        parent::__construct( isset($params['properties']) ? $params['properties'] : array());
    }

    public function __toString()
    {
        switch ($this->params['appearence'])
        {
            case 'minimal':
                return '<span class="choice">' .
                        '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                        $this->renderCheckboxes().
                    '</span><input type="hidden" name="' . $this->params['properties']['name'] . '" value="' . $this->renderValues(false). '" />';
            break;
            case 'table':

                $params = array(
                    'target' => $this->params['objClass'],
                    'fields' => $this->params['listFields'],
                    'from' => $this->params['targetProperty']
                );
                $sel = '<div class="manyTable" rel="' . htmlspecialchars( json_encode($params) , ENT_QUOTES ) . '">';

                if (isset($this->params['allowUpdate']) && $this->params['allowUpdate'])
                    $sel .= '<button class="orange"><span>Ajouter</span></button>';

                if (count($this->value) == 0) {
                    $sel .= '<span class="empty">Aucun élément</span>';
                } else {

                    $head = array();
                    $body = array();
                    $class = $this->params['targetClass'];
                    $sch = $class::getView();
                    $props = $sch->getProperties();
                    foreach ($this->params['listFields'] as $k=>$v) 
                        $head[]='<th><span>' . (is_array($props[ $v ]) ? $props[ $v ]['libelle'] : $props[ $v ]) . '</span><span class="sort"></span></th>';
                    if (isset($this->params['allowUpdate']) && $this->params['allowUpdate'])
                        $head[]='<th rel="actions"></th>';



                    foreach ($this->value as $value) {
                        $tbody = array();
                        foreach ($this->params['listFields'] as $k=>$v) 
                            $tbody[]='<td><span>' . $value->$v . '</span></td>';
                        if (isset($this->params['allowUpdate']) && $this->params['allowUpdate'])
                            $tbody[]='<td><button class="red"><span>Supprimer</span></button> <button class="green"><span>Editer</span></button></td>';
                        $body[] = '<tr id="' . $value->id . '">' . implode('', $tbody) . '</tr>';
                    }

                    $sel .= '<table>'.
                        '<thead>' .
                            '<tr>' . 
                                implode('', $head) . 
                            '</tr>' .
                        '</thead>' . 
                        '<tbody>' .
                            implode('', $body) . 
                        '</tbody>' . 
                    '</table>';

                }

                $sel .= '</div>';

                return $sel;
            break;
            default:
                return '<label ' . $this->renderLabelAttributes().'>' .
                        '<span class="label">' . $this->params['label']['libelle'] . '</span>' .
                        '<select ' . $this->renderAttributes().'>' .
                        $this->renderOptions().
                        '</select>' .
                        '<span class="select"></span>' .
                        '<div class="manyInfo"><ins>'. $this->params['label']['libelle'] .' associés :</ins> <i style="font-size:8pt">(cliquez sur son nom pour l\'enlever)</i></div>' .
                        '<div class="manyListe full">' . $this->renderValues(). '</div>' .
                    '</label><input type="hidden" name="' . $this->params['properties']['name'] . '" value="' . $this->renderValues(false). '" />';
            break;
        }
    }

    private function renderOptions() {
        if (!isset( $this->params['options'])) return;
        $reel = is_a( $this->value, '\Citrus\Cluster\Orm\ModelInterface') ? $this->value->id : $this->value;
        $res = array();
        foreach ($this->params['options'] as $id=>$el)
            $res [] = '<option value="' . $id . '">' . $el . '</option>';
        return implode('', $res);
    }

    private function renderValues( $html = true ) {

        $strOption = array();
        foreach ( $this->value as $elem ) {
            $strOption[]= $html ? '<span class="manyOption" id="enreg_'.$elem->id.'">'.
                ' <span class="elem">'.$elem.'</span>' .
                ' <span class="manySepar">, </span>'.
            '</span> ' : $elem->id;
        }
        return $html ? '<p class="manyEmpty" ' . ( $strOption != '' ? 'style="display:none;"' : '' ) . '>Aucun élement sélectionné</p>'. implode('', $strOption) : implode(',',$strOption);
    }

    private function renderCheckboxes() {
        if (!isset( $this->params['options'])) return;
        $reel = is_a( $this->value, '\Citrus\Cluster\Orm\ModelInterface') ? $this->value->id : $this->value;
        $res = array();
        $val = explode(',',$this->renderValues(false));
        foreach ($this->params['options'] as $id=>$el)
        {
            $res [] = '<label>' .
                    '<input type="checkbox" name="' . $this->params['properties']['name'] . '-Ctrl[' . $id . ']" value="' . $id . '"' . (in_array($id, $val) ? ' checked="checked"':''). ' />' .
                    '<span class="checkbox"></span>' .
                    '<span class="label">' . $el . '</span>' .
                '</label>';
        }
        return implode('', $res);
    }

}