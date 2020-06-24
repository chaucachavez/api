<?php
namespace App\Http\Controllers\Pdfs;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\grupodx;
use App\Models\terapia;
use App\Models\citamedica;
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
    public $borde = 1;
    public $nombresede;
    public $idcicloatencion;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';  
    // public $pathRubrica = 'http://lumenionic.pe/firmas_terapia';
    public $pathRubrica = 'https://sistemas.centromedicoosi.com/apiosi/public/firmas_terapia';
    public $titulo = 'HOJA DE ATENCIÓN';
    public $previsualizacion = false;
    public $paciente = null;

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\atenciones\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/atenciones/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/atenciones/';
    
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
        $this->Image($this->path.$this->logo, 2, 2, 50, 0, 'PNG'); 
        $this->setX(42);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 93, 169);
        $this->SetFillColor(0, 93, 169); 
        $this->SetDrawColor(0, 93, 169); 

        $this->Cell(92, 12, utf8_decode($this->titulo), 0, 0, 'C');

        if ($this->previsualizacion) {
            $x = $this->getX();
            $y = $this->getY();

            $this->Ln();
            
            $this->setXY(42, ($this->getY() - 3)); 
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(255, 0, 0); 
            $this->Cell(92, 6, utf8_decode('( NO IMPRIMIR )'), 0, 0, 'C');
            $this->setXY($x, $y);
        }

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'SEDE', 'T,L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->nombresede, 'T'); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'H.C.', 'T');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->hc, 'T,R');
        $this->Ln();  

        $this->setX(134);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'SEG.', 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->seguro); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'CICLO');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->idcicloatencion, 'R');
        $this->Ln();

        $this->setX(134);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'HORA', 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->horacita); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'FECHA');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(25, 4, $this->fechacita, 'R');
        $this->Ln();

        $this->setX(134);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(12, 4, 'TRASL.', 'L,B');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->Cell(62, 4, $this->traslado, 'B,R'); 
       
        $this->Ln(5);
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

class hojadeatencionController extends Controller 
{    
    // public function __construct(Request $request) 
    // {           
    //     $this->getToken($request);            
    // }
        
    // Request $request, 
    public function reporte($enterprise, $id, $idgrupodx, $idauth, $previsualizacion = false)
    {
        $pdf = new PDF(); 
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        $objCitamedica = new citamedica(); 
        $objCicloatencion = new cicloatencion(); 
        $objTerapia = new terapia(); 
        $objPresupuesto = new presupuesto(); 

        // $request = $request->all(); 

        //Información general
        $idempresa = $objEmpresa->idempresa($enterprise);

        $dataCitas = $objCicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $id]);
        $dataAutoriz = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);

        $paramDx = array('citamedica.idcicloatencion' => $id, 'diagnosticomedico.idgrupodx' => $idgrupodx);
        $paramEfect = array('cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38, 'terapiatratamiento.idgrupodx' => $idgrupodx); 

        $diagnosticosmedicos = $objCitamedica->diagnosticomedico($paramDx);
        
        $terapiasrealizadas = array();
        $dataterapia = $objTerapia->terapiatratamientos($paramEfect, array('terapia.idterapia', 'terapia.fecha',  'terapia.inicio', 'terapia.fin', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad', 'terapista.entidad as nombreterapista', 'sede.sedeabrev as nombresede', 'terapia.firma', 'terapia.fechafirma', 'respfirma.entidad as personalfirma'), TRUE, false, '', '', '', [], '', [], true); 

        $quiebre = array('idterapia' => 'idterapia');            
        $campoextra = array('fecha' => 'fecha', 'inicio' => 'inicio', 'fin' => 'fin', 'idterapista' => 'nombreterapista', 'nombresede' => 'nombresede', 'firma' => 'firma', 'idterapia' => 'idterapia', 'fechafirma' => 'fechafirma', 'personalfirma' => 'personalfirma');  
        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA','*'=>'OTROS')];
        $datatratxterapista = $this->agruparPorColumna($dataterapia, '', $quiebre, $campoextra, $gruposProducto);    
          
        $realizados = array();
        foreach($datatratxterapista as $row){                
            if(!isset($realizados[$row['idquiebre']])) {
                $realizados[$row['idquiebre']]['idterapia'] = $row['idterapia'];
                $realizados[$row['idquiebre']]['fecha'] = $row['fecha'];
                $realizados[$row['idquiebre']]['inicio'] = $row['inicio'];
                $realizados[$row['idquiebre']]['fin'] = $row['fin']; 
                $realizados[$row['idquiebre']]['nombreterapista'] = $row['nombreterapista'];
                $realizados[$row['idquiebre']]['nombresede'] = $row['nombresede'];
                $realizados[$row['idquiebre']]['firma'] = $row['firma'];
                $realizados[$row['idquiebre']]['fechafirma'] = $row['fechafirma'];
                $realizados[$row['idquiebre']]['personalfirma'] = $row['personalfirma'];
                
                foreach($gruposProducto[1] as $ind => $val){
                    $realizados[$row['idquiebre']][$val] = 0;
                }
            } 
             
            $realizados[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : '';              
        }  

        foreach ($realizados as $i => $row) { 
            $terapiasrealizadas[] = $row;
        }  
        //Fin Terapias 
        // dd($dataCitas, $dataAutoriz, $terapiasrealizadas);

        if (count($dataCitas) === 0) {
            dd("Ciclo sin citas médicas");
        } 

        $idcitamedica = $dataCitas[0]->idcitamedica;        

        $citamedica = $objCitamedica->citamedica($idcitamedica); 
        $cicloatencion = $objCicloatencion->cicloatencion($id, true);             
        $user = $objEntidad->entidad(['entidad.identidad' => $idauth]); //$this->objTtoken->my
        $entidad = $objEntidad->entidad(['entidad.identidad' => $citamedica->idpaciente]); 
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]);

        
        // Autorizaciones
        $sitedcodigo = [];
        $siteddescripcion = [];
        $cgcodigo = [];
        $cgdescripcion = [];
        $cccodigo = [];
        $ccdescripcion = [];
        $correocodigo = [];
        $correodescripcion = [];

        $arrayAbreviatura = [];
        $arraySeguro = [];
        $arrayDeducible = [];
        $arrayCoaseguro = [];
        foreach ($dataAutoriz as $row) {   

            if (!empty($row->abreviatura) && !in_array($row->abreviatura, $arrayAbreviatura)) {
                $arrayAbreviatura[] = $row->abreviatura;
            }

            if (!empty($row->deducible) &&!in_array($row->deducible, $arrayDeducible)) {
                $arrayDeducible[] = $row->deducible;
            } 

            if (!empty($row->nombrecoaseguro) &&!in_array($row->nombrecoaseguro, $arrayCoaseguro)) {
                $arrayCoaseguro[] = $row->nombrecoaseguro;
            }

            // dd($row);
            switch ($row->idtipo) {
                case 23: // Sited
                    $sitedcodigo[] = $row->codigo;
                    $siteddescripcion[] = $row->fecha;
                    break;
                case 24: // Carta garantia
                    $cgcodigo[] = $row->codigo;
                    $cgdescripcion[] = $row->numero;
                    break;
                case 25: // CallCenter
                    $cccodigo[] = $row->codigo;
                    $ccdescripcion[] = $row->fecha . ' ' . $row->hora;
                    break;
                case 45: // Correo
                    $correocodigo[] = $row->codigo;
                    $correodescripcion[] = $row->fecha . ' ' . $row->hora;
                    break;
            } 
        }

        $planes = implode(" / ", $arrayAbreviatura);
        $deducible = implode("/", $arrayDeducible);
        $coaseguro = implode("/", $arrayCoaseguro);

        $sitedcodigo = implode(" / ", $sitedcodigo);
        $siteddescripcion = implode(" / ", $siteddescripcion);
        $cgcodigo = implode(" / ", $cgcodigo);
        $cgdescripcion = implode(" / ", $cgdescripcion);
        $cccodigo = implode(" / ", $cccodigo);
        $ccdescripcion = implode(" / ", $ccdescripcion);
        $correocodigo = implode(" / ", $correocodigo);
        $correodescripcion = implode(" / ", $correodescripcion);
        // Fin autorizaciones

        // Datos decabecera
        $pdf->printBy = $user->entidad;          
        $pdf->web = $empresa->paginaweb;
        $pdf->logo = $empresa->url.'/logopdf.png';
        $pdf->nombresede = $citamedica->sedeabrev; 
        $pdf->paciente = $citamedica->nombrepaciente;
        $pdf->hc = $citamedica->hc; 
        $pdf->seguro = implode(" / ", $arrayAbreviatura);
        $pdf->idcicloatencion = $citamedica->idcicloatencion;
        $pdf->fechacita = $citamedica->fecha;
        $pdf->horacita = $citamedica->inicio;
        
        $pdf->previsualizacion = $previsualizacion;

        $pdf->traslado = (!empty($cicloatencion->sedeorigen) && $cicloatencion->sedeorigen !== $cicloatencion->sedeabrev) ? ($cicloatencion->sedeorigen.' => '.$cicloatencion->sedeabrev): '';

        $pdf->SetMargins(2, 2, 2);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->AliasNbPages(); 
        $pdf->SetFillColor(0, 93, 169); 
        $pdf->SetDrawColor(0, 93, 169); 
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169);
        $pdf->AddPage();   
           
        // Filiacion  
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(21, 5, 'PACIENTE:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(185, 5, utf8_decode($entidad->entidad), 'B', 0, 'L');
        $pdf->Ln(); 

        // Primer T
        $primerT = '';
        foreach ($terapiasrealizadas as $value) {
            $primerT = date('d/m/Y', strtotime('+21 day', strtotime($this->formatFecha($value['fecha'], 'yyyy-mm-dd'))));
            break;
        } 
        //dd($primerT);

        $pdf->Ln(); 
        $y = $pdf->getY();
        $pdf->setX(120);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(15, 5, 'T.E.', 0, 0, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(63, 5, utf8_decode($citamedica->enfermedadtiempo), 'B', 0, 'L');
        $pdf->Ln();
        $pdf->setX(120);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(15, 5, 'M.T.:', 0, 0, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);   
        $pdf->Cell(63, 5, utf8_decode($citamedica->nombremedico), 'B', 0, 'L');
        $pdf->Ln();
        $pdf->setX(120);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(15, 5, 'VENC.:', 0, 0, 'C'); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(63, 5, utf8_decode($primerT), 'B', 0, 'L');
        $pdf->Ln();

        // Diagnósticos
        $pdf->Ln();  
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->SetFillColor(153, 184, 223);
        $pdf->setXY(12, $y);
        $pdf->Cell(15, 5, utf8_decode('CIE10'), 'L,T', 0, 'C', true); 
        
        $nombregrupo = "";        
        $pdf->Cell(15, 5, utf8_decode('ZONA'), 'T', 0, 'C', true);
        $grupo = grupodx::find($idgrupodx);
        $nombregrupo = " (".$grupo->nombre.")";
       

        $pdf->Cell(75, 5, utf8_decode('DIAGNÓSTICO' . $nombregrupo), 'T,R', 0, 'L', true); 
        $pdf->Ln(); 

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        // $diagnosticosmedicos = array();

        $cantDx = 0;
        $excluirRepetidas = array();
        foreach ($diagnosticosmedicos as $value) {
            if (!in_array($value->iddiagnostico, $excluirRepetidas)) {
                $pdf->setX(12);
                $x1 = $pdf->getX();
                $y1 = $pdf->getY();
                $pdf->Cell(15, 5, utf8_decode($value->codigo), 'L', 0, 'C');   
                                
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

                $pdf->Cell(15, 5, utf8_decode($zona), '', 0, 'C');                   
                $pdf->MultiCell(75, 5, utf8_decode($value->nombre), 0, 'L', false, 3);                 

                $pdf->Line($x1, $y1, $x1, $pdf->GetY()); 
                $pdf->Line($x1 + 105, $y1, $x1 + 105, $pdf->GetY()); 
                $pdf->Line($x1, $pdf->GetY(), 117, $pdf->GetY());  
                $excluirRepetidas[] = $value->iddiagnostico;

                $cantDx++; 
            }
        }

        if (empty($diagnosticosmedicos)) { 
            $pdf->setX(12);
            $pdf->Cell(105, 10, utf8_decode(''), 1);
            $pdf->Ln(); 
        } 

        // Tratamiento          
        $pdf->Ln(5);
        if ($cantDx === 1) {
            $pdf->Ln(5);
        }
        $pdf->SetDrawColor(0, 93, 169);  
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->SetFillColor(0, 93, 169);      
        $pdf->Cell(7, 5, utf8_decode('N°'), 'L,T,B', 0, 'C', true); 
        $pdf->Cell(18, 5, utf8_decode('FECHA'), 'L,T,B', 0, 'C', true); 
        $pdf->Cell(63, 5, utf8_decode('PROF. TRATANTE'), 'L,T,B', 0, 'C', true); 
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->Cell(30, 5, utf8_decode('PACIENTE O APODERADO'), 'L,T,B', 0, 'C', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(11, 5, utf8_decode('T.F.'), 'L,T,B', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('ACU'), 'L,T,B', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('ESP'), 'L,T,B,R', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('BMG'), 'L,T,B,R', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('BL'), 'L,T,B,R', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('QT'), 'L,T,B,R', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('OCH'), 'L,T,B,R', 0, 'C', true);
        $pdf->Cell(11, 5, utf8_decode('OTROS'), 'L,T,B,R', 0, 'C', true);
        // $pdf->Cell(11, 5, utf8_decode('PAGO'), 'L,T,B,R', 0, 'C', true);
        $pdf->Ln();  

        // dd($pdf->pathRubrica);
        $i = 1; 
        foreach ($terapiasrealizadas as $value) {   
            if ($i % 2 === 0) {
                $pdf->SetFillColor(213, 222, 240); 
            } else {
                $pdf->SetFillColor(255, 255, 255); 
            }
            
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 93, 169); 
            $pdf->Cell(7, 15, $i++, 'T,B,L', 0, 'C', true);              
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0); 
            $pdf->Cell(18, 15, utf8_decode($value['fecha']), 1, 0, 'C', true);  
            $pdf->Cell(63, 15, utf8_decode(substr($value['nombreterapista'],0, 50)), 'T,R,B', 0, 'L', true);  
            
            if (!empty($value['firma'])) {
                $pdf->Image($pdf->pathRubrica.'/'.$value['firma'], $pdf->getX() + 1, $pdf->getY(), 28, 0, 'PNG');
            }

            $pdf->Cell(30, 15, utf8_decode(''), 1, 0, 'C', empty($value['firma']) ? true : false); 

            

            $pdf->Cell(11, 15, utf8_decode($value['TF']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['AC']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['ESP']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['BMG']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['BL']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['QT']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['OCH']), 1, 0, 'C', true);  
            $pdf->Cell(11, 15, utf8_decode($value['OTROS']), 1, 0, 'C', true);  
            // $pdf->Cell(11, 15, '', 1, 0, 'C', true);  
            // $pdf->Image($pdf->path.'/check-blue.png', $pdf->getX() - 8, $pdf->getY() + 5, 5, 0, 'PNG');
                        
            $pdf->Ln();
        }

        if (empty($terapiasrealizadas)) { 
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(206, 15, utf8_decode('No hay registros'), 1, 0, 'C');
            $pdf->Ln();
        }  

        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->SetFillColor(0, 93, 169);  
        $pdf->Cell(206, 5, utf8_decode('AUTORIZACIÓN / PRESUPUESTO'), 1, 0, 'C', true);        
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169);
        $pdf->SetFillColor(153, 184, 223); 
        $pdf->Cell(13, 8, utf8_decode('SITEDS'), 'L,B,T', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(45, 4, utf8_decode('N°de Autorización:'), 'L', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('Fecha:'), 'L,R', 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(13, 8, utf8_decode('C.C.'), 'L,B,T', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(45, 4, utf8_decode('Nombre del C.C.:'), 'L', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('Fecha y hora:'), 'L,R', 0, 'L');
        $pdf->Ln(); 
        $pdf->setX(15); 
        $pdf->SetTextColor(0, 0, 0); 

        $pdf->Cell(45, 4, utf8_decode($sitedcodigo), 'L,B', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode($siteddescripcion), 'L,B', 0, 'L');
        $pdf->setX(118); 
        $pdf->Cell(45, 4, utf8_decode($cccodigo), 'L,B', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode($ccdescripcion), 'L,R,B', 0, 'L');
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169);
        $pdf->Cell(13, 8, utf8_decode('C.G.'), 'L,B,T', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(45, 4, utf8_decode('Presupuesto:'), 'L', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('Número:'), 'L,R', 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(13, 8, utf8_decode('E-MAIL'), 'L,B,T', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(45, 4, utf8_decode('Nombre Ejecutiva del Seguro:'), 'L', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('Fecha y hora:'), 'L,R', 0, 'L');
        $pdf->Ln(); 
        $pdf->setX(15); 
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(45, 4, utf8_decode($cgcodigo), 'L,B', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode($cgdescripcion), 'L,B', 0, 'L');
        $pdf->setX(118); 
        $pdf->Cell(45, 4, utf8_decode($correocodigo), 'L,B', 0, 'L');
        $pdf->Cell(45, 4, utf8_decode($correodescripcion), 'L,R,B', 0, 'L');
        $pdf->Ln();         

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169);
        $pdf->Cell(13, 8, utf8_decode('SEGURO'), 'L,B,T', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(45, 8, utf8_decode($planes), 'L,B', 0, 'L');        
        $pdf->SetTextColor(0, 93, 169);
        $pdf->Cell(23, 4, utf8_decode('Deducible:'), 'L,R', 0, 'L');
        $pdf->Cell(22, 4, utf8_decode('Coaseguro:'), 'L,R', 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169);
        $pdf->Cell(13, 4, utf8_decode('Resp.'), 'L,T', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(90, 8, utf8_decode($cicloatencion->created), 'L,R,B', 0, 'L');        
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setXY(60,  $pdf->getY() - 4);  
        $pdf->Cell(23, 4, utf8_decode($deducible), 'L,R,B', 0, 'L');
        $pdf->Cell(22, 4, utf8_decode($coaseguro), 'L,R,B', 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169);
        $pdf->Cell(13, 4, utf8_decode('Admi.'), 'L,B,R', 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);        
        $pdf->Ln();

        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetTextColor(255, 255, 255); 
        $pdf->SetFillColor(0, 93, 169);  
        $pdf->Cell(206, 5, utf8_decode('CIERRE DE CICLO'), 1, 0, 'C', true);        
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 93, 169);  
        $pdf->Cell(9, 4, utf8_decode('1'), 'L,B,T', 0, 'C', true);
        $pdf->SetTextColor(0, 93, 169);        
        $pdf->SetFillColor(153, 184, 223); 
        $pdf->Cell(47, 4, utf8_decode('DIRECTOR DE TERAPIA'), 'L,B,T', 0, 'L', true);
        $pdf->Cell(47, 4, utf8_decode('FECHA Y HORA'), 'L,B,R,T', 0, 'L', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 93, 169); 
        $pdf->Cell(9, 4, utf8_decode('3'), 'L,B,T', 0, 'C', true);
        $pdf->SetTextColor(0, 93, 169);        
        $pdf->SetFillColor(153, 184, 223); 
        $pdf->Cell(47, 4, utf8_decode('J.ADMISIÓN'), 'L,B,T', 0, 'L', true);
        $pdf->Cell(47, 4, utf8_decode('FECHA Y HORA'), 'L,B,R,T', 0, 'L', true);
        $pdf->Ln(); 
        $pdf->Cell(9, 6, utf8_decode(''), 'L,B', 0, 'C');
        $pdf->Cell(47, 6, utf8_decode(''), 'B', 0, 'L');
        $pdf->Cell(47, 6, utf8_decode(''), 'B', 0, 'L');
        $pdf->Cell(9, 6, utf8_decode(''), 'L,B', 0, 'C');
        $pdf->Cell(47, 6, utf8_decode(''), 'B', 0, 'L');
        $pdf->Cell(47, 6, utf8_decode(''), 'B,R', 0, 'L');
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 93, 169); 
        $pdf->Cell(9, 4, utf8_decode('2'), 'L,B,T', 0, 'C', true);
        $pdf->SetTextColor(0, 93, 169);        
        $pdf->SetFillColor(153, 184, 223); 
        $pdf->Cell(47, 4, utf8_decode('ADMISIÓN'), 'L,B,T', 0, 'L', true);
        $pdf->Cell(47, 4, utf8_decode('FECHA Y HORA'), 'L,B,R,T', 0, 'L', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 93, 169); 
        $pdf->Cell(9, 4, utf8_decode('4'), 'L,B,T', 0, 'C', true);
        $pdf->SetTextColor(0, 93, 169);        
        $pdf->SetFillColor(153, 184, 223); 
        $pdf->Cell(25, 4, utf8_decode('CONTABILIDAD'), 'L,B,T', 0, 'L', true);
        $pdf->Cell(22, 4, utf8_decode('N° FACT.'), 'L,B,T', 0, 'L', true);
        $pdf->Cell(47, 4, utf8_decode('FECHA Y HORA'), 'L,B,R,T', 0, 'L', true);
        $pdf->Ln(); 
        $pdf->Cell(9, 6, utf8_decode(''), 'L,B', 0, 'C');
        $pdf->Cell(47, 6, utf8_decode(''), 'B', 0, 'L');
        $pdf->Cell(47, 6, utf8_decode(''), 'B', 0, 'L');
        $pdf->Cell(9, 6, utf8_decode(''), 'L,B', 0, 'C');
        $pdf->Cell(47, 6, utf8_decode(''), 'B', 0, 'L');
        $pdf->Cell(47, 6, utf8_decode(''), 'B,R', 0, 'L');
        $pdf->Ln();
         
        if ($previsualizacion) {
            $pdf->Output(); 
        } else {
            $nombreFile = 'HA' . '_' . (string) $id . '_' . (string) $idgrupodx;

            $pdf->Output('F', 'atenciones/' . $nombreFile . '.pdf');              

            if (file_exists($pdf->pathImg . $nombreFile . '.pdf')) 
            {
                $mensaje = array('generado' => 1, 'mensaje' => $nombreFile);
            } else 
            {
                $mensaje = array('generado' => 0, 'mensaje' => 'PDF no se genero');
            }
            
            return $mensaje;
        }
    }    
}