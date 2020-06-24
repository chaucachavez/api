<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\comunicado;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
  

class comunicadoController extends Controller 
{     

    public function reporte(Request $request, $enterprise, $id, $idpersonal)
    {    
        $objEntidad = new Entidad();
        $comunicado = comunicado::find($id);

        $this->getToken($request); 

        $entidad = $objEntidad->entidad(array('entidad.identidad' => $idpersonal), '', true);
        
        $pdf = app('dompdf.wrapper');

        $logopdf = null; 
        if (in_array($entidad->idempleador, [87, 239, 240, 244, 245, 256, 259, 261, 262, 263, 4844, 31058])) { 
            $logopdf = "https://sistemas.centromedicoosi.com/img/osi/logopdfinvoiceosi.png";
        } 

        if (in_array($entidad->idempleador, [25425, 29508])) { 
            $logopdf = "https://sistemas.centromedicoosi.com/img/osi/logopdfinvoiceunion.png";
        }
       
        $data = '<html>
                <head>
                    <style>
                        @page { 
                            margin: 15px 30px;
                        }

                        body { 
                            margin: 15px 30px;
                            font-family: Arial, Helvetica, sans-serif; 
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div style="margin-bottom: 10px;"> 

                        <div style="float: left; padding-top: 20px; font-size: 13px;">
                            <strong>EMPLEADOR: '.$entidad->empleador.'</strong>
                        </div> 
                        <div style="clear: both;"></div>
                    </div>
                    <div style="text-align: center; padding: 20px;">'.
                        '<strong>'.mb_strtoupper($comunicado->titulo).'<strong>
                    </div> '.
                    $comunicado->descripcion .
                '</body>
                </html>'; 
        
        $pdf->loadHTML($data);
 
        // return $pdf->download('mi-archivo.pdf');
        return \PDF::loadHTML( $data)->stream('archivo.pdf');
        
    }   
}
