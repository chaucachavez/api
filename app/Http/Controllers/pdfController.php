<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;

use App\Models\empresa;
use App\Models\sede;
use App\Models\venta;
use App\Models\entidad;
use App\Models\cicloatencion;
use App\Models\presupuesto;
use App\Models\citamedica;
use App\Models\citaterapeutica;
use App\Models\horariomedico;

class PDF extends baseFpdf {
        
    function Footer() {
        /*Condiciones de pago*/ 
        /**/
        $this->SetDrawColor(1, 87, 155);
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number

        $this->Cell(70, 10, 'http://www.centromedicoosi.com');
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
        $this->Cell(50, 10, utf8_decode('Impresión:') . ' ' . date('d/m/Y H:i'));
    }

}

class pdfController extends Controller { 
    public $entidad = '';
    
    public function __construct(Request $request) {
        $this->getToken($request);      
    }
    
    public function venta(Request $request, $enterprise, $id) {
        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre');

        $Objventa = new venta();
        $venta = $Objventa->venta($id, true);
        $ventadet = $Objventa->ventadet($id);
        //dd($ventadet);
        $serienumero = utf8_decode('N° ') . str_pad($venta->serienumero, 6, "0", STR_PAD_LEFT);
        $efectivo = in_array($venta->idmediopago, [1, 4]) ? 'X' : '';
        $tarjeta = in_array($venta->idmediopago, [2, 3, 4]) ? 'X' : '';
        $d = substr($venta->fechaventa, 0, 2);
        $m = substr($venta->fechaventa, 3, 2);
        $y = substr($venta->fechaventa, 6, 4);
        $mes = $meses[(int) $m - 1];
        //dd($venta);

        $pdf = new baseFpdf('L', 'mm', [148, 210]); //A5
        $border = 1;
        
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 11);
                
        $pdf->Ln(22);
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
        $pdf->Cell(10);
        $pdf->Cell(148, 7, utf8_decode($venta->cliente), $border);
        $pdf->Cell(17, 7, $venta->hc, $border);

        $pdf->Ln(8);
        $pdf->Cell(10);
        $pdf->Cell(70, 7, '', 0);
        $pdf->Cell(8);
        $pdf->Cell(45, 7, utf8_decode($venta->numerodoc), $border);
        $pdf->Cell(20);
        $pdf->Cell(11, 7, $efectivo, $border);
        $pdf->Cell(11, 7, $tarjeta, $border);

        $pdf->Ln(18);
        foreach ($ventadet as $row) {
            //$pdf->Cell(10, 6, '', $border);
            $pdf->Cell(10, 6, '', 0);
            $pdf->Cell(15, 6, $row->cantidad, $border);
            $pdf->Cell(85, 6, mb_substr(utf8_decode($row->nombreproducto), 0, 45) , $border);
            $pdf->Cell(20, 6, $row->preciounit, $border);
            $pdf->Cell(20, 6, $row->descuento, $border);
            $pdf->Cell(25, 6, $row->total, $border);
            $pdf->Ln();
        }

        $pdf->SetY(105);

        $pdf->Cell(50);
        $pdf->Cell(10, 7, $d, $border);
        $pdf->Cell(3);
        $pdf->Cell(22, 7, $mes, $border);
        $pdf->Cell(3);
        $pdf->Cell(7, 7, substr($y, -2), $border);
        $pdf->Cell(55);
        $pdf->Cell(25, 7, $venta->total, $border);

        $pdf->Output();
    }

    public function presupuesto(Request $request, $enterprise, $id) {

        $objCicloatencion = new cicloatencion();
        $objPresupuesto = new presupuesto();
        //dd($enterprise);
        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);
        $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
        $autorizaciones = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);
 
        $pdf = new PDF('P', 'mm', [210, 297]); //A4 200x..
        $pdf->AliasNbPages();
        $border = 1;
        $pdf->SetFillColor(1, 87, 155);
        $pdf->SetDrawColor(255, 255, 255);

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        
        $pdf->Image('https://sistemas.centromedicoosi.com/img/osi/logologin.png', 10, 10, 40, 0, 'PNG');
        $pdf->Cell(130);
        $pdf->Cell(20, 4, 'sede:', $border);
        $pdf->Cell(40, 4, $cicloatencion->nombresede, $border);
        $pdf->Ln();
        $pdf->Cell(130);
        $pdf->Cell(20, 4, utf8_decode('Código ciclo:'), $border);
        $pdf->Cell(40, 4, $cicloatencion->idcicloatencion, $border);
        $pdf->Ln(15);

        $pdf->SetFont('Arial', 'BU', 14);

        $pdf->Cell(190, 6, 'PRESUPUESTO', 0, 1, 'C');
        $pdf->Ln();
        $pdf->Ln();
        
        $pdf->SetDrawColor(1, 87, 155);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(10, $pdf->GetY() - 6, 200, $pdf->GetY() - 6);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(15, 6, 'Cliente: ', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(85, 6, utf8_decode($cicloatencion->entidad), 0);
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(20, 6, ucfirst(strtolower($cicloatencion->nombredocumento)) . ':', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(70, 6, $cicloatencion->numerodoc, 0);
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(15, 6, 'Correo: ', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(85, 6, $cicloatencion->email, 0);
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(20, 6, utf8_decode('Celular:'), 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(30, 6, $cicloatencion->celular, 0);
        $pdf->SetFont('Arial', 'B');
        $pdf->Cell(15, 6, 'H.C.: ', 0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(25, 6, $cicloatencion->hc, 0,1);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetLineWidth(0.4); 
        $pdf->Line(10, $pdf->GetY() - 6, 200, $pdf->GetY() - 6);
          
        $pdf->SetLineWidth(0.2);
         
        foreach($autorizaciones as $row){
            if($row->principal === '1'){
                $pdf->SetFont('Arial', 'BI', 10); 
                $pdf->Cell(15, 6, 'Seguro: ', 0);
                $pdf->SetFont('Arial', 'I');
                $pdf->Cell(70, 6, utf8_decode($row->nombreaseguradoraplan), 0);
                
                $pdf->SetFont('Arial', 'BI');
                $pdf->Cell(35, 6, utf8_decode('Orden autorización:'), 0);
                $pdf->SetFont('Arial', 'I');
                $pdf->Cell(30, 6, $row->codigo, 0, 0); 
                
                $pdf->SetFont('Arial', 'BI');
                $pdf->Cell(15, 6, 'Fecha: ', 0);
                $pdf->SetFont('Arial', 'I');
                $pdf->Cell(25, 6, $row->fecha, 0, 1);                
            }
        }
        
        if($autorizaciones){
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetLineWidth(0.4); 
            $pdf->Line(10, $pdf->GetY() - 6, 200, $pdf->GetY() - 6);
        }
        
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(67, 18, 'Tratamiento', $border, 0, 'L', true);
        $pdf->Cell(30, 8, 'Terapias', $border, 0, 'C', true);
        $pdf->Cell(1.5);
        //$pdf->Cell(15, 18, 'Dscto.', $border, 0, 'C', true);
        $pdf->Cell(1.5);
        $pdf->Cell(30, 8, 'Tarifa regular', $border, 0, 'C', true);
        $pdf->Cell(30, 8, 'Tarifa tarjeta', $border, 0, 'C', true);
        $pdf->Cell(30, 8, 'Tarifa efectivo', $border, 0, 'C', true);
        $pdf->Ln();
        $pdf->setX(77);
        $pdf->Cell(15, 5, 'Indic. x', 'T,L', 0, 'C', true);
        $pdf->Cell(15, 5, 'Indic. x', 'T,R,L', 0, 'C', true);
        $pdf->Cell(3);
        $pdf->Cell(15, 10, 'P. Unit.', $border, 0, 'C', true);
        $pdf->Cell(15, 10, 'Total', $border, 0, 'C', true);
        $pdf->Cell(15, 10, 'P. Unit.', $border, 0, 'C', true);
        $pdf->Cell(15, 10, 'Total', $border, 0, 'C', true);
        $pdf->Cell(15, 10, 'P. Unit.', $border, 0, 'C', true);
        $pdf->Cell(15, 10, 'Total', $border, 0, 'C', true);
        $pdf->Ln();
        $pdf->setY($pdf->GetY() - 6);
        $pdf->setX(77);
        $pdf->Cell(15, 5, utf8_decode('Médico'), 'B,L', 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Cliente'), 'R,B,L', 0, 'C', true);
      
        $pdf->Ln();

        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 9);

        foreach ($presupuestodet as $row) {
            $pdf->Cell(67, 8, utf8_decode(mb_substr($row->nombreproducto, 0, 27)), $border, 0, 'L', true);
            $pdf->Cell(15, 8, $row->cantmedico, $border, 0, 'C', true);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(15, 8, $row->cantcliente, $border, 0, 'C', true);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(1.5);
            //$pdf->Cell(15, 8, $row->descuento, $border, 0, 'C', true);
            $pdf->Cell(1.5);
            $pdf->Cell(15, 8, $row->preciounitregular, $border, 0, 'R', true);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(15, 8, $row->totalregular, $border, 0, 'R', true);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(15, 8, $row->preciounittarjeta, $border, 0, 'R', true);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(15, 8, $row->totaltarjeta, $border, 0, 'R', true);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(15, 8, $row->preciounitefectivo, $border, 0, 'R', true);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(15, 8, $row->totalefectivo, $border, 0, 'R', true);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Ln();
        }
        
        
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(100);
        $pdf->Cell(30, 12, 'S/. ' . $presupuesto->regular, $border, 0, 'C', true);
        $pdf->Cell(30, 12, 'S/. ' . $presupuesto->tarjeta, $border, 0, 'C', true);
        $pdf->Cell(30, 12, 'S/. ' . $presupuesto->efectivo, $border, 0, 'C', true);
  
        /**/
        $pdf->SetY(-50); 
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(95, 8, 'CONDICIONES DE PAGO ', $border, 1, 'C', true);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(67, 5, '1.- Solo a los Pagos de TARIFA REGULAR (sin descuento) se emite FACTURA.', $border, 1, 'L');
        $pdf->Cell(67, 5, '2.- A los pagos de TARIFA CON DESCUENTO se emite BOLETA DE VENTA.', $border, 1, 'L');
        $pdf->Cell(67, 5, '3.- A los pagos de ASEGURADOS Y EPS se emite BOLETA DE VENTA, en Deducibles, Copagos, Tarifa preferencial, Tarifa Especiales y otros.', $border, 1, 'L');
        $pdf->Cell(67, 5, utf8_decode('4.- Se acepta la atención de REEMBOLSO de asegurado y EPS solo a lo pagado con Tarifa Regular.'), $border, 1, 'L');
        
        $pdf->Output();
    }
    
    public function citasmedicas(Request $request, $enterprise) { 
        
        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica(); 
        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        
        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
        } 
        
        if (isset($paramsTMP['idmedico']) && !empty($paramsTMP['idmedico'])) {
            $param['citamedica.idmedico'] = $paramsTMP['idmedico'];
        } 

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        
        $whereInMed = [];
        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) { 
            $whereIn = explode(',', $paramsTMP['inEstado']); 
        }
        
        $notExists = false;
        $pendiente = false;
        $pagado = false;
        if (isset($paramsTMP['estadopago'])) { 
            if ($paramsTMP['estadopago'] === '0') //Pendiente
                $pendiente = true;            
            if ($paramsTMP['estadopago'] === '1') //Pagado
                $pagado = true;            
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $datacita = $citamedica->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn, $notExists, $whereInMed , $pendiente, $pagado);
        $whereIdcitamedicaIn = array();
        $whereIdcicloatencionIn = array();
        foreach($datacita as $row){ 
            if($row->idcicloatencion)
                $whereIdcicloatencionIn[] = $row->idcicloatencion;
        }

        //Autorizacion valida('1') de Fisioterapia(2)
        $coaseguos = \DB::table('cicloautorizacion') 
                ->select('cicloautorizacion.idcicloatencion', 'aseguradora.nombre as nombreaseguradora', 'cicloautorizacion.deducible', 
                         'cicloautorizacion.coaseguro', 'aseguradoraplan.nombre as nombreaseguradoraplan')
                ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora') 
                ->leftJoin('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
                ->where(array('cicloautorizacion.idproducto' => 2))
                ->whereIn('cicloautorizacion.idcicloatencion', $whereIdcicloatencionIn)
                ->whereNull('cicloautorizacion.deleted') 
                ->get()->all();

        // dd($datacita);

        foreach($datacita as $row){
            //Añadir coaseguro de FISIOTERAPIA 
            if ($row->idcicloatencion) { 
                $tmpcoa = null;
                foreach($coaseguos as $val){
                    if($val->idcicloatencion === $row->idcicloatencion){
                        $tmpcoa = $val; 
                        break;
                    }
                } 
                $row->nombreaseguradoraplan = $tmpcoa ? $tmpcoa->nombreaseguradoraplan : null;
            }
        } 

        //dd($datacita);
          
        //$pdf = new Fpdf('L', 'mm', [148, 210]); //A5
        $pdf = new PDF('L', 'mm', [210, 297]); //A4 200x..
        $pdf->SetMargins(5,5,5);
        //$pdf->SetAutoPageBreak(true, 15);
        $pdf->AliasNbPages();
        $border = 1;
        $pdf->SetFillColor(1, 87, 155);
        $pdf->SetDrawColor(255, 255, 255);

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        //
        $pdf->Image('https://sistemas.centromedicoosi.com/img/osi/logologin.png', 10, 10, 40, 0, 'PNG');
        $pdf->Cell(130); 
        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'BU', 14);

        $pdf->Cell(190, 6, utf8_decode('CITAS MÉDICAS'), 0, 1, 'C');
        $pdf->Ln();
        $pdf->Ln();
        
        $pdf->SetDrawColor(1, 87, 155);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        $pdf->SetLineWidth(0.2); 
         
        if(!empty($paramsTMP['idsede'])){ 
            
            $sede = sede::find($paramsTMP['idsede']);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(12, 6, 'Sede:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(33, 6, ucwords(strtolower($sede->nombre)), 0);
        }
        if(!empty($paramsTMP['likeentidad'])){ 
            $sede = sede::find($paramsTMP['idsede']);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(45, 6, 'Clientes con letra:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(100, 6, $paramsTMP['likeentidad'], 0);
        }
        if(!empty($paramsTMP['inEstado'])){ 
            $estadocita = $paramsTMP['inEstado'] === '4,5' ? 'Por atender' : ($paramsTMP['inEstado'] === '6' ? 'Atendido' : ($paramsTMP['inEstado'] === '7' ? 'Cancelado' : 'Todos'));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(22, 6, 'Estado cita:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(27, 6, $estadocita, 0);
        }
        if(!empty($paramsTMP['estadopago']) || $paramsTMP['estadopago'] === '0'){
            $estadopago = $paramsTMP['estadopago'] === '1' ? 'Pagado' : 'Por pagar';           
            $pdf->SetFont('Arial', 'B');
            $pdf->Cell(24, 6, 'Estado pago:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(27, 6, $estadopago, 0);
        }
        if(!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])){  
            //dd($this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd'));
            $rango = $this->formatFecha($paramsTMP['desde']).' - '.$this->formatFecha($paramsTMP['hasta']);
            //$rango = $paramsTMP['desde'] .' - '. $paramsTMP['hasta'] ;
            
            if($paramsTMP['desde'] === $paramsTMP['hasta']){
                $rango = $this->formatFecha($paramsTMP['desde']); 
            }
            $pdf->SetFont('Arial', 'B');
            $pdf->Cell(13, 6, 'Fecha:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(42, 6, $rango, 0);
        }  
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetLineWidth(0.4); 
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
          
        //dd($datacita);
        $citasPoratender = 0;
        $citasAtendidas = 0; 
        $citasCancelados = 0; 
        foreach ($datacita as $row) {
            if($row->idestado === 4 || $row->idestado === 5){ 
                $citasPoratender = $citasPoratender + 1;
            }
            if($row->idestado === 6){
                $citasAtendidas = $citasAtendidas + 1;
            } 
            if($row->idestado === 7){
                $citasCancelados = $citasCancelados + 1;
            } 
        }
        $citastotal = $citasPoratender + $citasAtendidas;
        if($citastotal > 0){
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Cell(27, 5, "Citas 'Por atender':", 0, 0, 'L'); 
            $pdf->Cell(20, 5, $citasPoratender, 0, 0, 'R'); 
            $pdf->Ln();
            $pdf->Cell(27, 5, "Citas 'Atendidos':", 0, 0, 'L'); 
            $pdf->Cell(20, 5, $citasAtendidas, 0, 0, 'R'); 
            $pdf->Ln(); 
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(27, 5, 'TOTAL DE CITAS: ', 'T', 0, 'L'); 
            $pdf->Cell(20, 5, $citastotal, 'T', 0, 'R'); 
            $pdf->Cell(106); 
            $pdf->Cell(27, 5, "Citas 'Canceladas':", 0, 0, 'L'); 
            $pdf->Cell(20, 5, $citasCancelados, 0, 0, 'R'); 
            $pdf->Ln(); 
        } 
        
        
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->Cell(7, 6, utf8_decode('N°'), $border, 0, 'C', true);
        $pdf->Cell(16, 6, 'Fecha', $border, 0, 'C', true);
        $pdf->Cell(11, 6, 'Hora', $border, 0, 'C', true);
        $pdf->Cell(43, 6, 'Paciente', $border, 0, 'C', true);
        $pdf->Cell(17, 6, 'HC.', $border, 0, 'C', true);
        $pdf->Cell(20, 6, 'Celular', $border, 0, 'C', true);
        $pdf->Cell(22, 6, 'Tipo', $border, 0, 'C', true);
        $pdf->Cell(20, 6, 'Ciclo', $border, 0, 'C', true);
        $pdf->Cell(20, 6, 'Est.Cita', $border, 0, 'C', true);
        $pdf->Cell(20, 6, 'Est.Pago', $border, 0, 'C', true);  
        $pdf->Cell(43, 6, utf8_decode('Médico'), $border, 0, 'C', true);  
        $pdf->Cell(30, 6, 'Seguro plan', $border, 0, 'C', true);  
        $pdf->Ln();

        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 8);
        
        $i = 0;
        foreach ($datacita as $row) {
            $row->estadopago = $row->idestadopago === 71 ? 'Pagado' : 'Por pagar';
            $row->estadocita = $row->idestado === 4 || $row->idestado === 5 ? 'Por atender' : $row->estadocita;
            
            $pdf->Cell(7, 5, ++$i, $border, 0, 'C', true);
            $pdf->Cell(16, 5, $row->fecha, $border, 0, 'L', true);
            $pdf->Cell(11, 5, mb_substr($row->inicio,0,5), $border, 0, 'C', true); 
            $pdf->Cell(43, 5, ucwords(strtolower(utf8_decode(mb_substr($row->paciente, 0, 40)))), $border, 0, 'L', true); 
            $pdf->Cell(17, 5, $row->hc, $border, 0, 'L', true); 
            $pdf->Cell(20, 5, $row->celular, $border, 0, 'L', true); 
            $pdf->Cell(22, 5, $row->nombretipo, $border, 0, 'L', true); 
            $pdf->Cell(20, 5, $row->idcicloatencion, $border, 0, 'C', true); 
            $pdf->Cell(20, 5, $row->estadocita, $border, 0, 'L', true); 
            $pdf->Cell(20, 5, $row->estadopago, $border, 0, 'L', true);  
            $pdf->Cell(43, 5, ucwords(strtolower(utf8_decode(mb_substr($row->medico, 0, 22)))), $border, 0, 'L', true);  
            $pdf->Cell(30, 5, utf8_decode($row->nombreaseguradoraplan), $border, 0, 'L', true);  
            $pdf->Ln();
        }
         
        $pdf->Output();
    }
    
    public function ventascaja(Request $request, $enterprise) { 
        
        $paramsTMP = $request->all();

        $empresa = new empresa();
        $venta = new venta();

        $param = array();
        $param['venta.idempresa'] = $empresa->idempresa($enterprise);
        $param['venta.idsede'] = $paramsTMP['idsede'];
        
        $orderName = '';
        $orderSort = '';
        $pageSize = '';

        $between = array();
        $betweenHora = array();
        
        
        if (isset($paramsTMP['idestadodocumento']) && !empty($paramsTMP['idestadodocumento'])) {
            $param['venta.idestadodocumento'] = $paramsTMP['idestadodocumento'];
        }
        
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }
        
        if (isset($paramsTMP['horaventainicio']) && isset($paramsTMP['horaventafin'])) {
            if (!empty($paramsTMP['horaventainicio']) && !empty($paramsTMP['horaventafin'])) { 
                $betweenHora = [$paramsTMP['horaventainicio'], $paramsTMP['horaventafin']];
            }
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        
        $dataventa = $venta->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $betweenHora);
        
          
        //$pdf = new Fpdf('L', 'mm', [148, 210]); //A5
        $pdf = new PDF('P', 'mm', [210, 297]); //A4 200x..
        $pdf->SetMargins(5,5,5);
        //$pdf->SetAutoPageBreak(true, 15);
        $pdf->AliasNbPages();
        $border = 1;
        $pdf->SetFillColor(1, 87, 155);
        $pdf->SetDrawColor(255, 255, 255);

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        //
        $pdf->Image('https://sistemas.centromedicoosi.com/img/osi/logologin.png', 10, 10, 40, 0, 'PNG');
        $pdf->Cell(130); 
        $pdf->Ln(15);

        $pdf->SetFont('Arial', 'BU', 14);

        $pdf->Cell(190, 6, utf8_decode('VENTAS EN CAJA'), 0, 1, 'C');
        $pdf->Ln();
        $pdf->Ln();
        
        $pdf->SetDrawColor(1, 87, 155);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        $pdf->SetLineWidth(0.2); 
         
        if(!empty($paramsTMP['idsede'])){ 
            $sede = sede::find($paramsTMP['idsede']);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(12, 6, 'Sede:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(33, 6, ucwords(strtolower($sede->nombre)), 0);
        }
        if(!empty($paramsTMP['likeentidad'])){ 
            $sede = sede::find($paramsTMP['idsede']);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(45, 6, 'Clientes con letra:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(100, 6, $paramsTMP['likeentidad'], 0);
        } 
        
        if(!empty($paramsTMP['idestadodocumento'])){
            $estadopago = $paramsTMP['idestadodocumento'] === '27' ? 'Pagado' : ($paramsTMP['idestadodocumento'] === '28' ? 'Anulado': '');           
            $pdf->SetFont('Arial', 'B');
            $pdf->Cell(24, 6, 'Estado venta:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(27, 6, $estadopago, 0);
        }
        if(!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])){  
            $rango = $this->formatFecha($paramsTMP['desde'] ).' - '.$this->formatFecha($paramsTMP['hasta'] );
            if($paramsTMP['desde'] === $paramsTMP['hasta']){
                $rango = $this->formatFecha($paramsTMP['desde'] );
            }
            $pdf->SetFont('Arial', 'B');
            $pdf->Cell(13, 6, 'Fecha:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(42, 6, $rango, 0);
        }
        
        if(!empty($paramsTMP['horaventainicio']) && !empty($paramsTMP['horaventafin'])){  
            $rango = mb_substr($paramsTMP['horaventainicio'],0,5).' - '.mb_substr($paramsTMP['horaventafin'],0,5);
            if($paramsTMP['horaventainicio'] === $paramsTMP['horaventafin']){
                $rango = $paramsTMP['horaventainicio'];
            }
            $pdf->SetFont('Arial', 'B');
            $pdf->Cell(13, 6, 'Hora:', 0);
            $pdf->SetFont('Arial', '');
            $pdf->Cell(42, 6, $rango, 0);
        }
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetLineWidth(0.4); 
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        
        //dd($dataventa);
        $ventaEfectivo = 0;
        $ventaMastercad = 0;
        $ventaVisa = 0;        
        $ventaAnulada = 0;
        foreach ($dataventa as $row) {
            if($row->idestadodocumento === 27){
                switch ($row->idmediopago):
                    case 1: $ventaEfectivo = $ventaEfectivo + $row->total; break; //Efectivo
                    case 2: $ventaVisa = $ventaVisa + $row->total; break; //Visa
                    case 3: $ventaMastercad = $ventaMastercad + $row->total; break; //Mastercad
                endswitch;
            }
            if($row->idestadodocumento === 28){
                $ventaAnulada = $ventaAnulada + $row->total;
            } 
        }
        $ventatotal = $ventaEfectivo + $ventaVisa + $ventaMastercad;
        if($ventatotal > 0){
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Cell(27, 8, 'Venta Efectivo S/.', 0, 0, 'L'); 
            $pdf->Cell(20, 8, $ventaEfectivo, 0, 0, 'R'); 
            $pdf->Ln();
            $pdf->Cell(27, 8, 'Venta Visa S/.', 0, 0, 'L'); 
            $pdf->Cell(20, 8, $ventaVisa, 0, 0, 'R'); 
            $pdf->Ln();
            $pdf->Cell(27, 8, 'Venta Mastercad S/.', 0, 0, 'L'); 
            $pdf->Cell(20, 8, $ventaMastercad, 0, 0, 'R'); 
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(27, 8, 'VENTA TOTAL S/.', 'T', 0, 'L'); 
            $pdf->Cell(20, 8, $ventaEfectivo + $ventaVisa + $ventaMastercad, 'T', 0, 'R'); 
            $pdf->Ln();
            $pdf->Ln();
        }
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->Cell(7, 8, utf8_decode('N°'), $border, 0, 'C', true);
        $pdf->Cell(27, 8, 'Fecha', $border, 0, 'C', true); 
        $pdf->Cell(30, 8, 'Doc. venta', $border, 0, 'C', true);  
        $pdf->Cell(42, 8, 'Cliente', $border, 0, 'C', true);
        $pdf->Cell(21, 8, 'M.Pago', $border, 0, 'C', true);
        $pdf->Cell(16, 8, 'Est.Vta', $border, 0, 'C', true); 
        $pdf->Cell(19, 8, 'Total', $border, 0, 'C', true); 
        $pdf->Cell(38, 8, 'Registrador', $border, 0, 'C', true);  
        $pdf->Ln();

        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 9);
        //dd($dataventa);
        $i = 0;
        foreach ($dataventa as $row) {
            
            $nombredocventa = $row->nombredocventa;
            switch ($row->iddocumentofiscal) {
                case 2:
                    $nombredocventa = 'B.Venta';
                    break;
                case 3:
                    $nombredocventa = 'R.Honorario';    
                    break;
                case 4:
                    $nombredocventa = 'R.Interno';
                    break;
                default:
                    break;
            }

            $row->nombredocventa = $nombredocventa;  
            $pdf->Cell(7, 8, ++$i, $border, 0, 'C', true);
            $pdf->Cell(27, 8, $row->fechaventa.' '.mb_substr($row->horaventa,0,5), $border, 0, 'L', true);
            $pdf->Cell(30, 8, $row->nombredocventa.' '.$row->serie.'-'.$row->serienumero, $border, 0, 'C', true); 
            $pdf->Cell(42, 8, ucwords(strtolower(utf8_decode(mb_substr($row->nombrecliente, 0, 40)))), $border, 0, 'L', true); 
            $pdf->Cell(21, 8, $row->mediopagonombre, $border, 0, 'L', true); 
            $pdf->Cell(16, 8, $row->estadodocumento, $border, 0, 'C', true); 
            $pdf->Cell(19, 8, $row->abrevmoneda.$row->total, $border, 0, 'R', true);  
            $pdf->Cell(38, 8, ucwords(strtolower(utf8_decode(mb_substr($row->created, 0, 24)))), $border, 0, 'L', true);  
            $pdf->Ln();
        }
         
        $pdf->Output();
    }
    
    public function terapiadiaria(Request $request, $enterprise) {
        
        $paramsTMP = $request->all();
                
        $citaterapeutica = new citaterapeutica();
        $horariomedico = new horariomedico(); 
        $empresa = new empresa();
        $sede = sede::find($paramsTMP['idsede']);
        
        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        $param['citaterapeutica.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        
        $param2 = array();
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param2['perfil.idsuperperfil'] = 4; //tipo terapista
        $param2['horariomedico.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
         
        $param3 = [];
        $param3['turnoterapia.idempresa'] = $idempresa;
        $param3['turnoterapia.idsede'] = $paramsTMP['idsede'];                   
        $ddmmyy = explode( '/', $paramsTMP['fecha']); 
        $diasem = date('N', mktime(0, 0, 0, (int)$ddmmyy[1], (int)$ddmmyy[0], (int)$ddmmyy[2])); //php date('N')(Lu=1,...,Do=7)                
        $param3['turnoterapia.dia'] = $diasem;  
        $whereIn = [32, 33, 34];  //32:pendiente, 33:confirmada, 34:atendida, 35:cancelada
        
        $param4 = [];
        $param4['camilla.idempresa'] = $idempresa;
        $param4['camilla.idsede'] = $paramsTMP['idsede'];
        $param4['camilla.activo'] = '1';
        
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn);
        $datahorario = $horariomedico->grid($param2);  
        $dataturnos = $empresa->turnosterapeuticas($param3);
        $cantcamillas = count($empresa->camillas($param4));
        
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }
        
        foreach ($datahorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }
        
        foreach ($dataturnos as $row) {
            $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->citas  = [];
            foreach ($datacita as $cita) {
                if ($cita->start_s === $row->start_s && $cita->end_s === $row->end_s)          
                    $row->citas[] = $cita;                 
            }            
        }  
        
        $cantmax = 0; 
        foreach ($dataturnos as $row) { 
            if( count($row->citas) > $cantmax )
                $cantmax = count($row->citas);            
        }
        
        switch ($paramsTMP['idsede']) {
            case 1: //MI
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:29:00'; $inicio_tt = '15:30:00'; $fin_tt = '21:59:00';                    
                }else{            
                    $inicio_tm = '06:15:00'; $fin_tm = '14:59:00'; $inicio_tt = '15:00:00'; $fin_tt = '21:59:00';
                }
                break;
            case 2: //CH
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '06:15:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;
            case 3: //TR
                if($diasem === '6'){                    
                    $inicio_tm = '06:15:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{
                    $inicio_tm = '06:15:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;
            case 4: //LO 
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '06:15:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 9: //MG 
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '06:15:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 10: //JM 
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '06:15:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 11: //LM 
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '06:15:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;
            case 12: //PR Igual a horario MI
                if($diasem === '6'){
                    $inicio_tm = '06:15:00'; $fin_tm = '15:29:00'; $inicio_tt = '15:30:00'; $fin_tt = '21:59:00';                    
                }else{            
                    $inicio_tm = '06:15:00'; $fin_tm = '14:59:00'; $inicio_tt = '15:00:00'; $fin_tt = '21:59:00';
                }
                break;

        }
        
        $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $inicio_tm, $fin_tm);                
        $turnos[] = array(
            'start_s' => mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
            'end_s' => mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y'])
        );
        
        $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $inicio_tt, $fin_tt);                
        $turnos[] = array(
            'start_s' => mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
            'end_s' => mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y'])
        );
        //dd($turnos);
        //        
        //$pdf = new Fpdf('L', 'mm', [148, 210]); //A5
        $pdf = new PDF('P', 'mm', [210, 297]); //A4 200x..
        $pdf->SetMargins(5,5,5); 
        $pdf->AliasNbPages();
        $border = 1;
        $pdf->SetFillColor(1, 87, 155);
        $pdf->SetDrawColor(255, 255, 255);

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
            
        $pdf->Image('https://sistemas.centromedicoosi.com/img/osi/logologin.png', 5, 5, 40, 0, 'PNG');
        $pdf->Cell(150);
        $pdf->Cell(20, 4, 'Sede:', $border);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(40, 4, utf8_decode($sede->nombre), $border);
        $pdf->Ln();
        $pdf->Cell(150);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(20, 4, 'Fecha:', $border);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(40, 4, $paramsTMP['fecha'], $border);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
            
        $pdf->SetFont('Arial', 'BU', 14); 
        $pdf->Cell(200, 6, utf8_decode('AGENDA CITAS TERAPEUTICAS'), $border, 1, 'C');
        $pdf->Ln(); 
        $pdf->Ln(); 
        
        $pdf->SetDrawColor(1, 87, 155);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        $pdf->SetLineWidth(0.2); 
                        
        $personal = '';
        foreach($datahorario as $pk => $row){
            $hora = date( 'h:i a', $row->start_s);    
            $personal =  ($pk + 1).'). '.ucwords(strtolower(utf8_decode($row->entidad))).' - '.$hora;
            $mod = ($pk + 1) % 2;
            
            if($mod === 0){ 
                $pdf->SetFont('Arial', '', 8.5);
                $pdf->Cell(92, 6, $personal); 
                
                if(($pk + 1) !== count($datahorario))
                    $pdf->Ln();
            }else{
                $pdf->SetFont('Arial', 'B', 8.5);
                $pdf->Cell(16, 6, ($pk === 0 ? 'Personal:' : ''));

                $pdf->SetFont('Arial', '', 8.5);
                $pdf->Cell(92, 6, $personal); 
            }             
        }
               
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetLineWidth(0.4); 
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        
        
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9); 
        
        //dd($cantmax); 
        $cantmax = $cantmax === 0 ? $cantcamillas : $cantmax;
        $whora = 15;
        $w = (200 - $whora ) / $cantmax;
        
        $pdf->Cell($whora, 8, 'HORA', $border, 0, 'C', true);         
        for ($i = 1; $i <= $cantmax; $i++) {
            $pdf->Cell($w, 8, $i, $border, 0, 'C', true); 
        }
                
        $pdf->Ln();
                
        $pdf->SetFont('Arial', '', 8);
        //$pdf->SetFillColor(245, 245, 245);
        $pdf->SetFillColor(224, 243, 176);
        $pdf->SetTextColor(0, 0, 0);
                
        $i = 0;
        foreach ($dataturnos as $row) {
            if($turnos[1]['start_s'] === $row->start_s){
                $pdf->SetFont('Arial', 'B', 9); 
                $pdf->SetFillColor(1, 87, 155); 
                $pdf->SetTextColor(255, 255, 255); 
                
                $pdf->Ln();
                $pdf->Cell($whora, 8, 'HORA', $border, 0, 'C', true);         
                for ($i = 1; $i <= $cantmax; $i++) {
                    $pdf->Cell($w, 8, $i, $border, 0, 'C', true); 
                }
                $pdf->Ln();
                
                $pdf->SetFont('Arial', '', 8);  
                $pdf->SetFillColor(254, 235, 201);
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $hora = date( 'h:i a', $row->start_s);  
            $pdf->SetFont('Arial', 'B', 8); 
            $pdf->Cell($whora, 8, $hora, $border, 0, 'C', true); 
            $pdf->SetFont('Arial', '', 8);             
            for ($i = 1; $i <= $cantmax; $i++) {    
                $paciente = '';
                if(isset($row->citas[$i-1])){
                    $nombre = explode(" ", $row->citas[$i-1]->nombre);
                    $paciente = ucwords(strtolower(utf8_decode($row->citas[$i-1]->apellidopat) .', '. utf8_decode($nombre[0])));
                }                               
                $pdf->Cell($w, 8, $paciente, $border, 0, 'L', true); 
            } 
            $pdf->Ln();
        } 
        $pdf->Output();
    } 

    public function terapiadiariaV2(Request $request, $enterprise) {
        
        $paramsTMP = $request->all();
                
        $citaterapeutica = new citaterapeutica();
        $horariomedico = new horariomedico(); 
        $empresa = new empresa();
        $sede = sede::find($paramsTMP['idsede']);
        
        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        $param['citaterapeutica.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd'); 
        
        $param4 = [];
        $param4['camilla.idempresa'] = $idempresa;
        $param4['camilla.idsede'] = $paramsTMP['idsede'];
        $param4['camilla.activo'] = '1';
        
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [88, 32, 33, 34]); 
        $camillas =  $empresa->camillas($param4);

        $cantcamillas = count($empresa->camillas($param4));
        $data = [];

        $horasTemp = [];
        $horas = [];
        $camas = [];
        $personal = []; 
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if (!in_array($row->inicio, $horasTemp)) {
                $horasTemp[] = $row->inicio;
                $horas[] = array('inicio'=> $row->inicio, 'start_s' => $row->start_s, 'horas' => []);
            }

            if ($row->idpaciente) 
                $camas[$row->inicio]['camillas'][] = $row;

            if (!in_array($row->terapista, $personal)) {
                $personal[] = $row->terapista; 
            }
        }    

        foreach($horas as $i => $row) { 
            if (isset($camas[$row['inicio']])) {
                $horas[$i]['horas'] = $camas[$row['inicio']]['camillas'];
            } 
        }
                  
        //$pdf = new Fpdf('L', 'mm', [148, 210]); //A5
        $pdf = new PDF('P', 'mm', [210, 297]); //A4 200x..
        $pdf->SetMargins(5,5,5); 
        $pdf->AliasNbPages();
        $border = 1;
        $pdf->SetFillColor(1, 87, 155);
        $pdf->SetDrawColor(255, 255, 255);

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
            
        $pdf->Image('https://sistemas.centromedicoosi.com/img/osi/logologin.png', 5, 5, 40, 0, 'PNG');
        $pdf->Cell(150);
        $pdf->Cell(20, 4, 'Sede:', $border);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(40, 4, $sede->nombre, $border);
        $pdf->Ln();
        $pdf->Cell(150);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(20, 4, 'Fecha:', $border);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(40, 4, $paramsTMP['fecha'], $border);
        $pdf->Ln();
        $pdf->Ln();

        // dd($personal);
            
        $pdf->SetFont('Arial', 'BU', 14); 
        $pdf->Cell(200, 6, utf8_decode('AGENDA CITAS TERAPIA'), $border, 1, 'C');
        $pdf->Ln();  
        $pdf->Ln();   

        $pdf->SetDrawColor(1, 87, 155);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        $pdf->SetLineWidth(0.2); 
                        
        $nombrePersonal = '';
        foreach($personal as $pk => $nombre){  
            $nombrePersonal =  ($pk + 1).'). '.ucwords(strtolower(utf8_decode($nombre)));
            $mod = ($pk + 1) % 2;
            
            if($mod === 0){ 
                $pdf->SetFont('Arial', '', 8.5);
                $pdf->Cell(92, 6, $nombrePersonal); 
                
                if(($pk + 1) !== count($personal))
                    $pdf->Ln();
            }else{
                $pdf->SetFont('Arial', 'B', 8.5);
                $pdf->Cell(16, 6, ($pk === 0 ? 'Personal:' : ''));

                $pdf->SetFont('Arial', '', 8.5);
                $pdf->Cell(92, 6, $nombrePersonal); 
            }             
        }
               
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetLineWidth(0.4); 
        $pdf->Line(5, $pdf->GetY() - 6, 205, $pdf->GetY() - 6);
        
         


        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9); 
        
        //dd($cantmax); 
        $cantmax = $cantcamillas;
        $whora = 15;
        $w = (200 - $whora ) / $cantmax;
        
        $pdf->Cell($whora, 8, 'HORA', $border, 0, 'C', true);      
        foreach ($camillas as $value) {  
            $pdf->Cell($w, 8, $value->nombre, $border, 0, 'C', true); 
        }
                
        $pdf->Ln();
                
        $pdf->SetFont('Arial', '', 8); 
        $pdf->SetTextColor(0, 0, 0);
                
        $i = 0;
        foreach ($horas as $row) { 
            
            if ($row['horas']) {
                $hora = date('h:i a', $row['start_s']); 

                if (date('a', $row['start_s']) === 'am') {                    
                    $pdf->SetFillColor(224, 243, 176);
                } else {
                    $pdf->SetFillColor(254, 235, 201);
                }

                $pdf->SetFont('Arial', 'B', 8); 
                $pdf->Cell($whora, 8, $hora, $border, 0, 'C', true); 
                $pdf->SetFont('Arial', '', 8);  

                foreach ($camillas as $camilla) { 
                    $paciente = '';
                    foreach ($row['horas'] as $hora) {        
                        if ($camilla->idcamilla === $hora->idcamilla) {
                            // dd($hora);
                            $nombre = explode(" ", $hora->nombre);
                            $paciente = $hora->apellidopat . ', ' . $nombre[0];
                            break;
                        } 
                        
                    }

                    $pdf->Cell($w, 8, $paciente, $border, 0, 'L', true); 
                }
                 
                $pdf->Ln();
            }
        } 
        $pdf->Output();
    } 
}
