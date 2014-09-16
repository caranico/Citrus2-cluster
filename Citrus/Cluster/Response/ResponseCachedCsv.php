<?php
namespace Citrus\Cluster\Response;

use Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\Request;


class ResponseCachedCsv extends Response
{

	public function __construct( Request $request, $string , $filename )
	{

		$res = array();
		foreach ($string as $row)
			$res[] = implode(',',$row);
		$final = implode(chr(10), $res);


		$hash = md5( $final );
		$ext = substr($final, strrpos($final, '.')+1);

		$headers = array('Etag' => $hash );

		if ($request->server->get('HTTP_IF_NONE_MATCH') && stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) == $hash ) 
		{
			$headers['Content-Length'] 	= 0;
    		parent::__construct( '', 304, $headers );
		} 
		else {
			$headers['Date'] 			= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['Last-Modified'] 	= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['content-type'] 	= "text/csv;charset=utf-8";
			$headers['content-disposition'] 	= "attachment; filename=\"$filename\"";
			//$headers['Expires'] 		= gmdate("D, d M Y H:i:s", ( time() + 60*60*24*50 ) )." GMT";
			$headers['Content-Length'] 	= strlen($final);
    		parent::__construct( $final, 200, $headers );
		}
	}

	static public function get( Request $request, $string , $filename ) {
      	return new self($request, $string , $filename);
	}

}