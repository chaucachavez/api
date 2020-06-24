<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
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
    public $pathFirma = 'https://sistemas.centromedicoosi.com/img/osi/firmas/firma-medico.png';
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
        $this->Cell(92, 12, utf8_decode($this->titulo), 0, 0, 'C');

        $this->SetFont('Arial', '', 8);
        $this->Cell(12, 4, 'SEDE', 'T,L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->nombresede, 'T'); 
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
        $this->Cell(12, 4, 'C.PCTE', 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->idpaciente); 
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

class informemedicoController extends Controller 
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
        $user = $objEntidad->entidad(['entidad.identidad' => 1]); //objTtoken->my
        $entidad = $objEntidad->entidad(['entidad.identidad' => $citamedica->idpaciente]); 
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]); 
        $diagnosticosmedico = $objCitamedica->diagnosticomedico(['citamedica.idcitamedica' => $id]);
        $tratamientosmedicos = $objCitamedica->tratamientomedico(['citamedica.idcitamedica' => $id]);

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
        $pdf->idpaciente = $citamedica->idpaciente;
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
 
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = 1;
        $param['citaterapeutica.idpaciente'] = $id;
           
        // Filiacion
        $pdf->SetFont('Arial', 'B', 9); 
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
        $pdf->Cell(34, 4, 'Hijos', 'L', 0, 'L');
        $pdf->Cell(34, 4, utf8_decode('Ocupación'), 'L,R', 0, 'L');
        $pdf->Ln(); 
        // dd($entidad);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(39, 4, utf8_decode($entidad->fechanacimiento), 'L,B', 0, 'L');
        $pdf->Cell(30, 4, utf8_decode($entidad->distrito), 'L,B', 0, 'L');
        $pdf->Cell(20, 4, utf8_decode($citamedica->edad), 'L,B', 0, 'L');
        $pdf->Cell(20, 4, utf8_decode($entidad->sexo), 'L,B', 0, 'L');
        $pdf->Cell(29, 4, '', 'L,B', 0, 'L');
        $pdf->Cell(34, 4, '', 'L,B', 0, 'L');
        $pdf->Cell(34, 4, '', 'L,R,B', 0, 'L');
        $pdf->Ln(8); 

        // Antecedentes patologicos
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ANTECEDENTES PATOLÓGICOS'), 1, 0, 'C', true);        
        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(0, 4, utf8_decode($antecedentes), 1, 0, 'L');
        $pdf->Ln(8); 

        // Motivo de la consulta 
        $maxItems = $this->maxItems($anamnesis, ['motivo_molestia', 'motivo_razon', 'motivo_causa', 'motivo_limita']); 

        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ANAMNESIS: MOTIVO DE LA CONSULTA'), 1, 0, 'L', true);        
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);

        $x = $pdf->getX();
        $y = $pdf->getY();

        // Molestia
        $pdf->Cell(52, 4, utf8_decode('¿Qué zona presenta molestia?'), 'L', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'motivo_molestia', 'L', 'L,B', $x + 0, $maxItems); 
        // Razón
        $pdf->setXY($x + 52, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(52, 4, utf8_decode('¿Razón por la cual está afectada?'), 'L', 0, 'L');   
        $pdf->Ln(); 
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'motivo_razon', 'L', 'L,B', $x + 52, $maxItems);

        // Causa
        $pdf->setXY($x + 104, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('¿Qué causó su problema?'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'motivo_causa', 'L', 'L,B', $x + 104, $maxItems); 
        
        // Limitacion
        $pdf->setXY($x + 155, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('¿Le limita sus actividades?'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(51, $pdf, $anamnesis, 'motivo_limita', 'L,R', 'R,B,L', $x + 155, $maxItems); 
  
        $pdf->Ln(4);

        // Sobre el dolor 
        $maxItems = $this->maxItems($anamnesis, ['dolor_tiempo', 'dolor_siente', 'dolor_corre', 'dolor_intensidad', 'dolor_aumenta', 'dolor_disminuye', 'dolor_como', 'dolor_afectividad']);

        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ANAMNESIS: SOBRE EL DOLOR'), 1, 0, 'L', true);        
        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);

        $x = $pdf->getX();
        $y = $pdf->getY();

        // Tiempo
        $pdf->Cell(52, 4, utf8_decode('¿Cuánto tiempo viene sufriéndolo?'), 'L', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'dolor_tiempo', 'L', 'L,B', $x + 0, $maxItems);

        // Siente
        $pdf->setXY($x + 52, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(52, 4, utf8_decode('¿Cómo lo siente?'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'dolor_siente', 'L', 'L,B', $x + 52, $maxItems); 

        // Corre
        $pdf->setXY($x + 104, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(52, 4, utf8_decode('¿El dolor corre o se irradia?'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'dolor_corre', 'L', 'L,B', $x + 104, $maxItems); 

        // Limitacion
        $pdf->setXY($x + 155, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('¿Cuanto le duele? (1-10)'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(51, $pdf, $anamnesis, 'dolor_intensidad', 'L,R', 'R,B,L', $x + 155, $maxItems);  
          
        $x = $pdf->getX();
        $y = $pdf->getY();

        // Aumenta
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(52, 4, utf8_decode('¿El dolor aumenta cuando?'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0); 
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'dolor_aumenta', 'L,R', 'R,B,L', $x, $maxItems); 

        // Disminuye
        $pdf->setXY($x + 52, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(52, 4, utf8_decode('¿El dolor disminuye cuando?'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'dolor_disminuye', 'L,R', 'R,B,L', $x + 52, $maxItems);         

        // Como
        $pdf->setXY($x + 104, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('¿Cómo es el dolor?'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        // dd($anamnesis);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'dolor_como', 'L', 'L,B', $x + 104, $maxItems); 
        
        // Afectividad
        $pdf->setXY($x + 155, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('Afectividad'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(51, $pdf, $anamnesis, 'dolor_afectividad', 'L,R', 'R,B,L', $x + 155, $maxItems); 
        $pdf->Ln(4); 
 
        // Sobre la limitación 
        $maxItems = $this->maxItems($anamnesis, ['limitacion_tiempo', 'limitacion_localizacion', 'limitacion_control', 'limitacion_empeora', 'limitacion_mejora', 'limitacion_intensidad']);

        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ANAMNESIS: SOBRE LA LIMITACIÓN'), 1, 0, 'L', true);        
        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);

        $x = $pdf->getX();
        $y = $pdf->getY(); 

        // Tiempo
        $pdf->Cell(69, 4, utf8_decode('¿Cuanto tiempo viene sufriéndolo?'), 'L', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(69, $pdf, $anamnesis, 'limitacion_tiempo', 'L', 'L,B', $x + 0, $maxItems);

        // Localización
        $pdf->setXY($x + 69, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(69, 4, utf8_decode('Localización'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(69, $pdf, $anamnesis, 'limitacion_localizacion', 'L', 'L,B', $x + 69, $maxItems); 
 
        // Fuerza
        $pdf->setXY($x + 138, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(68, 4, utf8_decode('Intensidad fuerza muscular (0-5)'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(68, $pdf, $anamnesis, 'limitacion_intensidad', 'L,R', 'R,B,L', $x + 138, $maxItems);         
          
        $x = $pdf->getX();
        $y = $pdf->getY();  

        // Control
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(69, 4, utf8_decode('Limitación funcional: Control'), 'L', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(69, $pdf, $anamnesis, 'limitacion_control', 'L', 'L,B', $x + 0, $maxItems);

        // Empeora
        $pdf->setXY($x + 69, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(69, 4, utf8_decode('¿Empeora con algo?'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(69, $pdf, $anamnesis, 'limitacion_empeora', 'L', 'L,B', $x + 69, $maxItems); 

        // Mejora
        $pdf->setXY($x + 138, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(68, 4, utf8_decode('¿Mejora con algo?'), 'L,R', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(68, $pdf, $anamnesis, 'limitacion_mejora', 'L', 'R,L,B', $x + 138, $maxItems); 
         
        $pdf->Ln(4);

        // Sobre la limitación 
        $maxItems = $this->maxItems($anamnesis, ['deformacion_parte', 'deformacion_es', 'deformacion_aumenta', 'deformacion_disminuye']);

        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ANAMNESIS: SOBRE LA DEFORMACIÓN'), 1, 0, 'L', true);        
        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);

        $x = $pdf->getX();
        $y = $pdf->getY(); 

        // Parte
        $pdf->Cell(52, 4, utf8_decode('Deformación'), 'L', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'deformacion_parte', 'L', 'L,B', $x + 0, $maxItems);

        // Es
        $pdf->setXY($x + 52, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(52, 4, utf8_decode('La deformación es'), 'L', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(52, $pdf, $anamnesis, 'deformacion_es', 'L', 'L,B', $x + 52, $maxItems); 

        // Aumenta
        $pdf->setXY($x + 104, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('¿Deformación aumenta cuando?'), 'L', 0, 'L');
        $pdf->Ln();         
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(51, $pdf, $anamnesis, 'deformacion_aumenta', 'L', 'L,B', $x + 104, $maxItems);  

        // Disminuye
        $pdf->setXY($x + 155, $y);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(51, 4, utf8_decode('¿Deformación disminuye cuando?'), 'L,R', 0, 'L');        
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $this->itemsAmanesis(51, $pdf, $anamnesis, 'deformacion_disminuye', 'L,R', 'L,R,B', $x + 155, $maxItems);          
        $pdf->Ln(4);

        //EXAMEN FISICO
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('EXAMEN FÍSICO'), 1, 0, 'C', true);        
        $pdf->Ln();
        $x = $pdf->getX();
        $y = $pdf->getY();
        // dd($citamedica);
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(40, 8, utf8_decode('Rom zona'), 'T,L,R', 0, 'L', true); 
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(64, 8, utf8_decode($citamedica->rom), 0, 0, 'L');  
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(40, 8, utf8_decode('Rom valor'), 'T,R,L', 0, 'L', true); 
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, utf8_decode($citamedica->romvalor), 0, 0, 'L');        
        $pdf->Ln();
        $x1 = $pdf->getX();
        $y1 = $pdf->getY();

        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(40, 8, utf8_decode('Fuerza muscular'), 'R,L', 0, 'L', true); 
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, utf8_decode($citamedica->fuerzamuscular), 0, 0, 'L');        
        $pdf->Ln();
        $x2 = $pdf->getX();
        $y2 = $pdf->getY();

        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(40, 12, utf8_decode('Prueba funcional'), 'R,L', 0, 'L', true);         
        $pdf->Ln();
        $x3 = $pdf->getX();
        $y3 = $pdf->getY();

        $pdf->Cell(40, 12, utf8_decode('Resultado examen auxiliar'), 'R,L', 0, 'L', true); 
        $pdf->Ln();        
        $x4 = $pdf->getX();
        $y4 = $pdf->getY();
 
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setXY(42, $y2);
        $pdf->MultiCell(0, 4, utf8_decode($citamedica->pruebafuncional), 0, 'L', false, 3);

        $pdf->setXY(42, $y3);
        $pdf->MultiCell(0, 4, utf8_decode($citamedica->notaexamen), 0, 'L', false, 3);
         
        // Lineas horizontales y verticales 
        // Horizontal
        $pdf->setXY($x1, $y1);        
        $pdf->Line($x1, $pdf->GetY(), 208, $pdf->GetY()); 

        $pdf->setXY($x2, $y2);   
        $pdf->Line($x2, $pdf->GetY(), 208, $pdf->GetY()); 

        $pdf->setXY($x3, $y3);   
        $pdf->Line($x3, $pdf->GetY(), 208, $pdf->GetY()); 

        $pdf->setXY($x4, $y4);
        $pdf->Line($x4, $pdf->GetY(), 208, $pdf->GetY()); 

        // Vertical
        $pdf->setXY($x, $y);
        $pdf->Line(208, $pdf->GetY(), 208, $pdf->GetY() + 40); 
        // Lineas horizontales y verticales 
        
        $pdf->setXY($x4, $y4);   
        $pdf->Ln();
 

        //Alta médica 
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(206, 5, utf8_decode('ALTA MÉDICA'), 1, 0, 'L', true);        
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(40, 4, utf8_decode('Alta médica'), 'L', 0, 'L'); 
        $pdf->Cell(166, 4, utf8_decode('Comentario'), 'L,R', 0, 'L');
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(40, 4, $citamedica->altamedica === '1' ? 'SI' : 'NO', 'L,B', 0, 'C');  
        $pdf->Cell(166, 4, utf8_decode($citamedica->altamedicacomentario), 'L,B,R', 0, 'L');
        $pdf->Ln(8);

        //Diagnóstico 
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->Cell(15, 5, 'CIE 10', 1, 0, 'L', true);
        $pdf->Cell(85, 5, utf8_decode('DIAGNÓSTICO'), 1, 0, 'L', true);    
        $x = $pdf->getX();
        $y = $pdf->getY();
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 

        foreach ($diagnosticosmedico as $value) {
            $x1 = $pdf->getX();
            $y1 = $pdf->getY();
            $pdf->Cell(15, 4, utf8_decode($value->codigo), 0, 0, 'L'); 
            // $pdf->Cell(85, 4, utf8_decode($value->nombre), 'R,B,L', 0, 'L');
            // $x = $pdf->getX();
            // $y = $pdf->getY();
            $pdf->MultiCell(85, 4, utf8_decode($value->nombre), 0, 'L', false, 3);
            // $pdf->Ln();
            // $pdf->setXY($x, $y);R
            $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
            $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
            $pdf->Line(2, $pdf->GetY(), 102, $pdf->GetY()); 
        } 

        if (empty($diagnosticosmedico)) {
            $pdf->Cell(100, 4, '', 1, 0, 'L');
        }

        // dd($x, $y);
        $pdf->setXY($x + 6, $y);
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(15, 5, 'CANTID.', 1, 0, 'L', true);    
        $pdf->Cell(85, 5, utf8_decode('TRATAMIENTOS'), 1, 0, 'L', true); 
        $pdf->Ln();        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($tratamientosmedicos as $value) {  
            $pdf->setX($x + 6);
            $pdf->Cell(15, 4, utf8_decode($value->cantidad), 'B,L', 0, 'C'); 
            $pdf->Cell(85, 4, utf8_decode($value->nombreproducto), 'R,B', 0, 'L'); 
            $pdf->Ln();    
        }
        if (empty($tratamientosmedicos)) {
            $pdf->setX($x + 6);
            $pdf->Cell(100, 4, '', 1, 0, 'L');
        }

        //Firma electrónica


        //Cierre de ciclo        
        $pdf->SetY(-35);  

        $b1 = 'T,R,B,L';
        $b2 = 'R,L';
        $b3 = 'B,R,L';
        if ($citamedica->firmamedico === '1') {
            $b1 = '';
            $b2 = '';
            $b3 = '';
            $pdf->Image($pdf->pathFirma, $pdf->getX() + 167, $pdf->getY() - 2, 25, 0, 'PNG');
        }

        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(156, 13, '', 0, 0, 'L');
        $pdf->Cell(50, 13, utf8_decode(''), $b1, 0, 'L'); 
        $pdf->Ln();           
        $pdf->Cell(156, 4, '', 0, 0, 'L');
        // dd($citamedica);
        $pdf->Cell(50, 4, utf8_decode($citamedica->nombremedico), $b2, 0, 'C'); 
        $pdf->Ln();  
        $pdf->Cell(156, 4, '', 0, 0, 'L');
        $pdf->Cell(50, 4, utf8_decode('Firma Médico CMP'), $b3, 0, 'C'); 
        $pdf->Ln();  
        

        $pdf->SetFillColor(0, 150, 136); 
        $pdf->SetFont('Arial', 'B', 9);  
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(25, 8, utf8_decode('CIERRE FCT.'), 0, 0, 'C', 1);
        $pdf->SetFont('Arial', '', 9);  
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(13, 8, utf8_decode('Ciclo'), 0, 0, 'R');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(15, 8, utf8_decode($citamedica->idcicloatencion), 0, 0, 'L');
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(21, 8, utf8_decode('Atención del '), 0, 0, 'R');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(20, 8, isset($cicloatencion->primert) ? $cicloatencion->primert : '', 0, 0, 'C');
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(4, 8, utf8_decode('al'), 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(20, 8, isset($cicloatencion->ultimot) ? $cicloatencion->ultimot : '', 0, 0, 'C');
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(29, 8, utf8_decode('Total sesiones '), 0, 0, 'R');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(10, 8, utf8_decode(''), 0, 0, 'L');
        $pdf->SetTextColor(0, 93, 185);
        $pdf->Cell(19, 8, utf8_decode('Director téc. '), 0, 0, 'R');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(30, 8, utf8_decode(''), 0, 0, 'L');

        $pdf->Output();       
    }   

    private function maxItems($data, $indices) {

        $max = []; 
        foreach ($data as $row) {
            if (in_array($row->tipo, $indices)) {
                if (isset($max[$row->tipo])) {
                    $max[$row->tipo] += 1;
                } else {
                    $max[$row->tipo] = 1;
                }                
            } 
        }

        $maximo = 0;
        foreach ($max as $valor) {             
            if ($valor > $maximo) {
                $maximo = $valor;
            }
        }
         
        return $maximo;  
    }

    private function filterData($data, $indice, $max = 0) { 

        $items = [];
        foreach ($data as $row) {
            if ($row->tipo === $indice) {
                $items[] = $row;              
            } 
        } 

        $total = count($items);

        if ($total < $max) {            
            for ($i=0; $i < ($max - $total); $i++) { 
                $items[] = array();
            }            
        }

        if ($max === 0)
            $items[] = array();

        return $items;
    }

    private function itemsAmanesis($w, $pdf, $anamnesis, $indice, $b1, $b2, $x, $maxItems) {
        
        $data = $this->filterData($anamnesis, $indice, $maxItems);
        if ($indice === 'motivo_limita') {
            // dd($data, $maxItems);    
        }
        // dd($maxItems, $data);
        foreach ($data as $i => $row) {
            $nombre = '';
            if (isset($row->nombre)) {
                $nombre = utf8_decode($row->nombre);
            }
            $nombre = strlen($nombre) > 40 ? substr($nombre, 0, 40) : $nombre;

            $b = $b1; 
            if (($i + 1) === count($data)) {
                $b = $b2;
            }

            $pdf->setX($x);
            $pdf->Cell($w, 4, $nombre, $b, 0, 'L');
            $pdf->Ln();
        } 
    }
    
}