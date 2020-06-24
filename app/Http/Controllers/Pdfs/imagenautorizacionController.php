<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\terapia;
use App\Models\citamedica;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\citaterapeutica;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
use App\Models\cicloautorizacion;
use App\Models\autorizacionimagen; 
use App\Http\Controllers\Controller; 
  
class PDF2 extends baseFpdf 
{    
    public $path = 'https://sistemas.centromedicoosi.com/img/';  
    // public $pathRubrica = 'http://lumenionic.pe/img_autorizaciones';
    public $pathRubrica = 'https://sistemas.centromedicoosi.com/apiosi/public/img_autorizaciones';

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\atenciones\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/atenciones/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/atenciones/'; 
}

class imagenautorizacionController extends Controller 
{     
    
    public function reporte($enterprise, $id, $previsualizacion = false)
    {   
        
        $objEntidad = new entidad();
        $objEmpresa = new empresa(); 
        $objCicloatencion = new cicloatencion();  
        $objAutorizacionimagen = new autorizacionimagen(); 
        $objCicloautorizacion = new cicloautorizacion();

        //Información general 
        $idempresa = $objEmpresa->idempresa($enterprise);
        $cicloautorizacion = $objCicloautorizacion->cicloautorizacion($id);
        $cicloatencion = $objCicloatencion->cicloatencion($cicloautorizacion->idcicloatencion, true);  

        $param = array(); 
        $param['autorizacionimagen.idcicloautorizacion'] = $id; 
        $data = $objAutorizacionimagen->grid($param); 
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]);
 
        // Datos decabecera 
        $pdf = new PDF2(); 
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(true, 0);  

        foreach ($data as $value) { 

            $imagen = getimagesize($pdf->pathRubrica.'/'.$value->nombre);
            $ancho = $imagen[0];
            $alto = $imagen[1];
            $orientacion = 'P';

            if ($ancho > $alto) {
                $orientacion = 'L';
            } 

            $pdf->AddPage($orientacion);      
            
            // $this->setXY(42, ($this->getY() - 3)); 
            
            // $pdf->Ln();
            

            if ($ancho > $alto) {
                $pdf->Image($pdf->pathRubrica.'/'.$value->nombre, $pdf->getX(), $pdf->getY(), 297);
            } else {
                //Mas alto: Generalmente el Sited Scanean 
                $pdf->Image($pdf->pathRubrica.'/'.$value->nombre, $pdf->getX(), $pdf->getY(), 210); 
            } 

            if ($previsualizacion) {  
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(255, 0, 0);               
                $pdf->Cell(0, 6, utf8_decode('( NO IMPRIMIR )'), 0, 0, 'C');
            }
        }

        // dd($previsualizacion);
        if ($previsualizacion) {
            $pdf->Output();  
            exit;
        } else {
            $nombreFile = 'AU' . '_' . (string) $cicloautorizacion->idcicloatencion . '_' . (string) $id;

            $pdf->Output('F', 'atenciones/' . $nombreFile . '.pdf');

            if (file_exists($pdf->pathImg . $nombreFile . '.pdf')) 
            {
                $mensaje = array('generado' => 1, 'mensaje' => $nombreFile);
            } else 
            {
                $mensaje = array('generado' => 0, 'mensaje' => 'PDF no se genero');
            }
            
            return $mensaje;
        }
    } 
}