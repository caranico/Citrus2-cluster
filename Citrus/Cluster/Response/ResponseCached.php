<?php
namespace Yvelines\Citrus\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class ResponseCached extends Response
{

	public function __construct(Request $request, $content = '', $status = 200, $headers = array())
	{

		$hash = md5( $content );
		$headers = array('Etag' => $hash );

		if ($request->server->get('HTTP_IF_NONE_MATCH') && stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) == $hash ) 
		{
			$headers['Content-Length'] 	= 0;
    		parent::__construct( '', 304, $headers );
		} 
		else {
			$headers['Date'] 			= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['Last-Modified'] 	= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['content-type'] 	= "text/html;charset=utf-8";
			$headers['Expires'] 		= gmdate("D, d M Y H:i:s", ( time() + 60*60*24*50 ) )." GMT";
		    if ( strpos($request->server->get('HTTP_ACCEPT_ENCODING'), 'gzip') !== false ) {
		        $content = gzencode(trim($content), 9);
		        $headers['Content-Encoding'] = 'gzip';
		    }
			$headers['Content-Length'] 	= strlen($content);

    		parent::__construct( $content, 200, $headers );
		}
	}

	static public function get(Request $request, $content = '', $status = 200, $headers = array()) {
      	return new self($request, $content , $status, $headers);
	}

}