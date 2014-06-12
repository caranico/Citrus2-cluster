<?php

namespace Citrus\Cluster\View\AsseticFilter;

use Assetic\Asset\AssetInterface;  
use Assetic\Filter\FilterInterface;

class CssUrlPath implements FilterInterface  
{
	public $basePath;

	public function __construct( $basePath )
	{
		$this->basePath = $basePath;
	}

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
    	$pathCss = '/asset' . array_pop( explode($this->basePath, $asset->getSourceRoot())) . '/';
    	$content = $asset->getContent();
    	$matches = array();
    	preg_match_all ( "/url ?\((['\"]?)([^'\")]*)(['\"]?)\)/s" , $content , $matches );
    	$arrRes= array();
    	if (isset($matches[0]) && count($matches[0]) > 0) {
    		for ($i=0; $i<count($matches[0]); $i++) {
    			if (substr($matches[2][ $i ], 0, 5) != 'data:')
    			$arrRes[]= array(
    				$matches[0][ $i ],
    				'url(' . $this->get_absolute( $matches[1][ $i ] . $pathCss . $matches[2][ $i ] . $matches[3][ $i ] ) . ')'
    			);

    		}
    	}

    	foreach ( $arrRes as $remp )
    		$content = str_replace( $remp[0] , $remp[1], $content );

        $asset->setContent($content);
    }

	private function get_absolute($path) {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}