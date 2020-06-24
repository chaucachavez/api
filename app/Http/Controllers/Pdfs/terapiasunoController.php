<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa;
use App\Models\sede;  
use App\Models\entidad; 
use App\Models\terapia;  
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
    public $titulo = 'RESUMEN ANUAL DE ATENCION'; 
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
        $this->Image($this->path.$this->logo, 3, 3, 40, 0, 'PNG');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(intval($this->w) === 210 ? 153 : (intval($this->w) === 297 ? 240 : 0));        
        $this->Cell(20, 4, 'Sede:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->sede->nombre, $this->borde);
        $this->Ln();
        $this->Cell(intval($this->w) === 210 ? 153 : (intval($this->w) === 297 ? 240 : 0));
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, utf8_decode('Año:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->request['ano'], $this->borde);
        $this->Ln();
        $this->SetFont('Arial', 'BU', 12);
        $this->Cell(0, 6, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Ln();  

        if($this->cabeceratabla){ 
            /*Cabecera de tabla*/ 
            
        } 
    } 
}

class terapiasunoController extends Controller 
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
        $this->pdf->printBy = $this->entidad->entidad;  
    }
    
    public function reporte(Request $request, $enterprise)
    {
        $empresa = new empresa(); 
        $terapia = new terapia();
        $citamedica = new citamedica(); 
        $sede = sede::find($request['idsede']);
        
        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'terapia.idempresa' => $idempresa,
            'terapia.idsede' => $request['idsede'],
            'terapia.idestado' => 38         
        ); 

        $param2 = array(
            'citamedica.idempresa' => $idempresa,
            'citamedica.idsede' => $request['idsede'],
            'citamedica.idestado' => 6 //Atendido 
        );

        $fields = ['terapia.idterapista', \DB::raw('MONTH(terapia.fecha) as mes')];
        $fields2 = ['producto.idproducto', 'terapiatratamiento.cantidad', \DB::raw('MONTH(terapia.fecha) as mes')];
        $fields3 = ['citamedica.idcitamedica', \DB::raw('MONTH(citamedica.fecha) as mes')];
 
        $dataterapia = $terapia->grid($param, '', '', '', 'terapista.entidad', 'asc', '', false, [], [], $request['ano'], $fields);
        $dataterapiatra = $terapia->terapiatratamientos($param, $fields2, TRUE, FALSE, '', '', [], [], $request['ano']);        
        $datacita = $citamedica->grid($param2, '', '', '', 'citamedica.fecha', 'asc', [], false, [], false, false, 'citamedica.fecha', '', false, false, false, '', $request['ano'], $fields3); 
        
        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA', '*'=>'OTROS')];   
        $database = $this->agruparPorColumna($dataterapia, '', ['mes' => 'mes']);
        $dataxmes = $this->agruparPorColumna($dataterapiatra, '', ['mes' => 'mes'], '', $gruposProducto);         
        $datacitaxmes = $this->agruparPorColumna($datacita, '', ['mes' => 'mes']);
        
        $personal = [];
        foreach($dataterapia as $row) {
            $personal[$row->mes][$row->idterapista] = null;
        } 

        $citas = [];
        foreach($datacitaxmes as $row) {
            $citas[$row['quiebre']] = $row['cantidad'];
        }
        
        $data = array();
        foreach($dataxmes as $row){ 
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }

            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        }
                
        foreach($database as $index => $row){  
            foreach($data[$row['quiebre']] as $pk => $row2){ 
                $database[$index][$pk] = $row2;
            } 
 
            $database[$index]['cantpersonal'] = count($personal[$row['quiebre']]);
            $database[$index]['cantcitas'] = isset($citas[$row['quiebre']]) ? $citas[$row['quiebre']] : 0; 
        } 

        //Start Logotipo
        $empresaTmp = $empresa->empresa(['empresa.idempresa' => $idempresa]);  
        $this->pdf->fillColor = explode(",", $empresaTmp->fondocolor);
        $this->pdf->web = $empresaTmp->paginaweb;
        $this->pdf->logo = $empresaTmp->url.'/'.$empresaTmp->imglogologin;  
        //Fin Logotipo
        
        $this->pdf->sede = $sede; 
        $this->pdf->request = $request; 
        $this->pdf->AddPage('P');     

        /*Cabecera de tabla*/
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(10, 10, utf8_decode('AÑO'), 1, 0, 'C', true);    
        $this->pdf->Cell(18, 10, utf8_decode('MES'), 1, 0, 'L', true); 
        $this->pdf->Cell(10, 10, utf8_decode('CM'), 1, 0, 'C', true); 
        $this->pdf->Cell(17, 5, utf8_decode('TOTAL'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(17, 5, utf8_decode('# PCTES.'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(10, 10, utf8_decode('TF'), 1, 0, 'C', true);  
        $this->pdf->Cell(70, 5, utf8_decode('PROCEDIMIENTOS ESPECIALES (PE)'), 1, 0, 'C', true); 
        $this->pdf->Cell(13, 5, utf8_decode('TOTAL'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(13, 5, utf8_decode('TOTAL'), 'T,R,L', 0, 'C', true); 
        $this->pdf->Cell(13, 5, utf8_decode('N°'), 'T,R,L', 0, 'C', true);  
        $this->pdf->Cell(13, 5, utf8_decode('%'), 'T,R,L', 0, 'C', true);  
        $this->pdf->Ln();
         
        $this->pdf->setX(41); 
        $this->pdf->Cell(17, 5, utf8_decode('PERS.'), 'R,B,L', 0, 'C', true); 
        $this->pdf->Cell(17, 5, utf8_decode('ATENDIDOS'), 'R,B,L', 0, 'C', true); 
        $this->pdf->setX(85); 
        $this->pdf->Cell(10, 5, utf8_decode('AC'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('QT'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('OCH'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('ESP'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('BL'), 1, 0, 'C', true); 
        $this->pdf->Cell(10, 5, utf8_decode('BMG'), 1, 0, 'C', true);  
        $this->pdf->Cell(10, 5, utf8_decode('OTROS'), 1, 0, 'C', true); 
        $this->pdf->Cell(13, 5, utf8_decode('PE'), 'R,B,L', 0, 'C', true); 
        $this->pdf->Cell(13, 5, utf8_decode('TF+PE'), 'R,B,L', 0, 'C', true); 
        $this->pdf->Cell(13, 5, utf8_decode('AGUJAS'), 'R,B,L', 0, 'C', true);  
        $this->pdf->Cell(13, 5, utf8_decode('A/B'), 'R,B,L', 0, 'C', true);  
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

        $tmp = $database;
        $database = [];

        for($i = 1; $i <= 12; $i++){
            $existe = false;
            foreach ($tmp as $row) {   
                if($i === $row['quiebre']){
                    $database[] = $row;
                    $existe = true;
                    break;
                }
            }

            if(!$existe){
                $database[] = array('quiebre' => $i, 'cantpersonal' => 0,  'cantidad' => 0, 'mes' => $i, 'TF' => 0, 'AC' => 0, 'QT' => 0, 'OCH' => 0, 'ESP' => 0, 'BL' => 0, 'BMG' => 0, 'AGUJA' => 0, 'OTROS' => 0);
            }
        }

        foreach ($database as $i => $row) {   
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
            $letra = ($i >= 4 && $i <= 7) ? substr($request['ano'], $i - 4, 1) : '';
            $PORCT = $TFPE > 0 ? ($PE * 100 / $TFPE) : '';

            $this->pdf->SetFillColor(220, 220, 220); 
            $this->pdf->Cell(10, 5, $letra, 'R,L', 0, 'C', true);  
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(18, 5, utf8_decode($this->convertMes($row['quiebre'])), 1, 0, 'L', true);
            $this->pdf->Cell(10, 5, !empty($row['cantcitas'])?$row['cantcitas']:'', 1, 0, 'C', true); 
            $this->pdf->Cell(17, 5, !empty($row['cantpersonal'])?$row['cantpersonal']:'', 1, 0, 'C', true); 
            $this->pdf->Cell(17, 5, !empty($row['cantidad'])?number_format($row['cantidad'], 0, '.', ','):'', 1, 0, 'C', true); 
            $this->pdf->Cell(10, 5, !empty($row['TF'])?number_format($row['TF'], 0, '.', ','):'', 1, 0, 'C', true); 
            $this->pdf->Cell(10, 5, !empty($row['AC'])?$row['AC']:'', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, !empty($row['QT'])?$row['QT']:'', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, !empty($row['OCH'])?$row['OCH']:'', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, !empty($row['ESP'])?$row['ESP']:'', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, !empty($row['BL'])?$row['BL']:'', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, !empty($row['BMG'])?$row['BMG']:'', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, !empty($row['OTROS'])?$row['OTROS']:'', 1, 0, 'C', true); 
            $this->pdf->Cell(13, 5, !empty($PE)?$PE:'', 1, 0, 'C', true);   
            $this->pdf->Cell(13, 5, !empty($TFPE)?number_format($TFPE, 0, '.', ','):'', 1, 0, 'C', true); 
            $this->pdf->Cell(13, 5, !empty($row['AGUJA'])?number_format($row['AGUJA'], 0, '.', ','):'', 1, 0, 'C', true);
            $this->pdf->Cell(13, 5, !empty($PORCT)? (round($PORCT).'%') :'', 1, 0, 'C', true);
            $this->pdf->Ln();
        }

        if(count($database) === 0){
            $this->pdf->Cell(204, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }

        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(10, 5, '', 'B,L', 0, 'C', true);        
        $this->pdf->Cell(18, 5, utf8_decode('Total'), 'T,R,B', 0, 'C', true);  
        $this->pdf->Cell(10, 5, '', 1, 0, 'C', true);
        $this->pdf->Cell(17, 5, '', 1, 0, 'C', true);
        $this->pdf->Cell(17, 5, $totalCantidad > 0 ? number_format($totalCantidad, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 5, $totalTF > 0 ? number_format($totalTF, 0, '.', ',') : '', 1, 0, 'C', true);        
        $this->pdf->Cell(10, 5, $totalAC > 0 ? number_format($totalAC, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 5, $totalQT > 0 ? number_format($totalQT, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 5, $totalOCH > 0 ? number_format($totalOCH, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 5, $totalESP > 0 ? number_format($totalESP, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 5, $totalBL > 0 ? number_format($totalBL, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(10, 5, $totalBMG > 0 ? number_format($totalBMG, 0, '.', ',') : '', 1, 0, 'C', true);  
        $this->pdf->Cell(10, 5, $totalOTROS > 0 ? number_format($totalOTROS, 0, '.', ',') : '', 1, 0, 'C', true); 
        $this->pdf->Cell(13, 5, $totalPE > 0 ? number_format($totalPE, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(13, 5, $totalTFPE > 0 ? number_format($totalTFPE, 0, '.', ',') : '', 1, 0, 'C', true);
        $this->pdf->Cell(13, 5, $totalAGUJA > 0 ? number_format($totalAGUJA, 0, '.', ',') : '', 1, 0, 'C', true); 
        $this->pdf->Cell(13, 5, '', 1, 0, 'C', true); 
        $this->pdf->Ln();
                
        $this->pdf->Output();           
    } 

}
