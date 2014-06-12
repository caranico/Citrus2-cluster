<?php
namespace Yvelines\Citrus\Response;

use Symfony\Component\HttpFoundation\Request;

class ResponseCachedJsonEnv extends ResponseCachedJson
{
	public function __construct( Request $request, $element, $retCode = 200 )
	{
		parent::__construct( $request, array(
			'content' => $element,
			'infos' => array(
				array(
					'type' => 'highlight',
					'title' => 'titre test',
					'content' => 'blablabla',
					'link' => '/'
				),
				array(
					'type' => 'error',
					'title' => 'titre test',
					'content' => 'blablabla',
					'link' => '/'
				)
			)
		), $retCode);
	}

	static public function get( Request $request, $element, $retCode = 200 ) {
      	return new self($request, $element, $retCode);
	}

}