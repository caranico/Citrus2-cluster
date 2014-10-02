<?php

namespace Citrus\Cluster\Controller;

use Symfony\Component\HttpFoundation\Request as SfRequest,
	Citrus\Cluster\Response\ResponseCachedJson,
	Citrus\Cluster\Form,
	Citrus\Cluster\Message,
	Symfony\Component\HttpFoundation\RedirectResponse;

class ObjectController extends Controller {

	protected $resource;
	protected $className;

	public function __construct() 
	{
		$this->className = preg_replace('/Controller$/', '', get_called_class());
	}

	public function doEdit( SfRequest $request )
	{
		$this->retrieveInstance($request);

		if (!$this->resource) 
			$this->resource = new $this->className();

		if ($request->getMethod() == 'POST') {
			if ($this->resource->hydrate( $request->request->all() ))
			{
				Message::addInfo($this->resource->getView()->getInformations('name'), 'Modification de ' . $this->resource );
				$this->resource->save();
			}

			if (!$request->isXmlHttpRequest())
				return new RedirectResponse("/classes/" . $this->getSlug( $this->className ) . "/" . $this->resource->id . "/edit");
		}

		$form = Form\Form::fromObject( $this->resource, 'edit' ) ;
		$form->setMethod( $request->isXmlHttpRequest() ? 'post' : 'inline');
		return array( 
			'resource' => $this->resource->toArray(),
			'object' => $this->resource->getView()->getInformations('name'),
			'form' => $form
		);
	}

	public function doDelete( SfRequest $request )
	{
		$this->retrieveInstance($request);
		if ($this->resource) {
			$this->resource->delete();
		}
	}

	public function doListMany( SfRequest $request )
	{
		$this->retrieveInstance($request);
        $prop = $request->get( 'property' );
		if ($this->resource && $prop) {
			$arr = array();
			foreach ( $this->resource->$prop as $el ) 
				$arr[] = $el->toArray( false, true );
			return $arr;
		}
	}

	public function doView( SfRequest $request )
	{
		$res = $this->doEdit( $request );
		$res['form']->setMethod('inline');
		return $res;

	}

	public function doList( SfRequest $request )
	{
		$schema = call_user_func_array( array( $this->className, 'getSchema'), array());
		$view = call_user_func_array( array( $this->className, 'getView'), array());

        $url = array_shift(explode('?', $request->getRequestUri()));
        $ext = false;
        if (false !== strpos($url, '.')) 
	        $ext = substr($url, strrpos($url, '.')+1);


        if ( $request->isXmlHttpRequest() && (!$ext || $ext != 'html')) {
	        return $view->jqgridList( $request );
        }
        else if ($ext && $ext != 'html') {
 	        return $view->export( $request );
        }
        else 
        {
        	$search = $view->getList('searchTemplate');
        	if (is_object($search) && $search instanceOf \Closure)
        		$search = $search( $request->currentApp );
			return array(
				'object'=> $view->getInformations('name'),
				'objects'=> $view->getInformations('pluriel'),
				'className'=> $this->className,
				'search'=> $search,
				'fields'=> $view->getList('search'),
				'jqgrid'=> $view->jqgrid(),
			);
		}

	}

    public function doListAction( SfRequest $request ) {
        $action = $request->get( 'oper' );
        if ( $action && $request->isXmlHttpRequest() ) {
            $resp = array( 1 );
            switch ( $action ) {
                case 'edit' : $this->doEdit( $request ); break;
                case 'del' : $this->doDelete( $request ); break;
                default : $resp = array(0, 'Action non trouvée');
            }

            return ResponseCachedJson::get( $request, $resp );
        }
    }

	public function retrieveInstance( SfRequest $request ) {
		$id = $request->get('id', false);
		if ($id) $this->resource = call_user_func_array( array( str_replace('Controller', '', get_called_class()), 'selectOne'), array( (int) $id ) );
	}

	public function setInstance( $object ) {
		$this->resource = $object;
	}


    static public function getSlug( $class )
    {
        $arr = explode('\\', $class );
        $app = array_shift( $arr );
        $arr = array_map('ucfirst', $arr);
        return implode('', $arr);
    }

    static public function getUnSlug( $class )
    {
        return preg_replace("/([a-z])([A-Z])/s", "$1\\\\$2", $class);
    }

}
