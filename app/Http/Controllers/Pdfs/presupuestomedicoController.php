<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\grupodx;
use App\Models\citamedica;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
use App\Http\Controllers\Controller; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0;
    public $nombresede;
    public $idcicloatencion;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'PLAN DE RECUPERACIÓN';
    
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
        $this->Image($this->path.$this->logo, 5, 5, 50, 0, 'PNG'); 
        $this->setX(60);

        $this->SetFont('Arial', 'BU', 12);
        $this->SetTextColor(0, 93, 169);  
        $this->Cell(95, 12, utf8_decode($this->titulo), $this->borde, 0, 'C');
        $this->SetFont('Arial', 'B', 8);

        $this->Cell(20, 4, 'SEDE:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(0, 4, $this->nombresede, $this->borde);
        $this->Ln();
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(20, 4, utf8_decode('CICLO:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(0, 4, $this->idcicloatencion, $this->borde);
        $this->Ln(15);
    }
}

class presupuestomedicoController extends Controller 
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
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);   
        
        $this->pdf->printBy = $this->entidad->entidad;        
        $this->pdf->web = $this->empresa->paginaweb;
        $this->pdf->logo = $this->empresa->url.'/logopdf.png';
    }
    
    public function reporte(Request $request, $enterprise, $id)
    {
        $objCicloatencion = new cicloatencion();
        $objPresupuesto = new presupuesto();
        $objCitamedica = new citamedica();
        $objGrupodx = new grupodx();
        
        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);
        $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
        $autorizaciones = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);

        $grupos = $objGrupodx->grid(['grupodx.idcicloatencion' => $id]);

        $tratamientos = $objCitamedica->tratamientomedicoLight($id); 
        $diagnosticosmedicos = $objCitamedica->diagnosticomedico(['citamedica.idcicloatencion' => $id]);
        
        // BEGIN Tratamientos //
        $tmpTratamientos = [];
        foreach ($tratamientos as $item) {

            $itemPresupuesto = null;
            foreach ($presupuestodet as $row) {
                if ($row->idproducto === $item->idproducto) {
                    $itemPresupuesto = $row;
                    break;
                }
            } 

            if (!isset($tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx])) {
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['idproducto'] = $item->idproducto; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['nombreproducto'] = $item->nombreproducto; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantmedico'] = 0;  
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['preciounitregular'] = (float) $itemPresupuesto->preciounitregular; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['totalregular'] = 0; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['preciounittarjeta'] = (float) $itemPresupuesto->preciounittarjeta; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['totaltarjeta'] = 0; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['preciounitefectivo'] = (float) $itemPresupuesto->preciounitefectivo; 
                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['totalefectivo'] = 0; 

                $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['idgrupodx'] = $item->idgrupodx;  
            }

            $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantmedico'] += $item->cantidad;  
        }  

        foreach ($tmpTratamientos as $index => $item) {
            $tmpTratamientos[$index]['totalregular'] = round($item['cantmedico'] * $item['preciounitregular'], 2);
            $tmpTratamientos[$index]['totaltarjeta'] = round($item['cantmedico'] * $item['preciounittarjeta'], 2);
            $tmpTratamientos[$index]['totalefectivo'] = round($item['cantmedico'] * $item['preciounitefectivo'], 2);
        } 
        // dd($tratamientos);
        // BEGIN Tratamientos //
        
        foreach ($grupos as $index => $row) {

            $grupos[$index]->tratamientos = [];
            $grupos[$index]->diagnosticos = [];

            foreach ($tmpTratamientos as $tratamiento) {
                if ($tratamiento['idgrupodx'] === $row->idgrupodx) { 
                    $grupos[$index]->tratamientos[] = $tratamiento; 
                }
            }

            foreach ($diagnosticosmedicos as $diagnostico) {
                if ($diagnostico->idgrupodx === $row->idgrupodx) {
                    $grupos[$index]->diagnosticos[] = $diagnostico; 
                }
            }
        } 
         
        $this->pdf->nombresede = $cicloatencion->nombresede;
        $this->pdf->idcicloatencion = $cicloatencion->idcicloatencion;
        
        /*Titulo del reporte*/
        $this->pdf->AddPage();     

        $this->pdf->SetDrawColor(0, 93, 169);

        // Filiacion  
        $this->pdf->Ln(); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->SetTextColor(0, 93, 169); 
        $this->pdf->Cell(21, 5, 'PACIENTE:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->SetTextColor(0, 0, 0); 
        $this->pdf->Cell(140, 5, utf8_decode($cicloatencion->entidad), 'B', 0, 'L');
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->SetTextColor(0, 93, 169); 
        $this->pdf->Cell(15, 5, 'H.C:', 0, 0, 'R');
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->SetTextColor(0, 0, 0); 
        $this->pdf->Cell(25, 5, utf8_decode($cicloatencion->hc), 'B', 0, 'L'); 
        $this->pdf->Ln(); 
        $this->pdf->Ln();  
         
        $coapec = '';
        /*Autorizaciones de seguro*/
        foreach($autorizaciones as $row)
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

            if ($row->idproducto === 2) {
                $coapec = '(COA + PECP)';
            } 
        }
        
        if ($autorizaciones)
        {
            $this->pdf->Ln();
        }
        
        /*Cabecera de tabla*/
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(0, 93, 169);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(80, 12, 'Tratamiento', 1, 0, 'L', true);
        $this->pdf->Cell(30, 6, 'Terapias', 1, 0, 'C', true); 
        $this->pdf->Cell(30, 6, 'Tarifa regular', 1, 0, 'C', true);
        $this->pdf->Cell(30, 6, 'Tarifa tarjeta', 1, 0, 'C', true);
        $this->pdf->Cell(30, 6, 'Tarifa efectivo', 1, 0, 'C', true);
        $this->pdf->Ln();
        $this->pdf->setX(85);
        $this->pdf->Cell(30, 6, utf8_decode('Indic. x Médico'), 1, 0, 'C', true); 
        $this->pdf->Cell(15, 6, 'Precio', 1, 0, 'C', true);
        $this->pdf->Cell(15, 6, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(15, 6, 'Precio', 1, 0, 'C', true);
        $this->pdf->Cell(15, 6, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(15, 6, 'Precio', 1, 0, 'C', true);
        $this->pdf->Cell(15, 6, 'Total', 1, 0, 'C', true);
        $this->pdf->Ln();  
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
 
        $regular = 0;
        $tarjeta = 0;
        $efectivo = 0;
        foreach ($grupos as $grupo) { 

            $this->pdf->SetFont('Arial', 'B', 8);
            $this->pdf->SetTextColor(0, 93, 169); 
            $this->pdf->SetFillColor(153, 184, 223);

            $this->pdf->Cell(20, 6, utf8_decode('Código'), 'L,T', 0, 'C', true);
            $this->pdf->Cell(20, 6, utf8_decode('Zona'), 'T', 0, 'C', true);
            $this->pdf->Cell(160, 6, utf8_decode('Diagnóstico' . ' ('.$grupo->nombre.')'), 'R,T', 0, 'L', true);
            $this->pdf->ln();

            foreach ($grupo->diagnosticos as $value) {
                    
                    $this->pdf->SetFont('Arial', '', 8);
                    $this->pdf->SetFillColor(255, 255, 255);                
                    $this->pdf->SetTextColor(0, 0, 0);

                    $this->pdf->setX(5);
                    $x1 = $this->pdf->getX();
                    $y1 = $this->pdf->getY();
                    $this->pdf->Cell(20, 5, utf8_decode($value->codigo), '', 0, 'C');   
                    
                    $zona = '';
                    if ($value->idzona === 'B') {
                        $zona = 'Bilateral';
                    }

                    if ($value->idzona === 'I') {
                        $zona = 'Izquierda';
                    }

                    if ($value->idzona === 'D') {
                        $zona = 'Derecha';
                    }

                    if ($value->idzona === 'N') {
                        $zona = '-';
                    }

                    $this->pdf->Cell(20, 5, utf8_decode($zona), '', 0, 'C');    
                    $this->pdf->MultiCell(160, 5, utf8_decode($value->nombre), 0, 'L', false, 3);  

                    $this->pdf->Line($x1, $y1, $x1, $this->pdf->GetY()); 
                    $this->pdf->Line($x1 + 200, $y1, $x1 + 200, $this->pdf->GetY()); 
                    $this->pdf->Line($x1, $this->pdf->GetY(), 205, $this->pdf->GetY());  
            }
            // $this->pdf->setY($this->pdf->getY() + 2);
            $totalRegular = 0;
            $totalTarjeta = 0;
            $totalEfectivo = 0;
            $contador = 0;
            foreach ($grupo->tratamientos as $row) {

                $regular += $row['totalregular'];
                $tarjeta += $row['totaltarjeta'];
                $efectivo += $row['totalefectivo'];

                $totalRegular += $row['totalregular'];
                $totalTarjeta += $row['totaltarjeta'];
                $totalEfectivo += $row['totalefectivo'];

                $nombreproducto =  $row['nombreproducto'];

                if($row['idproducto'] === 2) {
                    $nombreproducto .= $coapec;
                }
                $this->pdf->SetFont('Arial', '', 8);
                $this->pdf->SetFillColor(255, 255, 255);                
                $this->pdf->SetTextColor(0, 0, 0);

                $this->pdf->Cell(80, 8, utf8_decode(mb_substr($nombreproducto, 0, 27)), 1, 0, 'L', true);
                $this->pdf->Cell(30, 8, $row['cantmedico'], 1, 0, 'C', true); 
                // $this->pdf->Cell(1.5); 
                // $this->pdf->Cell(1.5);
                $this->pdf->Cell(15, 8, $row['preciounitregular'], 'T,B,L', 0, 'R', true); 
                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(15, 8, $row['totalregular'], 'T,B,R', 0, 'R', true); 
                $this->pdf->SetFillColor(255, 255, 255);     
                $this->pdf->Cell(15, 8, $row['preciounittarjeta'], 1, 0, 'R', true); 
                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(15, 8, $row['totaltarjeta'], 'T,B,R', 0, 'R', true); 
                $this->pdf->SetFillColor(255, 255, 255);     
                $this->pdf->Cell(15, 8, $row['preciounitefectivo'], 1, 0, 'R', true); 
                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(15, 8, $row['totalefectivo'], 'T,B,R', 0, 'R', true); 
                $this->pdf->Ln(); 

                $contador ++;
            } 

            if ($contador === count($grupo->tratamientos)) {
                $this->pdf->Cell(110, 8, '', 0, 0, 'L');
                $this->pdf->SetFillColor(255, 255, 255);   
                $this->pdf->Cell(15, 8, 'Total S/.', 'T,B,L', 0, 'R', true); 
                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(15, 8, $totalRegular, 'T,B,R', 0, 'R', true); 
                $this->pdf->SetFillColor(255, 255, 255);     
                $this->pdf->Cell(15, 8, 'Total S/.', 1, 0, 'R', true); 
                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(15, 8, $totalTarjeta, 'T,B,R', 0, 'R', true); 
                $this->pdf->SetFillColor(255, 255, 255);     
                $this->pdf->Cell(15, 8, 'Total S/.', 1, 0, 'R', true); 
                $this->pdf->SetFillColor(220, 220, 220);
                $this->pdf->Cell(15, 8, $totalEfectivo, 'T,B,R', 0, 'R', true); 
                $this->pdf->Ln(); 
            }

            $this->pdf->setY($this->pdf->getY() + 2);
        }

        $this->pdf->setY($this->pdf->getY() + 2);
        // $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(110, 12, 'Pago de dscto. por paquete de 5 sesiones');
        $this->pdf->Cell(30, 12, 'S/. ' . $regular, 1, 0, 'C', true);
        $this->pdf->Cell(30, 12, 'S/. ' . $tarjeta, 1, 0, 'C', true);
        $this->pdf->Cell(30, 12, 'S/. ' . $efectivo, 1, 0, 'C', true);
        $this->pdf->Ln();

        $this->pdf->setY($this->pdf->getY() + 2);
        $this->pdf->Image($this->pdf->path.'bannerpdf.png', null, null, 200, 0, 'PNG');
  
        /*Condiciones de pago de pago*/
        $this->pdf->SetY(-43); 
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(0, 8, 'CONDICIONES DE PAGO ', '', 1, 'C', true);
        $this->pdf->SetFont('Arial', '', 8.5);
        $this->pdf->Cell(67, 5, '1.- Solo a los Pagos de TARIFA REGULAR (sin descuento) se emite FACTURA.', $this->pdf->borde, 1, 'L');
        $this->pdf->Cell(67, 5, '2.- A los pagos de TARIFA CON DESCUENTO se emite BOLETA DE VENTA.', $this->pdf->borde, 1, 'L');
        $this->pdf->Cell(67, 5, '3.- A los pagos de ASEGURADOS Y EPS se emite BOLETA DE VENTA, en Deducibles, Copagos, Tarifa preferencial, Tarifas Especiales y otros.', $this->pdf->borde, 1, 'L');
        $this->pdf->Cell(67, 5, utf8_decode('4.- Se acepta la atención de REEMBOLSO de asegurado y EPS solo a lo pagado con Tarifa Regular.'), $this->pdf->borde, 0, 'L');
        
        $this->pdf->Output();       
    }    
}
