<?php
namespace Citrus\Cluster\Response;

use Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\Request,
	Assetic\Asset\FileAsset,
	Citrus\Cluster\View\AsseticFilter,
	Assetic\Filter\Yui,
	Assetic\Asset\AssetCollection;

class ResponseCachedFile extends Response
{

	public function __construct( Request $request, $path  )
	{

		if (!is_file($path)) 
			parent::__construct( '', 404 );



		$ext = substr($path, strrpos($path, '.')+1);

		$hash = md5_file( $path );
		$headers = array('Etag' => $hash );

		if ($request->server->get('HTTP_IF_NONE_MATCH') && stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) == $hash ) 
		{
			$headers['Content-Length'] 	= 0;
    		parent::__construct( '', 304, $headers );
		} 
		else {
			$ret = file_get_contents( $path );

			$headers['Date'] 			= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['Last-Modified'] 	= gmdate("D, d M Y H:i:s", filemtime($path))." GMT";
			$headers['content-type'] 	= mime_content_type($path) . ";charset=utf-8";
			//$headers['Expires'] 		= gmdate("D, d M Y H:i:s", ( time() + 60*60*24*50 ) )." GMT";

		    if ( strpos($request->server->get('HTTP_ACCEPT_ENCODING'), 'gzip') !== false && in_array($ext, array('gif', 'png', 'jpg')) ) {
		        $ret = gzencode(trim($ret), 9);
		        $headers['Content-Encoding'] = 'gzip';
		    }

			$headers['Content-Length'] 	= strlen($ret);
    		parent::__construct( $ret, 200, $headers );
		}
	}

	static public function get( Request $request, $filename, $context, $path = false  ) {

		if (!is_file($filename)) {
			$arr = explode('/', $filename);
			$file = array_pop( $arr );
			$arr[]='templates';
			$arr[]=$file;
			$filename = implode('/', $arr);
		}

		$ext = substr($filename, strrpos($filename, '.')+1);

		switch ($ext) {
			case 'css' :
				$filtersPath = new AsseticFilter\CssUrlPath( $path ? $context['libs'] : $context['asset'], $path );
				$filtersCompress = new Yui\CssCompressorFilter( $context['vendor'] .'/nervo/yuicompressor/yuicompressor.jar');
				return ResponseCachedCss::get($request, new AssetCollection(array(new FileAsset($filename)), array($filtersPath, $filtersCompress)), $context );
				break;
			case 'js' :
				$filtersComment = new AsseticFilter\JsRemoveComments();
				$filtersCompress = new Yui\JsCompressorFilter( $context['vendor'] .'/nervo/yuicompressor/yuicompressor.jar');
				return ResponseCachedJs::get($request, new AssetCollection(array(new FileAsset($filename)), array($filtersComment, $filtersCompress)), $context );
				break;
			default:
				return new self($request, $filename);
			break;
		}


      	
	}

}