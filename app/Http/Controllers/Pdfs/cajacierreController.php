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
use App\Models\sede;   
use App\Models\movimiento;   

use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{       
    public $printBy;
    public $web;
    public $borde = 0;
    public $nombresede;
    public $fechacierre;
    public $horacierre;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'CIERRE DE CAJA';
    
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
        $this->Ln(11);
    }
}

class cajacierreController extends Controller 
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
        
        $objSede = new sede();
        $objVenta = new venta();
        $empresa = new empresa();
        $movimiento = new movimiento(); 

        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise);
        $apertura = $objSede->apertura(array('apertura.idapertura' => $id));
        
        $totalresumeningresos = 0;
        $totalotrosegresos = 0;
        $totalotrosingresos = 0;
        $cajafinal = 0;
        
        if($apertura) { 
            //idestadodocumento 26:pendiente 27:pagado 28:anulado
            $ventasrealizadas = $objVenta->grid(['venta.idapertura' => $apertura->idapertura, 'venta.movecon' => '1'], '', '', '', '', '', '', FALSE, TRUE); //, 'venta.idestadodocumento' => 27
            $ventasrealizadas = $this->ordenarMultidimension($ventasrealizadas, 'acronimo', SORT_ASC, 'iddocumentofiscal', SORT_ASC, 'serienumero', SORT_ASC);
            
            $resumenVentas = $this->resumenVentas($ventasrealizadas);

            $apertura->totalefectivo = $resumenVentas['ventaefectivo'];
            $apertura->totaldeposito = $resumenVentas['ventadeposito'];
            $apertura->totalculqiexpress = $resumenVentas['ventaculqiexpress'];
            $apertura->totaltarjeta = $resumenVentas['ventatarjeta'];
            $apertura->ventatarjetaVisa = $resumenVentas['ventatarjetaVisa'];
          
            $apertura->ventatarjetaMastercad = $resumenVentas['ventatarjetaMastercad'];
            $apertura->totalventa = $resumenVentas['ventaefectivo'] + $resumenVentas['ventadeposito'] + $resumenVentas['ventaculqiexpress'] + $resumenVentas['ventatarjeta'];  
                    
            $resumeningresos = $this->resumenIngresos($ventasrealizadas); 

            $ventasanuladas = $objVenta->grid(['venta.idapertura' => $apertura->idapertura, 'venta.movecon' => '0'], '', '', '', '', '', '', FALSE, TRUE);   //, 'venta.idestadodocumento' => 28
            $otrosingresos = $movimiento->grid(array('movimiento.idapertura' => $apertura->idapertura, 'movimiento.tipo' => '1'));
            $otrosegresos = $movimiento->grid(array('movimiento.idapertura' => $apertura->idapertura, 'movimiento.tipo' => '2'));            
 
            // dd($resumeningresos);
            foreach($resumeningresos as $row){ 
                $totalresumeningresos += $row['monto'];
            }
            
            foreach($otrosegresos as $row){ 
                $totalotrosegresos += $row->total;  
            }
            
            foreach($otrosingresos as $row){
                $totalotrosingresos += $row->total; 
            }

            $cajafinal =  $apertura->saldoinicial + $apertura->totalefectivo + $totalotrosingresos - $totalotrosegresos; 
            
            $update['cajafinal'] = $cajafinal;
            $update['faltantesobrante'] = ($apertura->totalsoles + $apertura->totaldolares * $apertura->tcdolar) - $cajafinal;

            //dd($update);
            \DB::table('apertura')->where(array('idapertura' => $id))->update($update); 
        }
                    
        $h = 6;
        $this->pdf->nombresede = $apertura->nombresede; 
        $this->pdf->fechacierre = $apertura->fechacierre;
        $this->pdf->horacierre = $apertura->horacierre;
        
        /*Titulo del reporte*/
        $this->pdf->AddPage();        
        $this->pdf->SetFont('Arial', 'BU', 14);
        $this->pdf->Cell(0, 6, utf8_decode($this->pdf->titulo), 0, 1, 'C');
        $this->pdf->Ln();
        $this->pdf->Ln();
        
        /*Datos personales*/
        $this->pdf->SetDrawColor(1, 87, 155);
        $this->pdf->SetLineWidth(0.4);
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(25, 6, 'Apertura: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(105, 6, strtoupper(utf8_decode($apertura->personalapertura)), 0);  
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Fecha: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->fechaapertura, 0); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Hora: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->horaapertura, 0); 
        $this->pdf->Ln();  
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(25, 6, 'Cierre: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(105, 6, strtoupper(utf8_decode($apertura->personalcierre)), 0); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Fecha: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->fechacierre, 0); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Hora: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->horacierre, 0); 
        $this->pdf->Ln();  
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetLineWidth(0.4); 
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);  
        $this->pdf->SetLineWidth(0.2);  
        
        /*Resumen de ingresos*/ 
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(5, 8, '1)', 1, 0, 'L'); 
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(195, 8, 'Resumen de ingresos', 1, 1, 'L'); 
        $y = $this->pdf->GetY();
                    
        
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(50, 8, 'Documento', 1, 0, 'L', true);
        $this->pdf->Cell(28, 8, utf8_decode('Emisión'), 1, 0, 'C', true);  
        $this->pdf->Cell(20, 8, 'Monto', 1, 0, 'C', true);
        $this->pdf->Ln();   
                    
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        foreach ($resumeningresos as $row) { 
            $this->pdf->Cell(50, $h, utf8_decode('('.$row['acronimo'].') '.$row['nombredocventa']), 1, 0, 'L', true);  
            $this->pdf->Cell(28, $h, utf8_decode($row['emitidas']), 1, 0, 'C', true);  
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h, number_format($row['monto'], 2, '.', ','), 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln();
        }  
        if(count($resumeningresos) === 0)
            $this->pdf->Cell(98, $h, 'No hay registros.', 1, 1, 'C', true); 
        
        $this->pdf->SetFillColor(245, 245, 245);   
        $this->pdf->Cell(78, 10, 'Total: S/.', 1, 0, 'R', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(20, 10, number_format($totalresumeningresos, 2, '.', ','), 1, 0, 'R', true); 
        $this->pdf->Ln(); 
        $y2documento = $this->pdf->GetY();
        
        $this->pdf->SetY($y); 
        $this->pdf->SetX(107);   
        
        //Formasde pago
        $this->pdf->SetFillColor(1, 87, 155);
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(78, 8, 'Forma de pago', 1, 0, 'L', true);        
        $this->pdf->Cell(20, 8, 'Monto', 1, 0, 'C', true);
        $this->pdf->Ln();   
        $this->pdf->SetX(105);
        
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
       
        $formadepago = array(
            array('forma' => 'Efectivo', 'monto' => number_format($apertura->totalefectivo, 2, '.', ',')),
            array('forma' => 'Depósito o transferencia', 'monto' => number_format($apertura->totaldeposito, 2, '.', ',')),
            array('forma' => 'Culqi express', 'monto' => number_format($apertura->totalculqiexpress, 2, '.', ',')),            
            array('forma' => 'Tarjeta Visa (Lote: '.$apertura->visanetlote.')', 'monto' => number_format($apertura->ventatarjetaVisa, 2, '.', ',')),  
            array('forma' => 'Tarjeta Mastercard (Lote: '.$apertura->mastercadlote.')', 'monto' => number_format($apertura->ventatarjetaMastercad, 2, '.', ',')),  
        );
 
        foreach($formadepago as $row){
            $this->pdf->SetX(107);
            $this->pdf->Cell(78, $h, utf8_decode($row['forma']), 1, 0, 'L', true);   
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h,  $row['monto'], 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln(); 
        }
        $this->pdf->SetX(107);
        $this->pdf->SetFillColor(245, 245, 245);   
        $this->pdf->Cell(78, 10, 'Total: S/.', 1, 0, 'R', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(20, 10, number_format($apertura->totalventa, 2, '.', ','), 1, 0, 'R', true); 
        $this->pdf->Ln();
        

        // dd($apertura->ventatarjetaVisa, (string) $apertura->ventatarjetaVisa, gettype($apertura->ventatarjetaVisa));

        if ((string) $apertura->ventatarjetaVisa === '1205.8') {
            // dd('No entiendo del porque revisar https://www.php.net/manual/es/language.types.float.php');
            $apertura->ventatarjetaVisa = 1205.8;
        }

        // https://sistemas.centromedicoosi.com/apiosi/public/osi/pdf/caja/cierre/6424?us=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC93d3d3LmxhZ3JhbmVzY3VlbGEuY29tIiwibXkiOjEsIm15dXNlcm5hbWUiOiI0NDEyMDAyNiIsIm15c2VkZSI6W10sIm15dGltZSI6IjIwMTktMDYtMjcgMTY6MDA6MzkiLCJteWFkbWFzaXN0ZW5jaWEiOjEsIm15ZW50ZXJwcmlzZSI6MSwibXlwZXJmaWxpZHBhcmVudCI6MSwibXlwZXJmaWxpZCI6MSwibXlwZXJmaWxudWV2byI6IjEiLCJteXBlcmZpbGVkaXRhciI6IjEiLCJteXBlcmZpbGVsaW1pbmFyIjoiMSIsIm9wdGluZm9ybWUiOiIxIn0.7Nilg16ZT_xPdnveBU7QhNnrnaOOZrjNGDlrn7C9mCc
        if ((string) $apertura->ventatarjetaVisa === '962.4') {
            $apertura->ventatarjetaVisa = 962.4;
        }
        
        // dd($apertura->ventatarjetaVisa);
        
        // https://sistemas.centromedicoosi.com/apiosi/public/osi/pdf/caja/cierre/6414?us=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC93d3d3LmxhZ3JhbmVzY3VlbGEuY29tIiwibXkiOjEsIm15dXNlcm5hbWUiOiI0NDEyMDAyNiIsIm15c2VkZSI6W10sIm15dGltZSI6IjIwMTktMDYtMjcgMTY6MDA6MzkiLCJteWFkbWFzaXN0ZW5jaWEiOjEsIm15ZW50ZXJwcmlzZSI6MSwibXlwZXJmaWxpZHBhcmVudCI6MSwibXlwZXJmaWxpZCI6MSwibXlwZXJmaWxudWV2byI6IjEiLCJteXBlcmZpbGVkaXRhciI6IjEiLCJteXBlcmZpbGVsaW1pbmFyIjoiMSIsIm9wdGluZm9ybWUiOiIxIn0.7Nilg16ZT_xPdnveBU7QhNnrnaOOZrjNGDlrn7C9mCc
        if ((string) $apertura->ventatarjetaVisa === '2296.9') {
            $apertura->ventatarjetaVisa = 2296.9;
        }

        // https://sistemas.centromedicoosi.com/apiosi/public/osi/pdf/caja/cierre/6689?us=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC93d3d3LmxhZ3JhbmVzY3VlbGEuY29tIiwibXkiOjEsIm15dXNlcm5hbWUiOiI0NDEyMDAyNiIsIm15c2VkZSI6W10sIm15dGltZSI6IjIwMTktMDctMjAgMTA6MjY6MTIiLCJteWFkbWFzaXN0ZW5jaWEiOjEsIm15ZW50ZXJwcmlzZSI6MSwibXlwZXJmaWxpZHBhcmVudCI6MSwibXlwZXJmaWxpZCI6MSwibXlwZXJmaWxudWV2byI6IjEiLCJteXBlcmZpbGVkaXRhciI6IjEiLCJteXBlcmZpbGVsaW1pbmFyIjoiMSIsIm9wdGluZm9ybWUiOiIxIn0.Kq7FLpnqg1Q1VcObuG1zHveqS8QC5YAsTlxcQ3cU-uY
        if ((string) $apertura->ventatarjetaVisa === '463.7') {
            $apertura->ventatarjetaVisa = 463.7;
        }
        
        // https://sistemas.centromedicoosi.com/apiosi/public/osi/pdf/caja/cierre/7472?us=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC93d3d3LmxhZ3JhbmVzY3VlbGEuY29tIiwibXkiOjEsIm15dXNlcm5hbWUiOiI0NDEyMDAyNiIsIm15c2VkZSI6W10sIm15dGltZSI6IjIwMTktMDktMzAgMDg6MDY6MjgiLCJteWFkbWFzaXN0ZW5jaWEiOjEsIm15ZW50ZXJwcmlzZSI6MSwibXlwZXJmaWxpZHBhcmVudCI6MSwibXlwZXJmaWxpZCI6MSwibXlwZXJmaWxudWV2byI6IjEiLCJteXBlcmZpbGVkaXRhciI6IjEiLCJteXBlcmZpbGVsaW1pbmFyIjoiMSIsIm9wdGluZm9ybWUiOiIxIn0.Ov_95HHZDuKNct3onvsQ913IZqUdaDUQQbwecCxBg4U
        if ((string) $apertura->ventatarjetaVisa === '3553.4') {
            $apertura->ventatarjetaVisa = 3553.4;
        } 
        
        // dd($apertura->ventatarjetaVisa);

        $diferenciaVisa = (float) $apertura->totalvisa - $apertura->ventatarjetaVisa;
        $diferenciaMastercard = (float) $apertura->totalmastercard - (float) $apertura->ventatarjetaMastercad;

        // if ((float) $apertura->ventatarjetaVisa == 1205.8) {
        //     dd('Bien');
        // }

        // if ($apertura->ventatarjetaVisa == 1205.8) {
        //     dd('Bien');
        // }

        // if ($diferenciaVisa == -2.27373675443E-13) {
        //     dd('Bien d');   
        // }

        // if ((double) $apertura->ventatarjetaVisa == 1205.8) {
        //     dd('Bien');
        // } else {
        //     echo $apertura->ventatarjetaVisa; 
        //     dd('Mal', $apertura->ventatarjetaVisa, (double) $apertura->ventatarjetaVisa,  gettype($apertura->ventatarjetaVisa), gettype(1205.80), $diferenciaVisa);
        // }

        // $valores = array(
        //     'a' => (double) $apertura->totalvisa,
        //     'b' => $apertura->ventatarjetaVisa,
        //     'c' => ((double) $apertura->totalvisa) - $apertura->ventatarjetaVisa,
        //     'd' => gettype((double) $apertura->totalvisa),
        //     'e' => gettype($apertura->ventatarjetaVisa),
        //     'f' => 1205.8 - 1205.80
        // );
        // dd($valores);



        
        // $diferenciaVisa = -0.00;

        // dd($diferenciaVisa, gettype($diferenciaVisa));
        // var_dump($diferenciaVisa);

        if ($diferenciaVisa != 0) {
            $this->pdf->SetTextColor(255, 0, 0);
            $this->pdf->SetX(107);
            $this->pdf->SetFillColor(245, 245, 245);    
            $this->pdf->Cell(39, $h, 'Tarjeta Visa: S/.' . $apertura->totalvisa, 1, 0, 'L', true); 
            $this->pdf->Cell(39, $h, 'Faltante/sobrante S/.', 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220); 
            $this->pdf->Cell(20, $h, number_format($diferenciaVisa, 2, '.', ','), 1, 0, 'R', true); 
            $this->pdf->Ln();
            $this->pdf->SetTextColor(0, 0, 0);
        }

        if ($diferenciaMastercard != 0) {
            $this->pdf->SetTextColor(255, 0, 0);
            $this->pdf->SetX(107);
            $this->pdf->SetFillColor(245, 245, 245);   
            $this->pdf->Cell(39, $h, 'Tarjeta Mastercard: S/.' . $apertura->totalmastercard, 1, 0, 'L', true); 
            $this->pdf->Cell(39, $h, 'Faltante/sobrante S/.', 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h, number_format($diferenciaMastercard, 2, '.', ','), 1, 0, 'R', true); 
            $this->pdf->Ln();
            $this->pdf->SetTextColor(0, 0, 0);
        }
        $y2formapago = $this->pdf->GetY();
        
        
        /*Cuadre de caja*/ 
        $this->pdf->SetY($y2formapago > $y2documento ? $y2formapago : $y2documento ); 
        $this->pdf->Ln(6);
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(5, 8, '2)', 1, 0, 'L'); 
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(195, 8, 'Cuadre de caja', 1, 1, 'L'); 
        $y = $this->pdf->GetY(); 
        
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(78, 8, 'Caja final', 1, 0, 'L', true);  
        $this->pdf->Cell(20, 8, 'Monto', 1, 0, 'C', true);
        $this->pdf->Ln();   
                    
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8); 
        $cajafinala = array(
            array('forma' => 'Caja inicial', 'monto' => number_format($apertura->saldoinicial, 2, '.', ',')),  
            array('forma' => 'Efectivo', 'monto' => number_format($apertura->totalefectivo, 2, '.', ',')),  
            array('forma' => 'Otros ingresos', 'monto' => number_format($totalotrosingresos, 2, '.', ',')),  
            array('forma' => 'Otros egresos', 'monto' => '-'. number_format($totalotrosegresos, 2, '.', ','))
        );
        foreach ($cajafinala as $row) {  
            $this->pdf->Cell(78, $h,$row['forma'], 1, 0, 'L', true);  
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h, $row['monto'], 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln();
        }   
                    
        $this->pdf->SetFillColor(245, 245, 245); 
        $this->pdf->Cell(78, 10, 'Total: S/.', 1, 0, 'R', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(20, 10, number_format($cajafinal, 2, '.', ','), 1, 0, 'R', true); 
        $this->pdf->Ln();  
        
        $this->pdf->SetY($y); 
        $this->pdf->SetX(107);   
        
        //Formas de pago
        $this->pdf->SetFillColor(1, 87, 155);
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(78, 8, 'Resumen', 1, 0, 'L', true);        
        $this->pdf->Cell(20, 8, 'Monto', 1, 0, 'C', true);
        $this->pdf->Ln();   
        $this->pdf->SetX(105);
        
        $this->pdf->SetFillColor(245, 245, 245); 
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        
        $fs = round(($apertura->totalsoles + $apertura->totaldolares * $apertura->tcdolar) - $cajafinal, 2);
        $cuadrecaja = array(
            array('forma' => 'Caja final', 'monto' => 'S/. '.number_format($cajafinal, 2, '.', ',')),  
            array('forma' => '- Total soles', 'monto' => 'S/. '.number_format($apertura->totalsoles, 2, '.', ',')),  
            array('forma' => '- Total dolares (Tc: S/. '.$apertura->tcdolar.')', 'monto' => '$ '. number_format($apertura->totaldolares, 2, '.', ','))            
        );
        foreach($cuadrecaja as $row){
            $this->pdf->SetX(107);
            $this->pdf->Cell(78, $h, $row['forma'], 1, 0, 'L', true);   
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h,  $row['monto'], 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln(); 
        } 
        $this->pdf->SetX(107);
        $this->pdf->SetFillColor(245, 245, 245);    
        $this->pdf->Cell(78, 10, 'Faltante/sobrante de caja S/.', 1, 0, 'R', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(20, 10, number_format($fs, 2, '.', ','), 1, 0, 'R', true); 
        $this->pdf->Ln();
        
        /*Resumen de otros egresos*/ 
        $this->pdf->Ln();  
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(5, 8, '3)', 1, 0, 'L'); 
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(195, 8, 'Resumen de otros egresos', 1, 1, 'L');
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(7, 8, utf8_decode('N°'), 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Fecha', 1, 0, 'L', true);
        $this->pdf->Cell(48, 8, 'Persona/Empresa', 1, 0, 'L', true);
        $this->pdf->Cell(25, 8, 'Documento', 1, 0, 'L', true);
        $this->pdf->Cell(15, 8, utf8_decode('Número'), 1, 0, 'L', true);
        $this->pdf->Cell(15, 8, 'C.Gasto', 1, 0, 'L', true);
        $this->pdf->Cell(40, 8, 'Concepto', 1, 0, 'L', true);
        $this->pdf->Cell(15, 8, 'Tipo pago', 1, 0, 'L', true);  
        $this->pdf->Cell(20, 8, 'Total', 1, 0, 'R', true);
        $this->pdf->Ln();   
                    
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8); 
        $i = 1;
        foreach ($otrosegresos as $row) {
            $nombretipopago = $row->tipopago === '1' ? 'Efectivo' : 'Cheque';
            $this->pdf->Cell(7, $h, $i++, 1, 0, 'C', true); 
            $this->pdf->Cell(15, $h, $row->fecha, 1, 0, 'L', true);  
            $this->pdf->Cell(48, $h, strtoupper(utf8_decode($row->entidad)), 1, 0, 'L', true); 
            $this->pdf->Cell(25, $h, utf8_decode($row->nombredocumento), 1, 0, 'L', true); 
            $this->pdf->Cell(15, $h, $row->numero, 1, 0, 'L', true); 
            $this->pdf->Cell(15, $h, isset($row->codigo) ? $row->codigo : '', 1, 0, 'C', true); 
            $this->pdf->Cell(40, $h, strtoupper(utf8_decode(mb_substr($row->concepto, 0 , 30))), 1, 0, 'L', true); 
            $this->pdf->Cell(15, $h, $nombretipopago, 1, 0, 'C', true);             
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h, $row->total, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln();
        }   

        if(count($otrosegresos) === 0){
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 0, 'C', true); 
        }else{
            $this->pdf->SetFillColor(245, 245, 245);    
            $this->pdf->Cell(180, 10, 'Total: S/.', 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, 10, number_format($totalotrosegresos, 2, '.', ','), 1, 0, 'R', true);              
        }  
        $this->pdf->Ln();     

        /*Resumen de otros ingresos*/  
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(5, 8, '4)', 1, 0, 'L'); 
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(195, 8, 'Resumen de otros ingresos', 1, 1, 'L');
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(7, 8, utf8_decode('N°'), 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Fecha', 1, 0, 'L', true);
        $this->pdf->Cell(48, 8, 'Persona/Empresa', 1, 0, 'L', true);
        $this->pdf->Cell(25, 8, 'Documento', 1, 0, 'L', true);
        $this->pdf->Cell(15, 8, utf8_decode('Número'), 1, 0, 'L', true);
        $this->pdf->Cell(55, 8, 'Concepto', 1, 0, 'L', true);
        $this->pdf->Cell(15, 8, 'Tipo pago', 1, 0, 'L', true);  
        $this->pdf->Cell(20, 8, 'Total', 1, 0, 'R', true);
        $this->pdf->Ln();   
                    
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8); 
        $i = 1;
        foreach ($otrosingresos as $row) {
            $nombretipopago = $row->tipopago === '1' ? 'Efectivo' : 'Cheque';
            $this->pdf->Cell(7, $h, $i++, 1, 0, 'C', true); 
            $this->pdf->Cell(15, $h, $row->fecha, 1, 0, 'L', true);  
            $this->pdf->Cell(48, $h, strtoupper(utf8_decode($row->entidad)), 1, 0, 'L', true); 
            $this->pdf->Cell(25, $h, $row->nombredocumento, 1, 0, 'L', true); 
            $this->pdf->Cell(15, $h, $row->numero, 1, 0, 'L', true); 
            $this->pdf->Cell(55, $h, strtoupper(utf8_decode($row->concepto)), 1, 0, 'L', true); 
            $this->pdf->Cell(15, $h, $nombretipopago, 1, 0, 'C', true);             
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h, $row->total, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Ln();
        }  
        
        if(count($otrosingresos) === 0){
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 0, 'C', true); 
        }else{
            $this->pdf->SetFillColor(245, 245, 245);    
            $this->pdf->Cell(180, 10, 'Total: S/.', 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, 10, number_format($totalotrosingresos, 2, '.', ','), 1, 0, 'R', true);         
        } 

        /*Titulo del reporte*/
        $this->pdf->AddPage();        
        $this->pdf->SetFont('Arial', 'BU', 14);
        $this->pdf->Cell(0, 6, utf8_decode($this->pdf->titulo), 0, 1, 'C');
        $this->pdf->Ln();
        $this->pdf->Ln();
        
        /*Datos personales*/
        $this->pdf->SetDrawColor(1, 87, 155);
        $this->pdf->SetLineWidth(0.4);
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(25, 6, 'Apertura: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(105, 6, strtoupper(utf8_decode($apertura->personalapertura)), 0);  
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Fecha: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->fechaapertura, 0); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Hora: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->horaapertura, 0); 
        $this->pdf->Ln();  
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(25, 6, 'Cierre: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(105, 6, strtoupper(utf8_decode($apertura->personalcierre)), 0); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Fecha: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->fechacierre, 0); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(15, 6, 'Hora: ', 0);
        $this->pdf->SetFont('Arial', ''); 
        $this->pdf->Cell(20, 6, $apertura->horacierre, 0); 

        $this->pdf->Ln();  
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetLineWidth(0.4); 
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);  
        $this->pdf->SetLineWidth(0.2);  
        
        /*Resumen de ventas realizadas*/ 
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(5, 8, '5)', 1, 0, 'L'); 
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(195, 8, 'Ventas realizadas', 1, 1, 'L');
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(7, 8, utf8_decode('N°'), 1, 0, 'C', true);
        $this->pdf->Cell(55, 8, 'Documento', 1, 0, 'L', true);
        $this->pdf->Cell(20, 8, 'Cliente', 1, 0, 'L', true);
        $this->pdf->Cell(10, 8, utf8_decode('HC'), 1, 0, 'C', true); 
        $this->pdf->Cell(15, 8, 'F.Venta', 1, 0, 'C', true);
        $this->pdf->Cell(16, 8, 'M.Pago', 1, 0, 'L', true);
        $this->pdf->Cell(15, 8, 'M.Pago2', 1, 0, 'L', true);
        $this->pdf->Cell(12, 8, utf8_decode('N°.Ope'), 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Estado', 1, 0, 'C', true); 
        $this->pdf->Cell(15, 8, utf8_decode('Afec.Caja'), 1, 0, 'C', true); 
        $this->pdf->Cell(20, 8, 'Total', 1, 0, 'R', true);
        $this->pdf->Ln();   
                    
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8); 
        $i = 1;
        $total = 0;
        $nctotal = 0;
        foreach ($ventasrealizadas as $row) {   
            $afectacaja = $row->movecon === '1' ? 'Si' : 'No';
            
            $this->pdf->Cell(7, $h, $i++, 1, 0, 'C', true); 
            $this->pdf->Cell(55, $h, utf8_decode($row->documentoSerieNumero), 1, 0, 'L', true);  
            $this->pdf->Cell(20, $h, strtoupper(utf8_decode($row->nombrecliente)), 1, 0, 'L', true); 
            $this->pdf->Cell(10, $h, $row->hc, 1, 0, 'C', true); 
            $this->pdf->Cell(15, $h, $row->fechaventa, 1, 0, 'C', true); 
            $this->pdf->Cell(16, $h, utf8_decode($row->mediopagonombre), 1, 0, 'L', true); 
            $this->pdf->Cell(15, $h, utf8_decode($row->mediopagosegnombre), 1, 0, 'L', true); 
            $this->pdf->Cell(12, $h, $row->tarjetapriope . '' . $row->nrooperacion, 1, 0, 'C', true);         
            $this->pdf->Cell(15, $h, $row->estadodocumento, 1, 0, 'C', true);
            $this->pdf->Cell(15, $h, $afectacaja, 1, 0, 'C', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(20, $h, $row->total, 1, 0, 'R', true);
            $this->pdf->SetFillColor(245, 245, 245);  
            $this->pdf->Ln();
            $total += $row->total;

            $nctotal += 0;
        }  

        if(count($ventasrealizadas) === 0){
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 0, 'C', true); 
        }else{
            $this->pdf->SetFillColor(245, 245, 245);  
            $this->pdf->Cell(165, 10, 'Total: S/.', 1, 0, 'R', true);
            $this->pdf->Cell(15, 10, $nctotal > 0 ? number_format($nctotal, 2, '.', ',') : '', 1, 0, 'R', true);
            $this->pdf->SetFillColor(220, 220, 220);  
            $this->pdf->Cell(20, 10, number_format($total, 2, '.', ','), 1, 0, 'R', true); 
        }
        $this->pdf->Ln();
        
        /*Resumen de ventas anuldas*/ 
        $this->pdf->SetFillColor(1, 87, 155);  
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(255, 255, 255);        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(5, 8, '6)', 1, 0, 'L'); 
        $this->pdf->SetFont('Arial', 'BU', 12);
        $this->pdf->Cell(195, 8, utf8_decode('Anulación de ventas y reemplazos'), 1, 1, 'L');
        $this->pdf->SetTextColor(255, 255, 255); 
        $this->pdf->SetFont('Arial', 'B', 6.5);
        $this->pdf->Cell(7, 16, utf8_decode('N°'), 1, 0, 'C', true);
        $this->pdf->Cell(97, 8, 'COMPROBANTE', 1, 0, 'C', true);
        $this->pdf->Cell(96, 8, 'LA REEMPLAZA', 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->SetX(12);
        $this->pdf->Cell(51, 8, 'Documento', 1, 0, 'L', true);
        $this->pdf->Cell(13, 8, 'F.Venta', 1, 0, 'C', true);
        $this->pdf->Cell(11, 8, 'Estado', 1, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Af.Caja', 1, 0, 'C', true);
        $this->pdf->Cell(12, 8, 'Total', 1, 0, 'R', true);
        $this->pdf->Cell(50, 8, 'Documento', 1, 0, 'L', true);
        $this->pdf->Cell(13, 8, 'F.Venta', 1, 0, 'C', true);
        $this->pdf->Cell(11, 8, 'Estado', 1, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Af.Caja', 1, 0, 'C', true);
        $this->pdf->Cell(12, 8, 'Total', 1, 0, 'R', true);
        $this->pdf->Ln();   
        
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 6.5);

        $ventasanuladosreemplazos = [];
        foreach ($ventasanuladas as $row) {
            $existe = false;
            foreach ($ventasrealizadas as $item) {
                if ($row->idventa === $item->idventareemplazo) {
                    $existe = true;
                    break;
                }
            }

            if (!$existe) { 

                if ($row->idventareemplazo) {
                    // dd($row);
                    $ventasanuladosreemplazos[] = (object) array(
                        'documentoSerieNumero' => $row->reemplazoDocumentoSerieNumero,
                        'fechaventa' => $row->fechaventareemplazo,
                        'estadodocumento' => $row->reemplazoestadodocumento,
                        'movecon' => $row->reemplazomovecon,
                        'total' => $row->reemplazototal,

                        'reemplazoDocumentoSerieNumero' => $row->documentoSerieNumero,
                        'fechaventareemplazo' => $row->fechaventa,
                        'reemplazoestadodocumento' => $row->estadodocumento,
                        'reemplazomovecon' => $row->movecon,
                        'reemplazototal' => $row->total,                    
                    );
                } else {
                    $ventasanuladosreemplazos[] = $row;
                }

            }
        }

        foreach ($ventasrealizadas as $row) {
            if ($row->idventareemplazo) {
                // dd($row);
                $ventasanuladosreemplazos[] = (object) array(
                    'documentoSerieNumero' => $row->reemplazoDocumentoSerieNumero,
                    'fechaventa' => $row->fechaventareemplazo,
                    'estadodocumento' => $row->reemplazoestadodocumento,
                    'movecon' => $row->reemplazomovecon,
                    'total' => $row->reemplazototal,

                    'reemplazoDocumentoSerieNumero' => $row->documentoSerieNumero,
                    'fechaventareemplazo' => $row->fechaventa,
                    'reemplazoestadodocumento' => $row->estadodocumento,
                    'reemplazomovecon' => $row->movecon,
                    'reemplazototal' => $row->total,                    
                );
            }
        }

        $i = 1;
        $totalanulado = 0;
        $totalreemplazo = 0;
        foreach ($ventasanuladosreemplazos as $row) { 
            if (isset($row->totalanulado))
                $totalanulado += (double) $row->totalanulado;

            $totalreemplazo += (double) $row->total; 

            $afectacaja = $row->movecon === '1' ? 'Si' : 'No';

            $afectacajareemplazo = '';
            if (isset($row->reemplazomovecon)) {
                $afectacajareemplazo = $row->reemplazomovecon === '1' ? 'Si' : 'No';
            }
            // dd($row);
            $this->pdf->SetFillColor(245, 245, 245);  
            $this->pdf->Cell(7, $h, $i++, 1, 0, 'C', true); 
             
            $this->pdf->Cell(51, $h, utf8_decode($row->documentoSerieNumero), 1, 0, 'L', true);       
            $this->pdf->Cell(13, $h, $row->fechaventa, 1, 0, 'C', true);         
            $this->pdf->Cell(11, $h, $row->estadodocumento, 1, 0, 'C', true); 
            $this->pdf->Cell(10, $h, $afectacaja, 1, 0, 'C', true); 
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(12, $h, $row->total, 1, 0, 'R', true);

            $this->pdf->SetFillColor(245, 245, 245); 
            $this->pdf->Cell(50, $h, isset($row->reemplazoDocumentoSerieNumero) ? utf8_decode($row->reemplazoDocumentoSerieNumero) : NULL, 1, 0, 'L', true);   
            $this->pdf->Cell(13, $h, isset($row->fechaventareemplazo)?$row->fechaventareemplazo:NULL, 1, 0, 'C', true); 
            $this->pdf->Cell(11, $h, isset($row->reemplazoestadodocumento)?$row->reemplazoestadodocumento:NULL, 1, 0, 'C', true); 
            $this->pdf->Cell(10, $h, $afectacajareemplazo, 1, 0, 'C', true); 
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(12, $h, isset($row->reemplazototal) ? $row->reemplazototal: NULL, 1, 0, 'R', true); 
            $this->pdf->Ln();
        }  

        if(count($ventasanuladosreemplazos) === 0){
            $this->pdf->Cell(200, $h, 'No hay registros.', 1, 0, 'C', true); 
        }else{

            // $this->pdf->SetFillColor(245, 245, 245);      
            // $this->pdf->Cell(7, 10, '', 1, 0, 'R', true);
            // $this->pdf->Cell(77, 10, 'Total: S/.', 1, 0, 'R', true);
            // $this->pdf->SetFillColor(220, 220, 220);   
            // $this->pdf->Cell(20, 10, number_format($totalanulado, 2, '.', ','), 1, 0, 'R', true); 

            // $this->pdf->SetFillColor(245, 245, 245);   
            // $this->pdf->Cell(76, 10, 'Total: S/.', 1, 0, 'R', true);
            // $this->pdf->SetFillColor(220, 220, 220);  
            // $this->pdf->Cell(20, 10, number_format($totalreemplazo, 2, '.', ','), 1, 0, 'R', true);
        }

        $this->pdf->Output();             
    }        
}
