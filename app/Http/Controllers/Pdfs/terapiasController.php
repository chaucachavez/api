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
 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'TERAPIAS POR TURNO'; 
    public $sede;
    public $request; 
    public $cabeceratabla = true;

    function Footer() 
    {            
        $this->SetY(-10);
        $this->SetDrawColor(1, 87, 155); 
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
        $this->SetFillColor(1, 87, 155); 
        // dd($this->path.$this->logo);
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
        $this->SetDrawColor(1, 87, 155);
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
            $this->Cell(15, 10, utf8_decode('Ingreso'), 1, 0, 'C', true); 
            $this->Cell(15, 10, utf8_decode('HC'), 1, 0, 'C', true); 
            $this->Cell(51, 10, utf8_decode('Paciente'), 1, 0, 'L', true);  
            $this->Cell(15, 10, utf8_decode('Inicio'), 1, 0, 'L', true); 
            $this->Cell(15, 10, utf8_decode('Fin'), 1, 0, 'L', true); 
            $this->Cell(20, 10, utf8_decode('Camilla'), 1, 0, 'L', true); 
            $this->Cell(51, 10, utf8_decode('Personal'), 1, 0, 'L', true);                     
            $this->Cell(11, 10, utf8_decode('TF'), 1, 0, 'C', true); 
            $this->Cell(77, 5, utf8_decode('PROCEDIMIENTOS ESPECIALES (PE)'), 1, 0, 'C', true);   
            $this->Cell(15, 5, utf8_decode('N°'), 'T,R,L', 0, 'C', true);  
            $this->Ln();
         
            $this->setX(202);
            $this->Cell(11, 5, utf8_decode('AC'), 1, 0, 'C', true); 
            $this->Cell(11, 5, utf8_decode('QT'), 1, 0, 'C', true); 
            $this->Cell(11, 5, utf8_decode('OCH'), 1, 0, 'C', true); 
            $this->Cell(11, 5, utf8_decode('ESP'), 1, 0, 'C', true); 
            $this->Cell(11, 5, utf8_decode('BL'), 1, 0, 'C', true); 
            $this->Cell(11, 5, utf8_decode('BMG'), 1, 0, 'C', true);  
            $this->Cell(11, 5, utf8_decode('OTROS'), 1, 0, 'C', true);     
            $this->Cell(15, 5, utf8_decode('AGUJAS'), 'R,B,L', 0, 'L', true);          
            $this->Ln();
            
        } 
    } 
}

class terapiasController extends Controller 
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

        // dd($dataterapia, $dataterapiatra);

        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA', '*'=>'OTROS')];   

        //Data terapista 
        $datatratxterapista = $this->agruparPorColumna($dataterapiatra, '', ['idterapista' => 'nombreterapista'], '', $gruposProducto); 
        $data = array();
        foreach($datatratxterapista as $row){ 
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
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
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        }
        
        $dataterapias = $this->ordenarMultidimension($dataterapia, 'hora_llegada', SORT_ASC); 
        
        //dd($dataterapias);
        
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
        $this->pdf->AddPage('L');         
            
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        $i = 1; 
        $totalTF = 0;
        $totalAC = 0;
        $totalQT = 0;
        $totalOCH = 0;
        $totalESP = 0;
        $totalBL = 0;
        $totalBMG = 0;
        $totalOTROS = 0; 
        $totalAGUJA = 0;
        foreach ($dataterapias as $row) {   
            $totalTF += $row->TF;
            $totalAC += $row->AC;
            $totalQT += $row->QT;
            $totalOCH += $row->OCH;
            $totalESP += $row->ESP;
            $totalBL += $row->BL;
            $totalBMG += $row->BMG;
            $totalOTROS += $row->OTROS; 
            $totalAGUJA += $row->AGUJA;

            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(15, 5, $this->convertAmPm($row->hora_llegada), 1, 0, 'C', true);
            $this->pdf->Cell(15, 5, $row->hc, 1, 0, 'C', true);
            $this->pdf->Cell(51, 5, ucwords(strtolower(utf8_decode($row->paciente))), 1, 0, 'L', true); 
            $this->pdf->Cell(15, 5, $this->convertAmPm($row->inicio), 1, 0, 'C', true);
            $this->pdf->Cell(15, 5, $this->convertAmPm($row->fin), 1, 0, 'C', true);
            $this->pdf->Cell(20, 5, $row->nombrecamilla, 1, 0, 'C', true);
            $this->pdf->Cell(51, 5, ucwords(strtolower(utf8_decode($row->nombreterapista))), 1, 0, 'L', true);  
            $this->pdf->Cell(11, 5, $row->TF > 0 ? $row->TF : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->AC > 0 ? $row->AC : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->QT > 0 ? $row->QT : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->OCH > 0 ? $row->OCH : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->ESP > 0 ? $row->ESP : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->BL > 0 ? $row->BL : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->BMG > 0 ? $row->BMG : '', 1, 0, 'C', true);
            $this->pdf->Cell(11, 5, $row->OTROS > 0 ? $row->OTROS : '', 1, 0, 'C', true);
            $this->pdf->Cell(15, 5, $row->AGUJA > 0 ? $row->AGUJA : '', 1, 0, 'C', true);
            $this->pdf->Ln(); 
        }  

        if (count($dataterapiatra) === 0) { 
            $this->pdf->Cell(intval($this->pdf->GetPageWidth()) === 210 ? 204 : (intval($this->pdf->GetPageWidth()) === 297 ? 291 : 0) , 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }

        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(188, 5, '', 1, 0, 'C', true);  
        $this->pdf->Cell(11, 5, $totalTF > 0 ? $totalTF : '', 1, 0, 'C', true);        
        $this->pdf->Cell(11, 5, $totalAC > 0 ? $totalAC : '', 1, 0, 'C', true);
        $this->pdf->Cell(11, 5, $totalQT > 0 ? $totalQT : '', 1, 0, 'C', true);
        $this->pdf->Cell(11, 5, $totalOCH > 0 ? $totalOCH : '', 1, 0, 'C', true);
        $this->pdf->Cell(11, 5, $totalESP > 0 ? $totalESP : '', 1, 0, 'C', true);
        $this->pdf->Cell(11, 5, $totalBL > 0 ? $totalBL : '', 1, 0, 'C', true);
        $this->pdf->Cell(11, 5, $totalBMG > 0 ? $totalBMG : '', 1, 0, 'C', true);  
        $this->pdf->Cell(11, 5, $totalOTROS > 0 ? $totalOTROS : '', 1, 0, 'C', true);  
        $this->pdf->Cell(15, 5, $totalAGUJA > 0 ? $totalAGUJA : '', 1, 0, 'C', true); 
        $this->pdf->Ln(); 

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
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);    
        $this->pdf->Cell(56, 10, utf8_decode('Personal'), 1, 0, 'L', true); 
        $this->pdf->Cell(17, 5, utf8_decode('Pacientes'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(10, 10, utf8_decode('TF'), 1, 0, 'C', true);  
        $this->pdf->Cell(70, 5, utf8_decode('PROCEDIMIENTOS ESPECIALES (PE)'), 1, 0, 'C', true); 
        $this->pdf->Cell(15, 5, utf8_decode('TOTAL'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(15, 5, utf8_decode('TOTAL'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(15, 5, utf8_decode('N°'), 'T,R,L', 0, 'C', true);  
        $this->pdf->Ln();
         
        $this->pdf->setX(65); 
        $this->pdf->Cell(17, 5, utf8_decode('atendidos'), 'R,B,L', 0, 'C', true); 
        $this->pdf->setX(92); 
        $this->pdf->Cell(10, 5, utf8_decode('AC'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('QT'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('OCH'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('ESP'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('BL'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('BMG'), 1, 0, 'C', true);  
        $this->pdf->Cell(10, 5, utf8_decode('OTROS'), 1, 0, 'C', true); 
        $this->pdf->Cell(15, 5, utf8_decode('PE'), 'R,B,L', 0, 'C', true); 
        $this->pdf->Cell(15, 5, utf8_decode('TF+PE'), 'R,B,L', 0, 'C', true); 
        $this->pdf->Cell(15, 5, utf8_decode('AGUJAS'), 'R,B,L', 0, 'L', true);  
        $this->pdf->Ln();    

        /*Tratamientos resumen*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
       
        $i = 1;
        $totalCantidad = 0;
        $totalTF = 0;
        $totalAC = 0;
        $totalQT = 0;
        $totalOCH = 0;
        $totalESP = 0;
        $totalBL = 0;
        $totalBMG = 0;
        $totalOTROS = 0;
        $totalPE = 0;
        $totalTFPE = 0;
        $totalAGUJA = 0;
        foreach ($dataterapistas as $row) {   
            $PE = $row['AC'] + $row['QT'] + $row['OCH'] + $row['ESP'] + $row['BL'] + $row['BMG'] + $row['OTROS']; 
            $TFPE = $PE + $row['TF'];

            $totalCantidad += $row['cantidad'];
            $totalTF += $row['TF'];
            $totalAC += $row['AC'];
            $totalQT += $row['QT'];
            $totalOCH += $row['OCH'];
            $totalESP += $row['ESP'];
            $totalBL += $row['BL'];
            $totalBMG += $row['BMG'];
            $totalOTROS += $row['OTROS'];
            $totalPE += $PE;
            $totalTFPE += $TFPE;
            $totalAGUJA += $row['AGUJA'];

            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(56, 5, ucwords(strtolower(utf8_decode($row['nombreterapista']))), 1, 0, 'L', true);
            $this->pdf->Cell(17, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(10, 5, $row['TF'] > 0 ? $row['TF'] : '', 1, 0, 'C', true);
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(10, 5, $row['AC'] > 0 ? $row['AC'] : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row['QT'] > 0 ? $row['QT'] : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row['OCH'] > 0 ? $row['OCH'] : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row['ESP'] > 0 ? $row['ESP'] : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row['BL'] > 0 ? $row['BL'] : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row['BMG'] > 0 ? $row['BMG'] : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row['OTROS'] > 0 ? $row['OTROS'] : '', 1, 0, 'C', true);
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(15, 5, $PE, 1, 0, 'C', true);  
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(15, 5, $TFPE, 1, 0, 'C', true); 
            $this->pdf->Cell(15, 5, $row['AGUJA'], 1, 0, 'C', true); 
            $this->pdf->Ln();
        }

        if(count($dataterapistas) === 0){
            $this->pdf->Cell(204, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }

        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(62, 10, utf8_decode('Total de atención'), 1, 0, 'C', true);        
        $this->pdf->Cell(17, 10, $totalCantidad > 0 ? $totalCantidad : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 10, $totalTF > 0 ? $totalTF : '', 1, 0, 'C', true);        
        $this->pdf->Cell(10, 10, $totalAC > 0 ? $totalAC : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 10, $totalQT > 0 ? $totalQT : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 10, $totalOCH > 0 ? $totalOCH : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 10, $totalESP > 0 ? $totalESP : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 10, $totalBL > 0 ? $totalBL : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 10, $totalBMG > 0 ? $totalBMG : '', 1, 0, 'C', true);  
        $this->pdf->Cell(10, 10, $totalOTROS > 0 ? $totalOTROS : '', 1, 0, 'C', true); 
        $this->pdf->Cell(15, 10, $totalPE > 0 ? $totalPE : '', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, $totalTFPE > 0 ? $totalTFPE : '', 1, 0, 'C', true);
        $this->pdf->Cell(15, 10, $totalAGUJA > 0 ? $totalAGUJA : '', 1, 0, 'C', true); 
        $this->pdf->Ln();
        
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->Cell(62, 5, utf8_decode('DR:'), 1, 0, 'L', true);
        $this->pdf->Cell(17, 5, '', 1, 0, 'C', true);
        $this->pdf->Cell(70, 5, utf8_decode('Cumplió protocolo:'), 1, 0, 'L', true); 
        $this->pdf->Cell(55, 5, utf8_decode('No cumplió protocolo:'), 1, 0, 'L', true);  
        $this->pdf->Ln(); 
        $this->pdf->Cell(62, 5, utf8_decode('LIC:'), 1, 0, 'L', true);
        $this->pdf->Cell(17, 5, '', 1, 0, 'C', true);
        $this->pdf->Cell(70, 5, utf8_decode('Agujas inicio:'), 1, 0, 'L', true); 
        $this->pdf->Cell(55, 5, utf8_decode('Agujas termino:'), 1, 0, 'L', true);  
        $this->pdf->Ln();
        $this->pdf->Cell(89, 5, utf8_decode('PERSONAL RESPONSABLE:'), 'T,R,L', 0, 'L', true);
        $this->pdf->Cell(60, 5, utf8_decode('Pacientes iniciaron trat.:'), 1, 0, 'L', true);
        $this->pdf->Cell(55, 5, utf8_decode('FIRMA DE REVISADO:'), 'T,R,L', 0, 'L', true);      
        $this->pdf->Ln();        
        $this->pdf->Cell(89, 5, '', 'R,B,L', 0, 'C', true);
        $this->pdf->Cell(60, 5, utf8_decode('Pacientes fin de ciclo:'), 1, 0, 'L', true);
        $this->pdf->Cell(55, 5, '', 'R,B,L', 0, 'C', true);
        $this->pdf->Ln();         
        $this->pdf->Output();           
    } 

}
