<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa;
use App\Models\cicloatencion; 
use App\Models\presupuesto; 
use App\Models\entidad; 
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0;
    public $nombresede;
    public $idcicloatencion;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'PRESUPUESTO';
    
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
        $this->Cell(20, 4, 'sede:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->nombresede, $this->borde);
        $this->Ln();
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, utf8_decode('Código ciclo:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->idcicloatencion, $this->borde);
        $this->Ln(15);
    }
}

class presupuestoController extends Controller 
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
        $objCicloatencion = new cicloatencion();
        $objPresupuesto = new presupuesto();
        
        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);
        $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
        $autorizaciones = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);
         
        $this->pdf->nombresede = $cicloatencion->nombresede;
        $this->pdf->idcicloatencion = $cicloatencion->idcicloatencion;
        
        /*Titulo del reporte*/
        $this->pdf->AddPage();        
        $this->pdf->SetFont('Arial', 'BU', 14);
        $this->pdf->Cell(0, 6, $this->pdf->titulo, 0, 1, 'C');
        $this->pdf->Ln();
        $this->pdf->Ln();
        
        /*Datos personales del cliente*/
        $this->pdf->SetDrawColor(1, 87, 155);
        $this->pdf->SetLineWidth(0.4);
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Cliente: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(85, 6, utf8_decode($cicloatencion->entidad), 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, ucfirst(strtolower($cicloatencion->nombredocumento)) . ':', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(70, 6, $cicloatencion->numerodoc, 0);
        $this->pdf->Ln(); 
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(15, 6, 'Correo: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(85, 6, $cicloatencion->email, 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, utf8_decode('Celular:'), 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(30, 6, $cicloatencion->celular, 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(15, 6, 'H.C.: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(25, 6, $cicloatencion->hc, 0,1);
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetLineWidth(0.4); 
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6); 
        $this->pdf->SetLineWidth(0.2);
        
         $coapec = '';
        /*Autorizaciones de seguro*/
        foreach($autorizaciones as $row)
        {
            /*if($row->principal === '1')
            {*/
            $this->pdf->SetFont('Arial', 'BI', 10); 
            $this->pdf->Cell(15, 6, 'Seguro: ', 0);
            $this->pdf->SetFont('Arial', 'I');
            $this->pdf->Cell(70, 6, utf8_decode($row->nombreaseguradoraplan), 0);
            
            $this->pdf->SetFont('Arial', 'BI');
            $this->pdf->Cell(35, 6, utf8_decode('Orden autorización:'), 0);
            $this->pdf->SetFont('Arial', 'I');
            $this->pdf->Cell(30, 6, $row->codigo, 0, 0); 
            
            $this->pdf->SetFont('Arial', 'BI');
            $this->pdf->Cell(15, 6, 'Fecha: ', 0);
            $this->pdf->SetFont('Arial', 'I');
            $this->pdf->Cell(25, 6, $row->fecha, 0, 1);                
            // }

            if ($row->idproducto === 2) {
                $coapec = '(COA + PECP)';
            }

        }
        
        if($autorizaciones)
        {
            $this->pdf->Ln();
            $this->pdf->Ln();
            $this->pdf->SetLineWidth(0.4); 
            $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);
        }
        
        /*Cabecera de tabla*/
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(77, 18, 'Tratamiento', 1, 0, 'L', true);
        $this->pdf->Cell(30, 8, 'Terapias', 1, 0, 'C', true);
        $this->pdf->Cell(1.5); 
        $this->pdf->Cell(1.5);
        $this->pdf->Cell(30, 8, 'Tarifa regular', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Tarifa tarjeta', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Tarifa efectivo', 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->setX(82);
        $this->pdf->Cell(15, 5, 'Indic. x', 1, 0, 'C', true);
        $this->pdf->Cell(15, 5, 'Indic. x', 1, 0, 'C', true);
        $this->pdf->Cell(3);
        $this->pdf->Cell(15, 10, 'P. Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, 'P. Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, 'P. Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, 'Total', 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->setY($this->pdf->GetY() - 5);
        $this->pdf->setX(82);
        $this->pdf->Cell(15, 5, utf8_decode('Médico'), 'R,B,L', 0, 'C', true);
        $this->pdf->Cell(15, 5, utf8_decode('Cliente'), 'R,B,L', 0, 'C', true);      
        $this->pdf->Ln();
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);

        $this->pdf->Cell(107, 8, '', 1, 0, 'L', true); 
        $this->pdf->Cell(3);  
        $this->pdf->Cell(30, 8, '', 1, 0, 'R', true); 
        $this->pdf->Cell(60, 8, 'Pago de dscto. por paquete de 5 sesiones', 1, 0, 'C', true);  
        $this->pdf->Ln();

        foreach ($presupuestodet as $row) {

            $nombreproducto =  $row->nombreproducto;

            if($row->idproducto === 2) {
                $nombreproducto .= $coapec;
            }

            $this->pdf->Cell(77, 8, utf8_decode(mb_substr($nombreproducto, 0, 27)), 1, 0, 'L', true);
            $this->pdf->Cell(15, 8, $row->cantmedico, 1, 0, 'C', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, 8, $row->cantcliente, 1, 0, 'C', true);
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(1.5); 
            $this->pdf->Cell(1.5);
            $this->pdf->Cell(15, 8, $row->preciounitregular, 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, 8, $row->totalregular, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(15, 8, $row->preciounittarjeta, 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, 8, $row->totaltarjeta, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(15, 8, $row->preciounitefectivo, 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, 8, $row->totalefectivo, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Ln();
        } 
        
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(110);
        $this->pdf->Cell(30, 12, 'S/. ' . $presupuesto->regular, 1, 0, 'C', true);
        $this->pdf->Cell(30, 12, 'S/. ' . $presupuesto->tarjeta, 1, 0, 'C', true);
        $this->pdf->Cell(30, 12, 'S/. ' . $presupuesto->efectivo, 1, 0, 'C', true);
        $this->pdf->Ln();

        $this->pdf->SetY(-115); 
        $this->pdf->Image($this->pdf->path.'bannerpdf.png', null, null, 200, 0, 'PNG');
  
        /*Condiciones de pago de pago*/
        $this->pdf->SetY(-43); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(0, 8, 'CONDICIONES DE PAGO ', 1, 1, 'C', true);
        $this->pdf->SetFont('Arial', '', 8.5);
        $this->pdf->Cell(67, 5, '1.- Solo a los Pagos de TARIFA REGULAR (sin descuento) se emite FACTURA.', $this->pdf->borde, 1, 'L');
        $this->pdf->Cell(67, 5, '2.- A los pagos de TARIFA CON DESCUENTO se emite BOLETA DE VENTA.', $this->pdf->borde, 1, 'L');
        $this->pdf->Cell(67, 5, '3.- A los pagos de ASEGURADOS Y EPS se emite BOLETA DE VENTA, en Deducibles, Copagos, Tarifa preferencial, Tarifas Especiales y otros.', $this->pdf->borde, 1, 'L');
        $this->pdf->Cell(67, 5, utf8_decode('4.- Se acepta la atención de REEMBOLSO de asegurado y EPS solo a lo pagado con Tarifa Regular.'), $this->pdf->borde, 1, 'L');
        
        $this->pdf->Output();       
    }    
}
