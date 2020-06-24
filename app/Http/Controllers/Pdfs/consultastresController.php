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
    public $wcelda = 26;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'Consultas por admisionistas, sede y médico'; 
    public $request; 
    public $fechas; 
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
        $this->Cell(0, 4); 
        $this->Ln(); 
        $this->SetFont('Arial', 'BU', 14);
        $this->Cell(0, 6, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Ln();
        $this->Ln();
        
        //Subcabecera 
        $this->SetDrawColor(1, 87, 155);
        $this->SetLineWidth(0.4);
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2);
        
        
        switch ($this->request['estadopago']) {
            case '1':
                $estado = 'Pagado';
                break;
            case '0':
                $estado = 'No pagado';
                break; 
            default:
                $estado = 'Todos';
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
        $this->Cell(22, 6, 'Estado pago: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, utf8_decode($estado), 0);  

        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2);    
    }
}

class consultastresController extends Controller 
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

        $pendiente = false;
        $pagado = false;
        if (isset($request['estadopago'])) {
            if ($request['estadopago'] === '0') //Pendiente
                $pendiente = true;
            if ($request['estadopago'] === '1') //Pagado
                $pagado = true;
        }
 
        $datacita = $objCitamedica->grid($param, $between, '', '', 'citamedica.fecha', 'asc', [], false, [], $pendiente, $pagado, 'citamedica.fecha', '', true);
       
        $sedes = [];
        $sedesvalor = [];
        $medicos = [];
        $medicosvalor = [];
        foreach($datacita as $row){ 
             
            $sedes[$row->idsede] = !empty($row->idsede) ? $row->sedenombre : "(Vacío)";
            $medicos[$row->idmedico] = !empty($row->medico) ? $row->medico : "(Vacío)";

            switch ($row->idestado) {
                case 6: $estadocita = 'ATENDIDO'; break; 
                case 7: $estadocita = 'CANCELADO'; break; 
                case 48: $estadocita = 'NOASISTIO'; break; 
                case 4: $estadocita = 'PORATENDER'; 
                case 5: $estadocita = 'PORATENDER'; break;   
            }           

            if(!isset($sedesvalor[$row->idpersonal][$row->idsede][$estadocita]))
                $sedesvalor[$row->idpersonal][$row->idsede][$estadocita] = 0;             

            if(!isset($sedesvalor[$row->idpersonal][$row->idsede]['ATENDIDOPAGOTTO']))
                $sedesvalor[$row->idpersonal][$row->idsede]['ATENDIDOPAGOTTO'] = 0;   

            if(!isset($sedesvalor[$row->idpersonal][$row->idsede]['ATENDIDOSOLOCM']))
                $sedesvalor[$row->idpersonal][$row->idsede]['ATENDIDOSOLOCM'] = 0;             

            if(!isset($medicosvalor[$row->idpersonal][$row->idmedico][$estadocita]))
                $medicosvalor[$row->idpersonal][$row->idmedico][$estadocita] = 0;

            if(!isset($medicosvalor[$row->idpersonal][$row->idmedico]['ATENDIDOPAGOTTO']))
                $medicosvalor[$row->idpersonal][$row->idmedico]['ATENDIDOPAGOTTO'] = 0;   

            if(!isset($medicosvalor[$row->idpersonal][$row->idmedico]['ATENDIDOSOLOCM']))
                $medicosvalor[$row->idpersonal][$row->idmedico]['ATENDIDOSOLOCM'] = 0;  
             
            $sedesvalor[$row->idpersonal][$row->idsede][$estadocita] += 1;
            $medicosvalor[$row->idpersonal][$row->idmedico][$estadocita] += 1;

            if($row->idestado === 6){
                if(!empty($row->presupuesto)) {
                    $sedesvalor[$row->idpersonal][$row->idsede]['ATENDIDOPAGOTTO'] += 1;
                    $medicosvalor[$row->idpersonal][$row->idmedico]['ATENDIDOPAGOTTO'] += 1;
                } else {
                    $sedesvalor[$row->idpersonal][$row->idsede]['ATENDIDOSOLOCM'] += 1; 
                    $medicosvalor[$row->idpersonal][$row->idmedico]['ATENDIDOSOLOCM'] += 1;                
                }
            }
        }  
 
        $quiebre = array('idpersonal'=>'personal');                
        $database = $this->agruparPorColumna($datacita, '', $quiebre, ['idpersonal'=>'personal']);        
        $datacitaxpersonal = $this->agruparPorColumna($datacita, '', $quiebre, '', ['idestado', array('6'=>'ATENDIDO','7'=>'CANCELADO', '48'=>'NOASISTIO', '4,5'=>'PORATENDER')]);
         
        $data3 = array();
        foreach($datacitaxpersonal as $row){  
            if(!isset($data3[$row['idquiebre']])) { 
                $data3[$row['idquiebre']]['ATENDIDO'] = null;  
                $data3[$row['idquiebre']]['CANCELADO'] = null;  
                $data3[$row['idquiebre']]['NOASISTIO'] = null; 
                $data3[$row['idquiebre']]['PORATENDER'] = null; 
            }
            $data3[$row['idquiebre']][$row['grupo']] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        } 
        
        $i = 0;
        foreach($database as $row){    
            foreach($data3[$row['quiebre']] as $pk => $row2){ 
                $database[$i][$pk] = $row2;
            }   

            //Sedes
            $j = 0;
            foreach($sedes as $idsede => $nombre){    
                $database[$i]['SEDES'][$j]['nombre']  = $nombre; 
                $database[$i]['SEDES'][$j]['ATENDIDO']  = isset($sedesvalor[$row['quiebre']][$idsede]['ATENDIDO']) ? $sedesvalor[$row['quiebre']][$idsede]['ATENDIDO'] : 0;
                $database[$i]['SEDES'][$j]['CANCELADO']  = isset($sedesvalor[$row['quiebre']][$idsede]['CANCELADO']) ? $sedesvalor[$row['quiebre']][$idsede]['CANCELADO'] : 0;
                $database[$i]['SEDES'][$j]['NOASISTIO']  = isset($sedesvalor[$row['quiebre']][$idsede]['NOASISTIO']) ? $sedesvalor[$row['quiebre']][$idsede]['NOASISTIO'] : 0;
                $database[$i]['SEDES'][$j]['PORATENDER']  = isset($sedesvalor[$row['quiebre']][$idsede]['PORATENDER']) ? $sedesvalor[$row['quiebre']][$idsede]['PORATENDER'] : 0;

                $total = $database[$i]['SEDES'][$j]['ATENDIDO'] + $database[$i]['SEDES'][$j]['CANCELADO'] + $database[$i]['SEDES'][$j]['NOASISTIO'] + $database[$i]['SEDES'][$j]['PORATENDER'];
                $database[$i]['SEDES'][$j]['total']  = $total;                

                $database[$i]['SEDES'][$j]['ATENDIDOPAGOTTO']  = isset($sedesvalor[$row['quiebre']][$idsede]['ATENDIDOPAGOTTO']) ? $sedesvalor[$row['quiebre']][$idsede]['ATENDIDOPAGOTTO'] : 0;
                $database[$i]['SEDES'][$j]['ATENDIDOSOLOCM']  = isset($sedesvalor[$row['quiebre']][$idsede]['ATENDIDOSOLOCM']) ? $sedesvalor[$row['quiebre']][$idsede]['ATENDIDOSOLOCM'] : 0;
                $j = $j + 1;
            }

            //Medicos
            $j = 0;
            foreach($medicos as $idmedico => $nombre){   
                $database[$i]['MEDICOS'][$j]['nombre']  = $nombre; 
                $database[$i]['MEDICOS'][$j]['ATENDIDO']  = isset($medicosvalor[$row['quiebre']][$idmedico]['ATENDIDO']) ? $medicosvalor[$row['quiebre']][$idmedico]['ATENDIDO'] : 0;
                $database[$i]['MEDICOS'][$j]['CANCELADO']  = isset($medicosvalor[$row['quiebre']][$idmedico]['CANCELADO']) ? $medicosvalor[$row['quiebre']][$idmedico]['CANCELADO'] : 0;
                $database[$i]['MEDICOS'][$j]['NOASISTIO']  = isset($medicosvalor[$row['quiebre']][$idmedico]['NOASISTIO']) ? $medicosvalor[$row['quiebre']][$idmedico]['NOASISTIO'] : 0;
                $database[$i]['MEDICOS'][$j]['PORATENDER']  = isset($medicosvalor[$row['quiebre']][$idmedico]['PORATENDER']) ? $medicosvalor[$row['quiebre']][$idmedico]['PORATENDER'] : 0;

                $total = $database[$i]['MEDICOS'][$j]['ATENDIDO'] + $database[$i]['MEDICOS'][$j]['CANCELADO'] + $database[$i]['MEDICOS'][$j]['NOASISTIO'] + $database[$i]['MEDICOS'][$j]['PORATENDER'];
                $database[$i]['MEDICOS'][$j]['total']  = $total;

                $database[$i]['MEDICOS'][$j]['ATENDIDOPAGOTTO']  = isset($medicosvalor[$row['quiebre']][$idmedico]['ATENDIDOPAGOTTO']) ? $medicosvalor[$row['quiebre']][$idmedico]['ATENDIDOPAGOTTO'] : 0;
                $database[$i]['MEDICOS'][$j]['ATENDIDOSOLOCM']  = isset($medicosvalor[$row['quiebre']][$idmedico]['ATENDIDOSOLOCM']) ? $medicosvalor[$row['quiebre']][$idmedico]['ATENDIDOSOLOCM'] : 0;
                $j = $j + 1;
            }

            $i = $i + 1;
        }   
        
        //Start Logotipo
        $empresaTmp = $empresa->empresa(['empresa.idempresa' => $idempresa]);  
        $this->pdf->fillColor = explode(",", $empresaTmp->fondocolor);
        $this->pdf->web = $empresaTmp->paginaweb;
        $this->pdf->logo = $empresaTmp->url.'/'.$empresaTmp->imglogologin;  
        //Fin Logotipo
        
        $this->pdf->request = $request; 
        
        /*Reporte TD5*/
        $this->pdf->AddPage('P');         
        
        /*Titulo de tabla*/
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(13, 5, utf8_decode('TD7: Consultas reservadas por admisionistas y sede'), 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/ 
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7); 
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->pdf->Cell(50, 10, utf8_decode('Personal / Sede'), 1, 0, 'L', true); 
        $this->pdf->Cell($this->pdf->wcelda, 5, utf8_decode('Atendido'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Cancelado'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('No asistió'), 1, 0, 'C', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Por atender'), 1, 0, 'C', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true);
        $this->pdf->Ln(); 
        $this->pdf->SetXY(59, $this->pdf->GetY() - 5);
        $this->pdf->Cell($this->pdf->wcelda/2, 5, utf8_decode('Pagó tto.'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda/2, 5, utf8_decode('Solo cm.'), 1, 0, 'C', true);
        $this->pdf->Ln();  

        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1; 
        $totalgeneral = 0; 
        $totalatendido = 0; 
        $totalcancelado = 0; 
        $totalnoasistio = 0; 
        $totalporatender = 0;         
        $database = $this->ordenarMultidimension($database, 'cantidad', SORT_DESC);
      
        foreach ($database as $row) { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(50, 5, !empty($row['personal'])?utf8_decode($row['personal']):utf8_decode('(Vacío)'), 1, 0, 'L', true);  
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['ATENDIDO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['CANCELADO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['NOASISTIO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['PORATENDER'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad'];
            $totalatendido += $row['ATENDIDO'];
            $totalcancelado += $row['CANCELADO'];
            $totalnoasistio += $row['NOASISTIO'];
            $totalporatender += $row['PORATENDER'];

            $this->pdf->SetFont('Arial', 'I', 7);
           
            foreach ($row['SEDES'] as $fila) {  
                
                if ( $fila['total'] > 0 && !empty($row['personal'])) {
                    $this->pdf->Cell(6, 5, '', 'T,B,L', 0, 'C', true);  
                    $this->pdf->Cell(50, 5, utf8_decode($fila['nombre']), 'T,R,B', 0, 'L', true);   
                    $this->pdf->Cell($this->pdf->wcelda/2, 5, $fila['ATENDIDOPAGOTTO'] > 0 ? $fila['ATENDIDOPAGOTTO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda/2, 5, $fila['ATENDIDOSOLOCM'] > 0 ? $fila['ATENDIDOSOLOCM'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['CANCELADO'] > 0 ? $fila['CANCELADO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['NOASISTIO'] > 0 ? $fila['NOASISTIO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['PORATENDER'] > 0 ? $fila['PORATENDER'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['total'] > 0 ? $fila['total'] : '', 1, 0, 'C', true);
                    $this->pdf->Ln();
                }
            }
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(56, 5, utf8_decode('Total general'), 1, 0, 'C', true);                    
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalatendido > 0 ? $totalatendido : '', 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalcancelado > 0 ? $totalcancelado : '', 1, 0, 'C', true);
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalnoasistio > 0 ? $totalnoasistio : '', 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalporatender > 0 ? $totalporatender : '', 1, 0, 'C', true);
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalgeneral > 0 ? $totalgeneral : '', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }


        /*Reporte TD5.1*/
        $this->pdf->AddPage('P');         
        
        /*Titulo de tabla*/
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(13, 5, utf8_decode('TD7.1: Consultas reservadas por admisionistas y médico'), 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/ 
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7); 
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->pdf->Cell(50, 10, utf8_decode('Personal / Médico'), 1, 0, 'L', true); 
        $this->pdf->Cell($this->pdf->wcelda, 5, utf8_decode('Atendido'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Cancelado'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('No asistió'), 1, 0, 'C', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Por atender'), 1, 0, 'C', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true);
        $this->pdf->Ln(); 
        $this->pdf->SetXY(59, $this->pdf->GetY() - 5);
        $this->pdf->Cell($this->pdf->wcelda/2, 5, utf8_decode('Pagó tto.'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda/2, 5, utf8_decode('Solo cm.'), 1, 0, 'C', true);
        $this->pdf->Ln();  

        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1; 
        $totalgeneral = 0; 
        $totalatendido = 0; 
        $totalcancelado = 0; 
        $totalnoasistio = 0; 
        $totalporatender = 0;         
        $database = $this->ordenarMultidimension($database, 'cantidad', SORT_DESC);
        foreach ($database as $row) { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(50, 5, !empty($row['personal'])?utf8_decode($row['personal']):utf8_decode('(Vacío)'), 1, 0, 'L', true);  
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['ATENDIDO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['CANCELADO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['NOASISTIO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['PORATENDER'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad'];
            $totalatendido += $row['ATENDIDO'];
            $totalcancelado += $row['CANCELADO'];
            $totalnoasistio += $row['NOASISTIO'];
            $totalporatender += $row['PORATENDER'];

            $this->pdf->SetFont('Arial', 'I', 7);
            foreach ($row['MEDICOS'] as $fila) { 
                if ($fila['total'] > 0 && !empty($row['personal'])) {
                    $this->pdf->Cell(6, 5, '', 'T,B,L', 0, 'C', true);  
                    $this->pdf->Cell(50, 5, utf8_decode($fila['nombre']), 'T,R,B', 0, 'L', true);  
                    // $this->pdf->Cell($this->pdf->wcelda, 5, $fila['ATENDIDO'] > 0 ? $fila['ATENDIDO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda/2, 5, $fila['ATENDIDOPAGOTTO'] > 0 ? $fila['ATENDIDOPAGOTTO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda/2, 5, $fila['ATENDIDOSOLOCM'] > 0 ? $fila['ATENDIDOSOLOCM'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['CANCELADO'] > 0 ? $fila['CANCELADO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['NOASISTIO'] > 0 ? $fila['NOASISTIO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['PORATENDER'] > 0 ? $fila['PORATENDER'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['total'] > 0 ? $fila['total'] : '', 1, 0, 'C', true);
                    $this->pdf->Ln();
                }
            }
        }

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(56, 5, utf8_decode('Total general'), 1, 0, 'C', true);                    
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalatendido > 0 ? $totalatendido : '', 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalcancelado > 0 ? $totalcancelado : '', 1, 0, 'C', true);
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalnoasistio > 0 ? $totalnoasistio : '', 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalporatender > 0 ? $totalporatender : '', 1, 0, 'C', true);
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalgeneral > 0 ? $totalgeneral : '', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }  
         

        /*Salida*/
        $this->pdf->Output();       
    }   

}
