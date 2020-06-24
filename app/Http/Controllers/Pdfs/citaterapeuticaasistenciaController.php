<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\terapia;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\citaterapeutica;
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
    public $titulo = 'ASISTENCIAS A TERAPIA';
    public $paciente = null;
    
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
        // $this->Image($this->path.$this->logo, 5, 5, 40, 0, 'PNG');
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, 'SEDE:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->nombresede, $this->borde); 
        $this->Ln(15);

        /*Titulo del reporte*/
        $this->SetFont('Arial', 'BU', 14);
        $this->Cell(0, 6, $this->titulo, 0, 1, 'C');
        $this->Ln();
        $this->Ln(); 


        /*Datos personales del cliente*/
        $this->SetDrawColor(1, 87, 155);
        $this->SetLineWidth(0.4);
        $this->Line(5, $this->GetY() - 6, 205, $this->GetY() - 6);
        $this->SetLineWidth(0.2);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, 'Paciente: ', 0);
        $this->SetFont('Arial', '');
        $this->Cell(80, 6, utf8_decode($this->paciente->entidad), 0);
        $this->SetFont('Arial', 'B');
        $this->Cell(20, 6, ucfirst(strtolower($this->paciente->documentoabrev)) . ':', 0);
        $this->SetFont('Arial', '');
        $this->Cell(70, 6, $this->paciente->numerodoc, 0);
        $this->Ln(); 
        $this->SetFont('Arial', 'B');
        $this->Cell(15, 6, 'Correo: ', 0);
        $this->SetFont('Arial', '');
        $this->Cell(85, 6, $this->paciente->email, 0);
        $this->SetFont('Arial', 'B');
        $this->Cell(20, 6, utf8_decode('Celular:'), 0);
        $this->SetFont('Arial', '');
        $this->Cell(30, 6, $this->paciente->celular, 0);
        $this->SetFont('Arial', 'B');
        $this->Cell(15, 6, 'H.C.: ', 0);
        $this->SetFont('Arial', '');
        $this->Cell(25, 6, $this->paciente->hc, 0,1);
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(5, $this->GetY() - 6, 205, $this->GetY() - 6); 
        $this->SetLineWidth(0.2);

        /*Cabecera de tabla*/
        $this->SetFillColor(1, 87, 155); 
        $this->SetLineWidth(0.2);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9); 
        $this->Cell(8, 8, '#', 1, 0, 'C', true);
        $this->Cell(32, 8, 'SEDE', 1, 0, 'L', true);
        $this->Cell(60, 8, 'FECHA', 1, 0, 'L', true); 
        $this->Cell(25, 8, utf8_decode('RESERVACIÓN'), 1, 0, 'C', true);
        $this->Cell(25, 8, 'CICLO', 1, 0, 'C', true);
        $this->Cell(25, 8, 'HORA', 1, 0, 'C', true);
        $this->Cell(25, 8, 'ESTADO', 1, 0, 'C', true); 
        $this->Ln();
    }
}

class citaterapeuticaasistenciaController extends Controller 
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
        $citaterapeutica = new citaterapeutica();
        $cicloatencion = new cicloatencion();
        $empresa = new empresa();
        $entidad = new entidad();
        $terapia = new terapia();

        $sede = sede::find($request['idsede']);

        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $request['idsede'];
        $param['citaterapeutica.idpaciente'] = $id;

        $param2 = [];
        $param2['terapia.idempresa'] = $idempresa;
        $param2['terapia.idsede'] = $request['idsede'];
        $param2['terapia.idpaciente'] = $id;
        $param2['terapia.idestado'] = 38; 

        $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 

        $whereIn = [32,33,34]; //Pendiente,Confirmada,Atendida //explode(',', $request['inEstado']);
        $fecha = $this->formatFecha($request['fecha'], 'yyyy-mm-dd');

        $fechaAct = $this->fechaInicioFin($request['fecha'], $request['hora'], $request['hora']);
        $start_s = mktime((int) $fechaAct['Hi'], (int) $fechaAct['Mi'], 0, (int) $fechaAct['m'], (int) $fechaAct['d'], (int) $fechaAct['y']);

        //Ciclos de paciente 
        $dataterapia = $terapia->grid($param2, $between, '', '', '', '', '');  
        $datacita = $citaterapeutica->grid($param, $between, '', '', '', '', $whereIn); 
        $paciente = $entidad->entidad(['entidad.identidad' => $id], $request['idsede']);

        $datasistencia = [];
        $whereTerapiaIn = [];
        foreach ($dataterapia as $row) {
            $hora = '00:00:00';

            $fechaIF = $this->fechaInicioFin($row->fecha, $hora, $hora);            
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            
            if ($row->idcitaterapeutica) {
                foreach ($datacita as $index => $cita) {
                    if ($row->idcitaterapeutica === $cita->idcitaterapeutica) {
                        $hora =  $cita->inicio; 
                        unset($datacita[$index]);
                        break;
                    }
                }
            }

            $datasistencia[] = array(
                'table' => 'terapia',
                'nombresede' => $row->nombresede,
                'fecha' => $row->fecha,
                'hora' => $hora,
                'idestado' => $row->idestado,
                'nombreestado' => 'Asistió',//$row->estadocita,
                'reservacion' => $row->idcitaterapeutica ? 'Con cita' : 'Sin cita',
                'ciclo' => null, 
                'idterapia' => $row->idterapia,
                'start_s' => $row->start_s
            );

            // if (!in_array($row->idterapia, $whereTerapiaIn)) {
            $whereTerapiaIn[] = $row->idterapia;
            // }
        } 

        $campos = ['terapiatratamiento.idterapia','terapiatratamiento.idcicloatencion', 'cicloatencion.fecha as fechaopenciclo'];
        
        $tmpciclos = []; 
        $existencia = []; 
        if($whereTerapiaIn) {
            $tmpciclos = $terapia->terapiatratamientoslight('', $campos, true, '', [], false, [], true, $whereTerapiaIn);  
        }  

        $ciclos = $cicloatencion->grid(['cicloatencion.idempresa' => $idempresa, 'cicloatencion.idsede' => $request['idsede'], 'cicloatencion.idpaciente' => $id], '', $between, '', 'cicloatencion.fecha', 'desc', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha']);

        foreach ($ciclos as $row) {
            $existencia[] = $row->idcicloatencion;
        }

        foreach($tmpciclos as $row) {
            if (!in_array($row->idcicloatencion, $existencia)) {
                $row->fecha = $row->fechaopenciclo;
                unset($row->fechaopenciclo);
                $ciclos[] = $row;
                $existencia[] = $row->idcicloatencion;
            }
        } 

        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);            
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $reservacion = null;
            if ($row->start_s > $start_s) {
                if(in_array($row->idestado, [32, 33])) {
                    $row->estadocita = 'Reservado';
                } else {
                    $row->estadocita = null;
                }
            } else {
                if(in_array($row->idestado, [32, 33])) {
                    $row->estadocita = 'Faltó';
                } else {
                    $row->estadocita = 'Asistió';
                }
            } 

            $datasistencia[] = array(
                'table' => 'citaterapeutica',
                'nombresede' => $row->sedenombre,
                'fecha' => $row->fecha,
                'hora' => $row->inicio,
                'idestado' => $row->idestado,
                'nombreestado' => $row->estadocita,
                'reservacion' => '-',
                'ciclo' => null,
                'idterapia' => null,
                'start_s' => $row->start_s
            );
        } 
         
        $datasistencia = $this->ordenarMultidimension($datasistencia, 'start_s', SORT_DESC); 

        $resumenMes = [];
        $resumen = array(
            'asistio' => ['cant' => 0, 'nombre' => 'Asitió'],
            'falto' => ['cant' => 0, 'nombre' => 'Faltó'],  
            'reservo' => ['cant' => 0, 'nombre' => 'Reservó'] 
        );

        foreach($datasistencia as $row) {
            $anomes = substr($row['fecha'], 3, 7);

            if(!isset($resumenMes[$anomes])) {
                $resumenMes[$anomes] = array(
                    'asistio' => 0,
                    'falto' => 0,
                    'reservo' => 0
                );
            }

            switch ($row['nombreestado']) {
                case 'Asistió':
                    $resumenMes[$anomes]['asistio'] += 1; 
                    $resumen['asistio']['cant'] += 1;
                    break;

                case 'Faltó':
                    $resumenMes[$anomes]['falto'] += 1; 
                    $resumen['falto']['cant'] += 1;
                    break;

                case 'Reservado':
                    $resumenMes[$anomes]['reservo'] += 1; 
                    $resumen['reservo']['cant'] += 1;
                    break;
            } 
        }  

        $this->pdf->titulo = $this->pdf->titulo. ' ('.$request['desde'].' al '. $request['hasta'].')';
        $this->pdf->nombresede = $sede->nombre; 
        $this->pdf->paciente = $paciente;

        /*Titulo del reporte*/
        $this->pdf->AddPage();        
        
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9); 
        $i = count($datasistencia);
        $anomestmp = null;
        foreach ($datasistencia as $row) {   
            $strciclos = null;

            if($row['idterapia']) {
                foreach($tmpciclos as $ciclo) {
                    // dd($ciclo, $row);
                    if($ciclo->idterapia === $row['idterapia'])
                        $strciclos .= ($strciclos ? ', ' : '') . $ciclo->idcicloatencion;
                }
            }

            $anomes = substr($row['fecha'], 3, 7);
            $mes = substr($row['fecha'], 3, 2);
            $ano = substr($row['fecha'], 6, 4);

            if ($anomestmp !== $anomes) {
                $this->pdf->SetFont('Arial', 'B', 9); 
                $str_asistio = null;
                $str_falto = null;
                $str_reservo = null;

                $mes = $this->convertMes((int)$mes);

                if ($resumenMes[$anomes]['asistio'] > 0) {
                    $str_asistio = utf8_decode(', Asistió: ').$resumenMes[$anomes]['asistio'];
                }

                if ($resumenMes[$anomes]['falto'] > 0) {
                    $str_falto = utf8_decode(', Faltó: ').$resumenMes[$anomes]['falto'];
                }

                if ($resumenMes[$anomes]['reservo'] > 0) {
                    $str_reservo = utf8_decode(', Reservó: ').$resumenMes[$anomes]['reservo'];
                }

                $this->pdf->Cell(0, 8, $mes . ' ' . $ano . $str_asistio . $str_falto . $str_reservo, 1, 0, 'L', true);
                $this->pdf->Ln();
                $anomestmp = $anomes;
                $this->pdf->SetFont('Arial', '', 9); 
            }

            $this->pdf->Cell(8, 8, $i, 1, 0, 'C', true);
            $this->pdf->Cell(32, 8, $row['nombresede'], 1, 0, 'L', true);
            $this->pdf->Cell(60, 8, $row['fecha'], 1, 0, 'L', true); 
            $this->pdf->Cell(25, 8, $row['reservacion'], 1, 0, 'C', true); 
            $this->pdf->Cell(25, 8, $strciclos, 1, 0, 'C', true); 
            $this->pdf->Cell(25, 8, $this->transformHora($row['hora']), 1, 0, 'C', true); 
            $this->pdf->Cell(25, 8, utf8_decode($row['nombreestado']), 1, 0, 'C', true);             
            $this->pdf->Ln();
            $i--;
        }   
        

        // $this->pdf->SetLineWidth(0.2);
        // $this->pdf->SetTextColor(255, 255, 255);
        // $this->pdf->SetDrawColor(255, 255, 255);
        

        $this->pdf->Ln(); 
        $this->pdf->SetFont('Arial', 'BU', 9);
        $this->pdf->Cell(0, 8, 'RESUMEN DE ASISTENCIA', 1, 1, 'C'); 

        $y = $this->pdf->getY();
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(20, 8, utf8_decode('CÓDIGO'), 1, 0, 'L', true);
        $this->pdf->Cell(80, 8, 'CICLO', 1, 0, 'L', true); 
        $this->pdf->Ln();  
 
        $this->pdf->SetFont('Arial', '', 9);

        foreach ($ciclos as $row) { 
            $this->pdf->Cell(20, 8, $row->idcicloatencion, 1, 0, 'L', true);
            $this->pdf->Cell(80, 8, utf8_decode('Ciclo atención aperturado el ' . $row->fecha), 1, 0, 'L', true);  
            $this->pdf->Ln(); 
        }

        if(count($ciclos) === 0) {
            $this->pdf->Cell(100, 8, utf8_decode('No hay registros.'), 1, 0, 'C', true);  
        }

        $this->pdf->setXY(110, $y);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(60, 8, utf8_decode('DESCRIPCIÓN'), 1, 0, 'L', true);
        $this->pdf->Cell(35, 8, 'CANTIDAD', 1, 0, 'C', true); 
        $this->pdf->Ln();

        $this->pdf->SetFont('Arial', '', 9); 
        
        foreach ($resumen as $row) {   
            $this->pdf->setX(110);
            $this->pdf->Cell(60, 8, utf8_decode($row['nombre']), 1, 0, 'L', true);
            $this->pdf->Cell(35, 8, $row['cant'], 1, 0, 'C', true);  
            $this->pdf->Ln(); 
        }

        /*Condiciones de pago de pago*/
        // $this->pdf->SetFillColor(220, 220, 220); 
        // $this->pdf->SetY(-35); 
        // $this->pdf->SetFont('Arial', 'B', 10);
        // $this->pdf->Cell(0, 8, 'CONDICIONES DE RESERVACIONES', 1, 1, 'C', true);
        // $this->pdf->SetFont('Arial', '', 8.5);
        // $this->pdf->Cell(67, 5, utf8_decode('1.- A su tercera inasistencia a terapia, sus reservas programadas serán eliminadas.'), $this->pdf->borde, 1, 'L');
        // $this->pdf->Cell(67, 5, utf8_decode('2.- Sin embargo podrás volverlas a agendar llamando al 739 0888 Centro Médico OSI.'), $this->pdf->borde, 1, 'L'); 
        
        $this->pdf->Output();       
    }   

}
