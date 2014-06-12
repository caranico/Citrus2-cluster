<?php

namespace Citrus\Cluster\Loader;

interface Loader 
{
	public function __construct($config);
    public function getContent();
	static public function get($config, $exist = array());
}