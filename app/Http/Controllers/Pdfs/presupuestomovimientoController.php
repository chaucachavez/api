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
    public $titulo = 'MOVIMIENTO ECONÓMICO';
    
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

class presupuestomovimientoController extends Controller 
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
        $ventas = $venta->grid(['venta.idcicloatencion' => $id, 'venta.idestadodocumento' => 27],'','','','','','', TRUE); 
        $dataterapia = $terapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38], array('terapia.idterapia', 'terapia.fecha',  'terapia.inicio', 'terapia.fin', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad'), TRUE);        
        $movimientos = $ciclomovimiento->movimiento(['idcicloatencion' => $id], ['idcicloatencionref' => $id]);
         
        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA','*'=>'OTROS')];
        $quiebre = array('idterapia' => 'idterapia');
        
        $campoextra = array('fecha' => 'fecha', 'inicio' => 'inicio', 'fin' => 'fin');  
        $datatratxterapista = $this->agruparPorColumna($dataterapia, '', $quiebre, $campoextra, $gruposProducto);
        
        //dd($presupuestodet);
        $precios = array();
        foreach($presupuestodet as $row) { 
            $precios[$row->idproducto] = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);
        }
         
        $preciosTrat = array();
        foreach($dataterapia as $row) { 
            if(!isset($preciosTrat[$row->idterapia]))
                $preciosTrat[$row->idterapia] = 0;  
            $preciosTrat[$row->idterapia] += ($row->cantidad * $precios[$row->idproducto]);
        } 
        
        $realizados = array();
        foreach($datatratxterapista as $row){
             
            if(!isset($realizados[$row['idquiebre']])) {
                $realizados[$row['idquiebre']]['fecha'] = $row['fecha'];
                $realizados[$row['idquiebre']]['inicio'] = $row['inicio'];
                $realizados[$row['idquiebre']]['fin'] = $row['fin'];
                $realizados[$row['idquiebre']]['total'] = $preciosTrat[$row['idquiebre']];
                foreach($gruposProducto[1] as $ind => $val){
                    $realizados[$row['idquiebre']][$ind] = 0;
                }
            } 
             
            $realizados[$row['idquiebre']][$row['idgrupo']] += $row['cantidad'];
        }        
        
        //dd($presupuesto->tipotarifa); exit;        
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
        $this->pdf->Cell(20, 6, 'Paciente: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(80, 6, utf8_decode($cicloatencion->entidad), 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, ucfirst(strtolower($cicloatencion->nombredocumento)) . ':', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(40, 6, $cicloatencion->numerodoc, 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, utf8_decode('N° HC:'), 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(20, 6, $cicloatencion->hc, 0);
        $this->pdf->Ln();  
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetLineWidth(0.4); 
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);  
        $this->pdf->SetLineWidth(0.2);
        /*Cabecera de tabla*/ 
        
        
        
        /*Cabecera de tabla*/ 
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(110, 8, 'Presupuesto', 1, 1, 'L'); 
        $y = $this->pdf->GetY();
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(77, 16, 'Tratamiento', 1, 0, 'L', true);
        $this->pdf->Cell(30, 8, 'Terapias', 1, 0, 'C', true);
//        $this->pdf->Cell(1.5); 
//        $this->pdf->Cell(1.5); 
        
        $txtTarifa = '';
        if($presupuesto)
            $txtTarifa = $presupuesto->tipotarifa === 1 ? 'Tarifa regular' : ($presupuesto->tipotarifa === 2 ? 'Tarifa tarjeta' : 'Tarifa efectivo');
        
        $this->pdf->Cell(30, 8, $txtTarifa, 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->setX(82);
        $this->pdf->Cell(15, 4, 'Indic. x', 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Efect.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'P. Unit.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->setY($this->pdf->GetY() - 4);
        $this->pdf->setX(82);
        $this->pdf->Cell(15, 4, utf8_decode('Paciente'), 'R,B,L', 0, 'C', true);
        //$this->pdf->Cell(15, 5, utf8_decode('*'), 'R,B,L', 0, 'C', true);    
        $this->pdf->Ln();
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        foreach ($presupuestodet as $row) {
            $txtprecioUnit = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);
            $txttotal = $presupuesto->tipotarifa === 1 ? $row->totalregular : ($presupuesto->tipotarifa === 2 ? $row->totaltarjeta : $row->totalefectivo);
            
            $this->pdf->Cell(77, $h, utf8_decode($row->nombreproducto), 1, 0, 'L', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, $h, $row->cantcliente, 1, 0, 'C', true);
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(15, $h, $row->cantefectivo, 1, 0, 'C', true);            
//            $this->pdf->Cell(1.5); 
//            $this->pdf->Cell(1.5);
            $this->pdf->Cell(15, $h, $txtprecioUnit, 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, $h, $txttotal, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln();
        } 
        
        if(count($presupuestodet) === 0)
            $this->pdf->Cell(137, $h, 'No hay registros.', 1, 1, 'C', true); 
        
        $txtCosto = 0;
        if($presupuesto)
            $txtCosto = $presupuesto->tipotarifa === 1 ? $presupuesto->regular : ($presupuesto->tipotarifa === 2 ? $presupuesto->tarjeta : $presupuesto->efectivo);
        
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(107);
        $this->pdf->Cell(30, 12, 'S/. ' . $txtCosto, 1, 0, 'C', true); 
        $this->pdf->Ln(); 
         
        
        /*Cabecera de tabla*/
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(110, 8, 'Pagos', 1, 1, 'L');  
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(110, 8, 'Documento', 1, 0, 'L', true); 
        $this->pdf->Cell(30, 8, 'F.Venta', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'M.Pago', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Ln();
        
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        $txtPago = 0;
        foreach ($ventas as $row) { 
            $txtPago += $row->total;
            //dd($row);
            //BOLETA VENTA N° 002-017254
            $this->pdf->Cell(110, $h, utf8_decode($row->documentoSerieNumero), 1, 0, 'L', true);
            $this->pdf->Cell(30, $h, $row->fechaventa, 1, 0, 'C', true); 
            $this->pdf->Cell(30, $h, $row->mediopagonombre, 1, 0, 'C', true); 
            $this->pdf->Cell(30, $h, $row->total, 1, 0, 'C', true); 
            $this->pdf->Ln();
        }
        foreach ($movimientos as $row) { 
            if($row->tiponota === 'notadebito'){ 
                //Nota debito: para cobrarle un adicional mas        
                // $tiponota = 'Nota débito';
                $tiponota = 'Nota de saldo';  
                $signo = '-';                        
                $txtPago -= $row->monto;
            }
            
            if($row->tiponota === 'notacredito'){
                //Nota credito: para devolver dinero
                // $tiponota = 'Nota crédito';
                $tiponota = 'Nota de saldo';      
                $signo = '+';                
                $txtPago += $row->monto;
            }
            
            //dd($row);
            //BOLETA VENTA N° 002-017254
            $this->pdf->Cell(110, $h, utf8_decode($tiponota.' N° '.$row->numero), 1, 0, 'L', true);
            $this->pdf->Cell(30, $h, $row->fecha, 1, 0, 'C', true); 
            $this->pdf->Cell(30, $h, '', 1, 0, 'C', true); 
            $this->pdf->Cell(30, $h, $signo.' '.$row->monto, 1, 0, 'C', true); 
            $this->pdf->Ln();
        }
        
        if(count($ventas) === 0 && count($movimientos) === 0)
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 1, 'C', true); 
        
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(170);
        $this->pdf->Cell(30, 12, 'S/. ' . $txtPago, 1, 0, 'C', true); 
        $this->pdf->Ln(); 
        
        /*Cabecera de tabla*/
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(110, 8, 'Terapias', 1, 1, 'L');  
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(21, 8, 'Fecha', 1, 0, 'L', true); 
        $this->pdf->Cell(16, 8, 'Inicio', 1, 0, 'C', true);
        $this->pdf->Cell(16, 8, 'Fin', 1, 0, 'C', true);
        foreach($gruposProducto[1] as $val){
            $this->pdf->Cell(13, 8, ucfirst(strtolower($val)), 1, 0, 'C', true);
        }
        $this->pdf->Cell(30, 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Ln();
        
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
//        dd($realizados);
        
        $txtEfectuado = 0;
        foreach ($realizados as $row) { 
            $txtEfectuado += $row['total']; 
            $this->pdf->Cell(21, $h, $row['fecha'], 1, 0, 'L', true);
            $this->pdf->Cell(16, $h, substr($row['inicio'], 0, 5), 1, 0, 'C', true); 
            $this->pdf->Cell(16, $h, substr($row['fin'], 0, 5), 1, 0, 'C', true); 
            foreach($gruposProducto[1] as $ind => $val){ 
                $cantidad = $row[$ind] > 0 ? $row[$ind] : '';
                $this->pdf->Cell(13, $h, $cantidad, 1, 0, 'C', true); 
            }
            $this->pdf->Cell(30, $h, $row['total'], 1, 0, 'C', true); 
            $this->pdf->Ln(); 
        }
        
        if(count($realizados) === 0)
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 1, 'C', true);                     
        
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(170);
        $this->pdf->Cell(30, 12, 'S/. ' . $txtEfectuado, 1, 0, 'C', true); 
        $this->pdf->Ln();
        $this->pdf->Ln();
        
        /*Condiciones de pago de pago*/
        $txtDisponible = round($txtPago - $txtEfectuado, 2); 
        
        $creditodisp = 0;
        if($presupuesto)
            $creditodisp = round($presupuesto->montopago - $presupuesto->montoefectuado, 2);
        
        //dd(gettype($txtDisponible), gettype($creditodisp));
//        if((double)$txtDisponible !== $creditodisp){
        if((double) $txtDisponible !== (double) $creditodisp){
//            dd('Comunicarse con oficina de Sistema OSI, Julio Chauca: 970879206 Nota: '.$txtDisponible .' y '.$creditodisp);  
            $this->pdf->Cell(200, 8, 'Comunicarse con oficina de Sistema OSI, Julio Chauca: 970879206 Nota: '.$txtDisponible .' y '.$creditodisp, 1, 0, 'C'); 
        }
        
        $this->pdf->SetY($y); 
        $this->pdf->SetX(145);  
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(60, 16, 'RESUMEN', 1, 1, 'C', true);
        $this->pdf->SetX(145);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, $h, utf8_decode('Pagó:'), 1, 0, 'C', true);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(25, $h, 'S/. '.$txtPago, 1, 1, 'C', true);
        $this->pdf->SetX(145);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, $h, utf8_decode('Efectuado:'), 1, 0, 'C', true);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(25, $h, 'S/. '.$txtEfectuado, 1, 1, 'C', true);
        $this->pdf->SetX(145);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, $h * 2, utf8_decode('Crédito disponible:'), 1, 0, 'C', true); 
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(25, $h * 2, 'S/. '.$txtDisponible, 1, 1, 'C', true);
        
        
        $this->pdf->Output(); 
              
    }    
}
