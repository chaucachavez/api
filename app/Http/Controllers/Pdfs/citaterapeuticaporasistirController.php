<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
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
    public $titulo = 'RESERVACIONES A TERAPIA';
    
    function Footer() 
    {             

        /*Condiciones de pago de pago*/        
        $this->SetY(-40); 
        $this->SetFillColor(220, 220, 220);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 7, 'CONDICIONES DE RESERVACIONES', 1, 1, 'C', true);
        $this->SetFont('Arial', '', 8.5);
        $this->Cell(67, 5, utf8_decode('1.- Si en caso no pudiera asistir puede re-programar su cita con tres horas de anticipación.'), $this->borde, 1, 'L');
        $this->Cell(67, 5, utf8_decode('2.- A su primera inasistencia a terapia, sus reservas programadas serán eliminadas.'), $this->borde, 1, 'L'); 
        $this->Cell(67, 5, utf8_decode('3.- Sin embargo podrá volverlas a agenda ingresando a www.citas.centromedicoosi.com digitado su numero de DNI en el usuario y contraseña.'), $this->borde, 1, 'L'); 
        $this->Cell(67, 5, utf8_decode('      Dicha información puede ser modificada cuando usted lo desee.'), $this->borde, 1, 'L'); 
        $this->Cell(67, 5, utf8_decode('4.- O llamando a nuestra central telefónica 739 0888.'), $this->borde, 1, 'L'); 

        $this->SetY(-5);
        $this->SetDrawColor(1, 87, 155); 
        $this->SetFont('Arial', 'I', 8);            
        $this->Line(5, $this->GetY() , 205, $this->GetY());          
        $this->Cell(70, 5, $this->web, $this->borde);
        $this->Cell(70, 5, utf8_decode('Impresión: ').  date('d/m/Y H:i') . ' - '. utf8_decode($this->printBy), $this->borde);
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . '/{nb}', $this->borde, 1, 'R');  
    } 
    
    function Header()
    {    
        $this->SetDrawColor(0, 0, 0); 
        $this->Image($this->path.$this->logo, 5, 5, 40, 0, 'PNG');
        $this->Cell(150);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, 'SEDE:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->nombresede, $this->borde);

        $this->Ln(15);
    }
}

class citaterapeuticaporasistirController extends Controller 
{    
    public function __construct(Request $request) 
    {         
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        
        $this->getToken($request);
        
        $this->pdf = new PDF();       
        $this->pdf->SetMargins(5, 5, 5);
        $this->pdf->SetAutoPageBreak(true, 40);
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
        $sede = sede::find($request['idsede']);

        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $request['idsede'];
        $param['citaterapeutica.idpaciente'] = $id;

        $param2 = [];
        $param2['cicloatencion.idempresa'] = $idempresa;
        $param2['cicloatencion.idsede'] = $request['idsede'];
        $param2['cicloatencion.idpaciente'] = $id;
        $param2['cicloatencion.idestado'] = 20; 

        $whereIn = explode(',', $request['inEstado']);
        $fecha = $this->formatFecha($request['fecha'], 'yyyy-mm-dd');

        $fechaAct = $this->fechaInicioFin($request['fecha'], $request['hora'], $request['hora']);
        $start_s = mktime((int) $fechaAct['Hi'], (int) $fechaAct['Mi'], 0, (int) $fechaAct['m'], (int) $fechaAct['d'], (int) $fechaAct['y']);

        //Ciclos de paciente
        $fields = ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cliente.identidad as idcliente', 'cliente.entidad as paciente', 'cliente.email', 'sede.nombre as sedenombre'];
        
        $dataciclo = $cicloatencion->grid($param2, '', '', '', 'cicloatencion.fecha', 'asc', false, $fields); 

        $datacita = $citaterapeutica->grid($param, '', '', '', 'citaterapeutica.fecha', 'asc', $whereIn, [], '', [], $fecha);  
        $paciente = $entidad->entidad(['entidad.identidad' => $id], $request['idsede']);

        $datacitaporasistir = [];
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);            
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($row->start_s > $start_s) {
                $datacitaporasistir[] = $row;
            }
        }  
         
        $this->pdf->nombresede = $sede->nombre; 
        
        /*Titulo del reporte*/
        $this->pdf->AddPage();        
        $this->pdf->SetFont('Arial', 'BU', 14);
        $this->pdf->Cell(0, 6, $this->pdf->titulo, 0, 1, 'C');
        $this->pdf->Ln();
        $this->pdf->Ln();
        
        /*Datos personales del cliente*/
        $this->pdf->SetDrawColor(1, 87, 155);
        $this->pdf->SetLineWidth(0.4);
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(20, 6, 'Paciente: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(80, 6, utf8_decode($paciente->entidad), 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, ucfirst(strtolower($paciente->documentoabrev)) . ':', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(70, 6, $paciente->numerodoc, 0);
        $this->pdf->Ln(); 
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(15, 6, 'Correo: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(85, 6, $paciente->email, 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(20, 6, utf8_decode('Celular:'), 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(30, 6, $paciente->celular, 0);
        $this->pdf->SetFont('Arial', 'B');
        $this->pdf->Cell(15, 6, 'H.C.: ', 0);
        $this->pdf->SetFont('Arial', '');
        $this->pdf->Cell(25, 6, $paciente->hc, 0,1);
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetLineWidth(0.4); 
        $this->pdf->Line(5, $this->pdf->GetY() - 6, 205, $this->pdf->GetY() - 6); 
        $this->pdf->SetLineWidth(0.2);
             


        ////////////////////// ciclos ////////////////////////////
        // $this->pdf->Ln();
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(35, 6, 'Estimado paciente: ', 0);
        $this->pdf->Cell(0, 6, utf8_decode( mb_strtoupper($paciente->entidad) ), 0);
        $this->pdf->Ln();
        // $this->pdf->Ln();
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(0, 6, 'Usted tiene '.count($dataciclo). utf8_decode(' ciclo(s) de atención aperturado:'), 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(10); 
        $this->pdf->Cell(40, 8, 'SEDE', 1, 0, 'L', true);
        $this->pdf->Cell(140, 8, 'CICLO', 1, 0, 'L', true); 
        $this->pdf->Ln();
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);  
        foreach ($dataciclo as $row) { 
            $this->pdf->Cell(10); 
            $this->pdf->Cell(40, 8, $row->sedenombre, 1, 0, 'L', true);
            $this->pdf->Cell(173, 8, utf8_decode('Ciclo atención aperturado el ' . $row->fecha), 1, 0, 'L', true);  
            $this->pdf->Ln(); 
        }  


        ////////////////////// reservaciones ////////////////////////////
        $this->pdf->SetDrawColor(1, 87, 155);
        $this->pdf->Ln(); 
        $this->pdf->SetFont('Arial', '', 10);
        //$this->pdf->Cell(0, 6, 'Usted tiene '. count($datacitaporasistir) .' reservaciones por asistir desde el ' . $request['fecha'] . ', ' .$this->transformHora($request['hora']), 0); 
        $this->pdf->Cell(0, 6, 'Usted tiene '. count($datacitaporasistir) .' reservaciones por asistir desde:', 0); 
        $this->pdf->Ln();

        /*Cabecera de tabla*/
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(10);
        $this->pdf->Cell(8, 8, '#', 1, 0, 'C', true);
        $this->pdf->Cell(97, 8, 'SEDE', 1, 0, 'L', true);
        // $this->pdf->Cell(65, 8, 'TERAPEUTA', 1, 0, 'L', true); 
        $this->pdf->Cell(25, 8, 'FECHA', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'HORA', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'ESTADO', 1, 0, 'C', true);
        $this->pdf->Ln();
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9); 
        $i = 1;
        foreach ($datacitaporasistir as $row) {  
            $this->pdf->Cell(10);
            $this->pdf->Cell(8, 8, $i, 1, 0, 'C', true);
            $this->pdf->Cell(97, 8, $row->sedenombre, 1, 0, 'L', true);
            // $this->pdf->Cell(65, 8, utf8_decode($row->terapista), 1, 0, 'L', true); 
            $this->pdf->Cell(25, 8, $row->fecha, 1, 0, 'C', true); 
            $this->pdf->Cell(25, 8, $this->transformHora($row->inicio), 1, 0, 'C', true); 
            $this->pdf->Cell(25, 8, 'Por asistir', 1, 0, 'C', true); 
            $this->pdf->Ln();
            $i++;
        }   
        
   

        
        
        $this->pdf->Output();       
    }   

}
