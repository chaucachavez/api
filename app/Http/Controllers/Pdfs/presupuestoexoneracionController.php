<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
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
    public $idcicloatencion;
    public $fecha;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'EXONERACION DE PAGO';
    
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
        $this->Cell(20, 4, utf8_decode('Código ciclo:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->idcicloatencion, $this->borde);
        $this->Ln();
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, utf8_decode('Fecha ciclo:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->fecha, $this->borde);
        $this->Ln(11);
    }
}

class presupuestoexoneracionController extends Controller 
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
        $ciclomovimiento = new ciclomovimiento();
        $objPresupuesto = new presupuesto();        
        $terapia = new terapia();
        $venta = new venta();
        $presupuestodet = array();
        
        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);   
        if($presupuesto)
            $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto); 
       
        $whereIn = [];
        foreach($presupuestodet as $row) {  
            $whereIn[] = $row->idproducto;
        }
        
        $idsede = $cicloatencion->idsede;

        $dataTarifa = \DB::table('producto') 
                ->leftJoin('tarifario', function($join) use ($idsede) {
                    $join->on('producto.idproducto', '=', 'tarifario.idproducto') 
                         ->where('tarifario.idsede', '=', $idsede);
                })
                ->select('producto.idproducto', 'producto.valorventa', 'tarifario.partref', 'tarifario.partcta', 'tarifario.partsta')  
                ->whereIn('producto.idproducto', $whereIn)
                ->get()->all(); 

        $autorizaciones = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]); 

        $preciosexoneracion = array();
        foreach($dataTarifa as $row) { 
            // dd($presupuesto->tipotarifa);
            $precio = $presupuesto->tipotarifa === 1 ? $row->partref : ($presupuesto->tipotarifa === 2 ? $row->partcta : $row->partsta);

            foreach ($autorizaciones as $fila) {
                if ($fila->idproducto === $row->idproducto) { 
                    if ($fila->idproducto === 2) {
                        $precio = $fila->coaseguro;
                    } else {
                        $precio = $fila->deducible;
                    }
                    break;
                }
            }

            $preciosexoneracion[$row->idproducto] = $precio ? $precio : $row->valorventa;
        } 
               
        $h = 6;
        $this->pdf->nombresede = $cicloatencion->nombresede;
        $this->pdf->idcicloatencion = $cicloatencion->idcicloatencion;
        $this->pdf->fecha = $cicloatencion->fecha;
        
        /*Titulo del reporte*/
         
        $this->pdf->AddPage();        
        //dd($movimientos);
        $this->pdf->SetFont('Arial', 'BU', 14);
        $this->pdf->Cell(0, 6, utf8_decode($this->pdf->titulo), 0, 1, 'C');
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
         
        /*Autorizaciones de seguro*/
        foreach($autorizaciones as $row)
        {
            if($row->principal === '1')
            {
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
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(110, 8, 'Tratamientos exonerados de pago', 1, 1, 'L'); 
        $y = $this->pdf->GetY();
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(77, 16, 'Tratamiento', 1, 0, 'L', true);
        $this->pdf->Cell(30, 16, 'Cantidad', 1, 0, 'C', true); 
        
        $txtTarifa = '';
        if($presupuesto)
            $txtTarifa = $presupuesto->tipotarifa === 1 ? 'Tarifa regular' : ($presupuesto->tipotarifa === 2 ? 'Tarifa tarjeta' : 'Tarifa efectivo');
        
        $this->pdf->Cell(46, 8, $txtTarifa, 1, 0, 'C', true);
        $this->pdf->Cell(46, 8, utf8_decode('Beneficio exoneración'), 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->setX(112); 
        $this->pdf->Cell(23, 8, 'P. Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(23, 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(23, 8, 'P. Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(23, 8, 'Total', 1, 0, 'C', true);        
        $this->pdf->Ln();  
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        $totalExo = 0;
        foreach ($presupuestodet as $row) {
            
            $txtprecioUnit = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);
            
            if ((float) $txtprecioUnit == 0) {
                $txttotal = $presupuesto->tipotarifa === 1 ? $row->totalregular : ($presupuesto->tipotarifa === 2 ? $row->totaltarjeta : $row->totalefectivo);

                $preciounitExo = $preciosexoneracion[$row->idproducto];
                $preciounitTotalExo = $row->cantcliente * $preciounitExo;
                $totalExo += $preciounitTotalExo;
                
                $this->pdf->Cell(77, $h, utf8_decode($row->nombreproducto), 1, 0, 'L', true); 
                $this->pdf->Cell(30, $h, $row->cantcliente, 1, 0, 'C', true);    
                $this->pdf->Cell(23, $h, $txtprecioUnit, 1, 0, 'R', true);

                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(23, $h, $txttotal, 1, 0, 'R', true);
                $this->pdf->SetFillColor(245, 245, 245); 

                $this->pdf->Cell(23, $h, $preciounitExo, 1, 0, 'R', true); 

                $this->pdf->SetFillColor(220, 220, 220); 
                $this->pdf->Cell(23, $h, number_format($preciounitTotalExo, 2, '.', ','), 1, 0, 'R', true);
                $this->pdf->SetFillColor(245, 245, 245); 

                $this->pdf->Ln();
            }

        } 
        
        if(count($presupuestodet) === 0)
            $this->pdf->Cell(137, $h, 'No hay registros.', 1, 1, 'C', true);  
        
        
        $this->pdf->SetFont('Arial', '', 12);        
        $this->pdf->Cell(130, 12, '', 1, 0, 'C', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(23, 12, 'S/. 0', 1, 0, 'C', true); 
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->Cell(23, 12, '', 1, 0, 'C', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(23, 12, 'S/. ' . $totalExo .'*', 1, 0, 'C', true); 
        $this->pdf->Ln(); 

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(23, 12, utf8_decode('* Beneficio otorgado al paciente para la realización de tratamientos a costo cero.'), 1, 0, 'L'); 
        
        $this->pdf->Output(); 
              
    }    
}
