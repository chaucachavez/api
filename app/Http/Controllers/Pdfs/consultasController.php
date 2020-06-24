<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\empresa;
use App\Models\sede;  
use App\Models\entidad; 
use App\Models\citamedica;  
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf; 
  
class PDF extends baseFpdf 
{    
    public $printBy;
    public $web;
    public $borde = 0; 
 
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';
    public $titulo = 'REPORTE DE CONSULTA MÉDICA'; 
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
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(13, 6, 'Cierre: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(80, 6, ucwords(strtolower(utf8_decode($this->apertura->personalcierre))), 0); 
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(26, 6, 'Fecha cierre: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(25, 6, $this->apertura->fechacierre, 0); 
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(25, 6, 'Hora cierre: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(40, 6, $this->apertura->horacierre, 0); 
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 6, utf8_decode('Personal: '), 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, ucwords(strtolower(utf8_decode($this->medicos))), 0); 

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
        $this->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);  
        $this->Cell(10, 10, utf8_decode('HC'), 1, 0, 'C', true); 
        $this->Cell(40, 10, utf8_decode('Paciente'), 1, 0, 'L', true); 
        $this->Cell(17, 10, utf8_decode('F.Cita'), 1, 0, 'C', true); 
        $this->Cell(25, 10, utf8_decode('Seguro'), 1, 0, 'L', true); 
        $this->Cell(38, 10, utf8_decode('Diagnóstico'), 1, 0, 'L', true); 
        $this->Cell(7, 10, utf8_decode('Tipo'), 1, 0, 'C', true); 
        $this->Cell(50, 5, utf8_decode('Tratamientos por el médico'), 1, 0, 'C', true); 
        $this->Cell(50, 5, utf8_decode('Pagó el dia de la consulta'), 1, 0, 'C', true); 
        $this->Cell(21, 5, utf8_decode('Doc. Venta'), 1, 0, 'C', true); 
        $this->Cell(27, 5, utf8_decode('Control'), 1, 0, 'C', true);  
        $this->Ln();
         
        $this->setX(146);
        $this->Cell(10, 5, utf8_decode('TF'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('AC'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('QT'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('OCH'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('Otros'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('TF'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('AC'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('QT'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('OCH'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('Otros'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('Afil.'), 1, 0, 'C', true); 
        $this->Cell(11, 5, utf8_decode('Nro.'), 1, 0, 'C', true); 
        $this->Cell(10, 5, utf8_decode('Resp.'), 1, 0, 'L', true); 
        $this->Cell(17, 5, utf8_decode('Fecha'), 1, 0, 'L', true);  
        $this->Ln();   
        // dd($datacita);
    }
}

class consultasController extends Controller 
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
        $datacita = $this->devolverTratamientosPagados($datacita, ['venta.idapertura' => $id, 'venta.idestadodocumento' => '27']);
        $datacita = $this->ordenarMultidimension($datacita, 'idmedico', SORT_ASC);
        // $medicos = [];
        // foreach($datacita as $row) {
        //     if(!in_array($row->medico, $medicos)) 
        //         $medicos[] = $row->medico; 
        // }
        // $medicos = implode(" | ", $medicos);
     
        $apertura = $objSede->apertura(['apertura.idapertura' => $id]);  
        $this->pdf->medicos = count($datacita) > 0 ? $datacita[0]->medico : null; //$medicos;  
        $this->pdf->apertura = $apertura;
        $this->pdf->AddPage('L');
        
        /*Tratamientos del presupuesto*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
        $i = 1;
 
        // dd($datacita);
        $idmedicoTemp = count($datacita) > 0 ? $datacita[0]->idmedico : null; //$medicos;  
        foreach ($datacita as $row) {

            if($idmedicoTemp !== $row->idmedico) {
                $i = 1;
                $this->pdf->medicos = $row->medico;  
                $this->pdf->AddPage('L');
            }
            
            $tipo = '';
            if($row->idtipo === 42)
                $tipo = 'C';
            if($row->idtipo === 43)
                $tipo = 'N';
            if($row->idtipo === 44)
                $tipo = 'R';
            $otros = ($row->ESP + $row->BL + $row->BMG + $row->OTROS);
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true); 
            $this->pdf->Cell(10, 5, $row->hc, 1, 0, 'C', true);
            $this->pdf->Cell(40, 5, ucwords(strtolower(utf8_decode($row->paciente))) , 1, 0, 'L', true); 
            $this->pdf->Cell(17, 5, $row->fecha, 1, 0, 'C', true); 
            $this->pdf->Cell(25, 5, utf8_decode($row->nombreaseguradoraplan), 1, 0, 'L', true); 
            $this->pdf->Cell(38, 5, utf8_decode($row->diagnostico), 1, 0, 'L', true); 
            $this->pdf->Cell(7, 5, $tipo, 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row->TF > 0 ? $row->TF : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row->AC > 0 ? $row->AC : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row->QT > 0 ? $row->QT : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $row->OCH > 0 ? $row->OCH : '', 1, 0, 'C', true);
            $this->pdf->Cell(10, 5, $otros > 0 ? $otros : '', 1, 0, 'C', true);
                        
            if(!empty($row->AcuentaVTA) && $row->AcuentaVTA > 0){
                $this->pdf->Cell(50, 5,'**Acuenta**', 1, 0, 'C', true);
            } else {
                $otros = ($row->ESPVTA + $row->BLVTA + $row->BMGVTA + $row->OTROSVTA);
                // dd($otros);
                $this->pdf->Cell(10, 5, $row->TFVTA > 0 ? $row->TFVTA : '', 1, 0, 'C', true);
                $this->pdf->Cell(10, 5, $row->ACVTA > 0 ? $row->ACVTA : '', 1, 0, 'C', true);
                $this->pdf->Cell(10, 5, $row->QTVTA > 0 ? $row->QTVTA : '', 1, 0, 'C', true);
                $this->pdf->Cell(10, 5, $row->OCHVTA > 0 ? $row->OCHVTA : '', 1, 0, 'C', true);
                $this->pdf->Cell(10, 5, $otros > 0 ? $otros : '', 1, 0, 'C', true);
            } 
            

            $this->pdf->Cell(10, 5,$row->acronimo, 1, 0, 'C', true);
            $this->pdf->Cell(11, 5,$row->serienumero, 1, 0, 'C', true);
            $this->pdf->Cell(10, 5,$row->acronimoctrol, 1, 0, 'C', true);
            $this->pdf->Cell(17, 5,$row->fechactrol, 1, 0, 'C', true);
            $this->pdf->Ln();
 
            $idmedicoTemp = $row->idmedico;
        }  

        if(count($datacita) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
        }

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
        //, 'cicloautorizacion.principal' => '1'
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

    private function devolverTratamientosPagados($datacita, $where, $sufijo = 'VTA') {
 
        $productos = \DB::table('ventadet') 
                ->join('venta', 'venta.idventa', '=', 'ventadet.idventa')
                ->select('ventadet.idventa', 'ventadet.cantidad', 'ventadet.idproducto', 'venta.idcicloatencion')                    
                ->where($where)   
                ->whereNull('venta.deleted')                
                ->whereNull('ventadet.idcitamedica')
                ->whereNull('ventadet.deleted')                
                ->get()->all();

        //dd($productos); 
        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA', 22=>'Acuenta', '*'=>'OTROS')];
        $quiebre = array('idcicloatencion' => 'idcicloatencion');       
        
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
                $tmp = $val . $sufijo;
                $row->$tmp = null;
                if(isset($data[$row->idcicloatencion])){
                    $row->$tmp = $data[$row->idcicloatencion][$val];
                } 
            }   
        } 
        // dd($datacita);  
        return $datacita;
    }

}
