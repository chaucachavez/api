<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa;
use App\Models\sede;  
use App\Models\citamedica;  
use App\Models\entidad; 
use App\Models\terapia; 
use App\Models\presupuesto;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'AUDITORIA DE ATENCIÓN DE TERAPIA'; 
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
        
        if($this->request['turno'] === 'M')
            $turno = 'Mañana 06:00 AM - 2:45 PM';
        
        if($this->request['turno'] === 'T')
            $turno = 'Tarde 2:46 PM - 10:00 PM';        
        
        $this->SetDrawColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(13, 6, 'Turno: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(42, 6, utf8_decode($turno), 0);  
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(24, 6, utf8_decode('F. Auditoría:'), 0, 0, 'R');
        $this->SetFont('Arial', ''); 
        $this->Cell(22, 6, '', 'B');  
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 6, utf8_decode('Resp. Auditoría:'), 0, 0, 'R');
        $this->SetFont('Arial', ''); 
        $this->Cell(32, 6, '', 'B');  
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 6, 'Resp. turno TM:', 0, 0, 'R');
        $this->SetFont('Arial', ''); 
        $this->Cell(32, 6, '', 'B'); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 6, 'Resp. turno ADM:', 0, 0, 'R');
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, '', 'B'); 
        $this->Ln();
        $this->Ln();
        $this->Ln();

        $this->SetDrawColor(1, 87, 155);
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6); 
        $this->SetLineWidth(0.2);

        if($this->cabeceratabla){

            /*Cabecera de tabla*/
            $this->SetLineWidth(0.2);
            $this->SetTextColor(255, 255, 255);
            $this->SetDrawColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 7); //Width disponible: 291
            $this->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true); 
            $this->Cell(9, 10, utf8_decode('HC'), 1, 0, 'C', true);  
            $this->Cell(25, 10, utf8_decode('Paciente'), 1, 0, 'L', true);  
            $this->Cell(12, 10, utf8_decode('Ciclos'), 1, 0, 'C', true);  
            $this->Cell(27, 10, utf8_decode('Diagnóstico'), 1, 0, 'L', true);  
            $this->Cell(13, 10, utf8_decode('Ingreso'), 1, 0, 'C', true); 
            $this->Cell(13, 10, utf8_decode('Inicio'), 1, 0, 'C', true); 
            $this->Cell(12, 10, utf8_decode('T.Espera'), 1, 0, 'C', true);
            $this->Cell(12, 10, utf8_decode('Fin'), 1, 0, 'C', true); 
            $this->Cell(12, 10, utf8_decode('T.Terapia'), 1, 0, 'C', true);
            
            $this->Cell(25, 10, utf8_decode('Terapista'), 1, 0, 'L', true);                     
            $this->Cell(7, 5, utf8_decode('Nro.'), 'T,R,L', 0, 'C', true);  
            $this->Cell(7, 10, utf8_decode('Tf'), 1, 0, 'C', true);   
            $this->Cell(7, 10, utf8_decode('Ac'), 1, 0, 'C', true); 
            $this->Cell(7, 10, utf8_decode('Qt'), 1, 0, 'C', true); 
            $this->Cell(7, 10, utf8_decode('Och'), 1, 0, 'C', true); 
            $this->Cell(7, 10, utf8_decode('Esp'), 1, 0, 'C', true); 
            $this->Cell(7, 10, utf8_decode('Bl'), 1, 0, 'C', true); 
            $this->Cell(7, 10, utf8_decode('Bmg'), 1, 0, 'C', true);  
            $this->Cell(7, 10, utf8_decode('Otros'), 1, 0, 'C', true);     
            $this->Cell(10, 10, utf8_decode('Aguja'), 1, 0, 'L', true);  

            $this->SetFillColor(0, 153, 153); 
            $this->Cell(8, 10, utf8_decode('GPS'), 1, 0, 'C', true);     
            $this->Cell(8, 5, utf8_decode('T.'), 'T,R,L', 0, 'C', true);  
            $this->Cell(8, 5, utf8_decode('T.'), 'T,R,L', 0, 'C', true);  
            $this->Cell(8, 5, utf8_decode('Nro.'), 'T,R,L', 0, 'C', true);  
            $this->Cell(10, 10, utf8_decode('Visitado'), 1, 0, 'C', true);  
            $this->Cell(10, 5, utf8_decode('¿Todo'), 'T,R,L', 0, 'C', true);  
            $this->Ln(); 

            $this->setX(169);      
            $this->SetFillColor(1, 87, 155); 
            $this->Cell(7, 5, utf8_decode('Asist.'), 'R,B,L', 0, 'C', true); 

            $this->setX(250);      
            $this->SetFillColor(0, 153, 153);
            $this->Cell(8, 5, utf8_decode('Esp.'), 'R,B,L', 0, 'C', true);  
            $this->Cell(8, 5, utf8_decode('Terap.'), 'R,B,L', 0, 'C', true);  
            $this->Cell(8, 5, utf8_decode('TFs'), 'R,B,L', 0, 'C', true);  
            $this->setX(284);  
            $this->Cell(10, 5, utf8_decode('Bien?'), 'R,B,L', 0, 'C', true);  
            $this->Ln();
            $this->SetFillColor(1, 87, 155); 
            
        } 
    } 
}

class terapiasauditoriaController extends Controller 
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
        $objCitamedica = new citamedica();
        $empresa = new empresa();
        $objPresupuesto = new presupuesto();

        $sede = sede::find($request['idsede']);
        $idempresa = $empresa->idempresa($enterprise); 

        $betweenhora = [];
        if (isset($request['turno']) && !empty($request['turno'])) {
            if($request['turno'] === 'M')
                $betweenhora = array('06:00:00', '14:45:59'); 
            if($request['turno'] === 'T')
                $betweenhora = array('14:46:00', '22:00:00');            
        } 

        $param = array(
            'terapia.idsede' => $request['idsede'], 
            'terapia.fecha' => $this->formatFecha($request['fecha'], 'yyyy-mm-dd')         
        ); 
        
        $dataterapia = $terapia->grid($param, '', '', 15, 'terapia.hora_llegada', 'desc', [36, 37, 38], false, [], $betweenhora)->items(); 
        $dataterapiatra = $terapia->terapiatratamientos($param, '', TRUE, FALSE, '', '', $betweenhora, [36, 37, 38]);       
        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA', '*'=>'OTROS')];   
       
        $whereIdcicloatencionIn = []; 
        $tmpciclos = []; 
        foreach($dataterapiatra as $row) {
            if(!in_array($row->idcicloatencion, $whereIdcicloatencionIn))
                $whereIdcicloatencionIn[] = $row->idcicloatencion;    
                
            if(!isset($tmpciclos[$row->idterapia])){
                $tmpciclos[$row->idterapia]['ciclos'] = [];            
                $tmpciclos[$row->idterapia]['diagnosticos'] = [];    
            }

            if(!in_array($row->idcicloatencion, $tmpciclos[$row->idterapia]['ciclos']))
                $tmpciclos[$row->idterapia]['ciclos'][] = $row->idcicloatencion;
        } 

        $diasasistencia = \DB::table('terapia')
            ->join('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia') 
            ->select('terapiatratamiento.idcicloatencion', \DB::raw('count(distinct terapia.fecha) as nrodia'))
            ->where('terapia.idestado', 38)
            ->whereNull('terapia.deleted')
            ->whereNull('terapiatratamiento.deleted')
            ->whereIn('terapiatratamiento.idcicloatencion', $whereIdcicloatencionIn) 
            ->groupBy('terapiatratamiento.idcicloatencion')  
            ->get()->all(); 

        //Diagnositicos
        $diagnosticosmedicos = [];
        if(!empty($whereIdcicloatencionIn)){ 
            $diagnosticosmedicos = $objCitamedica->diagnosticomedico(['citamedica.idempresa' => $idempresa], '', $whereIdcicloatencionIn); 
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
            
            if(isset($data[$row->idterapia])){                
                foreach($data[$row->idterapia] as $pk => $row2){  
                    $row->$pk = $row2 > 0 ? $row2 : 0;
                }
            }else{
                foreach($gruposProducto[1] as $pk => $val) { 
                    $row->$val = '';
                }
            }
 
            //Ciclos y Dx
            $row->ciclos = '';            
            $row->diagnosticos = ''; 
            $row->tfisicas = ''; 
            $row->nrodia = 0; 
            if(isset($tmpciclos[$row->idterapia]) && !empty($tmpciclos[$row->idterapia]['ciclos']) ){
                $row->ciclos = implode(",", $tmpciclos[$row->idterapia]['ciclos']); 
                foreach($diagnosticosmedicos as $dx){
                    if($dx->idcicloatencion === $tmpciclos[$row->idterapia]['ciclos'][0]){ //Primer Dx
                        $row->diagnosticos = $dx->nombre; 
                        break;
                    }
                }
                 
                // foreach($presupuestosdetalles as $pre){
                //     if(in_array($pre->idcicloatencion, $tmpciclos[$row->idterapia]['ciclos']))
                //         $row->tfisicas = $row->tfisicas + $pre->cantcliente;                     
                // }

                //Añadir nros dias a OSI 
                $tmpdia = 0;
                foreach($tmpciclos[$row->idterapia]['ciclos'] as $idciclo){
                    foreach($diasasistencia as $val){
                        if($val->idcicloatencion === $idciclo){ 
                            $row->nrodia = $row->nrodia + $val->nrodia;  
                        }
                    } 
                }  
            }  
            
        }  

        $this->pdf->sede = $sede; 
        $this->pdf->request = $request; 
        $this->pdf->AddPage('L');         
            
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
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
        $fecha = $this->formatFecha($request['fecha'], 'yyyy-mm-dd'); 
         
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
            
            $tiempoespera = '';
            $tiempoterapia = ''; 

            if(!empty($row->hora_llegada) && !empty($row->inicio) && !empty($row->fin)){                
                $tiempoespera = $this->convertDiff($fecha, $row->hora_llegada, $row->inicio);
                $tiempoterapia = $this->convertDiff($fecha, $row->inicio, $row->fin);
            }

            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(9, 5, $row->hc, 1, 0, 'C', true); 
            $this->pdf->Cell(25, 5, ucwords(strtolower(utf8_decode($row->paciente))), 1, 0, 'L', true); 
            $this->pdf->Cell(12, 5, $row->ciclos, 1, 0, 'C', true); 
            $this->pdf->Cell(27, 5, utf8_decode($row->diagnosticos), 1, 0, 'L', true); 
            $this->pdf->Cell(13, 5, $this->convertAmPm($row->hora_llegada), 1, 0, 'C', true);
            $this->pdf->Cell(13, 5, $this->convertAmPm($row->inicio), 1, 0, 'C', true);
            $this->pdf->Cell(12, 5, $tiempoespera, 1, 0, 'C', true);  
            $this->pdf->Cell(12, 5, $this->convertAmPm($row->fin), 1, 0, 'C', true);
            $this->pdf->Cell(12, 5, $tiempoterapia, 1, 0, 'C', true);  
            $this->pdf->Cell(25, 5, ucwords(strtolower(utf8_decode($row->nombreterapista))), 1, 0, 'L', true);  
            $this->pdf->Cell(7, 5, $row->nrodia, 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->TF > 0 ? $row->TF : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->AC > 0 ? $row->AC : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->QT > 0 ? $row->QT : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->OCH > 0 ? $row->OCH : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->ESP > 0 ? $row->ESP : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->BL > 0 ? $row->BL : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->BMG > 0 ? $row->BMG : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->OTROS > 0 ? $row->OTROS : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row->AGUJA > 0 ? $row->AGUJA : '', 1, 0, 'C', true);
            $this->pdf->Cell(8, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(8, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(8, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(8, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(10, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(10, 5, '', 1, 0, 'C', true);  
            $this->pdf->Ln();  
        }  

        if(count($dataterapiatra) === 0){ 
            $this->pdf->Cell( 0, 5, 'No hay registros.', 1, 0, 'C', true);  
        } else {
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(173, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, $totalTF > 0 ? $totalTF : '', 1, 0, 'C', true);        
            $this->pdf->Cell(7, 5, $totalAC > 0 ? $totalAC : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $totalQT > 0 ? $totalQT : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $totalOCH > 0 ? $totalOCH : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $totalESP > 0 ? $totalESP : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $totalBL > 0 ? $totalBL : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $totalBMG > 0 ? $totalBMG : '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, $totalOTROS > 0 ? $totalOTROS : '', 1, 0, 'C', true);  
            $this->pdf->Cell(10, 5, $totalAGUJA > 0 ? $totalAGUJA : '', 1, 0, 'C', true); 
            $this->pdf->Cell(0, 5, '', 1, 0, 'C', true); 
        } 
        $this->pdf->Ln(); 
        $this->pdf->Cell(0, 5, utf8_decode('Nota: El presente reporte muestra las últimas 15 atenciones realizadas en el turno.'), 0);

        $this->pdf->Ln(); 
        $this->pdf->Ln();  
        $this->pdf->SetFont('Arial', 'BU', 9);
        $this->pdf->Cell(13, 5, 'Observaciones:', 0); 
        $this->pdf->Ln();
        $this->pdf->SetDrawColor(0, 0, 0); 
        $this->pdf->Cell(0, 7, '', 'B');
        $this->pdf->Ln();
        $this->pdf->Cell(0, 7, '', 'B');

        $this->pdf->Output();           
    } 

}
