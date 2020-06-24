<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\sede;
use App\Models\entidad;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
use \Firebase\JWT\JWT;

class PDF extends baseFpdf {
    
    public $printBy;
    public $web;
    public $borde = 0;
    
    function Footer() 
    {            
        $this->SetY(-10);
        $this->SetDrawColor(1, 87, 155); 
        $this->SetFont('Arial', 'I', 8);
                
        $this->Cell(70, 6, $this->web, $this->borde);
        $this->Cell(0, 6, utf8_decode('Página ') . $this->PageNo() . '/{nb}', $this->borde, 0, 'R');
        $this->Line(3, $this->GetY() , 207, $this->GetY());        
        $this->Ln();
        $this->Cell(0, 4, utf8_decode('Impresión: ').  date('d/m/Y H:i') . ' - '. utf8_decode($this->printBy), $this->borde);
    }    
    
    function Header()
    {
        // Logo
        $this->Image('https://sistemas.centromedicoosi.com/img/osi/logo.png', 3, 3, 40, 0, 'PNG');
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30,10,'Title',1,0,'C');
        // Line break
        $this->Ln(20);
    }    
}

class templateController extends Controller {
     
    
    public function __construct(Request $request) {        
        /* $this->pdf = new PDF('P', 'mm', [210, 297]); 
         * Ancho disponible: 204 = 210 - 6 */  
        
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        
        $this->getToken($request);
        
        $this->pdf = new PDF();       
        $this->pdf->SetMargins(3, 3, 3);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->AliasNbPages(); 
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial', '', 12);
        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]);   
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => $this->objTtoken->myenterprise]);   
        
        $this->pdf->printBy = $this->entidad->entidad;        
        $this->pdf->web = $this->empresa->paginaweb;
    }
    
    public function holamundo(request $request, $enterprise){        
        for($i=1;$i<=300;$i++)
            $this->pdf->Cell(0,5,"Line $i",0,1);
        
        $this->pdf->Output();
    }     
}
