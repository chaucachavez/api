<?php
namespace App\Http\Controllers;

use Excel;
use App\Models\post;
use App\Models\sede;
use App\Models\venta;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\grupodx;
use App\Models\terapia;
use App\Models\producto;
use App\Models\asistencia;
use App\Models\citamedica;
use App\Exports\DataExport;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\ciclomovimiento;
use App\Models\citaterapeutica;
use App\Models\autorizacionterapia;

class terapiaController extends Controller {
    
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\firmas_terapia\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/firmas_terapia/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/firmas_terapia/';
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario.
         */
        $empresa = new empresa();
        $sede = new sede();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
            'sedehorarios' => $empresa->listaSedeshorarios($idempresa)
        );

        return $this->crearRespuesta($data, 200);
    }

    // $terapia = $ObjTerapia->terapia('', $param, $whereIn);

    public function index2(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $terapia = new terapia();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['terapia.idempresa'] = $idempresa;
        
        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['terapia.idsede'] = $paramsTMP['idsede'];
        }
        
        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'terapia.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['terapia.idestado'] = $paramsTMP['idestado'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['terapia.idpaciente'] = $paramsTMP['idpaciente'];
        }

        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }

        $notExists = false;
        if (isset($paramsTMP['notexists']) && !empty($paramsTMP['notexists'])) {
            $notExists = (boolean) $paramsTMP['notexists'];
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
                        
        $datacita = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);        
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) { 
            $total = $datacita->total();
            $datacita = $datacita->items(); 
        } 
        
        if (isset($paramsTMP['trata']) && $paramsTMP['trata'] === '1') {
            $whereIdterapiaIn = array();
            foreach($datacita as $row){
                $whereIdterapiaIn[] = $row->idterapia;
            }


            // dd(count($datacita));
            //$datacita = $terapia->grid($param, $between, $like, '', '', '', $whereIn, true, $whereIdterapiaIn); //Borrar en el modelo

            $productos = \DB::table('terapiatratamiento')
                ->join('terapia', 'terapiatratamiento.idterapia', '=', 'terapia.idterapia') 
                ->join('presupuesto', 'terapiatratamiento.idcicloatencion', '=', 'presupuesto.idcicloatencion')  
                ->join('presupuestodet', function($join) {
                    $join->on('presupuesto.idpresupuesto', '=', 'presupuestodet.idpresupuesto')
                         ->on('presupuestodet.idproducto', '=', 'terapiatratamiento.idproducto');
                })
                ->select('terapia.idterapia', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad',  'terapiatratamiento.idcicloatencion',
                        'presupuesto.tipotarifa', 'presupuestodet.preciounitregular', 'presupuestodet.preciounittarjeta', 'presupuestodet.preciounitefectivo' )  
                ->where('terapiatratamiento.cantidad', '>', 0)
                ->whereIn('terapia.idterapia', $whereIdterapiaIn)                        
                ->whereNull('terapiatratamiento.deleted')
                ->whereNull('terapia.deleted')
                ->whereNull('presupuesto.deleted')
                ->whereNull('presupuestodet.deleted') 
                ->get()->all();

            // dd(count($productos));

            //dd($productos);     
            $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA', '*'=>'OTROS')];
            $quiebre = array('idterapia' => 'idterapia');
            // '' = array('idterapia' => 'idterapia', 'idpaciente' => 'idpaciente',  'idsede' => 'idsede', 'nombresede' => 'nombresede', 'fecha' => 'fecha', 'paciente' => 'paciente', 'hora_llegada' => 'hora_llegada', 'inicio' => 'inicio', 'fin' => 'fin', 'estadocita' => 'estadocita', 'nombreterapista' => 'nombreterapista');     
              
            $codciclos = array();
            foreach($productos as $row){
                if(!isset($codciclos[$row->idterapia]))
                    $codciclos[$row->idterapia] = array();
                
                if(!in_array($row->idcicloatencion, $codciclos[$row->idterapia]))
                    $codciclos[$row->idterapia][] = $row->idcicloatencion;                
            }

            $datatratxterapista = $this->agruparPorColumna($productos, '', $quiebre, '', $gruposProducto, ['cantidad'], false, true);  
            
            $data = array();
            foreach($datatratxterapista as $row) {
                if (!isset($data[$row['idquiebre']])) {
                    foreach($gruposProducto[1] as $ind => $val) {
                        $data[$row['idquiebre']][$val] = null;
                        $data[$row['idquiebre']][$val.'costo'] = null;
                    }
                }

                $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
                $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'costo'] = $row['cantidad'] > 0  ? $row['cantidadcosto'] : 0;
            }
            //dd($datacita);
            foreach($datacita as $row){
                //Añadir tratamientos
                $costototal = 0;
                foreach($gruposProducto[1] as $val){
                    $cantidad = $val ; 
                    $cantidadcosto = $val.'costo';  

                    $row->{$cantidad} = 0;   
                    $row->{$cantidadcosto} = 0;
                    
                    if(isset($data[$row->idterapia])){
                        $row->{$cantidad} = $data[$row->idterapia][$cantidad]; 
                        $row->{$cantidadcosto} = !empty($data[$row->idterapia][$cantidadcosto]) ? $data[$row->idterapia][$cantidadcosto] : 0; 
                        $costototal +=  $row->{$cantidadcosto};
                    } 
                }
                
                $row->codciclos = null;

                if(isset($codciclos[$row->idterapia]))
                    $row->codciclos = implode(", ", $codciclos[$row->idterapia]);
                
                $row->costototal = $costototal > 0  ? $costototal : '';
            }
        }
         
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){        
                
                $data = array(); 
                $i = 0;
                foreach($datacita as $row){  
                    $data[$i] = array( 
                        'SEDE' => $row->nombresede, 
                        'FECHA' => $row->fecha,                       
                        'PACIENTE' => $row->paciente,
                        'INGRESO' => $row->hora_llegada,
                        'INICIO' => $row->inicio,
                        'FIN' => $row->fin,
                        'ESTADO' => $row->estadocita,
                        'TERAPISTA' => $row->nombreterapista, 
                        'TERAPISTAJEFE' => $row->nombreterapistajefe, 
                        'CICLOS' => $row->codciclos,
                        'CICLODIA' => $row->idcicloatencion, //en ese dia
                        'MONTODISPONIBLE' => isset($row->montodisponible) ? $row->montodisponible : '', //en ese dia
                        'MONTOPENDIENTE' => isset($row->montopendiente) ? $row->montopendiente : '',//en ese dia
                        'MONTOEFECTUADO' => isset($row->montoefectuado) ? $row->montoefectuado : '',//en ese dia
                        'MONTOPAGADO' => isset($row->montopagado) ? $row->montopagado : '' //en ese dia
                    );
                         
                    $data[$i]['TF'] = $row->TF; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['TFCOSTO'] = $row->TFcosto;
                        $data[$i]['TFCOSTOACUM'] = round($row->TF * $row->TFcosto, 2);
                    }
 
                    $data[$i]['AC'] = $row->AC; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['ACCOSTO'] = $row->ACcosto;
                        $data[$i]['ACCOSTOACUM'] = round($row->AC * $row->ACcosto, 2);
                    }

                    $data[$i]['QT'] = $row->QT; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['QTCOSTO'] = $row->QTcosto;
                        $data[$i]['QTCOSTOACUM'] = round($row->QT * $row->QTcosto, 2);
                    }

                    $data[$i]['OCH'] = $row->OCH; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['OCHCOSTO'] = $row->OCHcosto;
                        $data[$i]['OCHCOSTOACUM'] = round($row->OCH * $row->OCHcosto, 2);
                    }
                        
                    $data[$i]['ESP'] = $row->ESP; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['ESPCOSTO'] = $row->ESPcosto;
                        $data[$i]['ESPCOSTOACUM'] = round($row->ESP * $row->ESPcosto, 2);
                    }

                    $data[$i]['BL'] = $row->BL; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['BLCOSTO'] = $row->BLcosto;
                        $data[$i]['BLCOSTOACUM'] = round($row->BL * $row->BLcosto, 2);
                    }

                    $data[$i]['BMG'] = $row->BMG; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['BMGCOSTO'] = $row->BMGcosto;
                        $data[$i]['BMGCOSTOACUM'] = round($row->BMG * $row->BMGcosto, 2);
                    }
                    
                    $data[$i]['AGUJA'] = $row->AGUJA; 
                    if($this->objTtoken->myperfilid === 1){
                        $data[$i]['AGUJACOSTO'] = $row->AGUJAcosto;
                        $data[$i]['AGUJACOSTOACUM'] = round($row->AGUJA * $row->AGUJAcosto, 2);
                    }

                    $data[$i]['OTROS'] = $row->OTROS; 
                    if($this->objTtoken->myperfilid === 1)
                        $data[$i]['OTROSCOSTO'] = $row->OTROScosto;   

                    if($this->objTtoken->myperfilid === 1)
                        $data[$i]['COSTOTOTAL'] = $row->costototal; 
                    
                    $data[$i]['SMS_SALA_ESPERA'] = !empty($row->codigosalasms) ? 'Si' : 'No';
                    $data[$i]['CALIF_SALA_ESPERA'] = $row->puntajesalasms; 
                    // $data[$i]['TEXTO_SALA_ESPERA'] = $row->textosalasms; 

                    $data[$i]['SMS_TERAPIA'] = !empty($row->codigosms) ? 'Si' : 'No';
                    $data[$i]['CALIF_TERAPIA'] = $row->puntajesms;      
                    // $data[$i]['TEXTO_TERAPIA'] = $row->textosms; 

                    $data[$i]['COMENTARIO'] = $row->comentario; 
                    $i++;
                } 

                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else { 
            return $this->crearRespuesta($datacita, 200, $total);
        } 
    }
    
    public function dashboard(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $terapia = new terapia();
        $producto = new producto();
        $objCitamedica = new citamedica();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['terapia.idempresa'] = $idempresa;

        $param2 = [];
        $param2['productometa.ano'] = substr($paramsTMP['desde'], 6 ,4);

        $param3 = [];
        $param3['citamedica.idempresa'] = $idempresa;
        $param3['citamedica.idestado'] = 6;
        
        if(isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])){
            $param['terapia.idsede'] = $paramsTMP['idsede'];
            $param2['productometa.idsede'] = $paramsTMP['idsede'];
            $param3['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {  
            $between = [$this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd'), $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd')]; 
        } 
        
        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['terapia.idestado'] = $paramsTMP['idestado'];
        }
 
        $mesi = (int) substr($paramsTMP['desde'], 3 ,2);
        $mesf = (int) substr($paramsTMP['hasta'], 3 ,2);
        $cantsedes = isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede']) ? 1 : 4;
        $datameta = array('TF'=> 0, 'AC'=>0, 'QT'=>0, 'OCH'=>0, 'ESP'=>0, 'BL'=>0, 'BMG'=>0);
        $datametainfo = array('TF'=> 0, 'AC'=>0, 'QT'=>0, 'OCH'=>0, 'ESP'=>0, 'BL'=>0, 'BMG'=>0);
        $productometa = array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG'); 
       
        $datacitaam = $terapia->gridlight($param, $between, '', '', 'terapia.fecha', 'asc', [], false, [], array('06:00:00', '14:45:59'), '', ['terapia.fecha', 'terapia.idterapista']);
        $datacitapm = $terapia->gridlight($param, $between, '', '', 'terapia.fecha', 'asc', [], false, [], array('14:46:00', '22:00:00'), '', ['terapia.fecha', 'terapia.idterapista']);

        $datacita = $terapia->gridlight($param, $between, '', '', 'terapia.fecha', 'asc', [], false, [], '', '', ['terapia.fecha', 'terapia.idterapista', 'terapista.entidad as nombreterapista', 'terapia.hora_llegada', 'terapia.inicio', 'terapia.fin']);  

        $dataterapiatra = $terapia->terapiatratamientoslight($param, ['terapia.fecha', 'terapiatratamiento.idproducto', 'terapia.idterapista', 'terapista.entidad as nombreterapista', 'terapiatratamiento.cantidad'], TRUE, $between);   
        $dataterapiatrasinaguja = $terapia->terapiatratamientoslight($param, ['terapia.fecha', 'terapiatratamiento.idproducto', 'terapia.idterapista', 'terapista.entidad as nombreterapista', 'terapiatratamiento.cantidad'], TRUE, $between, [23], TRUE);   
         
        if($cantsedes === 1){
            $datametas = $producto->metas($param2, [2,3,4,5,6,11,17]); 
        }else{
            $datametas = $producto->metastotales($param2, [2,3,4,5,6,11,17]);  
        }
         
        $tmpMaestroProducto = [];
        if ($idempresa === 1) {            
            $maestroProducto = array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA', '*'=>'OTROS');
        } else {     
            $maestroProducto = [];
            $tmpMaestroProducto = $producto->grid(['producto.idempresa' => $idempresa], '', '', '', '', ['producto.idproducto', 'producto.nombre', 'producto.codigo'], [57, 58]);
            foreach($tmpMaestroProducto as $row) {
                $maestroProducto[$row->idproducto] = $row->codigo;
            }
        } 

        $quiebre = array('fecha' => 'fecha');
        $gruposProducto = ['idproducto', $maestroProducto];         

        $dataatenciones = $this->agruparPorColumna($datacita, '', $quiebre);
        $dataatencionesam = $this->agruparPorColumna($datacitaam, '', $quiebre);
        $dataatencionespm = $this->agruparPorColumna($datacitapm, '', $quiebre);   
        $datatratxterapista = $this->agruparPorColumna($dataterapiatra, '', ['idterapista' => 'nombreterapista'], '', $gruposProducto, ['cantidad'], false, false, true); 
        $datatratxterapistasinaguja = $this->agruparPorColumna($dataterapiatrasinaguja, '', ['idterapista' => 'nombreterapista'], '', $gruposProducto, ['cantidad'], false, false, true); 
        $dataterapistas = $this->agruparPorColumna($datacita, '', ['idterapista' => 'nombreterapista'], ['idterapista' => 'nombreterapista']);
 
        $data = array();
        foreach($datatratxterapista as $row){ 
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                } 
            }
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : '';
        }
         
        $diashoras = [];
        foreach($datacita as $row){
            if(!isset($diashoras[$row->idterapista])){
                $diashoras[$row->idterapista]['dias'] = [];
                // $diashoras[$row->idterapista]['horas'] = 0;
                $diashoras[$row->idterapista]['horas'] = [];
                $diashoras[$row->idterapista]['teprom'] = 0; //tiempo espera promedio en minutos
                $diashoras[$row->idterapista]['ttprom'] = 0; //tiempo terapia promedio en minutos
            }
            
            if(!in_array($row->fecha, $diashoras[$row->idterapista]['dias'])) {
                $diashoras[$row->idterapista]['dias'][] =  $row->fecha;
            } 

            if(!empty($row->inicio) && !empty($row->fin)){                
                $fecha = $this->formatFecha($row->fecha, 'yyyy-mm-dd'); 
                // $diashoras[$row->idterapista]['horas'] += $this->convertDiff($fecha, $row->inicio, $row->fin, '%i');
                $fecha = $this->formatFecha($row->fecha, 'yyyy-mm-dd'); 
                $fechaIF = $this->fechaInicioFin($fecha, $row->inicio, $row->fin);
                $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $diashoras[$row->idterapista]['horas'][$row->fecha][] = array('inicio' => $row->inicio, 'fin' => $row->fin, 'start_s' => $start_s, 'end_s' => $end_s);
            }
     
            if(!empty($row->hora_llegada) && !empty($row->inicio) && !empty($row->fin)){                
                $fecha = $this->formatFecha($row->fecha, 'yyyy-mm-dd');  
                $diashoras[$row->idterapista]['teprom'] += $this->convertDiff($fecha, $row->hora_llegada, $row->inicio, '%i'); 
                $diashoras[$row->idterapista]['ttprom'] += $this->convertDiff($fecha, $row->inicio, $row->fin, '%i');
            }              
        }
        
        // dd($diashoras); 
        foreach($diashoras as $pk => $row){ 

            $totalminutos = 0;
            foreach($row['horas'] as $fecha => $row2){ 
                $menorhora = 0;
                $mayorhora = 0; 
                foreach($row2 as $row3){  
                    if($row3['start_s'] < $menorhora || $menorhora === 0){
                        $menorhora = $row3['start_s'];  
                    }

                    if($row3['end_s'] > $mayorhora || $mayorhora === 0){
                        $mayorhora = $row3['end_s']; 
                    }
                }     
                // $diashoras[$pk]['horas'][$fecha] = ($mayorhora - $menorhora) / 60; //Minutos
                $totalminutos += ($mayorhora - $menorhora) / 60; //Minutos
            }    

            $diashoras[$pk]['horas'] = $totalminutos;

        } 
        
        foreach($dataterapistas as $index => $row){
            foreach($data[$row['quiebre']] as $pk => $row2){                 
                $dataterapistas[$index][$pk] = $row2;
            }
           
            foreach($diashoras[$row['quiebre']] as $pk => $row2){ 
                if($pk === 'dias'){
                    $row2 = count($row2);
                }

                if($pk === 'horas'){
                    $row2 = round($row2 / 60, 0); //Minutos / 60(1hora)
                }

                if($pk === 'teprom'){
                    $row2 = round($row2 / $row['cantidad'], 0) . ' min.';
                }

                if($pk === 'ttprom'){
                    $row2 = round($row2 / $row['cantidad'], 0) . ' min.';
                }

                $dataterapistas[$index][$pk] = $row2;
            }

            $dataterapistas[$index]['cantidaddias'] = $dataterapistas[$index]['dias'] > 0 ? round($dataterapistas[$index]['cantidad'] / $dataterapistas[$index]['dias'], 1) : 0;
            $dataterapistas[$index]['cantidadhoras'] = $dataterapistas[$index]['horas'] > 0 ? round($dataterapistas[$index]['cantidad'] / $dataterapistas[$index]['horas'], 1) : 0;
        }           
        
        
        $dataterapistas = $this->ordenarMultidimension($dataterapistas, 'cantidad', SORT_DESC); 
        //fin Cuadro standard 

        $datatratxdia = $this->agruparPorColumna($dataterapiatrasinaguja, '', $quiebre, '', $gruposProducto);         

        if(!empty($datametas)){
            foreach($productometa as $idproducto => $val) {
                foreach($datametas as $row){ 
                    if($row->idproducto === $idproducto) {            
                        $dividendo = 0;
                        $divisor = 0;
                        for($i = $mesi; $i <= $mesf; $i++){
                            $mes = $this->convertMes($i, 1); 
                            $dividendo += ($row->{$mes} / $cantsedes);
                            $divisor += 26;
                        }

                        $cant = count($dataatenciones);
                        $metadia = round($dividendo /$divisor, 0);
                        $datameta[$val] = $metadia * $cant;
                        $datametainfo[$val] = '= '.$metadia . ' ' .$val.' * ' . $cant .' días';
                    }
                } 
            }            
        }          

        //

        $tratamientosmedicos = $objCitamedica->tratamientomedico($param3, false, false, false, $between, true);


        $datatratamientosmedicos = $this->agruparPorColumna($tratamientosmedicos, '', ['iddoctor' => 'nombremedico'], '', $gruposProducto, ['cantidad', 'cantefectivo'], false, false, true); 
        
        $data = array();
        foreach($datatratamientosmedicos as $row){ 
            if(!isset($data[$row['idquiebre']])) { 
                foreach($gruposProducto[1] as $val){ 
                    $data[$row['idquiebre']][$val] = null;
                    $data[$row['idquiebre']][$val.'efectuado'] = null;
                } 
            }
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : 0;
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'efectuado'] = $row['cantefectivo'] > 0  ? $row['cantefectivo'] : 0;
        }

        // dd($data, $datatratamientosmedicos);

        $datamedicos = [];
        if($idempresa === 1) {
            $datacita = $objCitamedica->grid($param3, $between, '', '', 'citamedica.fecha', 'asc', [], false, [], false, false, 'citamedica.fecha', '', true); 
            
            $diashoras = [];
            foreach($datacita as $row){            
                if(!isset($datamedicos[$row->idmedico])){
                    $datamedicos[$row->idmedico]['nombremedico'] = $row->medico;    
                    $datamedicos[$row->idmedico]['cantidad'] = 0;                    
                    $datamedicos[$row->idmedico]['ATENDIDOPAGOTTO'] = 0;    
                    $datamedicos[$row->idmedico]['ATENDIDOSOLOCM'] = 0;   
                    $diashoras[$row->idmedico]['dias'] = [];                
                    $diashoras[$row->idmedico]['tettprom'] = 0; //tiempo espera + tiempo cm promedio en minutos                
                }

                if(!in_array($row->fecha, $diashoras[$row->idmedico]['dias'])) {
                    $diashoras[$row->idmedico]['dias'][] =  $row->fecha;
                } 

                $datamedicos[$row->idmedico]['cantidad'] += 1; 

                if(!empty($row->presupuesto)) {
                    $datamedicos[$row->idmedico]['ATENDIDOPAGOTTO'] += 1;
                } else { 
                    $datamedicos[$row->idmedico]['ATENDIDOSOLOCM'] += 1;                
                }

                if(!empty($row->horaventa) && !empty($row->horaatencion)){                
                    $fecha = $this->formatFecha($row->fecha, 'yyyy-mm-dd');  
                    $diashoras[$row->idmedico]['tettprom'] += $this->convertDiff($fecha, $row->horaventa, $row->horaatencion, '%i'); 
                }
            } 
            
            foreach($datamedicos as $index => $row){
                if($data) { //Es vacia cuando medico no ingresa tratamientos
                    foreach($data[$index] as $pk => $row2){                 
                        $datamedicos[$index][$pk] = $row2;
                    }
                }
            }

            foreach($datamedicos as $index => $row){
     

                foreach($diashoras[$index] as $pk => $row2){ 
                    if($pk === 'dias'){
                        $row2 = count($row2);
                    } 

                    if($pk === 'tettprom'){
                        $row2 = round($row2 / $row['cantidad'], 0) . ' min.';
                    } 

                    $datamedicos[$index][$pk] = $row2;
                }

                //Campos calculados
                
                if ($idempresa === 1) {  
                    $totalpe = $row['AC'] + $row['QT'] + $row['OCH'] + $row['ESP'] + $row['BL'] + $row['BMG'];            
                    $totaltfpe = $row['TF'] + $totalpe;
                    $puntostotal = $row['TF'] + $row['AC'] + ($row['QT'] * 3) + ($row['OCH'] * 5) + $row['ESP'] + $row['BL'] + $row['BMG'];
                    $puntostotalefectuado = $row['TFefectuado'] + $row['ACefectuado'] + ($row['QTefectuado'] * 3) + ($row['OCHefectuado'] * 5) + $row['ESPefectuado'] + $row['BLefectuado'] + $row['BMGefectuado'];
                } else {
                    $totalpe = 0;
                    $totaltfpe = 0;
                    $puntostotal = 0;
                    $puntostotalefectuado = 0;
                }   

                $datamedicos[$index]['pctsxdia'] = round($row['cantidad']/$datamedicos[$index]['dias'], 1);
                $datamedicos[$index]['pctsxhora'] = round($datamedicos[$index]['pctsxdia']/4, 1);
                $datamedicos[$index]['porcefectividad'] = round($row['ATENDIDOPAGOTTO']/$row['cantidad'] * 100, 1);

                $datamedicos[$index]['totalpe'] = $totalpe;
                $datamedicos[$index]['totaltfpe'] = $totaltfpe;
                $datamedicos[$index]['porcpetfpe'] =  $totaltfpe > 0 ? round($totalpe/$totaltfpe * 100, 1) : 0;
                $datamedicos[$index]['puntostotal'] = $puntostotal;
                $datamedicos[$index]['puntoscm'] =  round($puntostotal/$row['cantidad'], 1);
                $datamedicos[$index]['puntostotalefectuado'] = $puntostotalefectuado;
                $datamedicos[$index]['puntoscmefectuado'] = $puntostotal > 0 ? round($puntostotalefectuado/$puntostotal, 1) : 0;

                if ($idempresa === 1) {
                    $datamedicos[$index]['porcTF'] = $row['TF'] > 0 ? round($row['TFefectuado']/$row['TF'] * 100, 1) : 0;
                    $datamedicos[$index]['porcAC'] = $row['AC'] > 0 ? round($row['ACefectuado']/$row['AC'] * 100, 1) : 0;
                    $datamedicos[$index]['porcQT'] = $row['QT'] > 0 ? round($row['QTefectuado']/$row['QT'] * 100, 1) : 0;
                    $datamedicos[$index]['porcOCH'] = $row['OCH'] > 0 ? round($row['OCHefectuado']/$row['OCH'] * 100, 1) : 0;
                    $datamedicos[$index]['porcESP'] = $row['ESP'] > 0 ? round($row['ESPefectuado']/$row['ESP'] * 100, 1) : 0;
                    $datamedicos[$index]['porcBL'] = $row['BL'] > 0 ? round($row['BLefectuado']/$row['BL'] * 100, 1) : 0;
                    $datamedicos[$index]['porcBMG'] = $row['BMG'] > 0 ? round($row['BMGefectuado']/$row['BMG'] * 100, 1) : 0;          
                } else {
                    $datamedicos[$index]['porcTF'] = 0;
                    $datamedicos[$index]['porcAC'] = 0;
                    $datamedicos[$index]['porcQT'] = 0;
                    $datamedicos[$index]['porcOCH'] = 0;
                    $datamedicos[$index]['porcESP'] = 0;
                    $datamedicos[$index]['porcBL'] = 0;
                    $datamedicos[$index]['porcBMG'] = 0;
                }
            }
 
        }

        // dd($tmpMaestroProducto);
        return $this->crearRespuesta(
            array(
                'dataatenciones' => $dataatenciones, 
                'dataatencionesam' => $dataatencionesam, 
                'dataatencionespm' => $dataatencionespm, 
                'datatratxterapista' => $datatratxterapistasinaguja, //2 grafico
                'dataterapistas' => $dataterapistas, //PROCEDIMIENTOS ESPECIALES (PE)
                'datatratxdia' => $datatratxdia, //3 grafico
                'datameta' => $datameta,
                'datametainfo' => $datametainfo,
                'datamedicos' => $datamedicos,
                'tmpmaestroproducto' => $tmpMaestroProducto
            ), 200);              
    }
                        
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $terapia = new terapia();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['terapia.idempresa'] = $idempresa;
        $param['terapia.idsede'] = $paramsTMP['idsede'];
        $param['terapia.fecha'] = date('Y-m-d');

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'terapia.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['terapia.idestado'] = $paramsTMP['idestado'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['terapia.idpaciente'] = $paramsTMP['idpaciente'];
        }

        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }

        $notExists = false;
        if (isset($paramsTMP['notexists']) && !empty($paramsTMP['notexists'])) {
            $notExists = (boolean) $paramsTMP['notexists'];
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $data = [];

        if (isset($paramsTMP['esperasala'])) {
            if ($paramsTMP['esperasala']) {
                $param['terapia.idestado'] = 36;
                $data['terapiaespera'] = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);

                $param['terapia.idestado'] = 37;
                $data['terapiasala'] = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);

                $param['terapia.idestado'] = 38;
                $data['terapiaatendido'] = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);
            }
        } else {
            //36: Espera  37: Sala 38: Atendido  39: Cancelada
            $param['terapia.idestado'] = 36;
            $data['terapiaespera'] = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);

            $param['terapia.idestado'] = 37;
            $data['terapiasala'] = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);

            $param['terapia.idestado'] = 38;
            $data['terapiaatendido'] = $terapia->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);

            $param = [];
            $param['camilla.idempresa'] = $idempresa;
            $param['camilla.idsede'] = $paramsTMP['idsede'];
            $data['terapiacamillas'] = $terapia->camillasterapia($param, 37, date('Y-m-d'));
        }

        return $this->crearRespuesta($data, 200);
    }
    

    public function show(Request $request, $enterprise, $id) {

        $objTerapia = new terapia();
        $entidad = new entidad();
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();

        $paramsTMP = $request->all();

        $terapia = $objTerapia->terapia($id);        
        $tmpempresa = $empresa->empresa(['url' => $enterprise]);
        $idempresa = $tmpempresa->idempresa;

        // dd($terapia);
        if ($terapia) {

            $listcombox = [];
            if (isset($paramsTMP['others'])) {
                $others = explode(',', $paramsTMP['others']);

                if (in_array('te', $others)) {
                    $param3 = array();
                    $param3['entidad.idempresa'] = $idempresa;
                    $param3['sede.idsede'] = $terapia->idsede;
                    $param3['tipopersonal'] = '1';
                    $param3['idcargoorg'] = $tmpempresa->codecargo;
                    $listcombox['terapistas'] = $entidad->entidadesSedes($param3);
                }

                if (in_array('ci', $others)) {
                    $fields = array('terapiatratamiento.idcicloatencion', 'producto.idproducto', 'producto.nombre as nombreproducto', 'terapiatratamiento.cantidad');
                    $terapiatratamientos = $objTerapia->terapiatratamientos(['terapiatratamiento.idterapia' => $terapia->idterapia], $fields);
                    // dd($terapiatratamientos);
                    $Tmp = [];
                    $ciclos = [];
                    foreach ($terapiatratamientos as $row) {
                        if (!in_array($row->idcicloatencion, $Tmp)) {
                            $Tmp[] = $row->idcicloatencion;
                            $ciclos[]['idcicloatencion'] = $row->idcicloatencion;
                        }
                    }
                    $listcombox['ciclos'] = $ciclos;
                }
            }

            return $this->crearRespuesta($terapia, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Terapia no encotrado', 404);
    }

    public function tratamientos(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $terapia = new terapia();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['terapia.idempresa'] = $idempresa;

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['terapia.idsede'] = $paramsTMP['idsede'];
        }

        $data = $terapia->terapiatratamientos($param, [], TRUE, FALSE, $between);

        foreach ($data as $row) {
            $row->turno = $this->turno($row->idsede, $row->fecha, $row->inicio, $row->fin);
        }
        //dd($data);  
        return $this->crearRespuesta($data, 200);
    }
 
    public function historial(Request $request, $enterprise) {


        $objEntidad = new entidad();
        $empresa = new empresa();
        $terapia = new terapia();
        $citamedica = new citamedica();
        $presupuesto = new presupuesto();
        $cicloatencion = new cicloatencion();

        $params = $request->all();
        $idempresa = $empresa->idempresa($enterprise);
        $entidad = $objEntidad->entidadHC(array('identidad' => $params['idpaciente']), $params['idsede']);

        if ($entidad) {


            $listcombox = array();

            if (isset($params['historiaclinica']) && $params['historiaclinica'] === '1') {

                $param = array(
                    'cicloatencion.idempresa' => $idempresa,
                    'cicloatencion.idsede' => $params['idsede'],
                    'cliente.identidad' => $params['idpaciente']
                );

                $ciclos = $cicloatencion->grid($param, '', '', '', 'cicloatencion.fecha', 'DESC', TRUE, ['cicloatencion.idcicloatencion', 'presupuesto.idpresupuesto', 'cicloatencion.fecha', 'presupuesto.montocredito', 'presupuesto.montoefectuado']);
                foreach ($ciclos as $row) {
                    $param = array(
                        'terapiatratamiento.idcicloatencion' => $row->idcicloatencion,
                        'terapia.idestado' => '38' //36: Espera  37: Sala 38: Atendido  39: Cancelada
                    );
                    $row->presupuestodet = $presupuesto->presupuestodet($row->idpresupuesto);
                    $row->historialterapeutico = $terapia->terapiatratamientos($param, [], TRUE);
                    $row->diagnosticosmedico = $citamedica->diagnosticomedico(['citamedica.idcicloatencion' => $row->idcicloatencion]);
                }
                $listcombox['historiaclinica'] = $ciclos;
            }

            if (isset($params['historialterapeutico']) && $params['historialterapeutico'] === '1') {
                $param = array(
                    'terapia.idempresa' => $idempresa,
                    'terapia.idsede' => $params['idsede'],
                    'terapia.idpaciente' => $params['idpaciente'],
                    'terapia.idestado' => '38' //36: Espera  37: Sala 38: Atendido  39: Cancelada
                );
                $listcombox['historialterapeutico'] = $terapia->terapiatratamientos($param, [], true);
            }

            return $this->crearRespuesta($entidad, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Terapia no encotrado', 404);
    }

    public function newatencion(Request $request, $enterprise) {

        $cicloatencion = new cicloatencion();
        $ObjTerapia = new terapia();
        $entidad = new entidad();
        $empresa = new empresa();
        $asistencia = new asistencia();

        $paramsTMP = $request->all();        
        $tmpempresa = $empresa->empresa(['url' => $enterprise]);
        $idempresa = $tmpempresa->idempresa;
        
        $terapia = '';
        if(isset($paramsTMP['idterapia']) && !empty($paramsTMP['idterapia'])){
            //Sala de terapia
            $terapia = $ObjTerapia->terapia($paramsTMP['idterapia']);
            $idsede = $terapia->idsede;
            $idpaciente = $terapia->idpaciente;   
            
            $param = array();
            $param['camilla.idempresa'] = $idempresa;
            $param['camilla.idsede'] = $idsede;             
            $camDisponible = $ObjTerapia->camillasdisponibles($param, ['terapia.fecha' => date('Y-m-d'), 'terapia.idestado' => 37]);

            $param = array(); 
            $param['terapia.idsede'] = $idsede;
            $param['terapia.idestado'] = 36; //Espera
            $param['terapia.fecha'] = date('Y-m-d');
            $tmpCamNoDisponible = $ObjTerapia->camillasnodisponibles($param);
            $camNoDisponible = array();
            foreach ($tmpCamNoDisponible as $row) {
                $camNoDisponible[] = $row->idcamilla;
            }

            $camillas = array();
            foreach ($camDisponible as $row) {
                if (! in_array($row->idcamilla, $camNoDisponible)) {
                    $camillas[] = $row;
                }
            }
        }else {
            //Regularizacion de terapias
            $idsede = $paramsTMP['idsede'];
            $idpaciente = $paramsTMP['idpaciente'];
            
            $param = array();
            $param['camilla.idempresa'] = $idempresa;
            $param['camilla.idsede'] = $idsede; 
            $camillas = $empresa->camillas($param);
        }
        
        $param = array();
        $param['cicloatencion.idempresa'] = $idempresa;
        $param['cicloatencion.idsede'] = $idsede; //$paramsTMP['idsede'];
        $param['cliente.identidad'] = $idpaciente; //$paramsTMP['idpaciente'];
        $param['cicloatencion.idestado'] = 20;
        
        $param3 = array();
        $param3['entidad.idempresa'] = $idempresa;
        $param3['sede.idsede'] = $idsede;  
        $param3['tipopersonal'] = '1';
        $param3['idcargoorg'] = $tmpempresa->codecargo;
        $terapistas = $entidad->entidadesSedes($param3); 


        if(isset($paramsTMP['filtroasistencia']) && $paramsTMP['filtroasistencia'] === '1'){
            //Asistencia de hoy Filtrar, AMAC lo solicita.
            $param4 = array();
            $param4['asistencia.idempresa'] = $idempresa;
            $param4['asistencia.idsede'] = $idsede;  
            $param4['asistencia.laborfechainicio'] = date('Y-m-d');

            $dataasistenciahoy = $asistencia->grid($param4); 
            // dd($param4, $dataasistenciahoy);
            $terapistasfiltro = [];
            foreach($terapistas as $row) {
                foreach($dataasistenciahoy as $row2) {
                    if($row['identidad'] === $row2->identidad) {
                        $terapistasfiltro[] = $row; 
                        break;
                    }                
                }            
            }
        } else {
            $terapistasfiltro = $terapistas;
        }
        // $terapistasfiltro = $terapistas; 

        $listcombox = array(
            'ciclos' => $cicloatencion->grid($param, '', '', '', 'cicloatencion.fecha', 'DESC', TRUE, ['cicloatencion.idcicloatencion', 'presupuesto.montocredito', 'presupuesto.montoefectuado', 'paquete.nombre as nombrepaquete']),
            'camillas' => $camillas,
            'terapistas' => $terapistasfiltro,
            'jefes' => $terapistasfiltro 
        );

        return $this->crearRespuesta($terapia, 200, '', '', $listcombox);
    }

    public function showatencion(Request $request, $enterprise, $id) {

        $ObjTerapia = new terapia();
        $citamedica = new citamedica();
        $objPresupuesto = new presupuesto();
        $cicloatencion = new cicloatencion();

        $params = $request->all();
        $terapia = $ObjTerapia->terapia($id);

        if (empty($params['idcicloatencion'])) {
            return $this->crearRespuesta('Especificar ciclo de atención.', [200, 'info']);
        }

        if ($terapia) { 

            $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $params['idcicloatencion']]);
            $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
 
            $param = array(
                    'terapiaprocedimiento.idterapia' => $id,
                    'terapiaprocedimiento.idcicloatencion' => $params['idcicloatencion'] 
            );

            $param2 = array(
                    'terapiatecnica.idterapia' => $id,
                    'terapiatecnica.idcicloatencion' => $params['idcicloatencion'] 
            );

            $param3 = array(
                    'terapiaimagen.idterapia' => $id,
                    'terapiaimagen.idcicloatencion' => $params['idcicloatencion'] 
            );

            $listcombox = array(
                // 'tratamientos' => $tratamientos,
                // 'diagnosticosmedico' => $citamedica->diagnosticomedico(['citamedica.idcicloatencion' => $params['idcicloatencion']]),
                'procedimientos' => [],//$ObjTerapia->procedimientos($param),
                'tecnicas' => [], //$ObjTerapia->tecnicasmanuales($param2),
                'puntos' => [] //$ObjTerapia->puntosimagen($param3)
            ); 

            if (isset($params['pagos']) && $params['pagos'] === '1') {    

                $venta = new venta();
                $ciclomovimiento = new ciclomovimiento();  

                $pagosrealizadas = array();
                $idcicloatencion = $params['idcicloatencion'];

                $saldos = $ciclomovimiento->movimiento(['idcicloatencion' => $idcicloatencion], ['idcicloatencionref' => $idcicloatencion]);
                $ventas = $venta->grid(['venta.idcicloatencion' => $idcicloatencion, 'venta.idestadodocumento' => 27]);
                // $whereIdventaCmIn = [];
                foreach ($ventas as $row) {
                    if(empty($row->idcitamedica)){
                        $pagosrealizadas[] = array(
                            'documento' => $row->documentoSerieNumero, 
                            'fechaventa' => $row->fechaventa, 
                            'mediopago' => $row->mediopagonombre, 
                            'total' =>  $row->total, 
                            'nota' => 'notacredito', 
                            'iddocumentofiscal' => $row->iddocumentofiscal,
                            'idventa' => $row->idventa,
                            'idventaref' => $row->idventaref
                        );
                    }
                }

                foreach ($saldos as $i => $row) {
                    $nota = $row->tiponota;
                    if($row->tiponota === 'notadebito'){ // Para cobrarle un adicional mas          
                        $tiponota = 'Nota de saldo';      
                    } 

                    if($row->tiponota === 'notacredito'){ // Para devolver dinero         
                        $tiponota = 'Nota de saldo';             
                    } 

                    $pagosrealizadas[] = array(
                        'documento' => $tiponota.' N° '.$row->numero, 
                        'fechaventa' => $row->fecha, 
                        'mediopago' => '', 
                        'total' =>  $row->monto, 
                        'nota' => $nota,
                        'iddocumentofiscal' => NULL,
                        'idventa' => NULL,
                        'idventaref' => NULL
                    );
                }

                $listcombox['pagosrealizadas'] = $pagosrealizadas;
            }

            if (isset($request['gruposdx']) && $request['gruposdx'] === '1') {

                $grupodx = new grupodx();  
                
                $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $params['idcicloatencion']]);
                // dd($gruposDx);
                $citas = $cicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $params['idcicloatencion']]);
                $diagnosticos = $citamedica->diagnosticomedico(['citamedica.idcicloatencion' => $params['idcicloatencion']]);
                $tratamientos = $citamedica->tratamientomedicoLight($params['idcicloatencion']); 

                $efectuadas = $ObjTerapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $params['idcicloatencion'], 'terapia.idestado' => 38], array('terapiatratamiento.idproducto', 'terapiatratamiento.idgrupodx', 'terapiatratamiento.cantidad'), TRUE);
                $porEfectuar = $ObjTerapia->terapiatratamientos(['terapia.idterapia' => $id, 'cicloatencion.idcicloatencion' => $params['idcicloatencion']], ['terapiatratamiento.idterapiatratamiento', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad', 'terapiatratamiento.idgrupodx', 'terapiatratamiento.val1', 'terapiatratamiento.val2', 'terapiatratamiento.val3', 'terapiatratamiento.val4', 'terapiatratamiento.val5', 'terapiatratamiento.val6', 'terapiatratamiento.val7', 'terapiatratamiento.val8', 'terapiatratamiento.val9', 'terapiatratamiento.val10']);
                // dd($gruposDx);
                // BEGIN Citas medicas //
                $whereInIdcita = [];
                foreach ($citas as $item) { 
                    $item->ultimo_informe = null;
                    $whereInIdcita[] = $item->idcitamedica;
                }

                if (!empty($whereInIdcita)) {
                    $informes = $citamedica->informes([], $whereInIdcita); 
                    $informes = $this->ordenarMultidimension($informes, 'idinforme', SORT_ASC);

                    foreach ($citas as $item) { 
                        $ultimo_informemedico = null;
                        foreach ($informes as $informe) { 
                            if ($informe->idcitamedica === $item->idcitamedica) {
                                $ultimo_informemedico = $informe->archivo;
                            }
                        }

                        $item->ultimo_informe = $ultimo_informemedico;
                    }
                }
                $listcombox['citas'] = $citas;
                // END  Citas medicas //

                // BEGIN Tratamientos //
                $tmpTratamientos = [];
                foreach ($tratamientos as $item) { 
                    $preciounit = null;
                    foreach ($presupuestodet as $row) {
                        if ($item->idproducto === $row->idproducto) {
                            $preciounit = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);                           
                        }
                    }

                    if (!isset($tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx])) {
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['idproducto'] = $item->idproducto; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['nombreproducto'] = $item->nombreproducto; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantmedico'] = 0; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantefectivo'] = 0; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['preciounit'] = (float) $preciounit; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['idgrupodx'] = $item->idgrupodx;  
                    }

                    $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantmedico'] += $item->cantidad;  
                } 
                // BEGIN Tratamientos //

                $tmpEfectuadas = [];
                foreach ($efectuadas as $item) { 
                    if (!isset($tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx])) {  
                        $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['idproducto'] = $item->idproducto;
                        $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['idgrupodx'] = $item->idgrupodx;  
                        $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['cantefectivo'] = 0;  
                    }

                    $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['cantefectivo'] += $item->cantidad;  
                }  

                foreach ($gruposDx as $row) { 
                    $row->diagnosticos = [];
                    $row->tratamientos = []; 

                    foreach ($diagnosticos as $diagnostico) {
                        if ($diagnostico->idgrupodx === $row->idgrupodx) {
                            $row->diagnosticos[] = $diagnostico;
                        } 
                    }

                    foreach ($tmpTratamientos as $tratamiento) {
                        if ($tratamiento['idgrupodx'] === $row->idgrupodx) { 

                            foreach ($tmpEfectuadas as $efectuada) {
                                if ($tratamiento['idgrupodx'] === $efectuada['idgrupodx'] && $tratamiento['idproducto'] === $efectuada['idproducto']) { 
                                    $tratamiento['cantefectivo'] = $efectuada['cantefectivo'];
                                } 
                            }
                            
                            foreach ($porEfectuar as $item) {
                                if ($tratamiento['idgrupodx'] === $item->idgrupodx && $tratamiento['idproducto'] === $item->idproducto) { 
                                    $tratamiento['idterapiatratamiento'] = $item->idterapiatratamiento;
                                    $tratamiento['cantidad'] = $item->cantidad;
                                    $tratamiento['val1'] = $item->val1;
                                    $tratamiento['val2'] = $item->val2;
                                    $tratamiento['val3'] = $item->val3;
                                    $tratamiento['val4'] = $item->val4;
                                    $tratamiento['val5'] = $item->val5;
                                    $tratamiento['val6'] = $item->val6;
                                    $tratamiento['val7'] = $item->val6;
                                    $tratamiento['val8'] = $item->val6;
                                    $tratamiento['val9'] = $item->val6;
                                    $tratamiento['val10'] = $item->val6;
                                } 
                            }

                            $row->tratamientos[] = $tratamiento;
                        } 
                    }  
                }  

                $listcombox['gruposdx'] = $gruposDx; 
            }

            return $this->crearRespuesta($terapia, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Terapia no encotrado', 404);
    }
 
    public function updateatencion(Request $request, $enterprise, $id) {

        $terapia = terapia::find($id);

        $objPresupuesto = new presupuesto();
        $objAutorizacionterapia = new autorizacionterapia();

        $request = $request->all();

        $idcicloatencionIn = [];
        foreach ($request['terapiatratamiento'] as $row) {
            if (!in_array($row['idcicloatencion'], $idcicloatencionIn))
                $idcicloatencionIn[] = $row['idcicloatencion'];            
        }

        //VALIDACIONES
        if (empty($terapia->tiempo) || empty($request['terapia']['tiempo'])) {
            return $this->crearRespuesta('No tiene asignado tiempo de sesión en camilla.', [200, 'info']);
        }
        /*  1.- Validar credito disponible si paciente no tiene autorizacion libre de ingreso a terapia. */

        $dataTerapiaTrat = [];
        foreach ($request['terapiatratamiento'] as $row) {
            if (isset($row['cantidad']) && $row['cantidad'] > 0)
                $dataTerapiaTrat[] = $row;
        }

        if ($terapia) {
            $param = array(
                'idempresa' => $terapia->idempresa,
                'idsede' => $terapia->idsede,
                'idcliente' => $terapia->idpaciente,
                'fecha' => date('Y-m-d')
            );

            $noTieneAutorizacion = empty($objAutorizacionterapia->autorizacionterapia('', $param)) ? true : false;

            if ($noTieneAutorizacion) {            
                //////////////////////////
                $mensaje = '';
                $inValid = false;
                foreach ($idcicloatencionIn as $idciclo) {

                    $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $idciclo]);
                    $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);

                    $montoaefectuarse = 0;
                    foreach ($dataTerapiaTrat as $row) {
                        foreach ($presupuestodet as $row2) {
                            if ($row['idproducto'] === $row2->idproducto) {
                                $preciounit = $presupuesto->tipotarifa === 1 ? $row2->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row2->preciounittarjeta : $row2->preciounitefectivo);
                                $montoaefectuarse = $montoaefectuarse + $preciounit * $row['cantidad'];
                                break;
                            }
                        }
                    }

                    $creditodisp = round($presupuesto->montocredito - $presupuesto->montoefectuado, 2);

                    if ($montoaefectuarse > $creditodisp) {
                        $inValid = true;
                        $mensaje = 'Crédito disponible S/.' . $creditodisp . ' es insuficiente en ciclo. <br>Se necesita S/.' . $montoaefectuarse; 
                    }
                }

                if ($inValid) {
                    return $this->crearRespuesta($mensaje, [200, 'info']);
                } 
            } 
 
            // return $this->crearRespuesta($hora .'|'. $request['terapia']['fin'], [200, 'info']);
        }

        $horafin = date('H:i:s', strtotime('+' . $request['terapia']['tiempo'] . ' minute', strtotime(date('Y-m-j' . ' ' . $terapia->inicio))));

        $request['terapia']['fin'] = $horafin;
        /* Campos auditores */
        $request['terapia']['updated_at'] = date('Y-m-d H:i:s');
        $request['terapia']['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if (isset($request['terapiatratamiento'])) {

            /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'terapiatratamiento'.
             */
            $terapiatratamientoInsert = [];
            $terapiatratamientoUpdate = [];
            $terapiatratamientoDelete = [];

            $fields = ['terapiatratamiento.idterapiatratamiento', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad'];
            // $dataTratamiento = $terapia->terapiatratamientos(['terapia.idterapia' => $id, 'cicloatencion.idcicloatencion' => $request['idcicloatencion']], $fields); 
            $dataTratamiento = $terapia->terapiatratamientos(['terapia.idterapia' => $id], $fields);

            foreach ($request['terapiatratamiento'] as $indice => $row) {

                $nuevo = true;
                $update = false;
                foreach ($dataTratamiento as $indice => $row2) {
                    if (isset($row['idterapiatratamiento']) && $row['idterapiatratamiento'] === $row2->idterapiatratamiento) {
                        $nuevo = false;
                        $update = true;
                        unset($dataTratamiento[$indice]);
                        break 1;
                    }
                }

                $tmp = array(
                    'idproducto' => $row['idproducto'],
                    'cantidad' => $row['cantidad'],
                    'idgrupodx' => isset($row['idgrupodx']) ? $row['idgrupodx'] : NULL,
                    'idcicloatencion' => $row['idcicloatencion']
                );


                if ($nuevo) {
                    $tmp['idterapia'] = $id;
                    $tmp['created_at'] = date('Y-m-d H:i:s');
                    $tmp['id_created_at'] = $this->objTtoken->my;

                    $terapiatratamientoInsert[] = $tmp;
                }

                if ($update) {
                    $tmp['updated_at'] = date('Y-m-d H:i:s');
                    $tmp['id_updated_at'] = $this->objTtoken->my;

                    $terapiatratamientoUpdate[] = array(
                        'data' => $tmp,
                        'where' => ['idterapiatratamiento' => $row['idterapiatratamiento']]
                    );
                }
            }

            if (!empty($dataTratamiento)) {
                $tmp = array();
                $tmp['deleted'] = '1';
                $tmp['deleted_at'] = date('Y-m-d H:i:s');
                $tmp['id_deleted_at'] = $this->objTtoken->my;

                foreach ($dataTratamiento as $row) {
                    $terapiatratamientoDelete[] = array(
                        'data' => $tmp,
                        'where' => array(
                            'idterapiatratamiento' => $row->idterapiatratamiento
                        )
                    );
                }
            }
        }

        if ($terapia) {

            $terapia->fill($request['terapia']);

            \DB::beginTransaction();
            try {
                //Graba en Terapia, Terapiatratamiento.

                $terapia->save();

                if (isset($request['terapiatratamiento'])) {
                    if (!empty($terapiatratamientoInsert))
                        \DB::table('terapiatratamiento')->insert($terapiatratamientoInsert);

                    foreach ($terapiatratamientoUpdate as $fila) {
                        \DB::table('terapiatratamiento')->where($fila['where'])->update($fila['data']);
                    }

                    foreach ($terapiatratamientoDelete as $fila) {
                        \DB::table('terapiatratamiento')->where($fila['where'])->update($fila['data']);
                    }
                }

                /*if (isset($request['procedimientos'])) {                    
                    $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                    $terapia->GrabarProcedimientos($request['procedimientos'], $param);
                }
                
                if (isset($request['tecnicas'])) {                    
                    $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                    $terapia->GrabarTecnicas($request['tecnicas'], $param);
                }

                if (isset($request['puntosimg'])) {                    
                    $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                    $terapia->GrabarPuntos($request['puntosimg'], $param);
                } */

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Terapia ha sido editado. ', 200, '', '', $terapiatratamientoInsert);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una cita médica', 404);
    }

    public function storeatencion(Request $request, $enterprise, $id) {
        //- Invocado para crear atencion.

        $terapia = terapia::find($id);
        $empresa = new empresa();
        $objPresupuesto = new presupuesto();
        $objAutorizacionterapia = new autorizacionterapia();

        $request = $request->all();

        $idcicloatencionIn = [];
        foreach ($request['terapiatratamiento'] as $row) {
            if (!in_array($row['idcicloatencion'], $idcicloatencionIn))
                $idcicloatencionIn[] = $row['idcicloatencion'];            
        }

        // AQUI
        // if (empty($request['idcicloatencion'])) {
        if (empty($idcicloatencionIn)) {
            return $this->crearRespuesta('Especificar ciclo de atención.', [200, 'info']);
        }

        // return $this->crearRespuesta('Especificar ciclo de atención.', [200, 'info']);
        $dataTerapiaTrat = [];
        foreach ($request['terapiatratamiento'] as $row) {
            if (isset($row['cantidad']) && $row['cantidad'] > 0) {
                $param = array(
                    'idterapia' => $id,
                    'idproducto' => $row['idproducto'],
                    'cantidad' => $row['cantidad'], 
                    'idgrupodx' => isset($row['idgrupodx']) ? $row['idgrupodx'] : NULL,
                    'idcicloatencion' => $row['idcicloatencion'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my
                );
                $dataTerapiaTrat[] = $param;
            }
        }

        // return $this->crearRespuesta($dataTerapiaTrat, [200, 'info']);
        /* 15.06.2016 Validaciones         
         */
         //1.- Validar que no haya tratameintos.
         $dataTratamiento = $terapia->terapiatratamientos(['terapia.idterapia' => $id]);
         if(count($dataTratamiento) > 0){
            return $this->crearRespuesta('Paciente ya tiene tratamientos ingresados.', [200, 'info']);
         }

        //2.- Validar credito disponible si paciente no tiene autorizacion libre de ingreso a terapia.
        if ($terapia) {
            $param = array(
                'idempresa' => $terapia->idempresa,
                'idsede' => $terapia->idsede,
                'idcliente' => $terapia->idpaciente,
                'fecha' => date('Y-m-d')
            );

            $noTieneAutorizacion = empty($objAutorizacionterapia->autorizacionterapia('', $param)) ? true : false;

            if ($noTieneAutorizacion) {
                $mensaje = '';
                $inValid = false;
                foreach ($idcicloatencionIn as $idciclo) {
                    $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $idciclo]);
                    $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);

                    $montoaefectuarse = 0;
                    foreach ($dataTerapiaTrat as $row) {
                        foreach ($presupuestodet as $row2) {
                            if ($row['idproducto'] === $row2->idproducto) {
                                $preciounit = $presupuesto->tipotarifa === 1 ? $row2->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row2->preciounittarjeta : $row2->preciounitefectivo);
                                $montoaefectuarse = $montoaefectuarse + $preciounit * $row['cantidad'];
                                break;
                            }
                        }
                    }

                    $creditodisp = round($presupuesto->montocredito - $presupuesto->montoefectuado, 2); 

                    if ($montoaefectuarse > $creditodisp) {
                        $inValid = true;
                        $mensaje = 'Crédito disponible S/.' . $creditodisp . ' es insuficiente en ciclo. <br>Se necesita S/.' . $montoaefectuarse;                        
                    }
                }

                if ($inValid) {
                    // return $this->crearRespuesta($mensaje, [200, 'info']);
                }
            }
        } 

        if ($terapia) {
            $sedehorario = $empresa->sedehorarios($terapia->idsede);

            $minutos = isset($request['terapia']['tiempo']) ? $request['terapia']['tiempo'] : substr($sedehorario->cronometroterapia, 3, 2);

            $hora = date('H:i:s'); 
            // return $this->crearRespuesta((string) $minutos, [200, 'info']);

            //25.06.2019 Solo idestado vaya ser 37(Sala), se establece inicio y fin.
            if (isset($request['terapia']['idestado']) && $request['terapia']['idestado'] === 37) {
                $request['terapia']['inicio'] = $hora;


                $request['terapia']['fin'] = date('H:i:s', strtotime('+' . $minutos . ' minute', strtotime(date('Y-m-j' . ' ' . $hora))));  
            }

            /* Campos auditores */
            $request['terapia']['updated_at'] = date('Y-m-d H:i:s');
            $request['terapia']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */

            $terapia->fill($request['terapia']);

            \DB::beginTransaction();
            try {
                //Graba en 1 tabla (terapia)
                $terapia->save();

                \DB::table('terapiatratamiento')->insert($dataTerapiaTrat);

                // if (isset($request['procedimientos'])) {
                //     $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                //     $terapia->GrabarProcedimientos($request['procedimientos'], $param);
                // }

                // if (isset($request['tecnicas'])) {                    
                //     $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                //     $terapia->GrabarTecnicas($request['tecnicas'], $param);
                // }

                // if (isset($request['puntosimg'])) {                    
                //     $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                //     $terapia->GrabarPuntos($request['puntosimg'], $param);
                // }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Tratamientos han sido añadido a la sesi&oacute;n', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una terapias', 404);
    }
    
    public function updatesms(Request $request, $enterprise, $id) {

        $request = $request->all();

        $terapia = terapia::find($id);

        if ($terapia) {
            if(isset($request['codigosms']) && !empty($request['codigosms'])){
                $data = array(
                    'codigosms' => $request['codigosms'] 
                ); 
            }

            if(isset($request['codigosalasms']) && !empty($request['codigosalasms'])){
                $data = array( 
                    'codigosalasms' => $request['codigosalasms'],
                ); 
            }

            $terapia->fill($data); 
            $terapia->save();

            return $this->crearRespuesta($terapia, 200);
        } 
    }


    public function store(Request $request, $enterprise) {
        /* Ingresan las acciones de: Finalizar y Cancelar
         */
        $empresa = new empresa();
        $objPresupuesto = new presupuesto();
        
        $idempresa = $empresa->idempresa($enterprise);
        
        $request = $request->all();
        
        //1ra Validacion 
        $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $request['idcicloatencion']]);
        $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);

        $montoaefectuarse = 0;
        foreach ($request['terapiatratamiento'] as $row) {
            foreach ($presupuestodet as $row2) {
                $row['cantidad'] = empty($row['cantidad']) ? 0 : $row['cantidad'];

                if ($row['idproducto'] === $row2->idproducto && $row['cantidad'] > 0) {                    
                    $preciounit = $presupuesto->tipotarifa === 1 ? $row2->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row2->preciounittarjeta : $row2->preciounitefectivo);
                    $montoaefectuarse = $montoaefectuarse + $preciounit * $row['cantidad'];
                    break;
                }
            }
        }
        
        $creditodisp = round($presupuesto->montocredito - $presupuesto->montoefectuado, 2);
        if ($montoaefectuarse > $creditodisp && $request['validarcredito'] === 1) 
            return $this->crearRespuesta('Crédito disponible S/.' . $creditodisp . ' es insuficiente en ciclo. <br>Se necesita S/.' . $montoaefectuarse, [200, 'info']);
        
        //2da Validacion de tiempos
        $a = strtotime(date('Y-m-j'). ' 06:00:00');
        $b = strtotime(date('Y-m-j'). ' 22:00:00');
        $llegada = strtotime(date('Y-m-j'). ' '.$request['terapia']['hora_llegada']);
        $inicio = strtotime(date('Y-m-j'). ' '.$request['terapia']['inicio']);
        $fin = strtotime(date('Y-m-j'). ' '.$request['terapia']['fin']);

        if($llegada < $a || $llegada >$b)
            return $this->crearRespuesta('Horario ingreso desde 6:00 A.m. - 10:00 P.m.' , [200, 'info']);
        
        if($llegada > $inicio)
            return $this->crearRespuesta('Hora llegada mayor a hora de inicio.' , [200, 'info']);
        
        if($inicio > $fin)
            return $this->crearRespuesta('Hora inicio mayor a hora fin.' , [200, 'info']);

        $request['terapia']['idempresa'] = $idempresa;
        $request['terapia']['idestado'] = 38; 
        $request['terapia']['fecha'] = $this->formatFecha($request['terapia']['fecha'], 'yyyy-mm-dd');;        
        $request['terapia']['created_at'] = date('Y-m-d H:i:s');
        $request['terapia']['id_created_at'] = $this->objTtoken->my;

        \DB::beginTransaction();
        try {
            $terapia = terapia::create($request['terapia']);
            
            $dataTerapiaTrat = [];
            foreach ($request['terapiatratamiento'] as $row) {
                if (isset($row['cantidad']) && $row['cantidad'] > 0) {
                    $param = array(
                        'idterapia' => $terapia->idterapia,
                        'idproducto' => $row['idproducto'],
                        'cantidad' => $row['cantidad'],
                        'idgrupodx' => $row['idgrupodx'],
                        'idcicloatencion' => $row['idcicloatencion'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my
                    );
                    $dataTerapiaTrat[] = $param;
                }
            }
            
            \DB::table('terapiatratamiento')->insert($dataTerapiaTrat); 

            // if (isset($request['procedimientos']) && isset($request['idcicloatencion'])) {
            //     $param = array('idterapia' => $terapia->idterapia, 'idcicloatencion' => $request['idcicloatencion']);

            //     $data = [];
            //     foreach ($request['procedimientos'] as $row) {
            //         $row['idterapia'] = $terapia->idterapia;
            //         $data[] = $row;
            //     }

            //     $terapia->GrabarProcedimientos($data, $param);
            // }
            
            // if (isset($request['tecnicas']) && isset($request['idcicloatencion'])) {
            //     $param = array('idterapia' => $terapia->idterapia, 'idcicloatencion' => $request['idcicloatencion']);

            //     $data = [];
            //     foreach ($request['tecnicas'] as $row) {
            //         $row['idterapia'] = $terapia->idterapia;
            //         $data[] = $row;
            //     }
                
            //     $terapia->GrabarTecnicas($data, $param);
            // }

            // if (isset($request['puntosimg']) && isset($request['idcicloatencion'])) {
            //     $param = array('idterapia' => $terapia->idterapia, 'idcicloatencion' => $request['idcicloatencion']);

            //     $data = [];
            //     foreach ($request['puntosimg'] as $row) {
            //         $row['idterapia'] = $terapia->idterapia;
            //         $data[] = $row;
            //     }
                
            //     $terapia->GrabarPuntos($data, $param);
            // } 
                                    
            $this->actualizarPresupuestos($terapia, 'sumar', TRUE, $enterprise);

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();
        
        return $this->crearRespuesta('Terapia ha sido creado.', 201);
    }
    
    public function update(Request $request, $enterprise, $id) {
        /* Ingresan las acciones de: Finalizar y Cancelar
         */        
        $objPresupuesto = new presupuesto(); 
        $empresa = new empresa();
        $post = new post();

        $paramsTMP = $request->all();
        $terapia = terapia::find($id);
        
        $ciclos = array();
        $idempresa = $empresa->idempresa($enterprise);
        

        /* 1.- Validacion de que trapista haya ingresado al menos un tratamiento en un ciclo de atencion */ 
        if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'finalizar') {
            $paramsTMP['terapia']['idestado'] = 38; //Atendido
            $paramsTMP['terapia']['fin'] = date('H:i:s');
            $ciclos = $terapia->terapiatratamientos(['terapia.idterapia' => $terapia->idterapia], ['terapiatratamiento.idcicloatencion'], TRUE, TRUE);

            if (count($ciclos) === 0)
                return $this->crearRespuesta('No ingres&oacute; sesiones a realizar, no puede finalizarse.', [200, 'info']);

            if ($terapia->idestado === 38)
                return $this->crearRespuesta('Terapia ya se encuentra finalizada, no puede finalizarse otra vez.', [200, 'info']);
        }        

        if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'cancelar') {

            if ($terapia->idestado === 39)
                return $this->crearRespuesta('Terapia ya se encuentra cancelada, no puede cancelarse otra vez.', [200, 'info']);

            $paramsTMP['terapia']['idestado'] = 39; //Cancelado  
            if ($terapia->idestado === 38){
                $ciclos = $terapia->terapiatratamientos(['terapia.idterapia' => $terapia->idterapia], ['terapiatratamiento.idcicloatencion'], TRUE, TRUE);
            }                                    
        } 

        if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'atendido') {

            $ciclo = cicloatencion::find($request['idcicloatencion']);

            if ($ciclo->idestado === 21) {
                return $this->crearRespuesta('Ciclo se encuentra cerrado. No se puede editar información.', [200, 'info']);
            }

            $paramsTMP['terapia']['idestado'] = 38; //Atendido
                
            $fields = ['terapiatratamiento.idterapiatratamiento', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad', 'terapiatratamiento.idgrupodx'];
            //Aun no se si validar AUTORIZACION
            $presupuesto = presupuesto::where('idcicloatencion', '=', $request['idcicloatencion'])->first();
            $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
            $tratamientos = $terapia->terapiatratamientos(['terapia.idterapia' => $id, 'cicloatencion.idcicloatencion' => $request['idcicloatencion']], $fields);

            // return $this->crearRespuesta('XD', [200, 'info']);
            
            // BEGIN Obtener variacion a partir de tratamientos
            $nuevos = [];
            $modificados = [];
            foreach ($request['terapiatratamiento'] as $row) {
                $row['cantidad'] = empty($row['cantidad']) ? 0 : $row['cantidad'];
                $esNuevo = true;

                foreach ($tratamientos as $tra) {
                    $tra->cantidad = empty($tra->cantidad) ? 0 : $tra->cantidad;

                    if ($row['idproducto'] === $tra->idproducto && $row['idgrupodx'] === $tra->idgrupodx) {
                        $esNuevo = false;

                        if ($row['cantidad'] !== $tra->cantidad) {
                            $modificados[] = array(
                                'idterapiatratamiento' => $tra->idterapiatratamiento,
                                'idproducto' => $row['idproducto'],
                                'cantidad' => $row['cantidad'],
                                'idgrupodx' => $row['idgrupodx'],
                                'variacion' => $row['cantidad'] - $tra->cantidad,
                                'updated_at' => date('Y-m-d H:i:s'),
                                'id_updated_at' => $this->objTtoken->my
                            );
                        }
                    }
                }

                if ($esNuevo && $row['cantidad'] > 0) {
                    $nuevos[] = array(
                        'idcicloatencion' => $request['idcicloatencion'],
                        'idterapia' => $terapia->idterapia,
                        'idproducto' => $row['idproducto'],
                        'cantidad' => $row['cantidad'],
                        'idgrupodx' => $row['idgrupodx'],
                        'variacion' => $row['cantidad'],
                        'created_at' => date('Y-m-d H:i:s'), 
                        'id_created_at' => $this->objTtoken->my
                    );
                }
            }

            $itemsTemp = [];
            foreach ($nuevos as $row) { 
                if (!isset($itemsTemp[$row['idproducto']])) {
                    $itemsTemp[$row['idproducto']]['idproducto'] = $row['idproducto'];
                    $itemsTemp[$row['idproducto']]['cantidad'] = 0;
                }
                $itemsTemp[$row['idproducto']]['cantidad'] += $row['variacion'];
            } 

            foreach ($modificados as $row) { 
                if (!isset($itemsTemp[$row['idproducto']])) {
                    $itemsTemp[$row['idproducto']]['idproducto'] = $row['idproducto'];
                    $itemsTemp[$row['idproducto']]['cantidad'] = 0;
                }
                $itemsTemp[$row['idproducto']]['cantidad'] += $row['variacion'];
            } 

            $items = [];
            foreach ($itemsTemp as $row) { 
                if ($row['cantidad'] !== 0) { // + Aumentará - disminuira lo efectuado en presupuesto.
                    $items[] = $row;
                }
            }
            // END Obtener variacion a partir de tratamiento 

            // BEGIN Variacion de efectuado para presupuesto
            $montoVARIACION = 0;  
            foreach ($items as $index => $row) {
                foreach ($presupuestodet as $row2) {
                    if ($row['idproducto'] === $row2->idproducto) {                        
                        $items[$index]['idpresupuestodet'] = $row2->idpresupuestodet;
                        $items[$index]['cantefectivo'] = empty($row2->cantefectivo) ? 0 : $row2->cantefectivo; 
                        $preciounit = $presupuesto->tipotarifa === 1 ? $row2->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row2->preciounittarjeta : $row2->preciounitefectivo);
                        $montoVARIACION += $preciounit * $row['cantidad'];
                        break;
                    }
                }
            }

            // return $this->crearRespuesta('Hola', [200, 'info'], '', '', [$nuevos, $modificados, $items, $montoVARIACION]);
            // END Variacion de efectuado para presupuesto
        }

        if( (isset($paramsTMP['terapia']['control']) && $paramsTMP['terapia']['control'] != $terapia->control) ||  
            (isset($paramsTMP['terapia']['controlcomentario']) && $paramsTMP['terapia']['controlcomentario'] != $terapia->controlcomentario) ){
            $paramsTMP['terapia']['identidadctrol'] = $this->objTtoken->my; 
            $paramsTMP['terapia']['fechactrol'] = date('Y-m-d');  
        } 

        //return $this->crearRespuesta($paramsTMP['accion'].' | '.$terapia->idestado, [200, 'info']);
        if ($terapia) {
            /* Campos auditores */
            $paramsTMP['terapia']['updated_at'] = date('Y-m-d H:i:s');
            $paramsTMP['terapia']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */
                        
            \DB::beginTransaction();
            try {
                //Graba en 1 tabla (terapia)
                //return $this->crearRespuesta("Jc".$terapia->idcitaterapeutica, [200, 'info']);
                $idestado = $terapia->idestado;

                if ($terapia->idcitaterapeutica) {
                    if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'cancelar') { //Enviado de cancelar
                        $idestado = 32;//Pendiente
                    } else {
                        $idestado = 34;//Atendido
                    }

                    \DB::table('citaterapeutica')
                        ->where(['idcitaterapeutica' => $terapia->idcitaterapeutica])
                        ->update(['idestado' => $idestado]);
                }

                if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'finalizar') {
                    $tmp = $this->actualizarPresupuestos($terapia, 'sumar', TRUE, $enterprise);
                    //return $this->crearRespuesta("Jc ".$terapia->idcitaterapeutica, [200, 'info'],'','', $tmp);
                }

                if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'cancelar') { 
                    //En caso terapia ya este atendida, se realizara el reajuste en el presupuesto                    
                    if ($terapia->idestado === 38) {                        
                        $this->actualizarPresupuestos($terapia, 'restar', TRUE, $enterprise); 
                    } 
                }

                if (isset($paramsTMP['accion']) && $paramsTMP['accion'] === 'atendido') {                    
                    foreach ($nuevos as $row) {
                        unset($row['variacion']);
                        \DB::table('terapiatratamiento')->insert($row);
                    }

                    foreach ($modificados as $row) {
                        $where = array('idterapiatratamiento' => $row['idterapiatratamiento']);    
                        $update = array(
                            'cantidad' => $row['cantidad'], 
                            'updated_at' => $row['updated_at'], 
                            'id_updated_at' => $row['id_updated_at']
                        );
                        \DB::table('terapiatratamiento')->where($where)->update($update); 
                    }

                    foreach ($items as $row) { 
                        $where = array('idpresupuestodet' => $row['idpresupuestodet']);
                        $update = array(
                            'cantefectivo' => $row['cantefectivo'] + $row['cantidad'], 
                            'updated_at' => date('Y-m-d H:i:s'), 
                            'id_updated_at' => $this->objTtoken->my
                        );
                        \DB::table('presupuestodet')->where($where)->update($update);
                    }

                    if ($montoVARIACION !== 0) { 
                        $fill['montoefectuado'] = $presupuesto->montoefectuado + $montoVARIACION;
                        $fill['updated_at'] = date('Y-m-d H:i:s');
                        $fill['id_updated_at'] = $this->objTtoken->my;

                        $presupuesto->fill($fill);
                        $presupuesto->save();
                        $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                    }
                }
                
                // if (isset($request['procedimientos']) && isset($request['idcicloatencion'])) {
                //     $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                //     $terapia->GrabarProcedimientos($request['procedimientos'], $param);
                // }
                
                // if (isset($request['tecnicas']) && isset($request['idcicloatencion'])) {
                //     $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                //     $terapia->GrabarTecnicas($request['tecnicas'], $param);
                // }

                // if (isset($request['puntosimg']) && isset($request['idcicloatencion'])) {
                //     $param = array('idterapia' => $id, 'idcicloatencion' => $request['idcicloatencion']);
                //     $terapia->GrabarPuntos($request['puntosimg'], $param);
                // }

                $terapia->fill($paramsTMP['terapia']);
                $terapia->save();
                $terapia->grabarLog($id, $this->objTtoken->my);

                if (isset($paramsTMP['accion']) && (($paramsTMP['accion'] === 'cancelar' && $idestado === 38) ||  $paramsTMP['accion'] === 'finalizar')) { 
                    foreach ($ciclos as $ciclo) {
                        \DB::table('cicloatencion')->where(['idcicloatencion' => $ciclo->idcicloatencion])->update([
                            'terminot' => $this->obtenerTerminotratamiento(array('cicloatencion.idcicloatencion' => $ciclo->idcicloatencion)),
                            'ultimot' => $terapia->ultimoTratamiento(array('terapiatratamiento.idcicloatencion' => $ciclo->idcicloatencion, 'terapia.idestado' => 38)),
                            'primert' => $terapia->primerTratamiento(array('terapiatratamiento.idcicloatencion' => $ciclo->idcicloatencion, 'terapia.idestado' => 38))
                        ]);

                        $p = array(
                            'post.idempresa' => $idempresa,
                            'post.idcicloatencion' => $ciclo->idcicloatencion,
                        ); 
                        $post->ultimallamadaefectiva($p, $ciclo->idcicloatencion, 'cicloatencion');
                        $post->cantidadllamadaefectiva($p, $ciclo->idcicloatencion, 'cicloatencion');
                    } 
                }                 

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Terapia ha sido editado.', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una terapiax', 404);
    }

    public function actcicloatencion($enterprise) { 
        $terapia = new terapia();

        $ciclos = \DB::table('cicloatencion')
            ->select(['cicloatencion.idcicloatencion']) 
            // ->where(['cicloatencion.idsede' => 1])
            ->whereRaw("YEAR(fecha) = 2016 AND MONTH(fecha) = 10")
            // ->whereRaw("YEAR(fecha) = 2016")
            ->whereNull('cicloatencion.deleted') 
            ->get()->all();

        // dd($ciclos);

        foreach ($ciclos as $ciclo) {
            \DB::table('cicloatencion')->where(['idcicloatencion' => $ciclo->idcicloatencion])->update([
                'terminot' => $this->obtenerTerminotratamiento(array('cicloatencion.idcicloatencion' => $ciclo->idcicloatencion)),
                'ultimot' => $terapia->ultimoTratamiento(array('terapiatratamiento.idcicloatencion' => $ciclo->idcicloatencion, 'terapia.idestado' => 38)),
                'primert' => $terapia->primerTratamiento(array('terapiatratamiento.idcicloatencion' => $ciclo->idcicloatencion, 'terapia.idestado' => 38))
            ]);             
        }
    }

    function obtenerTerminotratamiento($param){
        $objPresupuesto = new presupuesto();

        $terminot = '0';
        $presupuestosdetalles = $objPresupuesto->presupuestodetalle($param); 
        
        foreach($presupuestosdetalles as $row){            
            if($row->cantcliente !== $row->cantefectivo){
                $terminot = '0';
                break;
            }
            $terminot = '1';
        }

        return $terminot;
    }

    private function actualizarPresupuestos($terapia, $operacion, $cerrarCiclo = FALSE, $enterprise = '') {
        
        $empresa = new empresa();
        $objPresupuesto = new presupuesto();
        
        $idempresa = $empresa->idempresa($enterprise);
                
        $ciclos = $terapia->terapiatratamientos(['terapia.idterapia' => $terapia->idterapia], ['terapiatratamiento.idcicloatencion'], TRUE, TRUE);
        
        $cicloscerrados = [];
        foreach ($ciclos as $ciclo) {
            /* Actualizar presupuesto: Cantidades efectudadas y monto efectuado.
             */
            $presupuesto = presupuesto::where('idcicloatencion', '=', $ciclo->idcicloatencion)->first();
            $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);

            $fields = ['terapiatratamiento.idproducto', 'terapiatratamiento.cantidad'];
            $dataTratamiento = $terapia->terapiatratamientos(['terapia.idterapia' => $terapia->idterapia, 'cicloatencion.idcicloatencion' => $ciclo->idcicloatencion], $fields, TRUE);

            $itemsTemp = [];
            foreach ($dataTratamiento as $row) { 
                if (!isset($itemsTemp[$row->idproducto])) {
                    $itemsTemp[$row->idproducto]['idproducto'] = $row->idproducto;
                    $itemsTemp[$row->idproducto]['cantidad'] = 0;
                }
                $itemsTemp[$row->idproducto]['cantidad'] += $row->cantidad;
            } 

            $presupuestodetUpdate = [];

            $montoefectuado = 0;
            foreach ($presupuestodet as $row) {

                $cantidad = $row->cantefectivo;
                $preciounit = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);

                $tmp = array(
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my
                );

                foreach ($itemsTemp as $fila) {
                    if ($fila['idproducto'] === $row->idproducto) {

                        if ($operacion === 'sumar')
                            $cantidad = $row->cantefectivo + $fila['cantidad'];

                        if ($operacion === 'restar')
                            $cantidad = $row->cantefectivo - $fila['cantidad'];

                        $tmp['cantefectivo'] = $cantidad;
                        $presupuestodetUpdate[] = array(
                            'data' => $tmp,
                            'where' => ['idpresupuestodet' => $row->idpresupuestodet]
                        );

                        break;
                    }
                }

                if ($cantidad)
                    $montoefectuado = $montoefectuado + ( $preciounit * $cantidad);
            }

            //ACTUALIZAR DETALLE
            foreach ($presupuestodetUpdate as $fila) {
                \DB::table('presupuestodet')->where($fila['where'])->update($fila['data']);
            }

            //ACTUALIZAR CABECERA                                    
            $paramPresupuesto['montoefectuado'] = $montoefectuado;
            $paramPresupuesto['updated_at'] = date('Y-m-d H:i:s');
            $paramPresupuesto['id_updated_at'] = $this->objTtoken->my;

            $presupuesto->fill($paramPresupuesto);
            $presupuesto->save();

            //LogPresupuesto  
            $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
            
            if($cerrarCiclo) {
                //dd('Psa algo');
                $cicloscerrados[] = $this->cerrarCiclosdeatencionV2($idempresa, $ciclo->idcicloatencion);
            }
        }
        
        return $cicloscerrados;
    }

    public function destroy($enterprise, $id) {

        $citamedica = citamedica::find($id);

        //VALIDACIONES

        if ($citamedica) {
            \DB::beginTransaction();
            try {
                //Graba en 1 tablaa(citamedica)                 
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $citamedica->fill($auditoria);
                $citamedica->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita médica a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }

    public function ingreso(Request $request, $enterprise) {
        /* Autor: chaucachavez@gmail.com
         * Descripcion: Validacion de autenticacion para terapia
         * Param obligatorio(username, idsede)
         */
        //$paramsTMP = $request->all(); 
        //return $this->crearRespuesta('hola', [200, 'info']);
        $empresa = new empresa();
        $entidad = new entidad();
        $ObjTerapia = new terapia();
        $objPresupuesto = new presupuesto();
        $citaterapeutica = new citaterapeutica();
        $objAutorizacionterapia = new autorizacionterapia();
        $venta = new venta();

        $idsede = $request->get('idsede');
        $idempresa = $empresa->idempresa($enterprise);
        $idcicloatencion = $request->get('idcicloatencion');

        $user = $entidad->entidad(['entidad.idempresa' => $idempresa,'numerodoc' => $request->get('username')]);

        $terapiaCreate = NULL;
        if ($user) {
            //Usuario existe   
            if ($user->tipocliente === '1') {
                //Es cliente 
                $respuesta['cliente'] = $user;
                $respuesta['mensajes'] = $empresa->sedehorarios($idsede);

                $param = [];
                $param['terapia.idempresa'] = $idempresa;
                $param['terapia.idsede'] = $idsede;
                $param['terapia.fecha'] = date('Y-m-d');
                $param['terapia.idpaciente'] = $user->identidad;

                $whereIn = [36, 37]; //36: Espera | 37: Sala | 38: Atendido | 39: Cancelada
                $terapia = $ObjTerapia->terapia('', $param, $whereIn);

                $respuesta['terapias'] = $terapia;
 
                if (empty($terapia)) {
                    $param = array(
                        'presupuesto.idempresa' => $idempresa,                        
                        'presupuesto.idcliente' => $user->identidad,
                        'cicloatencion.idsede' => $idsede,
                        'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
                    );

                    $presupuestos = $objPresupuesto->grid($param);
                    $presupuestosdetalles = $objPresupuesto->presupuestodetalle($param);

                    if (!empty($presupuestos)) {
                        $param = array(
                            'idempresa' => $idempresa,
                            'idsede' => $idsede,
                            'idcliente' => $user->identidad,
                            'fecha' => date('Y-m-d')
                        );

                        $tieneAutorizacion = !empty($objAutorizacionterapia->autorizacionterapia('', $param)) ? true : false;
                        $tieneSaldo = false;
                        foreach ($presupuestos as $row) {
                            $creditodisp = $row->montocredito - $row->montoefectuado;
                            if ($creditodisp > 0) {
                                $tieneSaldo = true;
                            }
                        }

                        $costoCero = false;
                        foreach ($presupuestosdetalles as $row) {
                            $pendiente = $row->cantcliente - $row->cantefectivo;
                            if ($pendiente > 0) {
                                $costoCero = true;
                                break;
                            }
                        }

                        //Tiene saldo en al menos uno de sus presupuestos O tiene autorizacion libre a terapia O tiene tratamientos de costo cero.
                        if ($tieneSaldo || $tieneAutorizacion || $costoCero) {

                            //Ciclo referencia al cual el paciente espera atenderse, OJO en sala de terappia puede cambiar de ciclo.    
                            //Total de venta en el dia, hasta el momento del ingreso.
                            $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $idcicloatencion]);
                            $ventas = $venta->grid(['venta.idcicloatencion' => $idcicloatencion, 'venta.idestadodocumento' => 27, 'fechaventa' => date('Y-m-d')]);
                            $total = 0;

                            foreach($ventas as $row){
                                $total =+ $row->total;
                            }

                            $data = array(
                                'idempresa' => $idempresa,
                                'idsede' => $idsede,
                                'idpaciente' => $user->identidad,
                                'idestado' => 36, 
                                'hora_llegada' => date('H:i:s'),
                                'fecha' => date('Y-m-d'),
                                'created_at' => date('Y-m-d H:i:s'),
                                'id_created_at' => $this->objTtoken->my, 
                                'idcicloatencion' => isset($idcicloatencion) ? $idcicloatencion : NULL, //Referencia
                                'montodisponible' => round($presupuesto->montopago - $presupuesto->montoefectuado, 2), //Referencia
                                'montopendiente' => round($presupuesto->total - $presupuesto->montopago, 2), //Referencia
                                'montoefectuado' => $presupuesto->montoefectuado, //Referencia
                                'montopagado' => $total //Referencia
                            ); 

                            //Consulta si tiene hora programada 
                            $param = array(
                                'citaterapeutica.idempresa' => $idempresa,
                                'citaterapeutica.idsede' => $idsede,
                                'citaterapeutica.idpaciente' => $user->identidad,
                                'citaterapeutica.fecha' => date('Y-m-d')
                            );


                            //Quitado el 06.032018, porque ahora el idcitaterapeutica es enviado.

                            // $datacitat = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34]);

                            // $hora = mktime((int) date('H'), (int) date('i'), 0, (int) date('m'), (int) date('j'), (int) date('Y'));
                            // foreach ($datacitat as $row) {
                            //     $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                            //     $row->start_s_desde = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']) - 1800; // - 30 min
                            //     $row->start_s_hasta = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']) + 600; // + 30 min                                                                

                            //     $data['hora_cita'] = $row->inicio;

                            //     if ($row->start_s_desde <= $hora && $hora <= $row->start_s_hasta) {
                            //         $data['idcitaterapeutica'] = $row->idcitaterapeutica;
                            //         $data['asistencia'] = '1';
                            //         break;
                            //     }
                            // }

                            //Añadido el 06.032018, porque ahora el idcitaterapeutica es enviado.
                            if($request->get('idcitaterapeutica') && !empty($request->get('idcitaterapeutica'))) {
                                $rowcitat = $citaterapeutica->citaterapeutica($request->get('idcitaterapeutica'));
                                $data['idcitaterapeutica'] = $request->get('idcitaterapeutica');
                                $data['asistencia'] = '1';
                                $data['hora_cita'] = $rowcitat->inicio;
                            }

                            $terapiaCreate = terapia::create($data);
                            $respuesta['message'] = 'Paciente ingresado.';
                        } else {
                            return $this->crearRespuesta('No tiene cr&eacute;dito disponible', [200, 'info']);
                        }
                    } else {
                        return $this->crearRespuesta('Paciente no tiene presupuesto', [200, 'info']);
                    }
                } else {
                    switch ($terapia->idestado):
                        case 36: //36: Espera 
                            $respuesta['message'] = 'Paciente en sala de espera.';
                            break;
                        case 37: //37: Sala 
                            $respuesta['message'] = 'Paciente en sala de terapia.';
                            break;
                    endswitch;

                    return $this->crearRespuesta($respuesta['message'], [200, 'info']);
                }
            } else {
                //$respuesta = ['success' => false, 'message' => 'Usted no es un cliente.'];
                return $this->crearRespuesta('No es un cliente.', [200, 'info']);
            }
        } else {
            //$respuesta = ['success' => false, 'message' => 'Usuario no existe'];
            return $this->crearRespuesta('Usuario no existe', [200, 'info']);
        }

        return $this->crearRespuesta($respuesta['message'], 200, '', '', $terapiaCreate);
    } 
    
    public function cerrarCiclosdeatencionV2($idempresa, $idcicloatencion = '') {

        $cicloatencion = new cicloatencion(); 
        $maxDiasTerapia = 21; //al 22vo dia en que ya no viene a terapia se cerra el ciclo de atencion del paciente
        $maxDiasOpenCiclo = 21; 
        $resultado = [];

        $param = array(
            'cicloatencion.idempresa' => $idempresa,
            'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
        );

        if(!empty($idcicloatencion))
            $param['cicloatencion.idcicloatencion'] = $idcicloatencion; 

        // Todos los ciclos abiertos.
        // Ciclo (cantcliente es igual cantefectivo) entonces SE CIERRA. 
        // Ciclo con más de 21 días desde ultima terapia. SE CIERRA.
        // Ciclo con más de 21 días que no inicia terapias. SE CIERRA.
        $campos = array('cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.terminot', 'cicloatencion.ultimot');
        $dataciclo = $cicloatencion->grid($param, '', [], '', '', '', false, $campos);
 

        foreach($dataciclo as $row){ 
            $fechaIF = $this->fechaInicioFin($row->fecha, '00:00:00', '00:00:00');
            $fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $hoy_s = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));              
            $row->fecha_dias_tr = ($hoy_s - $fecha_s)/86400; //1 dia = 86400s 

            $row->ultimot_dias_tr = NULL;
            if($row->ultimot) {
                $fechaIF = $this->fechaInicioFin($row->ultimot, '00:00:00', '00:00:00');
                $fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $hoy_s = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));              
                $row->ultimot_dias_tr = ($hoy_s - $fecha_s)/86400; //1 dia = 86400s 
            }

            if ($row->terminot === '1' || 
                ($row->ultimot_dias_tr && $row->ultimot_dias_tr > $maxDiasTerapia) || 
                (!$row->ultimot_dias_tr && $row->fecha_dias_tr > $maxDiasOpenCiclo )) { 
                $resultado[] = $row->idcicloatencion;
            }
        } 

        // foreach($resultado as $val){
        //     \DB::table('cicloatencion')->where(['idcicloatencion' => $val])->update(['idestado' => 21, 'fechacierre' => date('Y-m-d')]); //20:Aperturado 21:Cerrado 22:Cancelado

        // }
        
        if($resultado) {
            \DB::table('cicloatencion')->whereIn('idcicloatencion', $resultado)
                ->update(['idestado' => 21, 'fechacierre' => date('Y-m-d')]); //20:Aperturado 21:Cerrado 22:Cancelado
        }

        return array($resultado);
    }

    private function cerrarCiclosdeatencion($idempresa, $hT = true, $idcicloatencion = '') {
        $maxDias = 21; //al 22vo dia en que ya no viene a terapia se cerra el ciclo de atencion del paciente
        $objPresupuesto = new presupuesto();
        $terapia = new terapia();
        
        $param = array(
            'presupuesto.idempresa' => $idempresa,
            'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
        );
        
        if(!empty($idcicloatencion))
            $param['cicloatencion.idcicloatencion'] = $idcicloatencion;        

        $presupuestos = $objPresupuesto->grid($param);
        $presupuestosdetalles = $objPresupuesto->presupuestodetalle($param);        
       
        $resultado = [];                
        $terapiasTerminadas = [];
        $terapiasPorpagarse = [];         
        
        foreach($presupuestos as $row){
            $row->terapiaterminada = false;
            foreach($presupuestosdetalles as $row2){
                if($row2->idpresupuesto === $row->idpresupuesto){
                    if($row2->cantcliente === $row2->cantefectivo){
                        $row->terapiaterminada = true;
                    }else{
                        $row->terapiaterminada = false;
                        break;
                        //Efectuado todas las indicadas por el cliente
                    }
                }
            }
            
            if($row->terapiaterminada)
                $terapiasTerminadas[] = $row->idcicloatencion;            
            
            $creditodisp = $row->montopago - $row->montoefectuado; 
            
            if($creditodisp < 0)
                $terapiasPorpagarse[] = $row->idcicloatencion;            
        } 
        
        /*Historial terapeutico*/
        if($hT){
            $terapiasTiempomax = [];
            $param2 = array(
                'terapia.idempresa' => $idempresa,
                'cicloatencion.idestado' => 20, //20:Aperturado 21:Cerrado 22:Cancelado
                'terapia.idestado' => 38 //36: Espera  37: Sala 38: Atendido  39: Cancelada
            );
            $historialterapeutico = $terapia->terapiatratamientos($param2, ['terapia.fecha', 'cicloatencion.idcicloatencion'], true);

            foreach($historialterapeutico as $row){ 
                $fechaIF = $this->fechaInicioFin($row->fecha, '00:00:00', '00:00:00');
                $row->fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $hoy_s = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y')); 
                $segundos = $hoy_s - $row->fecha_s;  
                $row->dias_tr = $segundos/86400; //1 dia = 86400s 
            }

            $historialterapeutico = $this->ordenarMultidimension($historialterapeutico, 'idcicloatencion', SORT_DESC, 'fecha_s', SORT_DESC);

            $idtmp = null;
            foreach($historialterapeutico as $row){
                if($idtmp !== $row->idcicloatencion){ //Tomara el primer valor
                    if($row->dias_tr > $maxDias)
                        $terapiasTiempomax[] = $row->idcicloatencion;                    
                    $idtmp = $row->idcicloatencion; 
                }
            }
            
            foreach($terapiasTiempomax as $val){
                if(!in_array($val, $resultado) && !in_array($val, $terapiasPorpagarse))
                    $resultado[] = $val;
            }
        }
        /*Fin Historial terapeutico*/        
        
        foreach($terapiasTerminadas as $val){
            if(!in_array($val, $resultado) && !in_array($val, $terapiasPorpagarse))
                $resultado[] = $val;
        }
        
        foreach($resultado as $val){
            \DB::table('cicloatencion')->where(['idcicloatencion' => $val])->update(['idestado' => 21]); 
        }
        
        return array($resultado, $terapiasPorpagarse);
    } 

    public function log($enterprise, $id) {
         
        $objTerapia = new terapia();  
        
        $data = $objTerapia->listaLogTerapia($id);
                
        return $this->crearRespuesta($data, 200); 
    } 
    
    public function logshow(Request $request, $enterprise, $id) {
          
        $objTerapia = new terapia(); 
        $empresa = new empresa(); 

        $paramsTMP = $request->all(); 

        $terapia = $objTerapia->logterapia($id);
        $idempresa = $empresa->idempresa($enterprise);

        if ($terapia) { 
 
            $tratamientos = $objTerapia->logterapiatratamientos(['logterapia.idlogterapia' => $id]);
   
            $listcombox = array(
                'tratamientos' => $tratamientos 
            );

            return $this->crearRespuesta($terapia, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Terapia no encotrado', 404); 
    }  
 
    public function storeFirma(Request $request, $enterprise, $id) {
        //moutecfb | Exito@2016
        $empresa = new empresa();
        $request = $request->all();
        $terapia = terapia::find($id);        
 
        $baseFirma = explode(',', $request['firma']); //"data:image/png;base64,BBBFBfj42Pj4....";
        $data = base64_decode($baseFirma[1]);
        
        if (!empty($terapia->firma)) {
            return $this->crearRespuesta('Paciente ya tiene firma.', [200, 'info']);
        }

        if ($terapia) {

            \DB::beginTransaction();
            try {

                $nombre = 'firma_'.$terapia->idpaciente.'_' . $terapia->idterapia . '_' . date('Y-m-d_H-i-s') .'.png';
                
                // $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\firmas_terapia\\' . $nombre;
                $pathImg =  '/home/centromedico/public_html/apiosi/public/firmas_terapia/'. $nombre;
                // $pathImg =  '/home/ositest/public_html/apiosi/public/firmas_terapia/'. $nombre;

                //25.03.2019 Si esta en Espera y desea pasar a Sala. (Opción Terapia > Sala de terapia)
                if (isset($request['idestado']) && $request['idestado'] === 37 && $terapia->idestado === 36) {

                    $sedehorario = $empresa->sedehorarios($terapia->idsede);
                    $minutos = substr($sedehorario->cronometroterapia, 3, 2);
                    $hora = date('H:i:s');

                    $terapia->idestado = 37;
                    $terapia->inicio = $hora;
                    $terapia->fin = date('H:i:s', strtotime('+' . $minutos . ' minute', strtotime(date('Y-m-j' . ' ' . $hora))));                    
                }

                file_put_contents($pathImg, $data);  

                $row = \DB::table('entidad')
                    ->select('entidad')
                    ->where('identidad', $this->objTtoken->my)
                    ->first();

                $fechafirma = date('Y-m-d H:i:s');

                $data = array(
                    'firma' => $nombre,                    
                    'fechafirma' => $fechafirma,
                    'personalfirma' => $row->entidad
                );

                $terapia->firma = $nombre;
                $terapia->identidadfirma = $this->objTtoken->my;
                $terapia->fechafirma = $fechafirma; 
                $terapia->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            
            return $this->crearRespuesta($data, 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una terapia', 404);
    }

    public function deleteFirma(Request $request, $enterprise, $id) {
      
        $empresa = new empresa();
        $terapia = terapia::find($id);         

        $request = $request->all();
        
        if (empty($terapia->firma)) {
            return $this->crearRespuesta('Terapia no tiene firma.', [200, 'info']);
        }

        if (!file_exists($this->pathImg . $terapia->firma)) {
            return $this->crearRespuesta('Archivo de firma no existe. Comunicarse con administrador.', [200, 'info']);
        }

        if (!is_readable($this->pathImg . $terapia->firma)) {
            return $this->crearRespuesta('Archivo de firma no es legible. Comunicarse con administrador.', [200, 'info']);
        }

        if ($terapia) {
            \DB::beginTransaction();
            try {
                if (isset($terapia->firma) && !empty($terapia->firma)) {                  
                    if (unlink($this->pathImg . $terapia->firma)) { 
                        $terapia->firma = NULL;
                        $terapia->identidadfirmadel = $this->objTtoken->my;
                        $terapia->fechafirmadel = date('Y-m-d H:i:s'); 
                        $terapia->save(); 
                    }
                }  
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta("Firma eliminada", 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una terapia', 404);
    }

    public function copiarFirmaxGrupo(Request $request, $enterprise, $idcicloatencion, $idgrupodx)  {

        if (empty($idcicloatencion)) {
            return $this->crearRespuesta('Especificar ciclo de atención.', [200, 'info']);
        }

        if (empty($idgrupodx)) {
            return $this->crearRespuesta('Especificar grupoDx.', [200, 'info']);
        }

        $data = \DB::table('terapiatratamiento')
                    ->select('terapia.idterapia', 'terapia.idpaciente')
                    ->join('terapia', 'terapiatratamiento.idterapia', '=', 'terapia.idterapia')
                    ->where('terapia.idestado', 38)
                    ->where('terapiatratamiento.idcicloatencion', $idcicloatencion)
                    ->where('terapiatratamiento.idgrupodx', $idgrupodx)
                    ->whereNull('terapia.firma')
                    ->whereNull('terapia.deleted')
                    ->whereNull('terapiatratamiento.deleted')
                    ->distinct()->get()->all();  
                    
        $idpaciente = null;
        $terapia = null;

        if ($data) {
            $terapia = \DB::table('terapia')
                        ->select('idterapia', 'firma')
                        ->where('idpaciente', '=', $data[0]->idpaciente)
                        ->where('idestado', 38)
                        ->whereNotNull('firma') 
                        ->whereNull('deleted')
                        ->orderBy('idterapia', 'desc')
                        ->first(); 

            if (!$terapia) {
                return $this->crearRespuesta('Paciente no cuenta con firma disponible a copiar.', [200, 'info']);
            }
        }
        // dd($data, $terapia);

        if ($data) {

            \DB::beginTransaction();
            try {
                foreach ($data as $row) {
                    $update = [
                        'identidadfirma' => $this->objTtoken->my,
                        'fechafirma' => date('Y-m-d'),
                        'firma' => $terapia->firma,
                        'firmaterapia' => $terapia->idterapia
                    ]; 

                    \DB::table('terapia')
                        ->where('idterapia', $row->idterapia)
                        ->update($update);

                   \Log::info(print_r($terapia->firma . 'copiada. idterapia: ' . $row->idterapia, true)); 
                }
            } catch (QueryException $e) {
                \DB::rollback(); 
            }
            \DB::commit();

            return $this->crearRespuesta($terapia, 200);
        }


        return $this->crearRespuestaError('No existes asistencias sin firma.', 404);
    }
}
