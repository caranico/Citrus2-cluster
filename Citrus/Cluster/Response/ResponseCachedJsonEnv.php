<?php
namespace Citrus\Cluster\Response;

use Symfony\Component\HttpFoundation\Request,
	Citrus\Cluster\Message;

class ResponseCachedJsonEnv extends ResponseCachedJson
{
	public function __construct( Request $request, $element, $retCode = 200 )
	{
		parent::__construct( $request, array(
			'content' => $element,
			'infos' => Message::getAll()
		), $retCode);
	}

	static public function get( Request $request, $element, $retCode = 200 ) {
      	return new self($request, $element, $retCode);
	}

}