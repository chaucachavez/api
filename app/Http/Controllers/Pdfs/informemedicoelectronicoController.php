<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\informe;
use App\Models\terapia;
use App\Models\citamedica;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\citaterapeutica;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
use Symfony\Component\Yaml\Tests\B;
use App\Http\Controllers\Controller; 
  
class PDF extends baseFpdf 
{   
    public $printBy;
    public $web;
    public $borde = 1;
    public $nombresede;
    public $idcicloatencion;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';  
    public $titulo = 'HISTORIA CLÍNICA';
    public $paciente = null;
    
    function Footer() 
    { 
        // Posición: a 1,5 cm del final
        $this->SetY(-5); 

        // Arial italic 8
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(0, 0, 0);

        // Número de página
        $this->Cell(70, 5, utf8_decode($this->web), 0, 0, 'L');
        $this->Cell(100, 5, utf8_decode($this->printBy) . ' - ' . date('d/m/Y H:i'), 0, 0, 'L');
        $this->Cell(0, 5, utf8_decode('Página ').$this->PageNo().'/{nb}', 0, 0, 'R');

        // Linea
        $this->SetDrawColor(0,0,0);  
        $this->SetLineWidth(.5); 
        $this->Line(2, $this->GetY(), 208, $this->GetY()); 
    } 
    
    function Header()
    {    
        $b = 1; 
        $this->Image($this->path.$this->logo, 2, 2, 33, 0, 'PNG');
        $this->setX(42);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(0, 93, 185);
        $this->SetFillColor(0, 93, 185); 
        $this->SetDrawColor(0, 93, 185); 

        $this->Cell(92, 12, utf8_decode($this->titulo), 0, 0, 'C');

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
        $this->Cell(12, 4, 'SEDE', 'T,L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, utf8_decode($this->nombresede), 'T'); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
        $this->Cell(12, 4, 'H.C.', 'T');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->hc, 'T,R');
        $this->Ln();  

        $this->setX(134);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
        $this->Cell(12, 4, 'SEG.', 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->seguro); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
        $this->Cell(12, 4, 'CICLO');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->idcicloatencion, 'R');
        $this->Ln();

        $this->setX(134);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
        $this->Cell(12, 4, 'HORA:', 'L,B');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->horacita, 'B'); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
        $this->Cell(12, 4, 'FECHA:', 'B');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->fechacita, 'R,B');
        $this->Ln(10);    

        // /*Datos personales del cliente*/
        // $this->SetDrawColor(1, 87, 155);
        // $this->SetLineWidth(0.4);
        // $this->Line(5, $this->GetY() - 6, 205, $this->GetY() - 6);
        // $this->SetLineWidth(0.2);
        // $this->SetFont('Arial', 'B', 10);
        // $this->Cell(20, 6, 'Paciente: ', 0);
        // $this->SetFont('Arial', '');
        // $this->Cell(80, 6, utf8_decode($this->paciente->entidad), 0);
        // $this->SetFont('Arial', 'B');
        // $this->Cell(20, 6, ucfirst(strtolower($this->paciente->documentoabrev)) . ':', 0);
        // $this->SetFont('Arial', '');
        // $this->Cell(70, 6, $this->paciente->numerodoc, 0);
        // $this->Ln(); 
        // $this->SetFont('Arial', 'B');
        // $this->Cell(15, 6, 'Correo: ', 0);
        // $this->SetFont('Arial', '');
        // $this->Cell(85, 6, $this->paciente->email, 0);
        // $this->SetFont('Arial', 'B');
        // $this->Cell(20, 6, utf8_decode('Celular:'), 0);
        // $this->SetFont('Arial', '');
        // $this->Cell(30, 6, $this->paciente->celular, 0);
        // $this->SetFont('Arial', 'B');
        // $this->Cell(15, 6, 'H.C.: ', 0);
        // $this->SetFont('Arial', '');
        // $this->Cell(25, 6, $this->paciente->hc, 0,1);
        // $this->Ln();
        // $this->Ln();
        // $this->SetLineWidth(0.4); 
        // $this->Line(5, $this->GetY() - 6, 205, $this->GetY() - 6); 
        // $this->SetLineWidth(0.2);

        // /*Cabecera de tabla*/
        // $this->SetFillColor(1, 87, 155); 
        // $this->SetLineWidth(0.2);
        // $this->SetTextColor(255, 255, 255);
        // $this->SetDrawColor(255, 255, 255);
        // $this->SetFont('Arial', 'B', 9); 
        // $this->Cell(8, 8, '#', 1, 0, 'C', true);
        // $this->Cell(32, 8, 'SEDE', 1, 0, 'L', true);
        // $this->Cell(60, 8, 'FECHA', 1, 0, 'L', true); 
        // $this->Cell(25, 8, utf8_decode('RESERVACIÓN'), 1, 0, 'C', true);
        // $this->Cell(25, 8, 'CICLO', 1, 0, 'C', true);
        // $this->Cell(25, 8, 'HORA', 1, 0, 'C', true);
        // $this->Cell(25, 8, 'ESTADO', 1, 0, 'C', true); 
        // $this->Ln();
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $maxline=0)
    {
        //Output text with automatic or explicit line breaks, at most $maxline lines
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 && $s[$nb-1]=="\n")
            $nb--;
        $b=0;
        if($border)
        {
            if($border==1)
            {
                $border='LTRB';
                $b='LRT';
                $b2='LR';
            }
            else
            {
                $b2='';
                if(is_int(strpos($border,'L')))
                    $b2.='L';
                if(is_int(strpos($border,'R')))
                    $b2.='R';
                $b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
            }
        }
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $ns=0;
        $nl=1;
        while($i<$nb)
        {
            //Get next character
            $c=$s[$i];
            if($c=="\n")
            {
                //Explicit line break
                if($this->ws>0)
                {
                    $this->ws=0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border && $nl==2)
                    $b=$b2;
                if($maxline && $nl>$maxline)
                    return substr($s,$i);
                continue;
            }
            if($c==' ')
            {
                $sep=$i;
                $ls=$l;
                $ns++;
            }
            $l+=$cw[$c];
            if($l>$wmax)
            {
                //Automatic line break
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                    if($this->ws>0)
                    {
                        $this->ws=0;
                        $this->_out('0 Tw');
                    }
                    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                }
                else
                {
                    if($align=='J')
                    {
                        $this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                        $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
                    }
                    $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                    $i=$sep+1;
                }
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border && $nl==2)
                    $b=$b2;
                if($maxline && $nl>$maxline)
                {
                    if($this->ws>0)
                    {
                        $this->ws=0;
                        $this->_out('0 Tw');
                    }
                    return substr($s,$i);
                }
            }
            else
                $i++;
        }
        //Last chunk
        if($this->ws>0)
        {
            $this->ws=0;
            $this->_out('0 Tw');
        }
        if($border && is_int(strpos($border,'B')))
            $b.='B';
        $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
        $this->x=$this->lMargin;
        return '';
    }
}

class informemedicoelectronicoController extends Controller 
{    
    public function __construct(Request $request) 
    {         
                
        $this->getToken($request);            
    }
    
    public function reporte($enterprise, $id)
    {
        $pdf = new PDF(); 
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        $objCitamedica = new citamedica(); 
        $objCicloatencion = new cicloatencion(); 

        //Información general
        $idempresa = $objEmpresa->idempresa($enterprise);
        $citamedica = $objCitamedica->citamedica($id); 
        $user = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]); // $this->objTtoken->my
        $entidad = $objEntidad->entidad(['entidad.identidad' => $citamedica->idpaciente]); 
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]); 

        $dataAutoriz = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $citamedica->idcicloatencion]);
        $diagnosticosmedico = $objCitamedica->diagnosticomedico(['citamedica.idcicloatencion' => $citamedica->idcicloatencion]);        
        $tratamientosmedicos = $objCitamedica->tratamientomedico(['citamedica.idcitamedica' => $id]);
        $examenescita = $objCitamedica->examenescita(['citamedica.idcitamedica' => $id]);
        $examenescitaobs = $objCitamedica->examenescitaobs(['citamedica.idcitamedica' => $id]);
        
        $examenfisicocita = $objCitamedica->examenfisicocita(['citamedica.idcitamedica' => $id]);

        $diagnosticosmedico = $this->ordenarMultidimension($diagnosticosmedico, 'nombregrupodx', SORT_ASC); 
        $tratamientosmedicos = $this->ordenarMultidimension($tratamientosmedicos, 'nombregrupodx', SORT_ASC); 
        // dd($diagnosticosmedico);

        $arrayAbreviatura = [];
        foreach ($dataAutoriz as $row) {    
            if (!empty($row->abreviatura) && !in_array($row->abreviatura, $arrayAbreviatura)) {
                $arrayAbreviatura[] = $row->abreviatura;
            }
        }

        foreach($examenfisicocita as $row) {
            $row->orden = (integer) substr($row->zona, 3);        
        }

        $examenfisicocita = $this->ordenarMultidimension($examenfisicocita, 'orden', SORT_ASC); 
   
        $efcfrontal = [];
        $efcposterior = []; 
        $examenArray[] = []; 
        foreach($examenfisicocita as $row) {
            $examenArray[] = $row->zona;
            if (substr($row->zona, 0, 1) === 'F')  {
                $efcfrontal[] = $row;
            } else {
                $efcposterior[] = $row;
            }       
        } 

        $cicloatencion = null;
        if ($citamedica->idcicloatencion) {
            $cicloatencion = $objCicloatencion->cicloatencion(['cicloatencion.idcicloatencion' => $citamedica->idcicloatencion]); 
        }


        //Información especifica
        $antecedentemedico = $objCitamedica->antecedentemedico(['antecedentemedico.idcitamedica' => $id]);
        $anamnesis = $objCitamedica->anamnesis(['citamedica.idcitamedica' => $id]);
        
        // dd($antecedentemedico);
        $antecedentes = '';
        foreach ($antecedentemedico as $value) {
            $coma = (strlen($antecedentes) > 0) ? ', ' : '';
            $antecedentes .=  $coma . $value->descripcion;
        }

        // dd($entidad);
        // dd($citamedica);
        // Datos decabecera
        $pdf->printBy = $user->entidad;          
        $pdf->web = $empresa->paginaweb;
        $pdf->logo = $empresa->url.'/'.$empresa->imglogologin;        
        $pdf->nombresede = $citamedica->nombresede; 
        $pdf->paciente = $citamedica->nombrepaciente;
        $pdf->hc = $citamedica->hc;
        // $pdf->idpaciente = $citamedica->idpaciente;
        $pdf->seguro = implode(" / ", $arrayAbreviatura);
        $pdf->idcicloatencion = $citamedica->idcicloatencion;
        $pdf->fechacita = $citamedica->fecha;
        $pdf->horacita = $citamedica->inicio;

        $pdf->SetMargins(2, 2, 2);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->AliasNbPages(); 
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetDrawColor(0, 93, 185); 
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->AddPage();   
           
        // Filiacion
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('FILIACIÓN'), 1, 0, 'C', true);        
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(69, 4, 'Apellido paterno:', 'L', 0, 'L');
        $pdf->Cell(69, 4, 'Apellido materno:', 'L', 0, 'L');
        $pdf->Cell(68, 4, 'Nombres:', 'L,R', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(69, 4, utf8_decode($entidad->apellidopat), 'L,B', 0, 'L');
        $pdf->Cell(69, 4, utf8_decode($entidad->apellidomat), 'L,B', 0, 'L');
        $pdf->Cell(68, 4, utf8_decode($entidad->nombre), 'L,B,R', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(39, 4, 'Fecha de nacimiento', 'L', 0, 'L');
        $pdf->Cell(30, 4, 'Lugar', 'L', 0, 'L');
        $pdf->Cell(20, 4, 'Edad', 'L', 0, 'L');
        $pdf->Cell(20, 4, 'Sexo', 'L', 0, 'L');
        $pdf->Cell(29, 4, 'Estado civil', 'L', 0, 'L');
        $pdf->Cell(18, 4, 'Hijos', 'L', 0, 'L');
        $pdf->Cell(50, 4, utf8_decode('Ocupación'), 'L,R', 0, 'L');
        $pdf->Ln();  
        $pdf->SetTextColor(0, 0, 0); 
        
        if ($entidad->sexo === 'F') {
            switch ($entidad->estadocivil) {
                case 'Casado':
                    $entidad->estadocivil = 'Casada';
                    break; 
                case 'Soltero':
                    $entidad->estadocivil = 'Soltera';
                    break; 
                case 'Divorciado separado':
                    $entidad->estadocivil = 'Divorciada separada';
                    break; 
                case 'Viudo':
                    $entidad->estadocivil = 'Viuda';
                    break;  
            }
        }

        $pdf->Cell(39, 4, utf8_decode($entidad->fechanacimiento), 'L,B', 0, 'L');
        $pdf->Cell(30, 4, utf8_decode($entidad->distrito), 'L,B', 0, 'L');
        $pdf->Cell(20, 4, utf8_decode($citamedica->edad), 'L,B', 0, 'L');
        $pdf->Cell(20, 4, utf8_decode($entidad->sexo), 'L,B', 0, 'L');
        $pdf->Cell(29, 4, utf8_decode($entidad->estadocivil), 'L,B', 0, 'L');
        $pdf->Cell(18, 4, utf8_decode($entidad->hijos), 'L,B', 0, 'L');
        $pdf->Cell(50, 4, utf8_decode($entidad->ocupacion), 'L,R,B', 0, 'L');
        $pdf->Ln(8); 

        // ANTECEDENTES PATOLÓGICOS
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ANTECEDENTES PATOLÓGICOS'), 1, 0, 'C', true);        
        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);  
        $pdf->MultiCell(0, 4, utf8_decode($antecedentes), 1, 'L', false, 3);        

        // Enfermedad actual
        $pdf->Ln();        
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('ENFERMEDAD ACTUAL'), 1, 0, 'L', true);
        $x = $pdf->getX();
        $y = $pdf->getY();     
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(100, 4, utf8_decode('Síntomas y disfunción'), 'L, R', 0, 'L');  
        $pdf->Ln();

        $x1Temp = $pdf->getX();
        $y1Temp = $pdf->getY();
        $pdf->SetTextColor(0, 0, 0); 
        $x1 = $pdf->getX();
        $y1 = $pdf->getY();
        $pdf->MultiCell(100, 4, utf8_decode($citamedica->nota), 'R, B, L', 'L', false, 10);    

        $descrtiempo = '';
        if ($citamedica->enfermedad) {
            $descrtiempo = 'Del ' . $citamedica->enfermedad. ' - ';
        }

        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(15, 4, 'Tiempo:', 'B,L', 0, 'L');   
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(85, 4, utf8_decode($descrtiempo) .  utf8_decode($citamedica->enfermedadtiempo), 'R,B', 0, 'L'); 
        $pdf->Ln(); 

        $xSyD = $pdf->getX();
        $ySyD = $pdf->getY();
 
        // Descanso médico
        $pdf->setXY($x + 6, $y);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('FUNCIONES VITALES'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->setX($x + 6);
        $pdf->Cell(25, 4, utf8_decode('PC'), 'L', 0, 'L'); 
        $pdf->Cell(25, 4, utf8_decode('FC'), 'L', 0, 'L'); 
        $pdf->Cell(25, 4, utf8_decode('Peso (Kg)'), 'L', 0, 'L'); 
        $pdf->Cell(25, 4, utf8_decode('Talla (cm)'), 'L,R', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setX($x + 6);
        $pdf->Cell(25, 4, utf8_decode($citamedica->fvpc), 'L,B', 0, 'C');  
        $pdf->Cell(25, 4, utf8_decode($citamedica->fvfc), 'L,B', 0, 'C');  
        $pdf->Cell(25, 4, utf8_decode($citamedica->fvpeso), 'L,B', 0, 'C');  
        $pdf->Cell(25, 4, utf8_decode($citamedica->fvtalla), 'L,B,R', 0, 'L');
        $pdf->Ln();
 
        
        //EXAMEN FISICO 
        $pdf->setXY($xSyD, $ySyD); 
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('EXAMEN FÍSICO'), 1, 0, 'C', true);        
        $pdf->Ln();
        $x = $pdf->getX();
        $y = $pdf->getY(); 

        //IMPRIMIR IMAGEN FRONTAL Y POSTERIOR 
        $pdf->setXY($x + 94, $y);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(50, 8, utf8_decode('FRONTAL'), 0, 0, 'C');     
        $pdf->Cell(4, 8); 
        $pdf->Cell(54, 8, utf8_decode('POSTERIOR'), 0, 0, 'C');  

        $pdf->setXY($x, $y);
        $pdf->Image($pdf->path.'erp-frontal.png', $pdf->getX() + 94, $pdf->getY() + 8, 50, 0, 'PNG');

        $pdf->setXY($x, $y);
        $pdf->Image($pdf->path.'erp-posterior.png', $pdf->getX() + 148, $pdf->getY() + 8, 54, 0, 'PNG');

        // Imprimir lineas
        // 2.5, 6.3
        $checkFrontal = array(
        ['h' => 8.2, 'c' =>array( '', '', '', '', '', '', '', '', '', 'FD-1', 'FI-1', '', '', '', '', '', '', '', '', '')],
        ['h' => 5.7, 'c' =>array( '', '', '', '', '', '', '', '', '', 'FD-2', 'FI-2', '', '', '', '', '', '', '', '', '')],
        ['h' => 12, 'c' =>array( '', '', '', '', '', 'FD-6', '', '', 'FD-3', '', '', 'FI-3', '', '', 'FI-6', '', '', '', '', '')],        
        ['h' => 6, 'c' =>array( '', '', '', '', '', 'FD-7', 'FD-8', '', 'FD-4', '', '', 'FI-4', '', 'FI-8', 'FI-7', '', '', '', '', '')],        
        ['h' => 7.9, 'c' =>array( '', '', '', 'FD-9', 'FD-10', '', '', '', 'FD-5', '', '', 'FI-5', '', '', '', 'FI-10', 'FI-9', '', '', '')],        
        ['h' => 8.8, 'c' =>array( 'FD-11', '', 'FD-12', '', '', '', 'FD-15', '', 'FD-14', '', '', 'FI-14', '', 'FI-15', '', '', '', 'FI-12', '', 'FI-11')],
        ['h' => 10, 'c' =>array( 'FD-13a', 'FD-13b', 'FD-13c', 'FD-13d', '', '', '', '', 'FD-16', 'FD-17', 'FI-17', 'FI-16', '', '', '', '', 'FI-13d', 'FI-13c', 'FI-13b', 'FI-13a')],
        ['h' => 5, 'c' =>array( '', '', '', '', '', '', '', '', 'FD-18', '', '', 'FI-18', '', '', '', '', '', '', '', '')],
        ['h' => 13.5, 'c' =>array( '', '', '', 'FD-27', '', '', '', 'FD-19', 'FD-20', '', '', 'FI-20', 'FI-19', '', '', '', 'FI-27', '', '', '')],
        ['h' => 6.5, 'c' =>array( '', '', '', 'FD-28', '', '', '', '', 'FD-21', '', '', 'FI-21', '', '', '', '', 'FI-28', '', '', '')],
        ['h' => 3.3, 'c' =>array( 'FD-26', '', '', '', '', '', '', 'FD-22', 'FD-24', 'FD-23', 'FI-23', 'FI-24', 'FI-22', '', '', '', '', '', '', 'FI-26')],
        ['h' => 5, 'c' =>array( '', '', '', 'FD-25e', 'FD-25d', 'FD-25c', 'FD-25b', 'FD-25a', '', '', '', '', 'FI-25a', 'FI-25b', 'FI-25c', 'FI-25d', 'FI-25e', '', '', '')]
        );

        $checkPosterior = array(
            ['h' => 6, 'c' =>array( '', '', '', '', '', '', '', '', '', 'PI-1', 'PD-1', '', '', '', '', '', '', '', '', '')],
            ['h' => 3.5, 'c' =>array( '', '', '', '', '', '', '', '', '', 'PI-2', 'PD-2', '', '', '', '', '', '', '', '', '')],
            ['h' => 14.9, 'c' =>array( '', '', '', '', '', 'PI-10', '', '', 'PI-3', '', '', 'PD-3', '', '', 'PD-10', '', '', '', '', '')], 
            ['h' => 5, 'c' =>array( '', '', '', '', '', 'PI-11', 'PI-12', '', 'PI-4', '', '', 'PD-4', '', 'PD-12', 'PD-11', '', '', '', '', '')],
            ['h' => 3, 'c' =>array( '', '', '', '', '', 'PI-6', '', '', '', '', '', '', '', '', 'PD-6', '', '', '', '', '')],
            ['h' => 8.5, 'c' =>array( '', '', '', 'PI-13', 'PI-14', '', '', '', 'PI-5', '', '', 'PD-5', '', '', '', 'PD-14', 'PD-13', '', '', '')], 
            ['h' => 8, 'c' =>array( 'PI-15', '', 'PI-16', '', '', '', '', '', 'PI-7', '', '', 'PD-7', '', '', '', '', '', 'PD-16', '', 'PD-15')],
            ['h' => 12, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-8', '', '', 'PD-8', '', '', '', '', '', '', '', '')],
            ['h' => 3.5, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-9', '', '', 'PD-9', '', '', '', '', '', '', '', '')], 
            ['h' => 14, 'c' =>array( '', '', '', '', '', '', '', 'PI-17', '', 'PI-18', 'PD-18', '', 'PD-17', '', '', '', '', '', '', '')], 
            ['h' => 7, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-19', '', '', 'PD-19', '', '', '', '', '', '', '', '')], 
            ['h' => 5.5, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-20', '', '', 'PD-20', '', '', '', '', '', '', '', '')] 
        );
 
 
        // Frontal
        $pdf->setXY($x + 91.9, $y + 8);
        foreach($checkFrontal as $row) {
            $pdf->setX($x + 94);
            foreach($row['c'] as $value) { 
                
                if (!empty($value)) {

                    if (substr($value, 1, 1) === 'D') {
                        $pdf->SetTextColor(255, 0, 0);                            
                    } else { 
                        $pdf->SetTextColor(0, 0, 255); 
                    }

                    if (in_array($value, $examenArray)) {  
                        $pdf->SetFont('Arial', 'B', 18);
                        $pdf->Cell(2.5, $row['h'], utf8_decode('.'), 0, 0, 'C');
 
                        $pdf->SetFont('Arial', '', 6);
                        $pdf->setX($pdf->getX() - 2.5); 
                        $pdf->Cell(2.5, $row['h'], substr($value, 3), 0, 0, 'C'); 
                    } else {        
                        //Manos
                        $caracter = in_array($value, 
                            ['FD-11', 'FI-11', 'FD-13a', 'FD-13b', 'FD-13c', 'FD-13d', 'FI-13d', 'FI-13c', 'FI-13b', 'FI-13a', 'FD-25e', 'FD-25d', 'FD-25c', 'FD-25b', 'FD-25a', 'FI-25a', 'FI-25b', 'FI-25c', 'FI-25d', 'FI-25e']) ? 'o' : '';
                    
                        $pdf->Cell(2.5, $row['h'], '', 0, 0, 'C');                         

                        $xtemp = $pdf->getX();
                        $ytemp = $pdf->getY();                        
                        if (!empty($caracter)) { 
                            $pdf->SetFont('Arial', '', 6);
                            $pdf->setXY($xtemp - 2.5, $ytemp + 1.4);
                            $pdf->Cell(2.5, $row['h'], $caracter, 0, 0, 'C'); 
                        }

                        $pdf->setXY($xtemp, $ytemp);
                    }
                } else { 
                    $pdf->Cell(2.5, $row['h'], '', 0, 0, 'C');
                }               
            }
            $pdf->Ln();
        } 

        // Posterior
        $pdf->setXY($x + 148, $y + 8); 
        foreach($checkPosterior as $row) {
            $pdf->setX($x + 148);
            foreach($row['c'] as $value) { 

                if (!empty($value) && in_array($value, $examenArray)) {

                    if (substr($value, 1, 1) === 'D') {
                        $pdf->SetTextColor(255, 0, 0);                            
                    } else { 
                        $pdf->SetTextColor(0, 0, 255); 
                    } 
  
                    $pdf->SetFont('Arial', 'B', 18);
                    $pdf->Cell(2.7, $row['h'], utf8_decode('.'), 0, 0, 'C');
 
                    $pdf->SetFont('Arial', '', 6);
                    $pdf->setX($pdf->getX() - 2.7); 
                    $pdf->Cell(2.7, $row['h'], substr($value, 3), 0, 0, 'C'); 
                } else {
                    $pdf->Cell(2.7, $row['h'], '', 0, 0, 'C');
                }               
            }
            $pdf->Ln();
        } 
        
        $yCuerpo = $pdf->getY();

        // IMPRIMIR TABLA EXAMEN FISICO        
        $pdf->setXY($x, $y);
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);        
        $pdf->Cell(8, 4, utf8_decode('Cód.'), 'T,L,R,B', 0, 'L', true);
        $pdf->Cell(21, 4, utf8_decode('Zona'), 'T,L,R,B', 0, 'L', true); 
        $pdf->Cell(30, 4, utf8_decode('Eva (1-5)'), 'T,L,R,B', 0, 'C', true);   
        $pdf->Cell(31, 4, utf8_decode('Grupo Dx'), 'T,L,R,B', 0, 'C', true); 
        $pdf->Ln(); 
        // $pdf->setY($pdf->getY() - 4);
        // $pdf->setX(31);
        // $pdf->Cell(30, 4, utf8_decode('(1-5)'), 'R,L,B', 0, 'C', true); 
        // $pdf->Cell(31, 4, utf8_decode('Dx'), 'R,L,B', 0, 'C', true); 

          
        // $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);    
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(90, 4, 'FRONTAL', 0, 0, 'C');  
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8); 
 
        foreach ($efcfrontal as $value) {
            if (substr($value->zona, 1, 1) === 'D') {
                $pdf->SetTextColor(255, 0, 0);                            
            } else { 
                $pdf->SetTextColor(0, 0, 255); 
            }

            $colorBg = $this->colorBg($value->zona);
            $pdf->SetFillColor($colorBg[0], $colorBg[1], $colorBg[2]);

            $zona = $this->zonaCuerpo($value->zona) . (substr($value->zona, 1, 1) === 'D' ? ' - D' : ' - I');

            $y1 = $pdf->getY();
            if (empty($value->rom) || empty($value->muscular) || empty($value->funcional)) {
                $pdf->Cell(8, 4, substr($value->zona, 3), 1, 0, 'C', true);   
            } else {
                $pdf->setX(10);
            }

            $pdf->Cell(21, 4, utf8_decode($zona), 1, 0, 'L');       
            $pdf->Cell(30, 4, utf8_decode($value->eva), 1, 0, 'C');   
            $pdf->Cell(31, 4, utf8_decode($value->nombregrupodx), 1, 0, 'C');   
            $pdf->Ln();
   
            if (!empty($value->muscular)) {

                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $pdf->setX(31);

                $y2 = $pdf->getY();
                $pdf->MultiCell(61, 4, utf8_decode($value->muscular), 'T,R,B', 'L', false, 4);   
                $y3 = $pdf->getY();
                
                $pdf->SetTextColor(0, 93, 185);   
                $pdf->setXY(10, $y2);
                $pdf->Cell(21, $y3 - $y2, 'Muscular(1-5)', 'L,T,B', 0, 'R');
                $pdf->Ln();  
            }

            if (!empty($value->funcional)) {

                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $pdf->setX(31);

                $y2 = $pdf->getY();
                $pdf->MultiCell(61, 4, utf8_decode($value->funcional), 'T,R,B', 'L', false, 4);   
                $y3 = $pdf->getY();
                
                $pdf->SetTextColor(0, 93, 185);   
                $pdf->setXY(10, $y2);
                $pdf->Cell(21, $y3 - $y2, 'Funcional', 'L,T,B', 0, 'R');
                $pdf->Ln();  
            }

            if (!empty($value->rom)) { 
                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $pdf->setX(31);

                $y2 = $pdf->getY();
                $pdf->MultiCell(61, 4, utf8_decode($value->rom), 'T,R,B', 'L', false, 4);   
                $y3 = $pdf->getY();
                
                $pdf->SetTextColor(0, 93, 185);   
                $pdf->setXY(10, $y2);
                $pdf->Cell(21, $y3 - $y2, 'Rom', 'L,T,B', 0, 'R');
                $pdf->Ln();  
            }

            if (!empty($value->rom) || !empty($value->muscular) || !empty($value->funcional)) { 
                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $y4 = $pdf->getY();
                $pdf->setY($y1);
                $pdf->Cell(8, $y4 - $y1, substr($value->zona, 3), 1, 0, 'C', true);
                $pdf->Ln();
            }

        }  
        if (empty($efcfrontal)) {
            $pdf->Cell(90, 4, '', 1, 0, 'L');
            $pdf->Ln();
        }

        $pdf->SetTextColor(0, 0, 0);  
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(90, 4, 'POSTERIOR', 0, 0, 'C');  
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8); 

        foreach ($efcposterior as $value) {
            if (substr($value->zona, 1, 1) === 'D') {
                $pdf->SetTextColor(255, 0, 0);                            
            } else { 
                $pdf->SetTextColor(0, 0, 255); 
            }

            $y1 = $pdf->getY();
            $colorBg = $this->colorBg($value->zona);
            $pdf->SetFillColor($colorBg[0], $colorBg[1], $colorBg[2]);

            $zona = $this->zonaCuerpo($value->zona) . (substr($value->zona, 1, 1) === 'D' ? ' - D' : ' - I');             
            // if (empty($value->rom)) {
            if (empty($value->rom) || empty($value->muscular) || empty($value->funcional)) {
                $pdf->Cell(8, 4, substr($value->zona, 3), 1, 0, 'C', true);   
            } else {
                $pdf->setX(10);
            }

            $pdf->setX(10);
            $pdf->Cell(21, 4, utf8_decode($zona), 1, 0, 'L');      
            $pdf->Cell(30, 4, utf8_decode($value->eva), 1, 0, 'C');   
            $pdf->Cell(31, 4, utf8_decode($value->nombregrupodx), 1, 0, 'C');  
            $pdf->Ln();            
            
            if (!empty($value->muscular)) { 
                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $pdf->setX(31);

                $y2 = $pdf->getY();
                $pdf->MultiCell(61, 4, utf8_decode($value->muscular), 'T,R,B', 'L', false, 4);   
                $y3 = $pdf->getY();
                
                $pdf->SetTextColor(0, 93, 185);   
                $pdf->setXY(10, $y2);
                $pdf->Cell(21, $y3 - $y2, 'Muscular(1-5)', 'L,T,B', 0, 'R');
                $pdf->Ln();  
            }

            if (!empty($value->funcional)) { 
                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $pdf->setX(31);

                $y2 = $pdf->getY();
                $pdf->MultiCell(61, 4, utf8_decode($value->funcional), 'T,R,B', 'L', false, 4);   
                $y3 = $pdf->getY();
                
                $pdf->SetTextColor(0, 93, 185);   
                $pdf->setXY(10, $y2);
                $pdf->Cell(21, $y3 - $y2, 'Funcional', 'L,T,B', 0, 'R');
                $pdf->Ln();  
            }

            if (!empty($value->rom)) { 
                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $pdf->setX(31);

                $y2 = $pdf->getY();
                $pdf->MultiCell(61, 4, utf8_decode($value->rom), 'T,R,B', 'L', false, 4);   
                $y3 = $pdf->getY();
                
                $pdf->SetTextColor(0, 93, 185);   
                $pdf->setXY(10, $y2);
                $pdf->Cell(21, $y3 - $y2, 'Rom', 'L,T,B', 0, 'R');
                $pdf->Ln();  
            }

            if (!empty($value->rom) || !empty($value->muscular) || !empty($value->funcional)) { 
                if (substr($value->zona, 1, 1) === 'D') {
                    $pdf->SetTextColor(255, 0, 0);                            
                } else { 
                    $pdf->SetTextColor(0, 0, 255); 
                }

                $y4 = $pdf->getY();
                $pdf->setY($y1);
                $pdf->Cell(8, $y4 - $y1, substr($value->zona, 3), 1, 0, 'C', true);
                $pdf->Ln();
            } 
        }       
         
        if (empty($efcposterior)) {
            $pdf->Cell(90, 4, '', 1, 0, 'L');
            $pdf->Ln();
        }  

        // Examen físico
        $pdf->Ln(4);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(90, 4, utf8_decode('Descripción de examen físico'), 'L, R, T', 0, 'L');  
        $pdf->Ln(); 
        $pdf->SetTextColor(0, 0, 0);  
        $pdf->MultiCell(90, 4, utf8_decode($citamedica->notaexamenfis), 'R, B, L', 'L', false, 10);    
        
        $yExamenfisico = $pdf->getY();
        if ($yExamenfisico > $yCuerpo) {  
            $pdf->setY($yExamenfisico + 4); 
        } else {
            $pdf->setY($yCuerpo + 4); 
        }
        
        // Diagnóstico  
        // $pdf->Ln(); //NO CONVIENE ZX A VECES PUEDE SER MUY ANCHO (Room extenso)
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('DIAGNÓSTICO CIE 10'), 1, 0, 'L', true);    

        $x = $pdf->getX();
        $y = $pdf->getY();

        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(12, 4, utf8_decode('Código'), 'L', 0, 'L'); 
        $pdf->Cell(13, 4, utf8_decode('Zona'), 'C', 0, 'L');
        $pdf->Cell(75, 4, utf8_decode('Nombre'), 'R', 0, 'L');
        $pdf->Ln();
 
        $pdf->SetTextColor(0, 0, 0);  
        $nombregrupodx = "";
        foreach ($diagnosticosmedico as $value) {
            $x1 = $pdf->getX();
            $y1 = $pdf->getY();
            
            if ($value->nombregrupodx !== $nombregrupodx) { 
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(100, 4, utf8_decode(mb_strtoupper($value->nombregrupodx)), 'L,T,R', 0, 'C');  
                $pdf->Ln();
                $nombregrupodx = $value->nombregrupodx;
                $pdf->SetFont('Arial', '', 8);
            }

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
 
            $nombre = $value->nombre . ( (integer)$id !== $value->idcitamedica ? ' (Dx de ciclo)' : '');

            $pdf->Cell(12, 4, utf8_decode($value->codigo), 0, 0, 'L');  
            $pdf->Cell(13, 4, $zona, 0, 0, 'L');  
            $pdf->MultiCell(75, 4, utf8_decode($nombre), 0, 'L', false, 3); 
            $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
            $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
            $pdf->Line(2, $pdf->GetY(), 102, $pdf->GetY()); 
        } 

        $yfin1 = $pdf->getY();

        if (empty($diagnosticosmedico)) {
            $pdf->Cell(100, 4, '', 1, 0, 'L');
            $pdf->Ln();
            $yfin1 = $pdf->getY();
        }


        // Tratamientos
        $pdf->setXY($x + 6, $y);
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255);     
        $pdf->Cell(100, 5, utf8_decode('INDICACIÓN MÉDICA'), 1, 0, 'L', true);         
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->setX($x + 6);
        $pdf->Cell(15, 4, utf8_decode('Cantidad'), 'L', 0, 'L'); 
        $pdf->Cell(85, 4, utf8_decode('Nombre'), 'R', 0, 'L');
        $pdf->Ln();        

        $pdf->SetTextColor(0, 0, 0); 
        $nombregrupodx = "";
        foreach ($tratamientosmedicos as $value) {  

            $pdf->setX($x + 6);

            if ($value->nombregrupodx !== $nombregrupodx) { 
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(100, 4, utf8_decode(mb_strtoupper($value->nombregrupodx)), 'L,T,R', 0, 'C');  
                $pdf->Ln();
                $pdf->setX($x + 6);
                $nombregrupodx = $value->nombregrupodx;
                $pdf->SetFont('Arial', '', 8);
            }

            $pdf->Cell(15, 4, utf8_decode($value->cantidad), 'B,L', 0, 'C'); 
            $pdf->Cell(85, 4, utf8_decode($value->nombreproducto), 'R,B', 0, 'L'); 
            $pdf->Ln();    
        } 
            
         
        if (empty($tratamientosmedicos)) {
            $pdf->setX($x + 6);
            $pdf->Cell(100, 4, '', 1, 0);
            $pdf->Ln();
            
        } 

        // Frecuencia
        $yfin2 = $pdf->getY();
        $pdf->setX($x + 6);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(50, 4, 'Asistencia:', 'B,L', 0, 'R');  
        $frecuencia = '';
        if ($citamedica->frecuencia === 'D') {
            $frecuencia = 'Diaria';
        }

        if ($citamedica->frecuencia === 'I') {
            $frecuencia = 'Interdiaria';
        }
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(50, 4, $frecuencia, 'R,B', 0, 'L'); 
        $pdf->Ln();
        $yfin2 = $pdf->getY();


        // Exámen auxiliar  
        if ($yfin1 >= $yfin2) {
            $pdf->setY($yfin1);
        } else {
            $pdf->setY($yfin2);
        }

        $pdf->Ln();
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('INDICACIÓN EXAMEN AUXILIAR'), 1, 0, 'L', true);    
        $x = $pdf->getX();
        $y = $pdf->getY();

        if (count($examenescita) > 0) { 
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 93, 185);
            $pdf->Cell(30, 4, utf8_decode('Nombre'), 'L', 0, 'L'); 
            $pdf->Cell(70, 4, utf8_decode('Descripción'), 'R', 0, 'L'); 
        }

        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 

        foreach ($examenescita as $value) {
            $x1 = $pdf->getX();
            $y1 = $pdf->getY();  
            $pdf->Cell(30, 4, utf8_decode($value->nombre), 0, 0);          
            $pdf->MultiCell(70, 4, utf8_decode($value->descripcion), 0, 'L', false, 3); 
            $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
            $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
            $pdf->Line(2, $pdf->GetY(), 102, $pdf->GetY()); 
            $yfin1 = $pdf->getY();
        } 

        if (empty($examenescita)) { 
            $pdf->Cell(100, 4, '', 1, 0);
            $pdf->Ln();
            $yfin1 = $pdf->getY();
        }




        $pdf->setXY($x + 6, $y);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('OBSERVACIÓN EXAMEN AUXILIAR'), 1, 0, 'L', true);    
        // $x7 = $pdf->getX();
        // $y7 = $pdf->getY();

        if (count($examenescitaobs) > 0) { 
            $pdf->Ln();
            $pdf->setX($x + 6);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 93, 185);
            $pdf->Cell(30, 4, utf8_decode('Nombre'), 'L', 0, 'L'); 
            $pdf->Cell(70, 4, utf8_decode('Descripción'), 'R', 0, 'L'); 
        }

        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 

        foreach ($examenescitaobs as $value) {

            $pdf->setX($x + 6);

            $x1 = $pdf->getX();
            $y1 = $pdf->getY(); 

            $pdf->Cell(30, 4, utf8_decode($value->nombre), 0, 0);          
            $pdf->MultiCell(70, 4, utf8_decode($value->descripcion), 0, 'L', false, 3); 
            $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
            $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
            $pdf->Line($x1, $pdf->GetY(), $x1 + 100, $pdf->GetY()); 
            $yfin2 = $pdf->getY();
        } 

        if (empty($examenescitaobs)) { 
            $pdf->setX($x + 6);
            $pdf->Cell(100, 4, '', 1, 0);
            $pdf->Ln();
            $yfin2 = $pdf->getY();
        } 

        if ($yfin1 >= $yfin2) {
            $pdf->setY($yfin1);
        } else {
            $pdf->setY($yfin2);
        }

        // Ind. Farmacológica
        $pdf->Ln();
        // $pdf->setXY($x + 6, $y);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('INDICACIÓN FARMACOLÓGICA'), 1, 0, 'L', true);    
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8); 
        $pdf->SetTextColor(0, 0, 0);
        // $pdf->setX($x + 6);

        $x1 = $pdf->getX();
        $y1 = $pdf->getY(); 

        $pdf->MultiCell(206, 4, utf8_decode($citamedica->notamedicamento), 'L', 'L', false, 7);
        $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
        $pdf->Line($x1 + 206, $y1, $x1 + 206, $pdf->GetY()); 
        $pdf->Line($x1, $pdf->GetY(), $x1 + 206, $pdf->GetY());   
        // $pdf->Ln(); 

        // $yfin2 = $pdf->getY();

        // Alta médica 
        // if ($yfin1 >= $yfin2) {
        //     $pdf->setY($yfin1);
        // } else {
        //     $pdf->setY($yfin2);
        // }

        $pdf->Ln();        
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('ALTA MÉDICA'), 1, 0, 'L', true);
        $x = $pdf->getX();
        $y = $pdf->getY();     
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(15, 4, utf8_decode(''), 'L', 0, 'L'); 
        $pdf->Cell(85, 4, utf8_decode('Motivo'), 'L,R', 0, 'L');
        $pdf->Ln();

        $x1 = $pdf->getX();
        $y1 = $pdf->getY();
        $pdf->SetTextColor(0, 0, 0);        
        $pdf->Cell(15, 4, $citamedica->altamedica === '1' ? 'SI' : 'NO', 0, 0, 'C');  
        $pdf->MultiCell(85, 4, utf8_decode($citamedica->altamedicacomentario), 'L', 'L', false, 4);
        $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
        $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
        $pdf->Line($x1, $pdf->GetY(), $x1 + 100, $pdf->GetY());  

        $yAlta = $pdf->getY();

        // Descanso médico
        $pdf->setXY($x + 6, $y);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('DESCANSO MÉDICO'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->setX($x + 6);
        $pdf->Cell(30, 4, utf8_decode('Del'), 'L', 0, 'L'); 
        $pdf->Cell(30, 4, utf8_decode('Al'), 'R', 0, 'L');
        $pdf->Cell(40, 4, utf8_decode('Días'), 'R', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setX($x + 6);
        $pdf->Cell(30, 4, $citamedica->descansodesde, 'L,B', 0, 'L');  
        $pdf->Cell(30, 4, $citamedica->descansohasta, 'B,R', 0, 'L');        
        $pdf->Cell(40, 4, utf8_decode($citamedica->descansodias), 'B,R', 0, 'C');  
        $pdf->Ln();
        
        $pdf->setY($yAlta);
        $pdf->Ln();
        $pdf->SetDrawColor(0,0,0); 
        $pdf->SetFillColor(0, 0, 0); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('MÉDICO ESPECIALISTA'), 1, 0, 'L', true);

        $x = $pdf->getX();
        $y = $pdf->getY();

        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8); 
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(100, 4, utf8_decode($citamedica->nombremedico), 'L,B,R', 0, 'L');
        // $pdf->Ln();

        // OBSERVACIONES
        $pdf->setXY($x + 6, $y);
        $pdf->SetDrawColor(0, 93, 185);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 8); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(100, 5, utf8_decode('OBSERVACIÓN / EVALUACIÓN'), 1, 0, 'L', true);
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);  
        $pdf->setX($x + 6);
        $pdf->MultiCell(100, 4, utf8_decode($citamedica->observacion), 1, 'L', false, 7);  
    
         
        $informe = new informe();

        $nombre = 'Historia_' . $citamedica->idcitamedica . '_' . date('Y-m-d_H-i-s') .'.pdf';

        $data = array(
            'idempresa' => $idempresa,
            'idcitamedica' => $id,
            'archivo' => $nombre,
            'id_created_at' => $this->objTtoken->my, // $this->objTtoken->my
            'created_at' => date('Y-m-d H:i:s')
        );
        $informe->fill($data);
        $informe->save();
            
        // $pdf->Output(); 
        $pdf->Output('F', 'informes_medicos/' . $nombre); 
        return $this->crearRespuesta($informe, 201, '', '', $data); 
    }    

    private function colorBg($zona) {
        $colorBg = array(255, 255, 255);

        if (in_array($zona, ['FD-1', 'FI-1', 'PD-1', 'PI-1'])) {
            $colorBg = array(255, 251, 198); //amarillo
        }

        if (in_array($zona, ['FD-2', 'FI-2', 'FD-14', 'FI-14', 'PD-2', 'PI-2', 'PD-7', 'PI-7'])) {
            $colorBg = array(255, 252, 219); //amarillo-claro
        }

        if (in_array($zona, ['FD-6', 'FI-6', 'PD-10', 'PI-10'])) {
            $colorBg = array(246, 225, 211); //melon
        }

        if (in_array($zona, ['FD-3', 'FI-3', 'PD-3', 'PI-3'])) {
            $colorBg = array(247, 201, 220); //rosado
        }

        if (in_array($zona, ['FD-4', 'FI-4', 'PD-4', 'PI-4'])) {
            $colorBg = array(250, 220, 233); //rosado-claro
        }

        if (in_array($zona, ['FD-5', 'FI-5', 'PD-5', 'PI-5'])) {
            $colorBg = array(253, 238, 244); //rosado-luz
        }

        if (in_array($zona, ['FD-7', 'FI-7', 'PD-11', 'PI-11'])) {
            $colorBg = array(202, 229, 205); //verde
        }

        if (in_array($zona, ['FD-8', 'FI-8', 'FD-9', 'FI-9', 'PD-12', 'PI-12', 'PD-13', 'PI-13'])) {
            $colorBg = array(216, 235, 218); //verde-claro
        }

        if (in_array($zona, ['FD-10', 'FI-10', 'PD-14', 'PI-14'])) {
            $colorBg = array(227, 241, 228); //verde-luz
        }

        if (in_array($zona, ['FD-11', 'FI-11', 'PD-15', 'PI-15'])) {
            $colorBg = array(152, 214, 246); //celeste
        }

        if (in_array($zona, ['FD-12', 'FI-12', 'PD-16', 'PI-16'])) {
            $colorBg = array(176, 223, 248); //celeste-claro
        }

        if (in_array($zona, ['FD-13a', 'FI-13a','FD-13b', 'FI-13b', 'FD-13c', 'FI-13c', 'FD-13d', 'FI-13d'])) {
            $colorBg = array(208, 236, 251); //celeste-luz
        }

        if (in_array($zona, ['FD-15', 'FI-15'])) {
            $colorBg = array(117, 130, 176); //morado
        }

        if (in_array($zona, ['FD-16', 'FI-16', 'PD-8', 'PI-8'])) {
            $colorBg = array(153, 159, 195); //morado-claro
        }

        if (in_array($zona, ['FD-17', 'FI-17'])) {
            $colorBg = array(171, 175, 205); //morado-luz
        }

        if (in_array($zona, ['FD-18', 'FI-18', 'PD-9', 'PI-9'])) {
            $colorBg = array(124, 183, 176); //esmeralda
        }

        if (in_array($zona, ['FD-19', 'FI-19', 'PD-17', 'PI-17'])) {
            $colorBg = array(0, 119, 195); //blue
        }

        if (in_array($zona, ['FD-20', 'FI-20', 'PD-18', 'PI-18'])) {
            $colorBg = array(18, 140, 206); //blue-claro
        }

        if (in_array($zona, ['FD-21', 'FI-21', 'PD-19', 'PI-19'])) {
            $colorBg = array(103, 166, 219); //blue-luz
        }

        if (in_array($zona, ['FD-22', 'FI-22', 'FD-24', 'FI-24', 'FD-25a', 'FI-25a', 'FD-25b', 'FI-25b', 'FD-25c', 'FI-25c', 'FD-25d', 'FI-25d', 'FD-25e', 'FI-25e', 'FD-26', 'FI-26', 'FD-27', 'FI-27'])) {
            $colorBg = array(250, 204, 122); //naranja
        }

        if (in_array($zona, ['FD-23', 'FI-23', 'PD-20', 'PI-20', 'FD-28', 'FI-28'])) {
            $colorBg = array(252, 215, 154); //naranja-claro
        }

        if (in_array($zona, ['PD-6', 'PI-6'])) {
            $colorBg = array(224, 200, 211); //Violeta
        }

        return $colorBg;
    }

    private function zonaCuerpo($zona) {
        $descripcion = "";

        if (in_array($zona, ['FD-1', 'FI-1', 'PD-1', 'PI-1'])) {
            $descripcion = "Cabeza";
        }

        if (in_array($zona, ['FD-2', 'FI-2', 'PD-2', 'PI-2'])) {
            $descripcion = "Cuello";
        }

        if (in_array($zona, ['FD-3', 'FI-3'])) {
            $descripcion = "Pecho";
        }

        if (in_array($zona, ['FD-4', 'FI-4', 'FD-5', 'FI-5'])) {
            $descripcion = "Abdomen";
        }

        if (in_array($zona, ['FD-6', 'FI-6', 'PD-10', 'PI-10'])) {
            $descripcion = "Hombro";
        }

        if (in_array($zona, ['FD-7', 'FI-7', 'FD-8', 'FI-8', 'PD-11', 'PI-11', 'PD-12', 'PI-12'])) {
            $descripcion = "Brazo";
        }

        if (in_array($zona, ['FD-9', 'FI-9', 'FD-10', 'FI-10', 'PD-13', 'PI-13', 'PD-14', 'PI-14'])) {
            $descripcion = "Antebrazo";
        }

        if (in_array($zona, ['FD-11', 'FI-11', 'FD-12', 'FI-12', 'FD-13a', 'FI-13a', 'FD-13b', 'FI-13b', 'FD-13c', 'FI-13c', 'FD-13d', 'FI-13d', 'PD-15', 'PI-15', 'PD-16', 'PI-16'])) {
            $descripcion = "Mano";
        }

        if (in_array($zona, ['FD-14', 'FI-14'])) {
            $descripcion = "Púbico";
        }

        if (in_array($zona, ['FD-15', 'FI-15', 'FD-16', 'FI-16', 'FD-17', 'FI-17', 'PD-8', 'PI-8'])) {
            $descripcion = "Muslo";
        }

        if (in_array($zona, ['FD-18', 'FI-18', 'PD-9', 'PI-9'])) {
            $descripcion = "Rodilla";
        }

        if (in_array($zona, ['FD-19', 'FI-19', 'FD-20', 'FI-20', 'FD-21', 'FI-21', 'PD-17', 'PI-17', 'PD-18', 'PI-18', 'PD-19', 'PI-19'])) {
            $descripcion = "Pierna";
        }

        if (in_array($zona, ['FD-22', 'FI-22'])) {
            $descripcion = "Pie";
        }

        if (in_array($zona, ['FD-23', 'FI-23', 'PD-20', 'PI-20'])) {
            $descripcion = "Talón";
        }

        if (in_array($zona, ['PD-3', 'PI-3', 'PD-4', 'PI-4', 'PD-5', 'PI-5'])) {
            $descripcion = "Espalda";
        }

        if (in_array($zona, ['PD-6', 'PI-6'])) {
            $descripcion = "Codo";
        }

        if (in_array($zona, ['PD-7', 'PI-7'])) {
            $descripcion = "Gluteo";
        } 

        if (in_array($zona, ['FD-24', 'FI-24'])) {
            $descripcion = "Arco";
        } 

        if (in_array($zona, ['FD-25a', 'FI-25a'])) {
            $descripcion = "Pulgar";
        }

        if (in_array($zona, ['FD-25b', 'FI-25b'])) {
            $descripcion = "Indice";
        }

        if (in_array($zona, ['FD-25c', 'FI-25c'])) {
            $descripcion = "Medio";
        }

        if (in_array($zona, ['FD-25d', 'FI-25d'])) {
            $descripcion = "Anular";
        }

        if (in_array($zona, ['FD-25e', 'FI-25e'])) {
            $descripcion = "Meñique";
        }

        if (in_array($zona, ['FD-26', 'FI-26'])) {
            $descripcion = "Tobillo";
        }

        if (in_array($zona, ['FD-27', 'FI-27'])) {
            $descripcion = "Superior";
        }

        if (in_array($zona, ['FD-28', 'FI-28'])) {
            $descripcion = "Inferior";
        }
                
        return $descripcion;
    } 
}
