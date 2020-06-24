<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa; 
use App\Models\sede; 
use App\Models\entidad; 
use App\Models\movimiento;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'INGRESOS Y EGRESOS OTROS DE CAJA'; 
    public $apertura;
    public $nombresede;
    function Footer() 
    {            
        $this->SetY(-10);
        $this->SetDrawColor(1, 87, 155); 
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0, 0, 0);

        $this->Line(3, $this->GetY() , 294, $this->GetY());  
        
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
        $this->Cell(250);        
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(12, 4, !empty($this->nombresede) ? 'Sede:' : '', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->nombresede, $this->borde);        
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
        $this->Line(3, $this->GetY() - 6, 294, $this->GetY() - 6);
        $this->SetLineWidth(0.2);
        
        $fecha = $this->request['desde'] === $this->request['hasta'] ? $this->request['desde'] : ($this->request['desde'] .' al '.$this->request['hasta']);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(13, 6, 'Fecha: ', 0);
        $this->SetFont('Arial', '');  
        $this->Cell(0, 6, $fecha);  

        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, 294, $this->GetY() - 6); 
        $this->SetLineWidth(0.2);

        /*Cabecera de tabla*/
        $this->SetLineWidth(0.2);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(6, 7, utf8_decode('N°'), 1, 0, 'C', true);  
        $this->Cell(26, 7, utf8_decode('Sede'), 1, 0, 'C', true);   
        $this->Cell(10, 7, utf8_decode('Caja'), 1, 0, 'C', true); 
        $this->Cell(12, 7, utf8_decode('Tipo'), 1, 0, 'L', true); 
        $this->Cell(16, 7, utf8_decode('Fecha'), 1, 0, 'L', true); 
        $this->Cell(30, 7, utf8_decode('Personal'), 1, 0, 'L', true); 
        $this->Cell(30, 7, utf8_decode('Proveedor'), 1, 0, 'L', true); 
        $this->Cell(22, 7, utf8_decode('Documento'), 1, 0, 'C', true); 
        $this->Cell(13, 7, utf8_decode('Número'), 1, 0, 'C', true);  
        $this->Cell(5, 7, utf8_decode('C'), 1, 0, 'C', true); 
        $this->Cell(20, 7, utf8_decode('Gasto'), 1, 0, 'L', true); 
        $this->Cell(56, 7, utf8_decode('Descripción'), 1, 0, 'C', true);          
        $this->Cell(30, 7, utf8_decode('Registrado'), 1, 0, 'C', true);  
        $this->Cell(15, 7, utf8_decode('Total'), 1, 0, 'C', true);  
        $this->Ln(); 
        // dd($datacita);
    }

}

class movimientoController extends Controller 
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
        //$this->empresa = $objEmpresa->empresa(['empresa.idempresa' => $this->objTtoken->myenterprise]);   
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);   
        
        $this->pdf->printBy = $this->entidad->entidad;        
        $this->pdf->web = $this->empresa->paginaweb;
        $this->pdf->logo = $this->empresa->url.'/'.$this->empresa->imglogologin;   
    }
    
    public function reporte(Request $request, $enterprise)
    {   
        //A4: 297 x  
        $movimiento = new movimiento();
        $sede = new sede();
 
        $request = $request->all();

        $param = array(
            'movimiento.idempresa' => $this->empresa->idempresa 
        );
 
        if (isset($request['idsede']) && !empty($request['idsede'])) { 
            $param['movimiento.idsede'] = $request['idsede'];  
            $sede = sede::find($request['idsede']);
            $this->pdf->nombresede = $sede->nombre;  
        }

        if (isset($request['tipo']) && !empty($request['tipo'])) {
            $param['movimiento.tipo'] = $request['tipo'];
            $this->pdf->titulo = $request['tipo'] === '1' ? 'INGRESOS OTROS A CAJA' : 'EGRESOS OTROS DE CAJA'; 
        }
        
        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {   
            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $data = $movimiento->grid($param, $between, $like);

         
        $this->pdf->request = $request;
        $this->pdf->AddPage('L');         
            
        /*Tratamientos del */
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        $i = 1;
        $total = 0;
        foreach ($data as $row) {  
            $total += $row->total;
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true); 
            $this->pdf->Cell(26, 5, $row->nombresede, 1, 0, 'L', true);  
            $this->pdf->Cell(10, 5, $row->idapertura, 1, 0, 'C', true);  
            $this->pdf->Cell(12, 5, $row->tipo === '1' ? 'Ingreso' : 'Egreso', 1, 0, 'L', true);  
            $this->pdf->Cell(16, 5, $row->fecha, 1, 0, 'L', true);  
            $this->pdf->Cell(30, 5, ucwords(strtolower(utf8_decode($row->entidad))), 1, 0, 'L', true);  
            $this->pdf->Cell(30, 5, ucwords(strtolower(utf8_decode($row->proveedor))), 1, 0, 'L', true); 
            $this->pdf->Cell(22, 5, $row->nombredocumento, 1, 0, 'L', true);  
            $this->pdf->Cell(13, 5, $row->numero, 1, 0, 'L', true);  
            $this->pdf->Cell(5, 5, $row->codigo, 1, 0, 'C', true);    
            $this->pdf->Cell(20, 5, utf8_decode($row->nombregasto), 1, 0, 'L', true);   
            $this->pdf->Cell(56, 5, utf8_decode(mb_substr($row->concepto, 0 , 50)), 1, 0, 'L', true);                          
            $this->pdf->Cell(30, 5, ucwords(strtolower(utf8_decode($row->personal))), 1, 0, 'L', true);              
            $this->pdf->Cell(15, 5, number_format($row->total, 2, '.', ','), 1, 0, 'R', true);   
            $this->pdf->Ln();
        }  

        if(count($data) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
        }else{ 
            $this->pdf->Cell(276, 10, 'Total: S/.', 1, 0, 'R', true);
            $this->pdf->Cell(15, 10, number_format($total, 2, '.', ','), 1, 0, 'R', true);  
        }        
        // dd($datacita); 
        $this->pdf->Output();       
    }  
}
