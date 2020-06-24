<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use App\Models\ordencompra;
use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\cicloatencion; 
use App\Models\ciclomovimiento; 
use App\Models\presupuesto; 
use App\Models\venta; 
use App\Models\entidad; 
use App\Models\terapia;   
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{       
    public $printBy;
    public $web;
    public $borde = 0;
    public $nombresede;
    public $documentoSerieNumero;
    public $fecha;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'ORDEN DE COMPRA';
    
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
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, 'Sede:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->nombresede, $this->borde);
        $this->Ln();
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, utf8_decode('Fecha:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->fecha, $this->borde);
        $this->Ln(11);
    }
}

class ordencompraController extends Controller
{    
    public function __construct(Request $request) 
    {         
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        
        $this->getToken($request);
        
        $this->pdf = new PDF();       
        $this->pdf->SetMargins(5, 5, 5);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->AliasNbPages(); 
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->SetDrawColor(255, 255, 255); 
        
        $this->pdf->SetFont('Arial', 'B', 8);
        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]);   
        //$this->empresa = $objEmpresa->empresa(['empresa.idempresa' => $this->objTtoken->myenterprise]);   
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);   
        
        $this->pdf->printBy = $this->entidad->entidad;        
        $this->pdf->web = $this->empresa->paginaweb;
        $this->pdf->logo = $this->empresa->url.'/'.$this->empresa->imglogologin;   
    }
    
    public function reporte(Request $request, $enterprise, $id)
    {
        $objOrdencompra = new ordencompra();

        $ordencompra = $objOrdencompra->ordencompra($id);
        $ordencompradet = $objOrdencompra->ordencompradet($id);

        $h = 10;
        $this->pdf->nombresede = $ordencompra->nombresede;
        $this->pdf->fecha = $ordencompra->fechaventa;
        
        /*Titulo del reporte*/

        $this->pdf->AddPage();        
        //dd($movimientos);
        $this->pdf->SetFont('Arial', 'BU', 14);
        $this->pdf->Cell(0, 6, strtoupper(utf8_decode($ordencompra->documentoSerieNumero)), 0, 1, 'C');
        $this->pdf->Ln();
        $this->pdf->Ln();
        
        /*Datos personales del cliente*/
        $this->pdf->SetDrawColor(1, 87, 155);
        $this->pdf->SetLineWidth(0.4);
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(20, 6, 'Cliente: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(120, 6, utf8_decode($ordencompra->nombrecliente), 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, ucfirst(strtolower($ordencompra->abrevdocumento)) . ':', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(40, 6, $ordencompra->numerodoc, 0);
        $this->pdf->Ln();  
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetLineWidth(0.4); 
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);  
        $this->pdf->SetLineWidth(0.2);
        /*Cabecera de tabla*/

        $this->pdf->SetFillColor(1, 87, 155);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);

        /*Cabecera de tabla*/
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(110, 8, 'Pagos', 1, 1, 'L');
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(100, 8, utf8_decode('Descripción'), 1, 0, 'L', true);
        $this->pdf->Cell(20, 8, 'Cantidad', 1, 0, 'C', true);
        $this->pdf->Cell(20, 8, 'P.Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(20, 8, utf8_decode('Cupón'), 1, 0, 'C', true);
        $this->pdf->Cell(20, 8, 'Dscto.', 1, 0, 'C', true);
        $this->pdf->Cell(20, 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Ln();
        
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        $txtPago = 0;
        foreach ($ordencompradet as $row) {

            $txtPago += $row->total;
            //dd($row);
            //BOLETA VENTA N° 002-017254
            $this->pdf->Cell(100, $h, utf8_decode($row->nombreproducto), 1, 0, 'L', true);
            $this->pdf->Cell(20, $h, $row->cantidad, 1, 0, 'C', true);
            $this->pdf->Cell(20, $h, $row->preciounit, 1, 0, 'C', true);
            $this->pdf->Cell(20, $h, $row->codigocupon, 1, 0, 'C', true);
            $this->pdf->Cell(20, $h, $row->descuento, 1, 0, 'C', true);
            $this->pdf->Cell(20, $h, $row->total, 1, 0, 'R', true);
            $this->pdf->Ln();
        }
        
        if(count($ordencompradet) === 0)
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 1, 'C', true); 
        
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(180);
        $this->pdf->Cell(20, 12, 'S/. ' . $txtPago, 1, 0, 'R', true);
        $this->pdf->Ln(); 
        

        
        $this->pdf->Output(); 
              
    }    
}
