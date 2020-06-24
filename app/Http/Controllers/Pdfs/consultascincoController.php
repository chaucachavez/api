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
    public $titulo = 'CONSULTAS POR REFERENCIA Y ASEGURADORA'; 
    public $request;  
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
        
        
        switch ($this->request['estadopago']) {
            case '1':
                $estado = 'Pagado';
                break;
            case '0':
                $estado = 'No pagado';
                break; 
            default:
                $estado = 'Todos';
                break;
        }
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 6, 'Desde: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(23, 6, $this->request['desde'], 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 6, 'Hasta: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(23, 6, $this->request['hasta'], 0); 
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(22, 6, 'Estado pago: ', 0);
        $this->SetFont('Arial', ''); 
        $this->Cell(0, 6, utf8_decode($estado), 0);  

        $this->Ln();
        $this->Ln();
        $this->Ln();
        $this->SetLineWidth(0.4); 
        $this->Line(3, $this->GetY() - 6, intval($this->w) === 210 ? 207 : (intval($this->w) === 297 ? 294 : 0), $this->GetY() - 6);
        $this->SetLineWidth(0.2);    
    }
}

class consultascincoController extends Controller 
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
            'citamedica.idempresa' => $idempresa 
        );

        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {    
            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 
        }

        $pendiente = false;
        $pagado = false;
        if (isset($request['estadopago'])) {
            if ($request['estadopago'] === '0') //Pendiente
                $pendiente = true;
            if ($request['estadopago'] === '1') //Pagado
                $pagado = true;
        }
 
        $datacita = $objCitamedica->grid($param, $between, '', '', 'citamedica.fecha', 'asc', [], false, [], $pendiente, $pagado);
        $datacita = $this->devolverTratamientos($datacita);
       
        $aseguradoras = [];
        $aseguradorasvalor = []; 
        foreach($datacita as $row){ 
            $nombreaseguradora = !empty($row->nombreaseguradora) ? $row->nombreaseguradora : "(Particular)";    
            $aseguradoras[$nombreaseguradora] = $nombreaseguradora; //No pongo el id porque la consulta no trae 

            switch ($row->idestado) {
                case 6: $estadocita = 'ATENDIDO'; break; 
                case 7: $estadocita = 'CANCELADO'; break; 
                case 48: $estadocita = 'NOASISTIO'; break; 
                case 4: $estadocita = 'PORATENDER'; 
                case 5: $estadocita = 'PORATENDER'; break;   
            }          

            if(!isset($aseguradorasvalor[$row->idreferencia][$nombreaseguradora][$estadocita]))
                $aseguradorasvalor[$row->idreferencia][$nombreaseguradora][$estadocita] = 0; 
            
            $aseguradorasvalor[$row->idreferencia][$nombreaseguradora][$estadocita] += 1;  
        }  
        
        
        $quiebre = array('idreferencia' => 'nombrereferencia');                
        $database = $this->agruparPorColumna($datacita, '', $quiebre, ['idreferencia'=>'nombrereferencia']);        
        $datacitaxreferencia = $this->agruparPorColumna($datacita, '', $quiebre, '', ['idestado', array('6'=>'ATENDIDO','7'=>'CANCELADO', '48'=>'NOASISTIO', '4,5'=>'PORATENDER')]);
         
        $data3 = array();
        foreach($datacitaxreferencia as $row){  
            if(!isset($data3[$row['idquiebre']])) { 
                $data3[$row['idquiebre']]['ATENDIDO'] = null;  
                $data3[$row['idquiebre']]['CANCELADO'] = null;  
                $data3[$row['idquiebre']]['NOASISTIO'] = null; 
                $data3[$row['idquiebre']]['PORATENDER'] = null; 
            }
            $data3[$row['idquiebre']][$row['grupo']] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
        } 
        
        $i = 0;
        foreach($database as $row){    
            foreach($data3[$row['quiebre']] as $pk => $row2){ 
                $database[$i][$pk] = $row2;
            }  

            //Aseguradoras
            $j = 0;
            foreach($aseguradoras as $aseguradora){ 
                $database[$i]['ASEGURADORAS'][$j]['nombre']  = $aseguradora; 
                $database[$i]['ASEGURADORAS'][$j]['ATENDIDO']  = isset($aseguradorasvalor[$row['quiebre']][$aseguradora]['ATENDIDO']) ? $aseguradorasvalor[$row['quiebre']][$aseguradora]['ATENDIDO'] : 0;
                $database[$i]['ASEGURADORAS'][$j]['CANCELADO']  = isset($aseguradorasvalor[$row['quiebre']][$aseguradora]['CANCELADO']) ? $aseguradorasvalor[$row['quiebre']][$aseguradora]['CANCELADO'] : 0;
                $database[$i]['ASEGURADORAS'][$j]['NOASISTIO']  = isset($aseguradorasvalor[$row['quiebre']][$aseguradora]['NOASISTIO']) ? $aseguradorasvalor[$row['quiebre']][$aseguradora]['NOASISTIO'] : 0;
                $database[$i]['ASEGURADORAS'][$j]['PORATENDER']  = isset($aseguradorasvalor[$row['quiebre']][$aseguradora]['PORATENDER']) ? $aseguradorasvalor[$row['quiebre']][$aseguradora]['PORATENDER'] : 0;

                $total = $database[$i]['ASEGURADORAS'][$j]['ATENDIDO'] + $database[$i]['ASEGURADORAS'][$j]['CANCELADO'] + $database[$i]['ASEGURADORAS'][$j]['NOASISTIO'] + $database[$i]['ASEGURADORAS'][$j]['PORATENDER'];
                $database[$i]['ASEGURADORAS'][$j]['total']  = $total;
                $j = $j + 1;
            } 

            $i = $i + 1;
        }   
        
        //Start Logotipo
        $empresaTmp = $empresa->empresa(['empresa.idempresa' => $idempresa]);  
        $this->pdf->fillColor = explode(",", $empresaTmp->fondocolor);
        $this->pdf->web = $empresaTmp->paginaweb;
        $this->pdf->logo = $empresaTmp->url.'/'.$empresaTmp->imglogologin;  
        //Fin Logotipo
        
        $this->pdf->request = $request; 
        
        /*Reporte TD5*/
        $this->pdf->AddPage('P');         
         
        /*Cabecera de tabla*/ 
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7); 
        $this->pdf->SetFillColor(1, 87, 155); 
        $this->pdf->Cell(6, 10, utf8_decode('N°'), 1, 0, 'C', true);   
        $this->pdf->Cell(50, 10, utf8_decode('Referencia / Aseguradora'), 1, 0, 'L', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Atendido'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Cancelado'), 1, 0, 'C', true);
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('No asistió'), 1, 0, 'C', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Por atender'), 1, 0, 'C', true); 
        $this->pdf->Cell($this->pdf->wcelda, 10, utf8_decode('Total'), 1, 0, 'C', true);
        $this->pdf->Ln(); 
 
        /*Cuerpo de tabla*/
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $i = 1; 
        $totalgeneral = 0; 
        $totalatendido = 0; 
        $totalcancelado = 0; 
        $totalnoasistio = 0; 
        $totalporatender = 0;         
        $database = $this->ordenarMultidimension($database, 'cantidad', SORT_DESC);
        foreach ($database as $row) {  
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(6, 5, $i++, 1, 0, 'C', true);  
            $this->pdf->Cell(50, 5, !empty($row['nombrereferencia'])?utf8_decode($row['nombrereferencia']):utf8_decode('(Vacío)'), 1, 0, 'L', true);  
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['ATENDIDO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['CANCELADO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['NOASISTIO'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['PORATENDER'], 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $row['cantidad'], 1, 0, 'C', true);
            $this->pdf->Ln();

            $totalgeneral += $row['cantidad'];
            $totalatendido += $row['ATENDIDO'];
            $totalcancelado += $row['CANCELADO'];
            $totalnoasistio += $row['NOASISTIO'];
            $totalporatender += $row['PORATENDER'];

            $this->pdf->SetFont('Arial', 'I', 7);
            foreach ($row['ASEGURADORAS'] as $fila) { 
                if ($fila['total'] > 0) {
                    $this->pdf->Cell(6, 5, '', 'T,B,L', 0, 'C', true);  
                    $this->pdf->Cell(50, 5, utf8_decode($fila['nombre']), 'T,R,B', 0, 'L', true);  
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['ATENDIDO'] > 0 ? $fila['ATENDIDO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['CANCELADO'] > 0 ? $fila['CANCELADO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['NOASISTIO'] > 0 ? $fila['NOASISTIO'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['PORATENDER'] > 0 ? $fila['PORATENDER'] : '', 1, 0, 'C', true); 
                    $this->pdf->Cell($this->pdf->wcelda, 5, $fila['total'] > 0 ? $fila['total'] : '', 1, 0, 'C', true);
                    $this->pdf->Ln();
                }
            }
        }  

        if(count($database) === 0){
            $this->pdf->Cell(291, 5, 'No hay registros.', 1, 0, 'C', true); 
            $this->pdf->Ln();
        }else { 
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(56, 5, utf8_decode('Total general'), 1, 0, 'C', true);                    
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalatendido > 0 ? $totalatendido : '', 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalcancelado > 0 ? $totalcancelado : '', 1, 0, 'C', true);
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalnoasistio > 0 ? $totalnoasistio : '', 1, 0, 'C', true); 
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalporatender > 0 ? $totalporatender : '', 1, 0, 'C', true);
            $this->pdf->Cell($this->pdf->wcelda, 5, $totalgeneral > 0 ? $totalgeneral : '', 1, 0, 'C', true); 
            $this->pdf->Ln();
        } 


        /*Salida*/
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
