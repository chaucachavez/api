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
    public $titulo = 'PROCEDIMIENTOS DE TRATAMIENTO';
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
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 93, 185);
        $this->SetFillColor(0, 93, 185); 
        $this->SetDrawColor(0, 93, 185); 

        $this->Cell(92, 12, utf8_decode($this->titulo), 0, 0, 'C');

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 185);
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

class informeprocedimientoelectronicoController extends Controller 
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
        $objTerapia = new terapia(); 

        //Información general
        $idempresa = $objEmpresa->idempresa($enterprise);

        $dataCita = \DB::table('citamedica')
                ->where(array('idcicloatencion' => $id, 'idestado' => 6)) 
                ->orderBy('citamedica.fecha', 'ASC') 
                ->whereNull('citamedica.deleted') 
                ->get()->all();       

        // dd($dataCita);
        $param = array(
            'terapia.idempresa' => $idempresa,
            'terapia.idestado' => 38,
            'terapiatratamiento.idcicloatencion' => $id 
        );
        $dataterapiatra = $objTerapia->terapiatratamientoslight($param, ['terapia.idterapia', 'terapia.fecha', 'terapia.comentario'], TRUE, '', [], true, [], true);

        // dd($dataterapiatra);
        $datacomentarios = [];
        foreach($dataterapiatra as $row) {
            if (!empty($row->comentario)) {
                $datacomentarios[] = $row;
            }
        }

        if (count($dataCita) === 0) {
            dd("Ciclo sin citas médicas");
        }



        $idcitamedica = $dataCita[0]->idcitamedica;        

        $citamedica = $objCitamedica->citamedica($idcitamedica); 
        $cicloatencion = $objCicloatencion->cicloatencion($id); 
        
        $dataProcedimientos = $objTerapia->procedimientos(['terapiaprocedimiento.idcicloatencion' => $id, 'terapia.idestado' => 38]);
        $dataTecnicas = $objTerapia->tecnicasmanuales(['terapiatecnica.idcicloatencion' => $id, 'terapia.idestado' => 38]);
        $dataPuntos = $objTerapia->puntosimagen(['terapiaimagen.idcicloatencion' => $id]);

        $user = $objEntidad->entidad(['entidad.identidad' => 1]); //objTtoken->my
        $entidad = $objEntidad->entidad(['entidad.identidad' => $citamedica->idpaciente]); 
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]);

        // Datos de Fisioterapia y acupuntura
        $matrizFisioterapia = [];
        $matrizAcupuntura = [];
        foreach ($dataProcedimientos as $value) {
            if ($value->idproducto === 2) {
                if (!isset($matrizFisioterapia[$value->idterapia])) {
                    $matrizFisioterapia[$value->idterapia] = array('fecha' => null, 'inicio' => null, 'inf_min' => null, 'inf_int' => null, 'com_min' => null, 'com_int' => null, 'mas_min' => null, 'mas_int' => null, 'ele_min' => null, 'ele_int' => null, 'ult_min' => null, 'ult_int' => null);
                }

                $tipo = '';
                switch ($value->tipo) {
                    case 'Infrarojo': $tipo = 'inf'; break; 
                    case 'Comp. Fría': $tipo = 'com'; break; 
                    case 'Masaj. Tera.': $tipo = 'mas'; break; 
                    case 'Elect. C/J.': $tipo = 'ele'; break; 
                    case 'Ultrasonido': $tipo = 'ult'; break; 
                } 

                $matrizFisioterapia[$value->idterapia]['fecha'] = $value->fecha;
                $matrizFisioterapia[$value->idterapia]['inicio'] = $value->inicio;
                $matrizFisioterapia[$value->idterapia][$tipo . '_min'] = $value->tiempo;
                $matrizFisioterapia[$value->idterapia][$tipo . '_int'] = $value->intensidad;
            }

            if ($value->idproducto === 3) {
                if (!isset($matrizAcupuntura[$value->idterapia])) {
                    $matrizAcupuntura[$value->idterapia] = array(
                        'fecha' => null, 
                        'inicio' => null, 
                        'punto1' => null, 
                        'punto2' => null, 
                        'punto3' => null, 
                        'punto4' => null, 
                        'punto5' => null, 
                        'punto6' => null, 
                        'punto7' => null
                    );
                }

                $tipo = '';
                switch ($value->tipo) {
                    case 'Punto 1': $tipo = 'punto1'; break;
                    case 'Punto 2': $tipo = 'punto2'; break;
                    case 'Punto 3': $tipo = 'punto3'; break;
                    case 'Punto 4': $tipo = 'punto4'; break;
                    case 'Punto 5': $tipo = 'punto5'; break;
                    case 'Punto 6': $tipo = 'punto6'; break;
                    case 'Punto 7': $tipo = 'punto7'; break;
                }

                $matrizAcupuntura[$value->idterapia]['fecha'] = $value->fecha;
                $matrizAcupuntura[$value->idterapia]['inicio'] = $value->inicio;
                $matrizAcupuntura[$value->idterapia][$tipo] = '1';
            }
        }

        $matrizPuntos = [];
        foreach ($dataPuntos as $value) {
            $matrizPuntos[$value->punto][] = $value->tipo;
        }
        // dd($matrizPuntos);

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

        // Imagen
        $yTemp = $pdf->getY();
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(103, 5, utf8_decode('PROCEDIMIENTOS EFECTUADOS SEGÚN LEYENDA'), 0, 0, 'C');
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(25.75, 5, utf8_decode('Infrarojo'), 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Cell(25.75, 5, utf8_decode('Comp. Fría'), 0, 0, 'L');
        $pdf->SetTextColor(255, 165, 0);
        $pdf->Cell(25.75, 5, utf8_decode('Masaj. Tera.'), 0, 0, 'L');
        $pdf->SetTextColor(0, 128, 0);
        $pdf->Cell(25.75, 5, utf8_decode('Elect. C/J.'), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetTextColor(75, 0, 130);
        $pdf->Cell(25.75, 5, utf8_decode('Ultrasonido'), 0, 0, 'L');
        $pdf->SetTextColor(165, 42, 42);
        $pdf->Cell(25.75, 5, utf8_decode('Magneto'), 0, 0, 'L');
        $pdf->SetTextColor(255, 0, 255);
        $pdf->Cell(25.75, 5, utf8_decode('Spectrum'), 0, 0, 'L');
        $pdf->SetTextColor(64, 224, 208);
        $pdf->Cell(25.75, 5, utf8_decode('Ondas de choque'), 0, 0, 'L');
        $pdf->Ln();

        $pdf->setX(105);
        $pdf->Image($pdf->path.'erp-procedimiento.png', $pdf->getX(), $pdf->getY(), 103, 0, 'PNG');
        
        $pdf->setX(105);
        $pdf->SetTextColor(75, 0, 130);

        $checks = array(
            [0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0],
            [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 1, 1, 1, 0, 0, 0, 0],
            [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0],
            [0, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0],
            [0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0],
            [0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0],
            [0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 0],
            [0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 1, 0, 0, 1, 1, 0, 0, 1, 1, 0, 0, 1, 0, 0],
            [0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 1, 0, 1, 0, 0],
            [0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
            [0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
            [1, 0, 0, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
            [0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0],
            [1, 1, 1, 1, 1, 0, 1, 1, 0, 1, 1, 0, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 1, 1, 0, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 1, 1, 0, 0, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 1, 1, 0, 0, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 1, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        );
        
        $f = 0;
        foreach ($checks as $value) {
            $c = 0;
            foreach ($value as $row) { 
                $img = '';
                if (isset($matrizPuntos[$f . ','.$c])) {                    
                    if (count($matrizPuntos[$f . ','.$c]) === 1) {
                        // dd($matrizPuntos);
                        switch ($matrizPuntos[$f . ','.$c][0]) {
                            case 'chinf':
                                $pdf->Image($pdf->path.'erp-puntored.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chcom':
                                $pdf->Image($pdf->path.'erp-puntoblue.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chmas':
                                $pdf->Image($pdf->path.'erp-puntoorange.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chele':
                                $pdf->Image($pdf->path.'erp-puntogreen.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chult':
                                $pdf->Image($pdf->path.'erp-puntoindigo.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chmag':
                                $pdf->Image($pdf->path.'erp-puntobrown.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chspe':
                                $pdf->Image($pdf->path.'erp-puntomagenta.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                            case 'chond':// dd($f . ','.$c);
                                $pdf->Image($pdf->path.'erp-puntoturquoise.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                                break;
                        }
                    } else {
                        $pdf->Image($pdf->path.'erp-puntoblack.png', $pdf->getX(), $pdf->getY(), 2, 0, 'PNG');
                    }
                }

                // $pdf->SetTextColor(255, 0, 0);
                $pdf->Cell(2.06, 2.66, '', 0, 0, 'C');
                $c++;            
            }
            $pdf->Ln();
            $pdf->setX(105);
            $f++;
        }        
        // 87.78
        // $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Image($pdf->path.'erp-puntoblack.png', $pdf->getX(), $pdf->getY() + 1.3, 2, 0, 'PNG');
        $pdf->setX(107);
        $pdf->Cell(25.75, 5, utf8_decode('Mas de un procedimiento'), 0, 0, 'L');
        $pdf->Ln(); 

        // Tecnicas manuales
        $pdf->Ln();
        $pdf->setXY(105, 148);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('TECNICAS MANUALES Y EJERCICIOS TERAPÉUTICOS'), 1, 0, 'C', true);    
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->Cell(25, 5, utf8_decode('Fecha'), 'L', 0, 'C', true); 
        $pdf->Cell(75, 5, utf8_decode('Nombre'), 'L,R', 0, 'C', true); 
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetTextColor(0, 0, 0); 

        foreach ($dataTecnicas as $value) {
            $pdf->Cell(25, 4, utf8_decode($value->fecha), 1, 0, 'L');   
            $pdf->Cell(75, 4, utf8_decode($value->descripcion), 1, 0, 'L');  
            $pdf->Ln();
            $pdf->setX(105);
        }

        if (empty($dataTecnicas)) { 
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
            $pdf->setX(105);
        }

        // Comentarios   
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->SetFillColor(0, 0, 0); 
        $pdf->SetDrawColor(0, 0, 0); 
        $pdf->Cell(25, 5, utf8_decode('Fecha'), 'T,L', 0, 'C', true); 
        $pdf->Cell(75, 5, utf8_decode('Comentario del tto.'), 'T,L,R', 0, 'L', true); 
        $pdf->Ln();
        $pdf->setX(105);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($datacomentarios as $value) { 
            $x1 = $pdf->getX();
            $y1 = $pdf->getY();
            $pdf->Cell(25, 4, utf8_decode($value->fecha), 0, 0, 'L');  
            $pdf->MultiCell(75, 4, utf8_decode($value->comentario), 0, 'L', false, 5); 

            $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
            $pdf->Line($x1 + 25, $y1, $x1 + 25, $pdf->GetY()); 
            $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
            $pdf->Line($x1, $pdf->GetY(), $x1 + 100, $pdf->GetY()); 
            $pdf->setX(105);
        }

        if (empty($datacomentarios)) {
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln(); 
        }

        $yTempTecnicas = $pdf->getY();

        // Fisioterapia  
        $pdf->setY($yTemp);
        $pdf->Ln(4);
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->SetDrawColor(0, 93, 185); 
        $pdf->Cell(100, 5, utf8_decode('FISIOTERAPIA'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213);         
        $pdf->Cell(15, 6, utf8_decode(''), 'L', 0, 'L', true); 
        $pdf->Cell(10, 6, utf8_decode(''), '', 0, 'L', true); 
        $pdf->Cell(15, 3, utf8_decode('Infrarojo'), 'L,B', 0, 'C', true); 
        $pdf->Cell(15, 3, utf8_decode('Comp.Fría'), 'L,B', 0, 'C', true);
        $pdf->Cell(15, 3, utf8_decode('Masaj.Tera.'), 'L,B', 0, 'C', true);
        $pdf->Cell(15, 3, utf8_decode('Elect.C/J.'), 'L,B', 0, 'C', true);
        $pdf->Cell(15, 3, utf8_decode('Ultrasonido'), 'L,B,R', 0, 'C', true);
        $pdf->Ln();
        $pdf->setX(27);
        $pdf->Cell(7.5, 3, utf8_decode('min.'), 1, 0, 'C', true); 
        $pdf->Cell(7.5, 3, utf8_decode('int.'), 1, 0, 'C', true);
        $pdf->Cell(7.5, 3, utf8_decode('min.'), 1, 0, 'C', true); 
        $pdf->Cell(7.5, 3, utf8_decode('int.'), 1, 0, 'C', true);
        $pdf->Cell(7.5, 3, utf8_decode('min.'), 1, 0, 'C', true); 
        $pdf->Cell(7.5, 3, utf8_decode('int.'), 1, 0, 'C', true);
        $pdf->Cell(7.5, 3, utf8_decode('min.'), 1, 0, 'C', true); 
        $pdf->Cell(7.5, 3, utf8_decode('int.'), 1, 0, 'C', true);
        $pdf->Cell(7.5, 3, utf8_decode('min.'), 1, 0, 'C', true);  
        $pdf->Cell(7.5, 3, utf8_decode('int.'), 1, 0, 'C', true);
        $pdf->Ln();
        
        $pdf->SetTextColor(0, 0, 0); 

        foreach ($matrizFisioterapia as $value) {   
            $pdf->Cell(15, 4, utf8_decode($value['fecha']), 'T,B,L', 0, 'L');  
            $pdf->Cell(10, 4, substr($value['inicio'],0, 5), 'T,R,B', 0, 'L');  
            $pdf->Cell(7.5, 4, utf8_decode($value['inf_min']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['inf_int']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['com_min']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['com_int']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['mas_min']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['mas_int']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['ele_min']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['ele_int']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['ult_min']), 1, 0, 'C');  
            $pdf->Cell(7.5, 4, utf8_decode($value['ult_int']), 1, 0, 'C');  
            $pdf->Ln();
        }

        if (empty($matrizFisioterapia)) { 
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
        } 

        // Acupuntura con aguja  
        $pdf->Ln();
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('ACUPUNTURA CON AGUJA'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213);  

        $pdf->Cell(15, 6, utf8_decode(''), 'L', 0, 'L', true); 
        $pdf->Cell(10.1, 6, utf8_decode(''), '', 0, 'L', true); 
        $pdf->Cell(10.7, 6, utf8_decode('Punto1'), 'L,B', 0, 'C', true); 
        $pdf->Cell(10.7, 6, utf8_decode('Punto2'), 'L,B', 0, 'C', true);
        $pdf->Cell(10.7, 6, utf8_decode('Punto3'), 'L,B', 0, 'C', true);
        $pdf->Cell(10.7, 6, utf8_decode('Punto4'), 'L,B', 0, 'C', true);
        $pdf->Cell(10.7, 6, utf8_decode('Punto5'), 'L,B', 0, 'C', true);
        $pdf->Cell(10.7, 6, utf8_decode('Punto6'), 'L,B', 0, 'C', true);
        $pdf->Cell(10.7, 6, utf8_decode('Punto7'), 'L,B,R', 0, 'C', true); 
        $pdf->Ln();
       
        $pdf->SetTextColor(0, 0, 0);  
        $sinItem = true;
        foreach ($matrizAcupuntura as $value) {   
            $pdf->Cell(15, 4, utf8_decode($value['fecha']), 'T,B,L', 0, 'L');  
            $pdf->Cell(10.1, 4, substr($value['inicio'],0, 5), 'T,R,B', 0, 'L');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto1']), 1, 0, 'C');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto2']), 1, 0, 'C');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto3']), 1, 0, 'C');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto4']), 1, 0, 'C');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto5']), 1, 0, 'C');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto6']), 1, 0, 'C');  
            $pdf->Cell(10.7, 4, utf8_decode($value['punto7']), 1, 0, 'C');    
            $pdf->Ln();
        }

        if (empty($matrizAcupuntura)) { 
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
        }

        // Acupuntura magnética  
        $pdf->Ln();
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('ACUPUNTURA MAGNÉTICA'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->Cell(25, 3, utf8_decode('Fecha'), 'L', 0, 'C', true);     
        $pdf->Cell(37.5, 3, utf8_decode('Tiempo'), 'L', 0, 'C', true);
        $pdf->Cell(37.5, 3, utf8_decode('Intensidad'), 'L,R', 0, 'C', true);

        $pdf->Ln();
        $pdf->setX(2);
        $pdf->Cell(25, 3, utf8_decode('Sesión'), 'L', 0, 'C', true); 
        $pdf->Cell(37.5, 3, utf8_decode('(Min.)'), 'L', 0, 'C', true);
        $pdf->Cell(37.5, 3, utf8_decode('(1-10)'), 'L,R', 0, 'C', true);
        $pdf->Ln();
       
        $pdf->SetTextColor(0, 0, 0);  
        $sinItem = true;
        foreach ($dataProcedimientos as $value) {  
            if ($value->idproducto === 17) { 
                $pdf->Cell(25, 4, utf8_decode($value->fecha), 1, 0, 'L');  
                $pdf->Cell(37.5, 4, utf8_decode($value->tiempo), 1, 0, 'C');  
                $pdf->Cell(37.5, 4, utf8_decode($value->intensidad), 1, 0, 'C');  
                $pdf->Ln();
                $sinItem = false;
            } 
        }

        if ($sinItem) {
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
        }

        // Acupuntura Spectrum  
        $pdf->Ln();
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('ACUPUNTURA SPECTRUM'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->Cell(25, 3, utf8_decode('Fecha'), 'L', 0, 'C', true);     
        $pdf->Cell(37.5, 3, utf8_decode('Tiempo'), 'L', 0, 'C', true);
        $pdf->Cell(37.5, 3, utf8_decode('Intensidad'), 'L,R', 0, 'C', true);

        $pdf->Ln();
        $pdf->setX(2);
        $pdf->Cell(25, 3, utf8_decode('Sesión'), 'L', 0, 'C', true); 
        $pdf->Cell(37.5, 3, utf8_decode('(Min.)'), 'L', 0, 'C', true);
        $pdf->Cell(37.5, 3, utf8_decode('(1-10)'), 'L,R', 0, 'C', true);
        $pdf->Ln();
        
        $pdf->SetTextColor(0, 0, 0);  
        $sinItem = true;
        foreach ($dataProcedimientos as $value) {  
            if ($value->idproducto === 6) { 
                $pdf->Cell(25, 4, utf8_decode($value->fecha), 1, 0, 'L');  
                $pdf->Cell(37.5, 4, utf8_decode($value->tiempo), 1, 0, 'C');  
                $pdf->Cell(37.5, 4, utf8_decode($value->intensidad), 1, 0, 'C');  
                $pdf->Ln();
                $sinItem = false;
            } 
        }

        if ($sinItem) {
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
        }

        // Quiropraxia computarizada  
        $pdf->Ln();
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('QUIROPRAXIA COMPUTARIZADA'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->Cell(25, 3, utf8_decode('Fecha'), 'L', 0, 'C', true); 
        $pdf->Cell(25, 6, utf8_decode('Fuerza'), 'L', 0, 'C', true); 
        $pdf->Cell(25, 6, utf8_decode('Rotación'), 'L', 0, 'C', true);
        $pdf->Cell(25, 6, utf8_decode('Flex/Ext.'), 'L,R', 0, 'C', true);
        $pdf->Ln();
        $pdf->setXY(2, $pdf->getY() - 3);
        $pdf->Cell(25, 3, utf8_decode('Sesión'), 'L,R', 0, 'C', true);  
        $pdf->Ln();
        
        $pdf->SetTextColor(0, 0, 0);  
        $sinItem = true;
        foreach ($dataProcedimientos as $value) {  
            if ($value->idproducto === 4) {
                $pdf->Cell(25, 4, utf8_decode($value->fecha), 1, 0, 'L');  
                $pdf->Cell(25, 4, utf8_decode($value->fuerza), 1, 0, 'C');  
                $pdf->Cell(25, 4, utf8_decode($value->rotacion), 1, 0, 'C');  
                $pdf->Cell(25, 4, utf8_decode($value->flex), 1, 0, 'C');  
                $pdf->Ln();
                $sinItem = false;
            } 
        }

        if ($sinItem) {
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
        }

        // Quiropraxia computarizada  
        $pdf->Ln();
        $pdf->SetFillColor(0, 93, 185); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255);  
        $pdf->Cell(100, 5, utf8_decode('ONDAS DE CHOQUE'), 1, 0, 'L', true);    
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 93, 185); 
        $pdf->SetFillColor(194, 204, 213); 
        $pdf->Cell(25, 3, utf8_decode('Fecha'), 'L', 0, 'C', true); 
        $pdf->Cell(25, 6, utf8_decode('Disparos'), 'L', 0, 'C', true); 
        $pdf->Cell(25, 6, utf8_decode('Bares'), 'L', 0, 'C', true);
        $pdf->Cell(25, 6, utf8_decode('Hz.'), 'L,R', 0, 'C', true);
        $pdf->Ln();
        $pdf->setXY(2, $pdf->getY() - 3);
        $pdf->Cell(25, 3, utf8_decode('Sesión'), 'L,R', 0, 'C', true);  
        $pdf->Ln();
        
        $pdf->SetTextColor(0, 0, 0);  
        $sinItem = true;
        foreach ($dataProcedimientos as $value) {  
            if ($value->idproducto === 5) {
                $pdf->Cell(25, 4, utf8_decode($value->fecha), 1, 0, 'L');  
                $pdf->Cell(25, 4, utf8_decode($value->disparo), 1, 0, 'C');  
                $pdf->Cell(25, 4, utf8_decode($value->bares), 1, 0, 'C');  
                $pdf->Cell(25, 4, utf8_decode($value->hz), 1, 0, 'C');  
                $pdf->Ln();
                $sinItem = false;
            } 
        }

        if ($sinItem) {
            $pdf->Cell(100, 4, utf8_decode(''), 1);
            $pdf->Ln();
        }

        $yTempOndas = $pdf->getY();
 
        if ($yTempTecnicas > $yTempOndas) {
            $pdf->setY($yTempTecnicas);
        } else {
            $pdf->setY($yTempOndas);
        }

        //Firma médico 
        $pdf->Ln();
        $pdf->SetFont('Arial', 'U', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(204, 204, 204);
        $pdf->SetDrawColor(0, 0, 0);         
        $pdf->Cell(10, 5, utf8_decode('Ciclo'), 'T,L', 0, 'L'); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(20, 5, utf8_decode($id), 'T', 0, 'L'); 
        $pdf->SetFont('Arial', 'U', 8);
        $pdf->Cell(8, 5, utf8_decode('del'), 'T', 0, 'L'); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(17, 5, utf8_decode($cicloatencion->primert), 'T', 0, 'L'); 
        $pdf->SetFont('Arial', 'U', 8);
        $pdf->Cell(8, 5, utf8_decode('al'), 'T', 0, 'L'); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(17, 5, utf8_decode($cicloatencion->ultimot), 'T,R', 0, 'L'); 

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->setX($pdf->getX() + 3);
        $pdf->Cell(60, 5, utf8_decode('Director técnico'), 'T,L,R', 0, 'L'); 
        $pdf->setX($pdf->getX() + 3);
        $pdf->Cell(60, 5, utf8_decode('Apellidos y nombres del terapeuta'), 'T,L,R', 0, 'L');
        $pdf->Ln();

        $pdf->SetFont('Arial', 'U', 8); 
        $pdf->setX(2);
        $pdf->Cell(22, 5, utf8_decode('Total sesiones'), 'L,B', 0, 'L');   
        $pdf->SetFont('Arial', '', 8);           
        $pdf->Cell(58, 5, utf8_decode(count($dataterapiatra)), 'B,R', 0, 'L');
        $pdf->setX($pdf->getX() + 3);
        $pdf->Cell(60, 5, utf8_decode(''), 'L,B,R', 0, 'L');  
        $pdf->setX($pdf->getX() + 3);
        $pdf->Cell(60, 5, utf8_decode(''), 'L,B,R', 0, 'L');
        $pdf->Ln();

        // ANTECEDENTES PATOLÓGICOS
        // $pdf->Ln();
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(206, 5, utf8_decode('ANTECEDENTES PATOLÓGICOS'), 1, 0, 'C', true);        
        // $pdf->Ln(); 
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 0, 0);       
        // $pdf->MultiCell(0, 4, utf8_decode('Jc'), 1, 'L', false, 3);        

        // // Enfermedad actual
        // $pdf->Ln();        
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(100, 5, utf8_decode('ENFERMEDAD ACTUAL'), 1, 0, 'L', true);
        // $x = $pdf->getX();
        // $y = $pdf->getY();     
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->Cell(100, 4, utf8_decode('Síntomas y disfunción'), 'L, R', 0, 'L'); 
        // $pdf->Ln();

        // $x1Temp = $pdf->getX();
        // $y1Temp = $pdf->getY();
        // $pdf->SetTextColor(0, 0, 0);
        // $x1 = $pdf->getX();
        // $y1 = $pdf->getY();
        // $pdf->MultiCell(100, 4, utf8_decode($citamedica->nota), 'R, B, L', 'L', false, 10);        
        
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->Cell(15, 4, 'Tiempo:', 'B,L', 0, 'L');   
        // $pdf->SetTextColor(0, 0, 0); 
        // $pdf->Cell(85, 4, utf8_decode($citamedica->enfermedadtiempo), 'R,B', 0, 'L'); 
        // $pdf->Ln(); 

        // $xSyD = $pdf->getX();
        // $ySyD = $pdf->getY();
 
        // // Descanso médico
        // $pdf->setXY($x + 6, $y);
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(100, 5, utf8_decode('FUNCIONES VITALES'), 1, 0, 'L', true);    
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->setX($x + 6);
        // $pdf->Cell(25, 4, utf8_decode('PC'), 'L', 0, 'L'); 
        // $pdf->Cell(25, 4, utf8_decode('FC'), 'L', 0, 'L'); 
        // $pdf->Cell(25, 4, utf8_decode('Peso'), 'L', 0, 'L'); 
        // $pdf->Cell(25, 4, utf8_decode('Talla'), 'L,R', 0, 'L');
        // $pdf->Ln();
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->setX($x + 6);
        // $pdf->Cell(25, 4, utf8_decode($citamedica->fvpc), 'L,B', 0, 'C');  
        // $pdf->Cell(25, 4, utf8_decode($citamedica->fvfc), 'L,B', 0, 'C');  
        // $pdf->Cell(25, 4, utf8_decode($citamedica->fvpeso), 'L,B', 0, 'C');  
        // $pdf->Cell(25, 4, utf8_decode($citamedica->fvtalla), 'L,B,R', 0, 'L');
        // $pdf->Ln();
 
        // //EXAMEN FISICO 
        // $pdf->setXY($xSyD, $ySyD); 
        // $pdf->Ln(); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(206, 5, utf8_decode('EXAMEN FÍSICO'), 1, 0, 'C', true);        
        // $pdf->Ln();
        // $x = $pdf->getX();
        // $y = $pdf->getY(); 

        // //IMPRIMIR IMAGEN FRONTAL Y POSTERIOR 
        // $pdf->setXY($x + 94, $y);
        // $pdf->SetFont('Arial', 'B', 8);
        // $pdf->SetTextColor(0, 0, 0); 
        // $pdf->Cell(50, 8, utf8_decode('FRONTAL'), 0, 0, 'C');     
        // $pdf->Cell(4, 8); 
        // $pdf->Cell(54, 8, utf8_decode('POSTERIOR'), 0, 0, 'C');  

        // $pdf->setXY($x, $y);
        // $pdf->Image($pdf->path.'erp-frontal.png', $pdf->getX() + 94, $pdf->getY() + 8, 50, 0, 'PNG');

        // $pdf->setXY($x, $y);
        // $pdf->Image($pdf->path.'erp-posterior.png', $pdf->getX() + 148, $pdf->getY() + 8, 54, 0, 'PNG');

        // // Imprimir lineas
        // $checkFrontal = array(
        // ['h' => 8.2, 'c' =>array( '', '', '', '', '', '', '', '', '', 'FD-1', 'FI-1', '', '', '', '', '', '', '', '', '')],
        // ['h' => 5.7, 'c' =>array( '', '', '', '', '', '', '', '', '', 'FD-2', 'FI-2', '', '', '', '', '', '', '', '', '')],
        // ['h' => 12, 'c' =>array( '', '', '', '', '', 'FD-6', '', '', 'FD-3', '', '', 'FI-3', '', '', 'FI-6', '', '', '', '', '')],        
        // ['h' => 6, 'c' =>array( '', '', '', '', '', 'FD-7', 'FD-8', '', 'FD-4', '', '', 'FI-4', '', 'FI-8', 'FI-7', '', '', '', '', '')],        
        // ['h' => 7.9, 'c' =>array( '', '', '', 'FD-9', 'FD-10', '', '', '', 'FD-5', '', '', 'FI-5', '', '', '', 'FI-10', 'FI-9', '', '', '')],        
        // ['h' => 8.8, 'c' =>array( 'FD-11', '', 'FD-12', '', '', '', 'FD-15', '', 'FD-14', '', '', 'FI-14', '', 'FI-15', '', '', '', 'FI-12', '', 'FI-11')],
        // ['h' => 10, 'c' =>array( 'FD-13a', 'FD-13b', 'FD-13c', 'FD-13d', '', '', '', '', 'FD-16', 'FD-17', 'FI-17', 'FI-16', '', '', '', '', 'FI-13d', 'FI-13c', 'FI-13b', 'FI-13a')],
        // ['h' => 5, 'c' =>array( '', '', '', '', '', '', '', '', 'FD-18', '', '', 'FI-18', '', '', '', '', '', '', '', '')],
        // ['h' => 13.5, 'c' =>array( '', '', '', '', '', '', '', 'FD-19', 'FD-20', '', '', 'FI-20', 'FI-19', '', '', '', '', '', '', '')],
        // ['h' => 6.5, 'c' =>array( '', '', '', '', '', '', '', '', 'FD-21', '', '', 'FI-21', '', '', '', '', '', '', '', '')],
        // ['h' => 3.3, 'c' =>array( '', '', '', '', '', '', '', 'FD-22', '', 'FD-23', 'FI-23', '', 'FI-22', '', '', '', '', '', '', '')],
        // ['h' => 5, 'c' =>array( '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '')]
        // );

        // $checkPosterior = array(
        //     ['h' => 6, 'c' =>array( '', '', '', '', '', '', '', '', '', 'PI-1', 'PD-1', '', '', '', '', '', '', '', '', '')],
        //     ['h' => 3.5, 'c' =>array( '', '', '', '', '', '', '', '', '', 'PI-2', 'PD-2', '', '', '', '', '', '', '', '', '')],
        //     ['h' => 14.9, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-3', '', '', 'PD-3', '', '', '', '', '', '', '', '')], 
        //     ['h' => 5, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-4', '', '', 'PD-4', '', '', '', '', '', '', '', '')],
        //     ['h' => 3, 'c' =>array( '', '', '', '', '', 'PI-6', '', '', '', '', '', '', '', '', 'PD-6', '', '', '', '', '')],
        //     ['h' => 8.5, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-5', '', '', 'PD-5', '', '', '', '', '', '', '', '')], 
        //     ['h' => 8, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-7', '', '', 'PD-7', '', '', '', '', '', '', '', '')],
        //     ['h' => 12, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-8', '', '', 'PD-8', '', '', '', '', '', '', '', '')],
        //     ['h' => 3.5, 'c' =>array( '', '', '', '', '', '', '', '', 'PI-9', '', '', 'PD-9', '', '', '', '', '', '', '', '')], 
        //     ['h' => 26.5, 'c' =>array( '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '')] 
        // );
 
 
        // // Frontal
        // $pdf->setXY($x + 91.9, $y + 8);
        // foreach($checkFrontal as $row) {
        //     $pdf->setX($x + 94);
        //     foreach($row['c'] as $value) { 
                
        //         if (!empty($value)) {

        //             if (substr($value, 1, 1) === 'D') {
        //                 $pdf->SetTextColor(255, 0, 0);                            
        //             } else { 
        //                 $pdf->SetTextColor(0, 0, 255); 
        //             }

        //             if (in_array($value, $examenArray)) {  
        //                 $pdf->SetFont('Arial', 'B', 18);
        //                 $pdf->Cell(2.5, $row['h'], utf8_decode('.'), 0, 0, 'C');
 
        //                 $pdf->SetFont('Arial', '', 6);
        //                 $pdf->setX($pdf->getX() - 2.5); 
        //                 $pdf->Cell(2.5, $row['h'], substr($value, 3), 0, 0, 'C'); 
        //             } else {        
        //                 //Manos
        //                 $caracter = in_array($value, ['FD-11', 'FD-13a', 'FD-13b', 'FD-13c', 'FD-13d', 'FI-11', 'FI-13d', 'FI-13c', 'FI-13b', 'FI-13a']) ? 'o' : '';
                    
        //                 $pdf->Cell(2.5, $row['h'], '', 0, 0, 'C');                         

        //                 $xtemp = $pdf->getX();
        //                 $ytemp = $pdf->getY();                        
        //                 if (!empty($caracter)) { 
        //                     $pdf->SetFont('Arial', '', 6);
        //                     $pdf->setXY($xtemp - 2.5, $ytemp + 1.4);
        //                     $pdf->Cell(2.5, $row['h'], $caracter, 0, 0, 'C'); 
        //                 }

        //                 $pdf->setXY($xtemp, $ytemp);
        //             }
        //         } else { 
        //             $pdf->Cell(2.5, $row['h'], '', 0, 0, 'C');
        //         }               
        //     }
        //     $pdf->Ln();
        // } 

        // // Posterior
        // $pdf->setXY($x + 148, $y + 8); 
        // foreach($checkPosterior as $row) {
        //     $pdf->setX($x + 148);
        //     foreach($row['c'] as $value) { 

        //         if (!empty($value) && in_array($value, $examenArray)) {

        //             if (substr($value, 1, 1) === 'D') {
        //                 $pdf->SetTextColor(255, 0, 0);                            
        //             } else { 
        //                 $pdf->SetTextColor(0, 0, 255); 
        //             } 
  
        //             $pdf->SetFont('Arial', 'B', 18);
        //             $pdf->Cell(2.7, $row['h'], utf8_decode('.'), 0, 0, 'C');
 
        //             $pdf->SetFont('Arial', '', 6);
        //             $pdf->setX($pdf->getX() - 2.7); 
        //             $pdf->Cell(2.7, $row['h'], substr($value, 3), 0, 0, 'C'); 
        //         } else {
        //             $pdf->Cell(2.7, $row['h'], '', 0, 0, 'C');
        //         }               
        //     }
        //     $pdf->Ln();
        // } 
 
        // $yCuerpo = $pdf->getY();

        // // IMPRIMIR TABLA EXAMEN FISICO        
        // $pdf->setXY($x, $y);
        // $pdf->SetFillColor(194, 204, 213); 
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);        
        // $pdf->Cell(8, 8, utf8_decode('Cód.'), 'T,L,R', 0, 'L', true);
        // $pdf->Cell(21, 8, utf8_decode('Zona'), 'T,L,R', 0, 'L', true);
        // $pdf->Cell(16, 8, utf8_decode('Rom'), 'T,R,L', 0, 'C', true); 
        // $pdf->Cell(15, 4, utf8_decode('Muscular'), 'T,R,L', 0, 'C', true); 
        // $pdf->Cell(15, 4, utf8_decode('Funcional'), 'T,R,L', 0, 'C', true); 
        // $pdf->Cell(15, 4, utf8_decode('Eva'), 'T,R,L', 0, 'C', true); 
        // $pdf->Ln(); 
        // $pdf->setX(47);
        // $pdf->Cell(15, 4, utf8_decode('F.(1-5)'), 'R,L', 0, 'C', true); 
        // $pdf->Cell(15, 4, utf8_decode('P.(1-5)'), 'R,L', 0, 'C', true); 
        // $pdf->Cell(15, 4, utf8_decode('(1-10)'), 'R,L', 0, 'C', true); 

        // $pdf->SetTextColor(0, 0, 0);      
        // $pdf->Ln();
        
        // $pdf->SetFont('Arial', 'B', 8);
        // $pdf->Cell(90, 4, 'FRONTAL', 1, 0, 'C');  
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
 
        // foreach ($efcfrontal as $value) {
        //     if (substr($value->zona, 1, 1) === 'D') {
        //         $pdf->SetTextColor(255, 0, 0);                            
        //     } else { 
        //         $pdf->SetTextColor(0, 0, 255); 
        //     }

        //     $colorBg = $this->colorBg($value->zona);
        //     $pdf->SetFillColor($colorBg[0], $colorBg[1], $colorBg[2]);

        //     $zona = $this->zonaCuerpo($value->zona) . (substr($value->zona, 1, 1) === 'D' ? ' - D' : ' - I');

        //     $pdf->Cell(8, 4, substr($value->zona, 3), 1, 0, 'C', true);   
        //     $pdf->Cell(21, 4, utf8_decode($zona), 1, 0, 'L');   
        //     $pdf->Cell(16, 4, utf8_decode($value->rom), 1, 0, 'C');   
        //     $pdf->Cell(15, 4, utf8_decode($value->muscular), 1, 0, 'C');   
        //     $pdf->Cell(15, 4, utf8_decode($value->funcional), 1, 0, 'C');   
        //     $pdf->Cell(15, 4, utf8_decode($value->eva), 1, 0, 'C');   
        //     $pdf->Ln(); 
        // }  
        // if (empty($efcfrontal)) {
        //     $pdf->Cell(90, 4, '', 1, 0, 'L');
        //     $pdf->Ln();
        // }

        // $pdf->SetTextColor(0, 0, 0); 
        // $pdf->SetFont('Arial', 'B', 8);
        // $pdf->Cell(90, 4, 'POSTERIOR', 1, 0, 'C');  
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);

        // foreach ($efcposterior as $value) {
        //     if (substr($value->zona, 1, 1) === 'D') {
        //         $pdf->SetTextColor(255, 0, 0);                            
        //     } else { 
        //         $pdf->SetTextColor(0, 0, 255); 
        //     }

        //     $colorBg = $this->colorBg($value->zona);
        //     $pdf->SetFillColor($colorBg[0], $colorBg[1], $colorBg[2]);

        //     $zona = $this->zonaCuerpo($value->zona) . (substr($value->zona, 1, 1) === 'D' ? ' - D' : ' - I');            
        //     $pdf->Cell(8, 4, substr($value->zona, 3), 1, 0, 'C', true);   
        //     $pdf->Cell(21, 4, utf8_decode($zona), 1, 0, 'L'); 
        //     $pdf->Cell(16, 4, utf8_decode($value->rom), 1, 0, 'C');   
        //     $pdf->Cell(15, 4, utf8_decode($value->muscular), 1, 0, 'C');   
        //     $pdf->Cell(15, 4, utf8_decode($value->funcional), 1, 0, 'C');   
        //     $pdf->Cell(15, 4, utf8_decode($value->eva), 1, 0, 'C');   
        //     $pdf->Ln(); 
        // }       
 
        // if (empty($efcposterior)) {
        //     $pdf->Cell(90, 4, '', 1, 0, 'L');
        //     $pdf->Ln();
        // }  
        
        // if (count($examenfisicocita) <= 19) { 
        //     $pdf->setY($yCuerpo); 
        // }
 
        // // Diagnóstico  
        // $pdf->Ln();
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255);  
        // $pdf->Cell(100, 5, utf8_decode('DIAGNÓSTICO CIE 10'), 1, 0, 'L', true);    

        // $x = $pdf->getX();
        // $y = $pdf->getY();

        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->Cell(15, 4, utf8_decode('Código'), 'L', 0, 'L'); 
        // $pdf->Cell(85, 4, utf8_decode('Nombre'), 'R', 0, 'L');
        // $pdf->Ln();
 
        // $pdf->SetTextColor(0, 0, 0);  
        // foreach ($diagnosticosmedico as $value) {
        //     $x1 = $pdf->getX();
        //     $y1 = $pdf->getY();
        //     $pdf->Cell(15, 4, utf8_decode($value->codigo), 0, 0, 'L');  
        //     $pdf->MultiCell(85, 4, utf8_decode($value->nombre), 0, 'L', false, 3); 
        //     $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
        //     $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
        //     $pdf->Line(2, $pdf->GetY(), 102, $pdf->GetY()); 
        // } 

        // $yfin1 = $pdf->getY();

        // if (empty($diagnosticosmedico)) {
        //     $pdf->Cell(100, 4, '', 1, 0, 'L');
        //     $pdf->Ln();
        //     $yfin1 = $pdf->getY();
        // }


        // // Tratamientos
        // $pdf->setXY($x + 6, $y);
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255);     
        // $pdf->Cell(100, 5, utf8_decode('INDICACIÓN MÉDICA'), 1, 0, 'L', true);         
        // $pdf->Ln();

        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->setX($x + 6);
        // $pdf->Cell(15, 4, utf8_decode('Cantidad'), 'L', 0, 'L'); 
        // $pdf->Cell(85, 4, utf8_decode('Nombre'), 'R', 0, 'L');
        // $pdf->Ln();        

        // $pdf->SetTextColor(0, 0, 0); 
        // foreach ($tratamientosmedicos as $value) {  
        //     $pdf->setX($x + 6);
        //     $pdf->Cell(15, 4, utf8_decode($value->cantidad), 'B,L', 0, 'C'); 
        //     $pdf->Cell(85, 4, utf8_decode($value->nombreproducto), 'R,B', 0, 'L'); 
        //     $pdf->Ln();    
        // } 
            
         
        // if (empty($tratamientosmedicos)) {
        //     $pdf->setX($x + 6);
        //     $pdf->Cell(100, 4, '', 1, 0);
        //     $pdf->Ln();
            
        // } 

        // // Frecuencia
        // $yfin2 = $pdf->getY();
        // $pdf->setX($x + 6);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->Cell(50, 4, 'Asistencia:', 'B,L', 0, 'R');  
        // $frecuencia = '';
        // if ($citamedica->frecuencia === 'D') {
        //     $frecuencia = 'Diaria';
        // }

        // if ($citamedica->frecuencia === 'I') {
        //     $frecuencia = 'Interdiaria';
        // }
        // $pdf->SetTextColor(0, 0, 0); 
        // $pdf->Cell(50, 4, $frecuencia, 'R,B', 0, 'L'); 
        // $pdf->Ln();
        // $yfin2 = $pdf->getY();


        // // Exámen auxiliar  
        // if ($yfin1 >= $yfin2) {
        //     $pdf->setY($yfin1);
        // } else {
        //     $pdf->setY($yfin2);
        // }

        // $pdf->Ln();
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(100, 5, utf8_decode('IND. EXÁMEN AUXILIAR'), 1, 0, 'L', true);    
        // $x = $pdf->getX();
        // $y = $pdf->getY();
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 0, 0); 

        // foreach ($examenescita as $value) {
        //     $x1 = $pdf->getX();
        //     $y1 = $pdf->getY();            
        //     $pdf->MultiCell(100, 4, utf8_decode($value->nombre), 0, 'L', false, 3); 
        //     $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
        //     $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
        //     $pdf->Line(2, $pdf->GetY(), 102, $pdf->GetY()); 
        //     $yfin1 = $pdf->getY();
        // } 

        // if (empty($examenescita)) { 
        //     $pdf->Cell(100, 4, '', 1, 0);
        //     $pdf->Ln();
        //     $yfin1 = $pdf->getY();
        // }


        // // Ind. Farmacológica
        // $pdf->setXY($x + 6, $y);
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(100, 5, utf8_decode('IND. FARMACOLGÓGICA'), 1, 0, 'L', true);    
        // $pdf->Ln();

        // $pdf->SetFont('Arial', '', 8); 
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->setX($x + 6);

        // $x1 = $pdf->getX();
        // $y1 = $pdf->getY();
        // // $pdf->Cell(100, 4, utf8_decode($citamedica->notamedicamento), 'L,B,R', 0, 'L');  

        // $pdf->MultiCell(100, 4, utf8_decode($citamedica->notamedicamento), 'L', 'L', false, 5);
        // $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
        // $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
        // $pdf->Line($x1, $pdf->GetY(), $x1 + 100, $pdf->GetY());  


        // $pdf->Ln(); 
        // $yfin2 = $pdf->getY();

        // // Alta médica 
        // if ($yfin1 >= $yfin2) {
        //     $pdf->setY($yfin1);
        // } else {
        //     $pdf->setY($yfin2);
        // }

        // // $pdf->Ln();        
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(100, 5, utf8_decode('ALTA MÉDICA'), 1, 0, 'L', true);
        // $x = $pdf->getX();
        // $y = $pdf->getY();     
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->Cell(15, 4, utf8_decode(''), 'L', 0, 'L'); 
        // $pdf->Cell(85, 4, utf8_decode('Observación'), 'L,R', 0, 'L');
        // $pdf->Ln();

        // $x1 = $pdf->getX();
        // $y1 = $pdf->getY();
        // $pdf->SetTextColor(0, 0, 0);        
        // $pdf->Cell(15, 4, $citamedica->altamedica === '1' ? 'SI' : 'NO', 0, 0, 'C');  
        // $pdf->MultiCell(85, 4, utf8_decode($citamedica->altamedicacomentario), 'L', 'L', false, 4);
        // $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
        // $pdf->Line($x1 + 100, $y1, $x1 + 100, $pdf->GetY()); 
        // $pdf->Line($x1, $pdf->GetY(), $x1 + 100, $pdf->GetY());  

        // $yAlta = $pdf->getY();

        // // Descanso médico
        // $pdf->setXY($x + 6, $y);
        // $pdf->SetFillColor(0, 93, 185); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(100, 5, utf8_decode('DESCANSO MÉDICO'), 1, 0, 'L', true);    
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 93, 185);
        // $pdf->setX($x + 6);
        // $pdf->Cell(50, 4, utf8_decode('Del'), 'L', 0, 'L'); 
        // $pdf->Cell(50, 4, utf8_decode('Al'), 'R', 0, 'L');
        // $pdf->Ln();
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->setX($x + 6);
        // $pdf->Cell(50, 4, $citamedica->descansodesde, 'L,B', 0, 'C');  
        // $pdf->Cell(50, 4, utf8_decode($citamedica->descansohasta), 'B,R', 0, 'C');        
        // $pdf->Ln();
     

        // //Cierre de ciclo    
        // $pdf->setY($yAlta);
        // $pdf->Ln();

        // if ($pdf->getY() >= 281.9) {
        //     $pdf->AddPage();
        // }

        // $pdf->SetDrawColor(0,0,0); 
        // $pdf->SetFillColor(0, 0, 0); 
        // $pdf->SetFont('Arial', 'B', 9); 
        // $pdf->SetTextColor(255, 255, 255); 
        // $pdf->Cell(206, 5, utf8_decode('MÉDICO ESPECIALISTA'), 1, 0, 'L', true);
        // $pdf->Ln();
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->SetFillColor(204, 204, 204);
        // $pdf->Cell(20, 4, utf8_decode('CMP'), 'L', 0, 'L', true); 
        // $pdf->Cell(186, 4, utf8_decode('Apellidos y nombres'), 'L,R', 0, 'L', true);
        // $pdf->Ln();
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->Cell(20, 4, utf8_decode(''), 'L,B', 0, 'C');  
        // $pdf->Cell(186, 4, utf8_decode($citamedica->nombremedico), 'L,B,R', 0, 'L');
        // $pdf->Ln();
        
        $pdf->Output();
    }   
 

    private function colorBg($zona) {
        $colorBg = array(255, 255, 255);

        if (in_array($zona, ['FD-1', 'FI-1', 'PD-1', 'PI-1'])) {
            $colorBg = array(255, 251, 198); //amarillo
        }

        if (in_array($zona, ['FD-2', 'FI-2', 'FD-14', 'FI-14', 'PD-2', 'PI-2', 'PD-7', 'PI-7'])) {
            $colorBg = array(255, 252, 219); //amarillo-claro
        }

        if (in_array($zona, ['FD-6', 'FI-6'])) {
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

        if (in_array($zona, ['FD-7', 'FI-7'])) {
            $colorBg = array(202, 229, 205); //verde
        }

        if (in_array($zona, ['FD-8', 'FI-8', 'FD-9', 'FI-9'])) {
            $colorBg = array(216, 235, 218); //verde-claro
        }

        if (in_array($zona, ['FD-10', 'FI-10'])) {
            $colorBg = array(227, 241, 228); //verde-luz
        }

        if (in_array($zona, ['FD-11', 'FI-11'])) {
            $colorBg = array(152, 214, 246); //celeste
        }

        if (in_array($zona, ['FD-12', 'FI-12'])) {
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

        if (in_array($zona, ['FD-19', 'FI-19'])) {
            $colorBg = array(0, 119, 195); //blue
        }

        if (in_array($zona, ['FD-20', 'FI-20'])) {
            $colorBg = array(18, 140, 206); //blue-claro
        }

        if (in_array($zona, ['FD-21', 'FI-21'])) {
            $colorBg = array(103, 166, 219); //blue-luz
        }

        if (in_array($zona, ['FD-22', 'FI-22'])) {
            $colorBg = array(250, 204, 122); //naranja
        }

        if (in_array($zona, ['FD-23', 'FI-23'])) {
            $colorBg = array(252, 215, 154); //naranja-claro
        }

        if (in_array($zona, ['PD-6', 'PI-6'])) {
            $colorBg = array(224, 200, 211); //225
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

        if (in_array($zona, ['FD-6', 'FI-6'])) {
            $descripcion = "Hombro";
        }

        if (in_array($zona, ['FD-7', 'FI-7', 'FD-8', 'FI-8'])) {
            $descripcion = "Brazo";
        }

        if (in_array($zona, ['FD-9', 'FI-9', 'FD-10', 'FI-10'])) {
            $descripcion = "Antebrazo";
        }

        if (in_array($zona, ['FD-11', 'FI-11', 'FD-12', 'FI-12', 'FD-13a', 'FI-13a', 'FD-13b', 'FI-13b', 'FD-13c', 'FI-13c', 'FD-13d', 'FI-13d'])) {
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

        if (in_array($zona, ['FD-19', 'FI-19', 'FD-20', 'FI-20', 'FD-21', 'FI-21'])) {
            $descripcion = "Pierna";
        }

        if (in_array($zona, ['FD-22', 'FI-22'])) {
            $descripcion = "Pie";
        }

        if (in_array($zona, ['FD-23', 'FI-23'])) {
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

        return $descripcion;
    } 
 
    
}