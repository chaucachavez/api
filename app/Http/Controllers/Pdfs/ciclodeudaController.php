<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa;
use App\Models\sede;  
use App\Models\entidad; 
use App\Models\cicloatencion;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 

class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'DEUDAS EN CICLOS DE ATENCIONES'; 
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
        $this->Cell(20, 4, $this->sede ? 'Sede:': '', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4,  $this->sede ? $this->sede->nombre: '', $this->borde);
        $this->Ln(); 
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
        
        $turno = ''; 
         
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(13, 6, 'Desde: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(20, 6, $this->request['desde'], 0);  
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(13, 6, 'Hasta: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, $this->request['hasta'], 0);  

        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6); 
        $this->SetLineWidth(0.2);

        if($this->cabeceratabla){

            /*Cabecera de tabla*/
            $this->SetLineWidth(0.2);
            $this->SetTextColor(255, 255, 255);
            $this->SetDrawColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 8); //Width disponible: 291
            $this->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
            $this->Cell(30, 10, utf8_decode('Sede'), 1, 0, 'C', true); 
            $this->Cell(15, 10, utf8_decode('Ciclo'), 1, 0, 'C', true); 
            $this->Cell(60, 10, utf8_decode('Paciente'), 1, 0, 'L', true);     
            $this->Cell(20, 10, utf8_decode('F.Apertura'), 1, 0, 'C', true); 
            $this->Cell(20, 10, utf8_decode('F.Cierre'), 1, 0, 'C', true); 
            $this->Cell(20, 10, utf8_decode('Estado'), 1, 0, 'L', true);      
            $this->Cell(20, 10, utf8_decode('Primera asist.'), 'T,R,L', 0, 'C', true);  
            $this->Cell(20, 10, utf8_decode('Última asist.'), 'T,R,L', 0, 'C', true);  
            $this->Cell(60, 5, utf8_decode('Presupuesto'), 'T,R,L', 0, 'C', true);  
            $this->Cell(20, 10, utf8_decode('Deuda'), 'T,R,L', 0, 'R', true); 
            $this->Ln();
                     
            $this->setY($this->GetY() - 5);
            $this->setX(214);
             
            $this->Cell(20, 5, utf8_decode('Costo'), 'T,R,L', 0, 'R', true);  
            $this->Cell(20, 5, utf8_decode('Pagó'), 'T,R,L', 0, 'R', true);  
            $this->Cell(20, 5, utf8_decode('Efectuado'), 'T,R,L', 0, 'R', true);  
            $this->Ln();  
        } 

        
    } 
}

class ciclodeudaController extends Controller 
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
        $cicloatencion = new cicloatencion();  

        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {    
            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')];            
        }

        $param = [];  
        if (isset($request['idsede']) && !empty($request['idsede'])) {
            $this->pdf->sede = sede::find($request['idsede']);
            $param['cicloatencion.idsede'] = $request['idsede'];
        }
         
        $datadeudas = $cicloatencion->grid($param, '', $between, '', '', '', true, [], false, false, false, false, false, [], true); 
        // dd($datadeudas); 
               
        $this->pdf->request = $request; 
        $this->pdf->AddPage('L');         
            
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        $i = 1;  
        //  dd($datadeudas);
        $totaldeuda = 0;
        foreach ($datadeudas as $row) { 
            $deuda = $row->montoefectuado - $row->montopago;
            $totaldeuda = $totaldeuda + $deuda;

            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(30, 5, $row->sedenombre, 1, 0, 'L', true);
            $this->pdf->Cell(15, 5, $row->idcicloatencion, 1, 0, 'C', true);
            $this->pdf->Cell(60, 5, ucwords(strtolower(utf8_decode($row->paciente))), 1, 0, 'L', true); 
            $this->pdf->Cell(20, 5, $row->fecha, 1, 0, 'C', true);
            $this->pdf->Cell(20, 5, $row->fechacierre, 1, 0, 'C', true);
            $this->pdf->Cell(20, 5, $row->estadociclo, 1, 0, 'L', true);  
            $this->pdf->Cell(20, 5, $row->primert, 1, 0, 'C', true);
            $this->pdf->Cell(20, 5, $row->ultimot, 1, 0, 'C', true);
            $this->pdf->Cell(20, 5, $row->total, 1, 0, 'R', true);
            $this->pdf->Cell(20, 5, $row->montopago, 1, 0, 'R', true);
            $this->pdf->Cell(20, 5, $row->montoefectuado, 1, 0, 'R', true);
            $this->pdf->SetTextColor(213, 48, 50);
            $this->pdf->Cell(20, 5, number_format($deuda, 2, '.', ','), 1, 0, 'R', true); 
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Ln();  
        }  

        if(count($datadeudas) === 0){ 
            $this->pdf->Cell( intval($this->pdf->GetPageWidth()) === 210 ? 204 : (intval($this->pdf->GetPageWidth()) === 297 ? 291 : 0) , 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        } else {   
            $this->pdf->SetFillColor(220, 220, 220);
            $this->pdf->Cell(271, 5, '', 1, 0, 'C', true);   
            $this->pdf->SetTextColor(213, 48, 50);
            $this->pdf->Cell(20, 5, number_format($totaldeuda, 2, '.', ','), 1, 0, 'R', true); 
            $this->pdf->Ln(); 
        }

        $this->pdf->Output();           
    } 

}
