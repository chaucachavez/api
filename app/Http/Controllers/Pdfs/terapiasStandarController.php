<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa;
use App\Models\sede;  
use App\Models\entidad; 
use App\Models\terapia;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
    public $fillColor = null;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'TERAPIAS POR TURNO'; 
    public $sede;
    public $request; 
    public $cabeceratabla = true;

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

        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(intval($this->w) === 210 ? 153 : (intval($this->w) === 297 ? 240 : 0));        
        $this->Cell(20, 4, 'Sede:', $this->borde);

        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->sede->nombre, $this->borde);
        $this->Ln();
        $this->Cell(intval($this->w) === 210 ? 153 : (intval($this->w) === 297 ? 240 : 0));

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, 'Fecha:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->request['fecha'], $this->borde);
        $this->Ln();
        

        //Subcabecera

        /*Titulo del reporte*/
        $this->SetFont('Arial', 'BU', 14);
        $this->Cell(0, 6, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Ln();
        $this->Ln();
        
        /*Datos personales */
        $this->SetDrawColor($this->fillColor[0], $this->fillColor[1], $this->fillColor[2]);
        $this->SetLineWidth(0.4);
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2);
        
        if($this->request['turno'] === 'M'){
            $turno = 'Mañana 06:00 AM - 2:45 PM';
        }
        if($this->request['turno'] === 'T'){
            $turno = 'Tarde 2:46 PM - 10:00 PM';
        }
         
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(13, 6, 'Turno: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, utf8_decode($turno), 0);  

        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6); 
        $this->SetLineWidth(0.2);

        if($this->cabeceratabla){

            /*Cabecera de tabla*/
            $this->SetLineWidth(0.2);
            $this->SetTextColor(255, 255, 255);
            $this->SetDrawColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 8); //Width disponible: 291

            $this->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);              
            $this->Cell(51, 10, utf8_decode('Paciente'), 1, 0, 'L', true);  
            $this->Cell(15, 10, utf8_decode('Inicio'), 1, 0, 'L', true); 
            $this->Cell(15, 10, utf8_decode('Fin'), 1, 0, 'L', true);  
            $this->Cell(51, 10, utf8_decode('Personal'), 1, 0, 'L', true);   

            $width = 153 / count($this->distinctproductos);
            foreach($this->distinctproductos as $value) { 
                $this->Cell($width, 10, $value, 1, 0, 'C', true);               
            }  
            $this->Ln(); 
        } 
    } 
}

class terapiasStandarController extends Controller 
{    
    public function __construct(Request $request) 
    {         
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        
        $this->getToken($request);
        
        $this->pdf = new PDF();       
        $this->pdf->SetMargins(3, 3, 3);
        $this->pdf->SetAutoPageBreak(true, 12);
        $this->pdf->AliasNbPages();  
        $this->pdf->SetDrawColor(255, 255, 255); 
        
        $this->pdf->SetFont('Arial', 'B', 8);
        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]);    
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);   
        
        $this->pdf->printBy = $this->entidad->entidad;        
        $this->pdf->web = $this->empresa->paginaweb;
        $this->pdf->logo = $this->empresa->url.'/'.$this->empresa->imglogologin;   
    }
    
    public function reporte(Request $request, $enterprise)
    {   
        //A4: 297 x 
        $terapia = new terapia(); 
        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $sede = sede::find($request['idsede']);
   
        $betweenhora = [];
        if (isset($request['turno']) && !empty($request['turno'])) {
            if($request['turno'] === 'M')
                $betweenhora = array('06:00:00', '14:45:59'); 
            if($request['turno'] === 'T')
                $betweenhora = array('14:46:00', '22:00:00');            
        } 

        $param = array(
            'terapia.idsede' => $request['idsede'],
            'terapia.idestado' => 38,
            'terapia.fecha' => $this->formatFecha($request['fecha'], 'yyyy-mm-dd')         
        ); 
        
        $dataterapia = $terapia->grid($param, '', '', '', 'terapista.entidad', 'asc', '', false, [], $betweenhora);

        $dataterapiatra = $terapia->terapiatratamientos($param, '', TRUE, FALSE, '', '', $betweenhora);

        

        $distinctproductos = [];

        foreach ($dataterapiatra as $value) {
            if (!in_array($value->codigo, $distinctproductos)) {
                // dd($value);
                $distinctproductos[$value->idproducto] = $value->codigo;
            }
        }
        
        // dd($distinctproductos);

        $gruposProducto = ['idproducto', $distinctproductos];   

        //Data terapista 
        $datatratxterapista = $this->agruparPorColumna($dataterapiatra, '', ['idterapista' => 'nombreterapista'], '', $gruposProducto); 
        $data = array();
        foreach($datatratxterapista as $row){ 
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : '';
        }
          
        $dataterapistas = $this->agruparPorColumna($dataterapia, '', ['idterapista' => 'nombreterapista'], ['idterapista' => 'nombreterapista']);
        foreach($dataterapistas as $index => $row){
            foreach($data[$row['quiebre']] as $pk => $row2){ 
                $dataterapistas[$index][$pk] = $row2;
            }
        }

        
        //Data terapia 
        $datatratxterapia = $this->agruparPorColumna($dataterapiatra, '', ['idterapia' => 'idterapia'], '', $gruposProducto); 
        $data = array();
        foreach($datatratxterapia as $row){  
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : '';
        }
        
        $dataterapias = $this->ordenarMultidimension($dataterapia, 'hora_llegada', SORT_ASC); 
        foreach($dataterapias as $index => $row){
            foreach($data[$row->idterapia] as $pk => $row2){ 
                $row->$pk = $row2;
            }
        } 
        
        //Start Logotipo
        $empresaTmp = $empresa->empresa(['empresa.idempresa' => $idempresa]);  
        $this->pdf->fillColor = explode(",", $empresaTmp->fondocolor);
        $this->pdf->web = $empresaTmp->paginaweb;
        $this->pdf->logo = $empresaTmp->url.'/'.$empresaTmp->imglogologin;  
        //Fin Logotipo

        $this->pdf->sede = $sede; 
        $this->pdf->request = $request; 
        $this->pdf->distinctproductos = $distinctproductos;
        $this->pdf->AddPage('L');         
            
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        $i = 1; 
        $totales = [];

        foreach($this->pdf->distinctproductos as $value) {
            $totales[$value] = 0;
        }

        foreach ($dataterapias as $row) {   

            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);              
            $this->pdf->Cell(51, 5, ucwords(strtolower(utf8_decode($row->paciente))), 1, 0, 'L', true); 
            $this->pdf->Cell(15, 5, $this->convertAmPm($row->inicio), 1, 0, 'C', true);
            $this->pdf->Cell(15, 5, $this->convertAmPm($row->fin), 1, 0, 'C', true); 
            $this->pdf->Cell(51, 5, ucwords(strtolower(utf8_decode($row->nombreterapista))), 1, 0, 'L', true);  

            $width = 153 / count($this->pdf->distinctproductos);
             
            foreach($this->pdf->distinctproductos as $value) { 
                $this->pdf->Cell($width, 5, $row->$value, 1, 0, 'C', true);
                // dd($value, $row->$value);
                $totales[$value] += $row->$value;
            } 
            $this->pdf->Ln(); 
        }  

        if(count($dataterapiatra) === 0){ 
            $this->pdf->Cell( intval($this->pdf->GetPageWidth()) === 210 ? 204 : (intval($this->pdf->GetPageWidth()) === 297 ? 291 : 0) , 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        } 

        $this->pdf->Cell(138, 5, 'Total', 1, 0, 'C', true);           
        foreach($distinctproductos as $value) { 
            $this->pdf->Cell($width, 5, $totales[$value] > 0 ? $totales[$value] : '', 1, 0, 'C', true);        
        } 

        if($this->pdf->getY() > 130){
            $this->pdf->cabeceratabla = false;
            $this->pdf->AddPage('L'); 
        }else{
            $this->pdf->Ln();  
        } 

        /*Resumen*/    
        $this->pdf->SetFont('Arial', 'BU', 10);
        $this->pdf->Cell(13, 5, 'Resumen', 0); 
        $this->pdf->Ln();  

        /*Cabecera de tabla*/
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFillColor($this->pdf->fillColor[0], $this->pdf->fillColor[1], $this->pdf->fillColor[2]); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);    
        $this->pdf->Cell(56, 10, utf8_decode('Personal'), 1, 0, 'L', true); 
        $this->pdf->Cell(30, 10, utf8_decode('Pacientes atendidos'), 'T,R,L', 0, 'C', true); 

        $width = 199 / count($this->pdf->distinctproductos);
             
        foreach($distinctproductos as $value) { 
            $this->pdf->Cell($width, 10, $value, 1, 0, 'C', true); 
        }  
        $this->pdf->Ln();    

        /*Tratamientos resumen*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
       
        $i = 1;
        $totalCantidad = 0; 
        

        $totales = [];
        foreach($this->pdf->distinctproductos as $value) {
            $totales[$value] = 0;
        }

        foreach ($dataterapistas as $row) {    
            // dd($row);
            $totalCantidad += $row['cantidad'];
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(56, 5, ucwords(strtolower(utf8_decode($row['nombreterapista']))), 1, 0, 'L', true);
            $this->pdf->Cell(30, 5, $row['cantidad'], 1, 0, 'C', true);            

            foreach($this->pdf->distinctproductos as $value) {
                $this->pdf->Cell($width, 5, $row[$value], 1, 0, 'C', true);
                $totales[$value] += $row[$value];
            }  

            $this->pdf->Ln();
        } 

        if(count($dataterapistas) === 0){
            $this->pdf->Cell(204, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        } 

        $this->pdf->Cell(62, 5, utf8_decode('Total'), 1, 0, 'C', true);        
        $this->pdf->Cell(30, 5, $totalCantidad > 0 ? $totalCantidad : '', 1, 0, 'C', true);
        foreach($distinctproductos as $value) {
            $this->pdf->Cell($width, 5, $totales[$value], 1, 0, 'C', true); 
        } 
        $this->pdf->Ln();     
        
        $this->pdf->Output();           
    } 
}