<?php

namespace Citrus\Cluster\View\AsseticFilter;

use Assetic\Asset\AssetInterface,
    Assetic\Filter\FilterInterface;

class JsRemoveComments implements FilterInterface  
{
    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $asset->setContent(preg_replace(
        	array(
        		"/^\n*\/\*.*?\*\/\n*/s",
        		"/^\n*\/\/.*?\n*$/s"
        	), array(
        		"",
        		"",
        	), $asset->getContent()));
    }
}