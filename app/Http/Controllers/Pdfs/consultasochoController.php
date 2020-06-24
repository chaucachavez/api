<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa; 
use App\Models\entidad; 
use App\Models\citamedica;  
use App\Models\horariomedico;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
    public $fillColor = null;
    public $wcelda = 20;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'CONSULTAS POR TURNOS DE MÉDICO'; 
    public $request;  
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
        $this->SetFont('Arial', 'BU', 12);
        $this->Cell(0, 6, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Ln();
        $this->Ln();
        
        //Subcabecera 
        $this->SetDrawColor(1, 87, 155);
        $this->SetLineWidth(0.4);
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 6, 'Desde: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(23, 6, $this->request['desde'], 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 6, 'Hasta: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(23, 6, $this->request['hasta'], 0);  

        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2); 
        
        /*Cabecera de tabla*/ 
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7); 
        $this->SetFillColor(1, 87, 155); 
        $this->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->Cell(50, 10, utf8_decode('Médico'), 1, 0, 'L', true); 
        $this->Cell(38, 10, utf8_decode('Horario'), 1, 0, 'C', true); 
        $this->Cell(30, 10, utf8_decode('Sede'), 1, 0, 'L', true); 
        $this->Cell($this->wcelda, 10, utf8_decode('Disponibilidad'), 1, 0, 'C', true);
        $this->Cell($this->wcelda, 10, utf8_decode('Agendadas'), 1, 0, 'C', true);
        $this->Cell($this->wcelda, 10, utf8_decode('Pagadas'), 1, 0, 'C', true);  
        $this->Cell($this->wcelda, 10, utf8_decode('Atendidas'), 1, 0, 'C', true);
        $this->Ln();    
    }
}

class consultasochoController extends Controller 
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
       
        $objCitamedica = new citamedica();
        $horariomedico = new horariomedico();
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'citamedica.idempresa' => $idempresa 
        );
        $param2 = array(
            'horariomedico.idempresa' => $idempresa,
            'perfil.idsuperperfil' => 3 
        );

        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {    
            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 
        } 
 
        $datacita = $objCitamedica->grid($param, $between, '', '', 'citamedica.fecha', 'asc', [4, 5, 6, 48]); 
        $matrizhorario = $horariomedico->grid($param2, $between);
      
        $opcion = true;
        foreach ($matrizhorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($opcion) {
                /* Obtiene las horas y lo multiplica por el factor
                 * Anado 60 s porque es equivalente a 1 min. y obtengo por ejemplo 4 horas
                 * fACTOR 7.4 = 30 CONSULTAS en 4 HORAS
                 */
                $row->disponibles = ceil((($row->end_s + 60 - $row->start_s ) / 3600) * 7.5);
                $disponibilidad[$row->idhorariomedico]['disponibilidad'] = isset($disponibilidad[$row->idhorariomedico]['disponibilidad']) ? ($disponibilidad[$row->idhorariomedico]['disponibilidad'] + $row->disponibles) : $row->disponibles;
                $disponibilidad[$row->idhorariomedico]['agendada'] = 0;
                $disponibilidad[$row->idhorariomedico]['pagado'] = 0;
                $disponibilidad[$row->idhorariomedico]['atendido'] = 0;
            }
        }

        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $idhorariomedico = '';
            foreach ($matrizhorario as $rowh) {
                if ($rowh->idmedico === $row->idmedico && $rowh->idsede === $row->idsede && $row->start_s >= $rowh->start_s && $row->end_s <= $rowh->end_s) {
                    $idhorariomedico = $rowh->idhorariomedico;
                    break;
                }
            }

            //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada
            $row->idestado = ($row->idestado === 4 || $row->idestado === 5) ? 5 : $row->idestado;
            
            if (empty($idhorariomedico)) {
                if (!isset($sinHorario[$row->idmedico])) {

                    $sinHorario[$row->idmedico]['identidad'] = $row->idmedico;
                    $sinHorario[$row->idmedico]['entidad'] = $row->medico;
                    $sinHorario[$row->idmedico]['disponibilidad'] = 0;
                    $sinHorario[$row->idmedico]['agendada'] = 0;
                    $sinHorario[$row->idmedico]['atendido'] = 0;
                    $sinHorario[$row->idmedico]['pagado'] = 0;
                }

                $sinHorario[$row->idmedico]['agendada'] = $sinHorario[$row->idmedico]['agendada'] + 1;
                if ($row->idestado === 6)
                    $sinHorario[$row->idmedico]['atendido'] = $sinHorario[$row->idmedico]['atendido'] + 1;
                
                if ($row->idestadopago === 71)
                    $sinHorario[$row->idmedico]['pagado'] = $sinHorario[$row->idmedico]['pagado'] + 1;
                
            }else {
                $disponibilidad[$idhorariomedico]['agendada'] = $disponibilidad[$idhorariomedico]['agendada'] + 1;
                if ($row->idestado === 6)
                    $disponibilidad[$idhorariomedico]['atendido'] = $disponibilidad[$idhorariomedico]['atendido'] + 1;
                
                if ($row->idestadopago === 71)
                    $disponibilidad[$idhorariomedico]['pagado'] = $disponibilidad[$idhorariomedico]['pagado'] + 1;
            }
        }
        
        $data = [];
        foreach ($matrizhorario as $row) {
            $temp = array(
                'fecha' => $row->fecha,
                'inicio' => $row->inicio,
                'fin' => $row->fin,
                'identidad' => $row->idmedico,
                'entidad' => $row->entidad,
                'nombresede' => $row->nombresede,
                'disponibilidad' => $disponibilidad[$row->idhorariomedico]['disponibilidad'],
                'agendada' => $disponibilidad[$row->idhorariomedico]['agendada'],
                'atendido' => $disponibilidad[$row->idhorariomedico]['atendido'],
                'pagado' => $disponibilidad[$row->idhorariomedico]['pagado'],
                'idsede' => $row->idsede
            );
            $data[] = $temp;
        }

        if (!empty($sinHorario)) {
            foreach ($sinHorario as $row) {
                $temp = array(
                    'fecha' => null,
                    'inicio' => null,
                    'fin' => null,
                    'identidad' => $row['identidad'],
                    'entidad' => $row['entidad'],
                    'nombresede' => null,
                    'disponibilidad' => $row['disponibilidad'],
                    'agendada' => $row['agendada'],
                    'atendido' => $row['atendido'],
                    'pagado' => $row['pagado'],
                    'idsede' => null
                );
                $data[] = $temp;
            }
        }

        foreach ($data as $ind => $row) {
            $resta = $row['disponibilidad'] - $row['agendada'];
            $data[$ind]['libre'] = $resta > 0 ? $resta : 0;
        } 
        
        $database = $this->ordenarMultidimension($data, 'entidad', SORT_ASC);
       
        //Start Logotipo
        $empresaTmp = $empresa->empresa(['empresa.idempresa' => $idempresa]);  
        $this->pdf->fillColor = explode(",", $empresaTmp->fondocolor);
        $this->pdf->web = $empresaTmp->paginaweb;
        $this->pdf->logo = $empresaTmp->url.'/'.$empresaTmp->imglogologin;  
        //Fin Logotipo
        
        $this->pdf->request = $request; 
        
        /*Reporte TD5*/
        $this->pdf->AddPage('P'); 
 
        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1;  
        foreach ($database as $row) {   
            $horario = '';
            if(!empty($row['inicio']) && !empty($row['fin']))
                $horario = $this->convertAmPm($row['inicio']) . ' - ' . $this->convertAmPm( $this->sumarUnminuto($row['fin']));
            
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(50, 5, utf8_decode($row['entidad']), 1, 0, 'L', true);  
            $this->pdf->Cell(38, 5, $horario, 1, 0, 'C', true);  
            $this->pdf->Cell(30, 5, utf8_decode($row['nombresede']), 1, 0, 'L', true);  
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['disponibilidad'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['agendada'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['pagado'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['atendido'], 1, 0, 'C', true);  
            $this->pdf->Ln(); 
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }  

        /*Salida*/
        $this->pdf->Output();       
    }  
 

}
