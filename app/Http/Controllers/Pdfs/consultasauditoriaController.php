<?php
namespace App\Http\Controllers\Pdfs;
 
use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\citamedica;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
use App\Http\Controllers\Controller; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'AUDITORIA DE ATENCIÓN MÉDICA Y ADHERENCIA A GPC'; 
    public $apertura;
    public $medicos;
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
        $this->Cell(240);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, 'Sede:', $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->apertura->nombresede, $this->borde);
        $this->Ln();
        $this->Cell(240);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 4, utf8_decode('Caja:'), $this->borde);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->apertura->idapertura, $this->borde);
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
        
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(21, 6, 'Resp. cierre: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(45, 6, ucwords(strtolower(utf8_decode($this->apertura->personalcierre))), 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(11, 6, 'Fecha: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(20, 6, $this->apertura->fechacierre, 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(10, 6, 'Hora: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(18, 6, $this->apertura->horacierre, 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 6, utf8_decode('Médico: '), 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(50, 6, ucwords(strtolower(utf8_decode($this->medicos))), 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(24, 6, utf8_decode('F. Auditoría:'), 0, 0, 'R');
        $this->SetFont('Arial', ''); 
        $this->Cell(22, 6, '', 'B');  
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 6, utf8_decode('Resp. Auditoría:'), 0, 0, 'R');
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, '', 'B');  

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
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(186, 5);   
        $this->SetFillColor(0, 153, 153);
        $this->Cell(105, 5, utf8_decode('AUDITORIA'), 1, 0, 'C', true);  
        $this->Ln();
        $this->SetFillColor(1, 87, 155); 
        $this->Cell(123, 5);  
        $this->Cell(63, 5, utf8_decode('Tratamientos por el médico'), 1, 0, 'C', true);   
        $this->SetFillColor(0, 153, 153);
        $this->Cell(56, 5, utf8_decode('HC'), 1, 0, 'C', true);  
        $this->Cell(28, 5, utf8_decode('Siteds'), 1, 0, 'C', true);
        $this->Cell(7, 5, utf8_decode('Lab.'), 'R,T,L', 0, 'C', true);
        $this->Cell(7, 5, utf8_decode('I.CM'), 'R,T,L', 0, 'C', true);
        $this->Cell(7, 15, utf8_decode('GPC'), 1, 0, 'C', true);
        $this->Ln();    
        $this->setY($this->getY() - 10);
        $this->SetFillColor(1, 87, 155); 
        $this->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);  
        $this->Cell(13, 10, utf8_decode('Hora'), 1, 0, 'C', true); 
        $this->Cell(10, 10, utf8_decode('HC'), 1, 0, 'C', true); 
        $this->Cell(35, 10, utf8_decode('Paciente'), 1, 0, 'L', true); 
        $this->Cell(10, 10, utf8_decode('Ciclo'), 1, 0, 'C', true); 
        $this->Cell(29, 10, utf8_decode('Diagnóstico'), 1, 0, 'L', true); 
        $this->Cell(20, 10, utf8_decode('Seguro'), 1, 0, 'L', true);           
        $this->Cell(7, 10, utf8_decode('Tf'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Ac'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Qt'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Och'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Esp'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Bl'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Bmg'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Otros'), 1, 0, 'C', true); 
        $this->Cell(7, 10, utf8_decode('Aguja'), 1, 0, 'C', true);  
        $this->SetFillColor(0, 153, 153);
        $this->Cell(7, 5, utf8_decode('Enf.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 10, utf8_decode('Ant.'), 1, 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Ex.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Dx.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Dx.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 10, utf8_decode('Sello'), 1, 0, 'C', true);  
        $this->Cell(7, 10, utf8_decode('Firm'), 1, 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Let.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Dx.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Dx.'), 'R,T,L', 0, 'C', true);  
        $this->Cell(7, 10, utf8_decode('Sello'), 1, 0, 'C', true);  
        $this->Cell(7, 10, utf8_decode('Firma'), 1, 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Just'), 'R,L', 0, 'C', true); 
        $this->Cell(7, 5, utf8_decode('Just'), 'R,L', 0, 'C', true); 
        $this->Ln();
         
        $this->setX(189); 
        $this->Cell(7, 5, utf8_decode('Act.'), 'R,B,L', 0, 'C', true);  
        $this->setX($this->getX() + 7);
        $this->Cell(7, 5, utf8_decode('Fisic'), 'R,B,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('CIE'), 'R,B,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Escrit'), 'R,B,L', 0, 'C', true);  
        $this->setX($this->getX() + 14);
        $this->Cell(7, 5, utf8_decode('Leg.'), 'R,B,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('CIE'), 'R,B,L', 0, 'C', true);  
        $this->Cell(7, 5, utf8_decode('Escrit'), 'R,B,L', 0, 'C', true);   
        $this->setX($this->getX() + 14);
        $this->Cell(7, 5, utf8_decode(''), 'R,B,L', 0, 'C', true);   
        $this->Cell(7, 5, utf8_decode(''), 'R,B,L', 0, 'C', true);

        $this->Ln();   
        // dd($datacita);
    }
}

class consultasauditoriaController extends Controller 
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
    
    public function reporte(Request $request, $enterprise, $id)
    {   
        //A4: 297 x 
        $objSede = new sede(); 
        $objCitamedica = new citamedica();

        $request = $request->all();
     
        $param = array(
            'citamedica.idapertura' => $id,
            'citamedica.idestado' => 6
        );
        $datacita = $objCitamedica->grid($param, '', '', '', 'cliente.entidad', 'asc', [], false, [], false, false, 'citamedica.fecha', '', true);
        $datacita = $this->devolverTratamientos($datacita); 
        
        $medicos = [];
        foreach($datacita as $row) {
            if(!in_array($row->medico, $medicos)) 
                $medicos[] = $row->medico; 
        }
        $medicos = implode(" | ", $medicos);
        $apertura = $objSede->apertura(['apertura.idapertura' => $id]);  
        $this->pdf->medicos = $medicos;  
        $this->pdf->apertura = $apertura;
        $this->pdf->AddPage('L');         
            
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1;
        foreach ($datacita as $row) {
         
            $otros = ($row->ESP + $row->BL + $row->BMG + $row->OTROS);
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true); 
            $this->pdf->Cell(13, 5, $this->convertAmPm($row->inicio), 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row->hc, 1, 0, 'C', true);
            $this->pdf->Cell(35, 5, ucwords(strtolower(utf8_decode($row->paciente))) , 1, 0, 'L', true); 
            $this->pdf->Cell(10, 5, $row->idcicloatencion, 1, 0, 'C', true); 
            $this->pdf->Cell(29, 5, utf8_decode($row->diagnostico), 1, 0, 'L', true); 
            $this->pdf->Cell(20, 5, utf8_decode($row->nombreaseguradoraplan), 1, 0, 'L', true);  
            $this->pdf->Cell(7, 5, $row->TF > 0 ? $row->TF : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->AC > 0 ? $row->AC : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->QT > 0 ? $row->QT : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->OCH > 0 ? $row->OCH : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->ESP > 0 ? $row->ESP : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->BL > 0 ? $row->BL : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->BMG > 0 ? $row->BMG : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->OTROS > 0 ? $row->OTROS : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, $row->AGUJA > 0 ? $row->AGUJA : '', 1, 0, 'C', true);
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);  
            $this->pdf->Cell(7, 5, '', 1, 0, 'C', true);   
            $this->pdf->Ln();
        }  

        if(count($datacita) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }
 
        $this->pdf->Ln();  
        $this->pdf->SetFont('Arial', 'BU', 9);
        $this->pdf->Cell(13, 5, 'Observaciones:', 0); 
        $this->pdf->Ln();
        $this->pdf->SetDrawColor(0, 0, 0); 
        $this->pdf->Cell(0, 7, '', 'B');
        $this->pdf->Ln();
        $this->pdf->Cell(0, 7, '', 'B');
        // dd($datacita); 
        $this->pdf->Output();       
    } 

    private function devolverTratamientos($datacita) {

        $whereIdcitamedicaIn = array();
        $whereIdcicloatencionIn = array();
        foreach($datacita as $row){
            $whereIdcitamedicaIn[] = $row->idcitamedica;
            if($row->idcicloatencion)
                $whereIdcicloatencionIn[] = $row->idcicloatencion;
        }

        //Autorizacion valida('1') de Fisioterapia(2)
        $coaseguos = \DB::table('cicloautorizacion')
            ->select('cicloautorizacion.idcicloatencion', 'aseguradora.nombre as nombreaseguradora', 'cicloautorizacion.deducible', 
                        'cicloautorizacion.coaseguro', 'aseguradoraplan.nombre as nombreaseguradoraplan')
            ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora') 
            ->leftJoin('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
            ->where(array('cicloautorizacion.idproducto' => 2))
            ->whereIn('cicloautorizacion.idcicloatencion', $whereIdcicloatencionIn)
            ->whereNull('cicloautorizacion.deleted') 
            ->get()->all(); 

        
        $productos = \DB::table('tratamientomedico') 
                ->select('tratamientomedico.idcitamedica', 'tratamientomedico.cantidad', 'tratamientomedico.idproducto', 'tratamientomedico.parentcantidad')    
                ->whereIn('tratamientomedico.idcitamedica', $whereIdcitamedicaIn) 
                ->whereNull('tratamientomedico.deleted')
                ->get()->all();
        
        foreach($productos as $row){
            if (!empty($row->parentcantidad)) 
                $row->cantidad = $row->cantidad * $row->parentcantidad; 
        }
        
        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA','*'=>'OTROS')];
        $quiebre = array('idcitamedica' => 'idcitamedica');        
        $datatratxterapista = $this->agruparPorColumna($productos, '', $quiebre, '', $gruposProducto);    
                
        $data = array();
        foreach($datatratxterapista as $row){ 
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }  
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        }
                
        foreach($datacita as $row){ 
            foreach($gruposProducto[1] as $val){
                $row->$val = null;
                if(isset($data[$row->idcitamedica])){
                    $row->$val = $data[$row->idcitamedica][$val];
                } 
            }  

            //Añadir coaseguro de FISIOTERAPIA  
            $tmpcoa = null;
            foreach($coaseguos as $val){
                if($val->idcicloatencion === $row->idcicloatencion){
                    $tmpcoa = $val; 
                    break;
                }
            }    
            
            $row->nombreaseguradora = $tmpcoa ? $tmpcoa->nombreaseguradora : null;
            $row->nombreaseguradoraplan = $tmpcoa ? $tmpcoa->nombreaseguradoraplan : null;
        } 

        return $datacita;
    } 

}
