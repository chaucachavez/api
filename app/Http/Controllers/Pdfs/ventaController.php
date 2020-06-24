<?php
namespace App\Http\Controllers\Pdfs;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller;
use App\Models\empresa; 
use App\Models\entidad; 
use App\Models\venta; 
use App\Models\cicloatencion; 
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 

class PDF extends baseFpdf 
{
    public $printBy;
    public $web;
    public $borde = 0; 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'RECIBO INTERNO';
    
    function Footer() 
    {
        $this->SetY(-10);
        $this->SetDrawColor(1, 87, 155); 
        $this->SetFont('Arial', 'I', 8);
                
        $this->Line(5, $this->GetY() , 205, $this->GetY());  
        
        $this->Cell(70, 5, $this->web, $this->borde);
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . '/{nb}', $this->borde, 1, 'R'); 
        $this->Cell(0, 5, utf8_decode('Impresión: ').  date('d/m/Y H:i') . ' - '. utf8_decode($this->printBy), $this->borde);
    } 
    
    function Header()
    {    
        $this->SetDrawColor(0, 0, 0); 
        $this->Image($this->path.$this->logo, 5, 5, 40, 0, 'PNG'); 
        $this->Ln(20);
    }
}

class ventaController extends Controller 
{       
    public function reporte(Request $request, $enterprise, $id)
    {            
        $Objventa = new venta();
        
        $venta = $Objventa->venta($id, true);
            
        switch ($venta->iddocumentofiscal) {
            case 4: //Recibo interno 
                $this->reciboInterno($id, $venta, $request);
                break;
            default:
                $this->documentoVenta($id, $venta);
                break;
        }         
    }
    
    private function documentoVenta($id, $venta)
    {
        $Objventa = new venta();
            
        $ventadet = $Objventa->ventadet($id);
        
        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $serienumero = utf8_decode('N° ') . str_pad($venta->serienumero, 6, "0", STR_PAD_LEFT);
        $efectivo = in_array($venta->idmediopago, [1, 4]) ? 'X' : '';
        $tarjeta = in_array($venta->idmediopago, [2, 3, 4]) ? 'X' : '';
        $d = substr($venta->fechaventa, 0, 2);
        $m = substr($venta->fechaventa, 3, 2);
        $y = substr($venta->fechaventa, 6, 4);
        $mes = $meses[(int) $m - 1];
        $border = 1; 
        
        $pdf = new baseFpdf('L', 'mm', [148, 210]); 
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 11);   
        $pdf->Ln(20);
        
        /*Fecha, Numero y Serie de comprobante*/
        $pdf->Cell(40);
        $pdf->Cell(10, 7, $d, $border);
        $pdf->Cell(3);
        $pdf->Cell(22, 7, $mes, $border);
        $pdf->Cell(3);
        $pdf->Cell(7, 7, substr($y, -2), $border);
        $pdf->Cell(30);
        if ($border) {
            $pdf->Cell(20, 7, $venta->serie, $border);
            $pdf->Cell(30, 7, $serienumero, $border);
        }
        $pdf->Ln(12);
        
        /*Paciente y Numero H.C.*/
        $pdf->Cell(10);
        $pdf->Cell(148, 7, utf8_decode($venta->cliente), $border);
        $pdf->Cell(17, 7, $venta->hc, $border); 
        $pdf->Ln(8);
        
        /*Documento identdad y Tipo de pago*/
        $pdf->Cell(10);
        //$pdf->Cell(70, 7, utf8_decode($venta->direccion), $border);
        $pdf->Cell(70, 7, '', 0);
        $pdf->Cell(8);
        $pdf->Cell(45, 7, utf8_decode($venta->numerodoc), $border);  
        $pdf->Cell(20);
        $pdf->Cell(11, 7, $efectivo, $border);
        $pdf->Cell(11, 7, $tarjeta, $border);        
        $pdf->Ln(18);
                
        /*Conceptos de cobro*/
        foreach ($ventadet as $row) {
            $nombreproducto = utf8_decode($row->nombreproducto) . (!empty($row->descripcion)?(' '. utf8_decode($row->descripcion)) : '');

            $pdf->Cell(10, 6, '', 0);
            $pdf->Cell(15, 6, $row->cantidad, $border);
            $pdf->Cell(85, 6, mb_substr($nombreproducto, 0, 42) , $border);
            $pdf->Cell(20, 6, $row->preciounit, $border);
            $pdf->Cell(20, 6, $row->descuento, $border);
            $pdf->Cell(25, 6, $row->total, $border, 0, 'R');
            $pdf->Ln();
        }
        
        /*Fecha y Monto de cobro*/
        $pdf->SetY(-38);  
        $pdf->Cell(125);
        $pdf->Cell(25, 7, 'Descuento', 0, 0, 'R');
        $pdf->Cell(25, 7, round($venta->descuento * 1.18, 2), $border, 0, 'R');
        // $pdf->Ln();

        $pdf->SetY(-30); 
        $pdf->Cell(50);
        $pdf->Cell(10, 7, $d, $border);
        $pdf->Cell(3);
        $pdf->Cell(22, 7, $mes, $border);
        $pdf->Cell(3);
        $pdf->Cell(7, 7, substr($y, -2), $border);
        $pdf->Cell(30);
        $pdf->Cell(25, 7, 'Total', 0, 0, 'R');
        $pdf->Cell(25, 7, $venta->total, $border, 0, 'R');

        $pdf->Output(); 
        exit;
    }
    
    private function reciboInterno($id, $venta, $request)
    {
        $Objventa = new venta();
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        $objCicloatencion = new cicloatencion();
                
        $this->getToken($request);
        
        $pdf = new PDF(); 
        $pdf->SetMargins(5, 5, 5);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AliasNbPages();  
        
        $pdf->SetFont('Arial', 'B', 8);
        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]);   
        //$this->empresa = $objEmpresa->empresa(['empresa.idempresa' => $this->objTtoken->myenterprise]);   
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);   
        
        $autorizaciones = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $venta->idcicloatencion]);
        //dd($venta->idcicloatencion);
        $pdf->printBy = $this->entidad->entidad;        
        $pdf->web = $this->empresa->paginaweb;
        $pdf->logo = $this->empresa->url.'/'.$this->empresa->imglogologin; 
        
        $ventadet = $Objventa->ventadet($id);
        $serienumero = $venta->serie .' - '.str_pad($venta->serienumero, 6, "0", STR_PAD_LEFT);
        
        //dd($venta);
        $pdf->AddPage();  
        
        /*Titulo del reporte*/
        $pdf->SetFont('Arial', 'BU', 14);
        $pdf->Cell(200, 6, utf8_decode($pdf->titulo), 0, 1, 'C');
        $pdf->Ln(10);
        
        /*Datos personales del cliente*/               
        $pdf->SetFont('Arial', 'BU', 10);
        $pdf->Cell(10);
        $pdf->Cell(0, 6, 'DATOS DEL CLIENTE', 0, 1);
        // dd('<pre>', $venta);
        $pdf->Cell(10);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 6, 'Cliente: ', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(80, 6, utf8_decode($venta->cliente), 0);
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(15, 6, utf8_decode('N°HC: '), 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(20, 6, $venta->hc, 0);
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(20, 6, $venta->abrevdocumento . ':', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(25, 6, $venta->numerodoc, 0);
        $pdf->Ln();
        $pdf->Ln();
            
        /*Datos personales del cliente*/               
        $pdf->SetFont('Arial', 'BU', 10);
        $pdf->Cell(10);
        $pdf->Cell(0, 6, 'DATOS DEL DOCUMENTO', 0, 1);
        
        $pdf->SetFont('Arial', 'B', 10);        
        $pdf->Cell(10);
        $pdf->Cell(20, 6, 'Sede: ', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(115, 6, utf8_decode($venta->nombresede), 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 6, 'Fecha: ', 0);        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(25, 6, utf8_decode($venta->fechaventa), 0, 1);
        
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(10);
        $pdf->Cell(22, 6, 'Documento' . ':', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(113, 6,$venta->nombredocfiscal, 0);
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(20, 6, utf8_decode('Número') . ':', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(25, 6, $serienumero);
        
        
        /*Autorizaciones de seguro*/
        if($autorizaciones)
        {
            $pdf->Ln();
            $pdf->Ln(); 
            $pdf->SetFont('Arial', 'BU', 10);
            $pdf->Cell(10);
            $pdf->Cell(0, 6, 'AUTORIZACIONES DE SEGURO', 0, 0);

            foreach($autorizaciones as $row)
            { 
                //dd($row);
                $pdf->Ln();
                $pdf->SetFont('Arial', 'BI', 10); 
                $pdf->Cell(10);
                $pdf->Cell(15, 6, 'Seguro: ');
                $pdf->SetFont('Arial', 'I');
                $pdf->Cell(50, 6, utf8_decode($row->nombreaseguradoraplan));

                $pdf->SetFont('Arial', 'BI');
                $pdf->Cell(35, 6, utf8_decode('Orden autorización:'));
                $pdf->SetFont('Arial', 'I');
                $pdf->Cell(35, 6, $row->codigo); 

                $pdf->SetFont('Arial', 'BI');
                $pdf->Cell(20, 6, 'Fecha: ');
                $pdf->SetFont('Arial', 'I');
                $pdf->Cell(25, 6, $row->fecha);  
                
                if(!empty($row->descripcion))
                {
                    $pdf->Ln();
                    $pdf->SetFont('Arial', 'BI', 10); 
                    $pdf->SetFillColor(250, 250, 250);  
                    $pdf->Cell(10);
                    $pdf->Cell(25, 6, 'Comentario: ' );
                    $pdf->SetFont('Arial', 'I'); 
                    $pdf->MultiCell(155, 6, utf8_decode($row->descripcion) );
                }
            }
        } 
        
        
        /*Conceptos de cobro*/
        $pdf->Ln(20);
        $pdf->SetDrawColor(1, 87, 155);  
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(1, 87, 155); 
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(10, 6, '', 0);
        $pdf->Cell(15, 6, 'Cant.', 1, 0, 'L',true);
        $pdf->Cell(100, 6, utf8_decode('Descripción'), 1, 0, 'L',true);
        $pdf->Cell(20, 6, 'P.Unit', 1, 0, 'L',true);
        $pdf->Cell(20, 6, 'Dscto.', 1, 0, 'L',true);
        $pdf->Cell(25, 6, 'Importe', 1, 1, 'R',true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        foreach ($ventadet as $row) {
            $nombreproducto = utf8_decode($row->nombreproducto) . (!empty($row->descripcion)?(' '. utf8_decode($row->descripcion)) : '');

            $pdf->Cell(10, 6, '', 0);
            $pdf->Cell(15, 6, $row->cantidad, 1);
            $pdf->Cell(100, 6, mb_substr($nombreproducto, 0, 45) , 1);
            $pdf->Cell(20, 6, $row->preciounit, 1);
            $pdf->Cell(20, 6, $row->descuento, 1);
            $pdf->Cell(25, 6, $row->total, 1, 0, 'R');
            $pdf->Ln();
        } 
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(165, 7, 'TOTAL:', 0, 0, 'R');
        $pdf->Cell(25, 7, $venta->total, 1, 0, 'R');
        
        $pdf->Output();  
    }
}
