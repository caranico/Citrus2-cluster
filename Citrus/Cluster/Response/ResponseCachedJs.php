<?php
namespace Yvelines\Citrus\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Assetic\Asset\AssetCollection;


class ResponseCachedJs extends Response
{

	public function __construct( Request $request, AssetCollection $assetCollection, $cachePath )
	{
		$hash = md5( $assetCollection->getLastModified() . json_encode( $assetCollection->all()));
		$headers = array('Etag' => $hash );

		if ($request->server->get('HTTP_IF_NONE_MATCH') && stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) == $hash ) 
		{
			$headers['Content-Length'] 	= 0;
    		parent::__construct( '', 304, $headers );
		} 
		else {
			if (!is_dir($cachePath)) mkdir($cachePath,0777, true);
			$file = $cachePath . 'js_' . $hash;
			if (!is_file( $file )) 
				file_put_contents($file, $assetCollection->dump());
			$ret = file_get_contents( $file );
			$headers['Date'] 			= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['Last-Modified'] 	= gmdate("D, d M Y H:i:s", $assetCollection->getLastModified())." GMT";
			$headers['content-type'] 	= "application/javascript;charset=utf-8";
			$headers['Expires'] 		= gmdate("D, d M Y H:i:s", ( time() + 60*60*24*50 ) )." GMT";

			if ( strpos($request->server->get('HTTP_ACCEPT_ENCODING'), 'gzip') !== false ) {
		        $ret = gzencode(trim($ret), 9);
		        $headers['Content-Encoding'] = 'gzip';
		    }
			$headers['Content-Length'] 	= strlen($ret);

    		parent::__construct( $ret, 200, $headers );
		}
	}

	static public function get( Request $request, AssetCollection $assetCollection, $context ) {
      	return new self($request, $assetCollection, $context['cache'] .'/assetic/');
	}

}