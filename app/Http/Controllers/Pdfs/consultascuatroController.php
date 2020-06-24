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
    public $wcelda = 20;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'CONSULTAS ATENDIDAS POR DIAGNÓSTICO'; 
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
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(22, 6, 'Estado cita: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, utf8_decode('Atendido'), 0);  

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
        $this->Cell(178, 10, utf8_decode('Diagnóstico'), 1, 0, 'L', true);  
        $this->Cell($this->wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true); 
        $this->Ln();  
    }
}

class consultascuatroController extends Controller 
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
            'citamedica.idempresa' => $idempresa,
            'citamedica.idestado' => 6 //Atendido 
        );

        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {    
            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 
        }

        $datacita = $objCitamedica->grid($param, $between, '', '', 'citamedica.fecha', 'asc'); 
        $quiebre = array('iddiagnostico'=>'diagnostico');                
        $database = $this->agruparPorColumna($datacita, '', $quiebre, ['iddiagnostico'=>'diagnostico']);                         
        
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
        $totalgeneral = 0;         
        $database = $this->ordenarMultidimension($database, 'cantidad', SORT_DESC);
        foreach ($database as $row) {  
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(178, 5, !empty($row['diagnostico'])?utf8_decode($row['diagnostico']):utf8_decode('(Vacío)'), 1, 0, 'L', true);   
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad']; 
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(184, 5, utf8_decode('Total general'), 1, 0, 'C', true);                     
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalgeneral > 0 ? $totalgeneral : '', 1, 0, 'C', true); 
            $this->pdf->Ln();
        } 


        /*Salida*/
        $this->pdf->Output();       
    }    
}
