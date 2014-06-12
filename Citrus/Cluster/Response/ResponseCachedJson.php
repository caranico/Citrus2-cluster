<?php
namespace Citrus\Cluster\Response;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request;

class ResponseCachedJson extends Response
{

	public function __construct( Request $request, $element, $retCode = 200 )
	{
		$ret = json_encode($element);
		$hash = md5( $ret );
		$headers = array('Etag' => $hash );

		if ($request->server->get('HTTP_IF_NONE_MATCH') && stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) == $hash ) 
		{
			$headers['Content-Length'] 	= 0;
    		parent::__construct( '', 304, $headers );
		} 
		else {

			$headers['Date'] 			= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['content-type'] 	= "application/json;charset=utf-8";
			$headers['Expires'] 		= gmdate("D, d M Y H:i:s", ( time() + 60*60*24*50 ) )." GMT";
		    if ( strpos($request->server->get('HTTP_ACCEPT_ENCODING'), 'gzip') !== false ) {
		        $ret = gzencode(trim($ret), 9);
		        $headers['Content-Encoding'] = 'gzip';
		    }
			$headers['Content-Length'] 	= strlen($ret);
    		parent::__construct( $ret, $retCode, $headers );
		}
	}

	static public function get( Request $request, $element, $retCode = 200 ) {
      	return new self($request, $element, $retCode);
	}

}