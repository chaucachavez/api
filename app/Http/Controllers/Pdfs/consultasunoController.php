<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa; 
use App\Models\entidad; 
use App\Models\citamedica;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
    public $fillColor = null;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';  
    public $titulo = 'CONSULTAS POR ADMISIONISTAS'; 
    public $request; 
    public $fechas; 

    function Footer() 
    {            
        $this->SetY(-10);
        $this->SetDrawColor($this->fillColor[0], $this->fillColor[1], $this->fillColor[2]); 
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0, 0, 0);

        $this->Line(3, $this->GetY(), intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY());  
        
        $this->Cell(70, 5, $this->web, $this->borde);
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . '/{nb}', $this->borde, 1, 'R'); 
        $this->Cell(0, 5, utf8_decode('Impresión: ').  date('d/m/Y H:i') . ' - '. utf8_decode($this->printBy), $this->borde);
    } 
    
    function Header()
    {    
        //Cabecera
        $this->SetDrawColor(0, 0, 0); 
        $this->SetFillColor($this->fillColor[0], $this->fillColor[1], $this->fillColor[2]); 
        $this->Image($this->path.$this->logo, 3, 3, 40, 0, 'PNG');
        $this->Cell(0, 4); 
        $this->Ln();
 
        
        /*Titulo del reporte*/
        $this->SetFont('Arial', 'BU', 14);
        $this->Cell(0, 6, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Ln();
        $this->Ln();
        
        //Subcabecera 
        $this->SetDrawColor($this->fillColor[0], $this->fillColor[1], $this->fillColor[2]);
        $this->SetLineWidth(0.4);
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2);
        
        
        switch ($this->request['inEstado']) {
            case '4,5,6,7,48':
                $estado = 'Todos';
                break;
            case '6':
                $estado = 'Atendido';
                break;
            case '4,5':
                $estado = 'Por atender';
                break;
            case '7':
                $estado = 'Cancelado';
                break;
            case '48':
                $estado = 'No asistió';
                break;
            default:
                $estado = '';
                break;
        }
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 6, 'Desde: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(23, 6, $this->request['desde'], 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 6, 'Hasta: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(23, 6, $this->request['hasta'], 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 6, 'Estado cita: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, utf8_decode($estado), 0);  
        // ($this->path.'|'.$this->logo)
        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2);    
    }
}

class consultasunoController extends Controller 
{    
    public function __construct(Request $request) 
    {         
        $objEntidad = new entidad();
         
        $this->getToken($request);
        
        $this->pdf = new PDF();       
        $this->pdf->SetMargins(3, 3, 3);
        $this->pdf->SetAutoPageBreak(true, 12);
        $this->pdf->AliasNbPages();  
        $this->pdf->SetDrawColor(255, 255, 255); 
        
        $this->pdf->SetFont('Arial', 'B', 8);        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]);    
        $this->pdf->printBy = $this->entidad->entidad;         
    }
    
    public function reporte(Request $request, $enterprise)
    {    
        $objCitamedica = new citamedica();
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'citamedica.idempresa' => $idempresa 
        );

        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {    
            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 
        }

        $whereIn = [];
        if (isset($request['inEstado']) && !empty($request['inEstado'])) {
            $whereIn = explode(',', $request['inEstado']);
        } 

        $datacita = $objCitamedica->grid($param, $between, '', '', 'citamedica.fecha', 'asc', $whereIn);
         
        $fechas = [];
        foreach($datacita as $row){
            $fechas[$row->fecha] = $row->fecha;
            $row->tmpventa =  $row->idestadopago === 71 ? 1 : 0;
        } 
       
        $quiebre = array('id_created_at' => 'id_created_at');      
        $gruposProducto = ['fecha', $fechas];             
        $database = $this->agruparPorColumna($datacita, '', $quiebre, ['id_created_at'=>'created']);  
        $datacitaxdias = $this->agruparPorColumna($datacita, '', ['fecha' => 'fecha']);  
        $datacitaxdiaagrupado = $this->agruparPorColumna($datacita, '', $quiebre, '', $gruposProducto);  
        $datacitaxestadopago = $this->agruparPorColumna($datacita, '', $quiebre, '', ['tmpventa', array(0=>'NOPAGADO', 1=>'PAGADO')]);        
        $datacitaxorigen = $this->agruparPorColumna($datacita, '', $quiebre, '', ['idatencion', array(18=>'SEDE', 19=>'TELEFONO', '*'=>'OTROS')]);
         
        $data = array();
        foreach($datacitaxdiaagrupado as $row){  
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }
            $data[$row['idquiebre']][ $gruposProducto[1][$row['idgrupo']] ] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        }  
       
        $data2 = array();
        foreach($datacitaxestadopago as $row){  
            if(!isset($data2[$row['idquiebre']])) { 
                $data[$row['idquiebre']]['NOPAGADO'] = null;  
                $data[$row['idquiebre']]['PAGADO'] = null;  
            }
            $data2[$row['idquiebre']][$row['grupo']] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        } 

        $data3 = array();
        foreach($datacitaxorigen as $row){  
            if(!isset($data3[$row['idquiebre']])) { 
                $data[$row['idquiebre']]['SEDE'] = null;  
                $data[$row['idquiebre']]['TELEFONO'] = null;  
            }
            $data3[$row['idquiebre']][$row['grupo']] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        } 
      
        $i = 0;
        foreach($database as $row){   
            foreach($data[$row['quiebre']] as $pk => $row2){ 
                $database[$i][$pk] = $row2;
            }  
            foreach($data2[$row['quiebre']] as $pk => $row2){ 
                $database[$i][$pk] = $row2;
            } 
            foreach($data3[$row['quiebre']] as $pk => $row2){ 
                $database[$i][$pk] = $row2;
            }  
            $i = $i + 1;
        }  

        $totaldias = [];
        foreach($datacitaxdias as $row){
            $totaldias[$row['quiebre']] = $row['cantidad'];
        } 

        //Start Logotipo
        $empresaTmp = $empresa->empresa(['empresa.idempresa' => $idempresa]);  
        $this->pdf->fillColor = explode(",", $empresaTmp->fondocolor);
        $this->pdf->web = $empresaTmp->paginaweb;
        $this->pdf->logo = $empresaTmp->url.'/'.$empresaTmp->imglogologin;  
        //Fin Logotipo

        $this->pdf->fechas = $fechas;
        $this->pdf->request = $request;
         
        /*Reporte TD1*/
        $this->pdf->AddPage('L');         
        
        /*Titulo de tabla*/
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(13, 5, utf8_decode('TD1: Consultas reservadas por admisionistas(fecha)'), 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/
        $wcelda = 245 / (count($this->pdf->fechas) + 1); 
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7); 
        $this->pdf->SetFillColor($this->pdf->fillColor[0], $this->pdf->fillColor[1], $this->pdf->fillColor[2]); 
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->pdf->Cell(40, 10, utf8_decode('Personal'), 1, 0, 'L', true); 
         
        foreach($this->pdf->fechas as $fecha){  
            $this->pdf->Cell($wcelda, 5, (int)substr($fecha,0,2), 'T,R,L', 0, 'C', 1);   
            $x = $this->pdf->GetX();
            $y = $this->pdf->GetY();
            $this->pdf->Ln();
            $this->pdf->SetX($x - $wcelda);
            $this->pdf->Cell($wcelda, 5,  $this->convertMes(substr($fecha,3,2)), 'R,B,L', 0, 'C', 1); 
            $this->pdf->SetXY($x, $y);  
        } 
        $this->pdf->Cell($wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true);
        // $this->pdf->RotatedText($this->pdf->GetX() - ($wcelda - 5), $this->pdf->GetY() + 14, 'Total', 90);
        $this->pdf->Ln(); 
 

        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1;
        $wcelda = 245 / (count($this->pdf->fechas) + 1);
        $totalgeneral = 0; 
        $database = $this->ordenarMultidimension($database, 'cantidad', SORT_DESC);
        foreach ($database as $row) { 
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(40, 5, utf8_decode($row['created']), 1, 0, 'L', true);  
            foreach($this->pdf->fechas as $fecha){
                $this->pdf->Cell($wcelda, 5, $row[$fecha] > 0 ? $row[$fecha] : '', 1, 0, 'C', true);
            } 
            $this->pdf->Cell($wcelda, 5, $row['cantidad'] > 0 ? $row['cantidad'] : '', 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad'];
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(46, 5, utf8_decode('Total general'), 1, 0, 'C', true);        
            foreach($this->pdf->fechas as $fecha){
                $this->pdf->Cell($wcelda, 5, $totaldias[$fecha], 1, 0, 'C', true);
            } 
            $this->pdf->Cell($wcelda, 5, $totalgeneral, 1, 0, 'C', true);
            $this->pdf->Ln();
        }

        /*Reporte TD3*/
        $this->pdf->AddPage('P');         
        
        /*Titulo de tabla*/
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(13, 5, utf8_decode('TD3: Consultas reservadas por admisionistas(estado pago)'), 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/
        $wcelda = 20; 
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7); 
        $this->pdf->SetFillColor($this->pdf->fillColor[0], $this->pdf->fillColor[1], $this->pdf->fillColor[2]); 
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->pdf->Cell(40, 10, utf8_decode('Personal'), 1, 0, 'L', true); 
        $this->pdf->Cell($wcelda, 10, utf8_decode('Pagado'), 1, 0, 'C', true);
        $this->pdf->Cell($wcelda, 10, utf8_decode('Por pagar'), 1, 0, 'C', true);
        $this->pdf->Cell($wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true); 
        $this->pdf->Ln(); 


        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1; 
        $totalgeneral = 0; 
        $totalpagado = 0; 
        $totalnopagado = 0; 
        $database = $this->ordenarMultidimension($database, 'created', SORT_ASC);
        foreach ($database as $row) { 
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(40, 5, utf8_decode($row['created']), 1, 0, 'L', true);  
            $this->pdf->Cell($wcelda, 5, $row['PAGADO'], 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $row['NOPAGADO'], 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad'];
            $totalpagado += $row['PAGADO'];
            $totalnopagado += $row['NOPAGADO'];
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(46, 5, utf8_decode('Total general'), 1, 0, 'C', true);        
            $this->pdf->Cell($wcelda, 5, $totalpagado, 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $totalnopagado, 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $totalgeneral, 1, 0, 'C', true);
            $this->pdf->Ln();
        }
        
        /*Reporte TD3.1*/
        $this->pdf->AddPage('P');         
        
        /*Titulo de tabla*/
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(13, 5, utf8_decode('TD3.1: Consultas reservadas por admisionistas(origen)'), 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/
        $wcelda = 20; 
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7); 
        $this->pdf->SetFillColor($this->pdf->fillColor[0], $this->pdf->fillColor[1], $this->pdf->fillColor[2]); 
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->pdf->Cell(40, 10, utf8_decode('Personal'), 1, 0, 'L', true); 
        $this->pdf->Cell($wcelda, 10, utf8_decode('Sede'), 1, 0, 'C', true);
        $this->pdf->Cell($wcelda, 10, utf8_decode('Teléfono'), 1, 0, 'C', true);
        $this->pdf->Cell($wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true); 
        $this->pdf->Ln(); 


        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1; 
        $totalgeneral = 0; 
        $totalsede = 0; 
        $totaltelefono = 0; 
        // $database = $this->ordenarMultidimension($database, 'cantidad', SORT_ASC);
        foreach ($database as $row) { 
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(40, 5, utf8_decode($row['created']), 1, 0, 'L', true);  
            $this->pdf->Cell($wcelda, 5, $row['SEDE'], 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $row['TELEFONO'], 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad'];
            $totalsede += $row['SEDE'];
            $totaltelefono += $row['TELEFONO'];
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(46, 5, utf8_decode('Total general'), 1, 0, 'C', true);        
            $this->pdf->Cell($wcelda, 5, $totalsede, 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $totaltelefono, 1, 0, 'C', true); 
            $this->pdf->Cell($wcelda, 5, $totalgeneral, 1, 0, 'C', true);
            $this->pdf->Ln();
        }

        /*Salida*/
        $this->pdf->Output();       
    }  

}

