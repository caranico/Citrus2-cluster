<?php

namespace Yvelines\Citrus\Form;

abstract class Element {

    protected $attributes = array();

    public function __construct( Array $params = array())
    {
    	$this->attributes = $params;
    }

    public function setAttribute( $ident, $value = false ) 
    {
    	$this->attributes[ $ident ] = $value;
    }

    public function getAttribute( $ident )
    {
        if (isset($this->attributes[ $ident ]))
    	   return $this->attributes[ $ident ];
        else return ;
    }

    public function mergeAttribute( $ident, $value )
    {
        $init = isset($this->attributes[ $ident ]) ? $this->attributes[ $ident ] : array();
        if (is_array( $init ))
            $this->attributes[ $ident ] = array_merge( $init, $value );
    }

    public function renderAttributes() {
    	$arrRender = array();
    	foreach ($this->attributes as $key => $val)
    		$arrRender[] = $key . '="' . ( is_array( $val ) ? implode(';', $val) : $val ) . '"';
    	return implode(' ', $arrRender);
    }

}