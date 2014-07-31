<?php
namespace Citrus\Cluster\Response;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request;


class ResponseCachedXls extends Response
{

	public function __construct( Request $request, $string , $filename )
	{

		$hash = md5( json_encode($string) );

		$headers = array('Etag' => $hash );

		if ($request->server->get('HTTP_IF_NONE_MATCH') && stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) == $hash ) 
		{
			$headers['Content-Length'] 	= 0;
    		parent::__construct( '', 304, $headers );
		} 
		else {

			$cols = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$objPHPExcel = new \PHPExcel();
			$objPHPExcel->setActiveSheetIndex(0);


			foreach ($string as $id => $row) {
				$real = $id + 1;
				foreach ($row as $id => $val) {
					$col = $cols[$id];
					$objPHPExcel->getActiveSheet()->setCellValueExplicit($col.$real, (string) $val, \PHPExcel_Cell_DataType::TYPE_STRING);
				}
			}
			$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

			$headers['Date'] 			= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['Last-Modified'] 	= gmdate("D, d M Y H:i:s", time())." GMT";
			$headers['content-type'] 	= "application/vnd.ms-excel;charset=utf-8";
			$headers['content-disposition'] 	= "attachment; filename=\"$filename\"";
			$headers['Expires'] 		= gmdate("D, d M Y H:i:s", ( time() + 60*60*24*50 ) )." GMT";
			ob_start();
			$objWriter->save('php://output');
			$final = ob_get_clean();
			$headers['Content-Length'] 	= strlen($final);


    		parent::__construct( $final, 200, $headers );
		}
	}

	static public function get( Request $request, $string , $filename ) {
      	return new self($request, $string , $filename);
	}

}