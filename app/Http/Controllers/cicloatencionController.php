<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\post;
use App\Models\sede;
use App\Models\tarea;
use App\Models\venta;
use App\Models\modelo;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\grupodx;
use App\Models\paquete;
use App\Models\terapia;
use App\Models\producto;
use App\Models\tareadet;
use App\Models\ventadet;
use App\Models\tarifario;
use App\Models\citamedica;
use App\Exports\DataExport;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\dxtratamiento;
use App\Models\ciclomovimiento;
use App\Models\cicloautorizacion;
use App\Models\autorizacionimagen;
use App\Http\Controllers\Pdfs\hojadeatencionController;
use App\Http\Controllers\Pdfs\imagenautorizacionController;

class cicloatencionController extends Controller {
    
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\atenciones\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/atenciones/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/atenciones/';

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario 
         */
        $sede = new sede();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
        );

        return $this->crearRespuesta($data, 200);
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $objCitamedica = new citamedica();
        $cicloatencion = new cicloatencion();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['cicloatencion.idempresa'] = $idempresa; 

        if (isset($paramsTMP['id_created_at']) && !empty($paramsTMP['id_created_at'])) {
            $param['cicloatencion.id_created_at'] = $paramsTMP['id_created_at'];
        }

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['cicloatencion.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['cliente.identidad'] = $paramsTMP['idpaciente'];
        }

        if (isset($paramsTMP['numerodoc']) && !empty($paramsTMP['numerodoc'])) {
            $param['cliente.numerodoc'] = $paramsTMP['numerodoc'];
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['cicloatencion.idestado'] = $paramsTMP['idestado'];
        } 

        if (isset($paramsTMP['terminot']) && !empty($paramsTMP['idestado'])) {
            $param['cicloatencion.terminot'] = $paramsTMP['terminot'];
        }  

        if (isset($paramsTMP['ultimot']) && !empty($paramsTMP['ultimot'])) {
            $param['cicloatencion.ultimot'] = $this->formatFecha($paramsTMP['ultimot'], 'yyyy-mm-dd');
        } 

        if (isset($paramsTMP['idestadofactura']) && !empty($paramsTMP['idestadofactura'])) {
            $param['cicloatencion.idestadofactura'] = $paramsTMP['idestadofactura'];
        }  

        //dd($param['cicloatencion.terminot']);

        $whereInestadopago = [];
        if (isset($paramsTMP['inEstadopago']) && !empty($paramsTMP['inEstadopago'])) {
            $whereInestadopago = explode(',', $paramsTMP['inEstadopago']);
        }

        $notultimot = false;
        if (isset($paramsTMP['notultimot']) && $paramsTMP['notultimot'] === '1') {
            $notultimot = true;
        } 

        $seguimiento = false;
        if (isset($paramsTMP['seguimiento']) && $paramsTMP['seguimiento'] === '1') {
            $seguimiento = true;
        } 

        $notexistscicloopen = false;
        if (isset($paramsTMP['notexistscicloopen']) && $paramsTMP['notexistscicloopen'] === '1') {
            $notexistscicloopen = true;
        }
        
        $notexistsultimat = false;
        if (isset($paramsTMP['notexistsultimat']) && $paramsTMP['notexistsultimat'] === '1') {
            $notexistsultimat = true;
        }
        
        $ordenseguimiento = false;
        if (isset($paramsTMP['ordenseguimiento']) && $paramsTMP['ordenseguimiento'] === '1') {
            $ordenseguimiento = true;
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'cicloatencion.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $between = array();

        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        // Validacion 45 por COVID
        if (isset($paramsTMP['formato']) && 
            !empty($paramsTMP['formato']) &&  
            in_array($paramsTMP['formato'], ['xls', 'xlsx']) &&
            $this->objTtoken->myperfilid !== 1 && 
            $this->objTtoken->my !== 28874 // Maribel
        ) {

            $fechaMaxima = strtotime('-45 day', strtotime(date('Y-m-d')));
            $fechausuario = strtotime($paramsTMP['desde']);

            if ($fechausuario <= $fechaMaxima) {
                return response()->json('ACCESO DENEGADO: Solo puedes descargar ultimo 45 dias.', 200);
            }
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $distrito = false;
        if (isset($paramsTMP['distrito']) && $paramsTMP['distrito'] === '1') {          
            $distrito = true; 
        }
        
        $dataciclo = $cicloatencion->grid($param, $like, $between, $pageSize, $orderName, $orderSort, false, [], $notultimot, $notexistscicloopen, $notexistsultimat, $seguimiento, $ordenseguimiento, $whereInestadopago, false, [], [], false, false, false, false, $distrito);   
        
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $dataciclo->total();
            $dataciclo = $dataciclo->items();
        }

        // dd('');
         
        if (isset($paramsTMP['trata']) && $paramsTMP['trata'] === '1' && !empty($dataciclo)) {
            
            $whereIdcicloatencionIn = array();
            foreach($dataciclo as $row){ 
                $whereIdcicloatencionIn[] = $row->idcicloatencion;
            }

            // Autorizacion valida('1') de Fisioterapia(2) 
            // ->where(array('cicloautorizacion.idproducto' => 2, 'cicloautorizacion.principal' => '1')) 
            // dd($whereIdcicloatencionIn);
            $coaseguos = \DB::table('cicloautorizacion') 
                ->select('cicloautorizacion.idcicloatencion', 'aseguradora.nombre as nombreaseguradora', 'cicloautorizacion.deducible', 'cicloautorizacion.coaseguro', 
                    'afiliado.acronimo', 
                    'venta.serie', 
                    'venta.serienumero',
                    'venta.fechaventa',
                    'venta.total'
                )
                ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora') 
                ->leftJoin('venta', 'cicloautorizacion.idventa', '=', 'venta.idventa') 
                ->leftJoin('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->where(array('cicloautorizacion.idproducto' => 2)) 
                ->whereIn('cicloautorizacion.idcicloatencion', $whereIdcicloatencionIn) 
                ->whereNull('cicloautorizacion.deleted') 
                ->get()->all();

            // dd($coaseguos);
            //Citas atendidas(6), ordenadas por tiempo(importante)
            $citasmedicas = \DB::table('citamedica') 
                ->select('citamedica.idcicloatencion', 'citamedica.idcitamedica', 'citamedica.fecha','citamedica.inicio', 'tipo.nombre as nombretipo', 'medico.entidad as medico', 'diagnostico.nombre as diagnostico')
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
                ->leftJoin('estadodocumento as tipo', 'citamedica.idtipo', '=', 'tipo.idestadodocumento')
                ->leftJoin('diagnostico', 'citamedica.iddiagnostico', '=', 'diagnostico.iddiagnostico') 
                ->where('citamedica.idestado', 6)
                ->whereIn('citamedica.idcicloatencion', $whereIdcicloatencionIn)
                ->whereNull('citamedica.deleted')
                ->orderBy('citamedica.idcicloatencion', 'DESC')
                ->orderBy('citamedica.fecha', 'DESC')
                ->orderBy('citamedica.inicio', 'DESC')
                ->get()->all();
                
            //Diagnositicos
            $diagnosticosmedicos = [];
            if(!empty($whereIdcicloatencionIn)){ 
                $datadiagnosticos = $objCitamedica->diagnosticomedico(['citamedica.idempresa' => $idempresa], '', $whereIdcicloatencionIn); 
                foreach($datadiagnosticos as $row){
                    $diagnosticosmedicos[$row->idcicloatencion][] = $row->nombre;
                }
            }

            //Calculo de dias asistidos a terapia
            $diasasistencia = \DB::table('terapia')
                ->join('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia') 
                ->select('terapiatratamiento.idcicloatencion', \DB::raw('count(distinct terapia.fecha) as nrodia'))
                ->where('terapia.idestado', 38)
                ->whereNull('terapia.deleted')
                ->whereNull('terapiatratamiento.deleted')
                ->whereIn('terapiatratamiento.idcicloatencion', $whereIdcicloatencionIn) 
                ->groupBy('terapiatratamiento.idcicloatencion')  
                ->get()->all(); 
               // dd(\DB::getQueryLog());
            //dd($diasasistencia); 
            
            $terapia = new terapia();
            $param = array(
                'terapia.idempresa' => $idempresa,
                'terapia.idestado' => 38
            );

            $dataterapiatratmp = [];
            $dataterapiatra = $terapia->terapiatratamientoslight($param, ['terapia.idterapia', 'terapia.fecha', 'terapiatratamiento.idcicloatencion', 'terapia.idterapista', 'terapista.entidad as nombreterapista'], TRUE, '', [], true, $whereIdcicloatencionIn, true);
            foreach($dataterapiatra as $row){
                $dataterapiatratmp[$row->idcicloatencion][] = $row;
            }

            
            //Calculo de presupuesto en base a productos
            $productos = \DB::table('presupuestodet')
                ->join('presupuesto', 'presupuestodet.idpresupuesto', '=', 'presupuesto.idpresupuesto')
                ->join('producto', 'presupuestodet.idproducto', '=', 'producto.idproducto')
                ->select('presupuesto.idcicloatencion','presupuesto.tipotarifa', 'presupuestodet.idproducto', 'presupuestodet.cantcliente', 'presupuestodet.cantefectivo', 
                        'presupuestodet.preciounitregular', 'presupuestodet.preciounittarjeta', 'presupuestodet.preciounitefectivo')
                ->whereNull('presupuestodet.deleted')
                ->whereNull('presupuesto.deleted')
                ->whereIn('presupuesto.idcicloatencion', $whereIdcicloatencionIn)
                ->get()->all();
            
            $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA','*'=>'OTROS')];
            $quiebre = array('idcicloatencion' => 'idcicloatencion');
           
            $datatratxterapista = $this->agruparPorColumna($productos, '', $quiebre, '', $gruposProducto, ['cantcliente', 'cantefectivo'], true);    

            
            $data = array();
            foreach($datatratxterapista as $row){ 
                if(!isset($data[$row['idquiebre']])) { 
                    foreach($gruposProducto[1] as $val){ 
                        $data[$row['idquiebre']][$val.'cantcliente'] = null;
                        $data[$row['idquiebre']][$val.'cantefectivo'] = null;
                        $data[$row['idquiebre']][$val.'cantclientecosto'] = null;
                        $data[$row['idquiebre']][$val.'cantefectivocosto'] = null;
                    }
                }

                $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'cantcliente'] = $row['cantcliente'] > 0  ? $row['cantcliente'] : '';
                $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'cantefectivo'] = $row['cantefectivo'] > 0  ? $row['cantefectivo'] : '';
                $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'cantclientecosto'] = $row['cantclientecosto'] > 0  ? $row['cantclientecosto'] : '';
                $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'cantefectivocosto'] = $row['cantefectivocosto'] > 0  ? $row['cantefectivocosto'] : '';
            }

             
            foreach($dataciclo as $row){ 

                //Añadir primer y ultimo personal
                $primerpersonal = null;
                $ultimopersonal = null; 
                $cantidadatencion = isset($dataterapiatratmp[$row->idcicloatencion]) ?  count($dataterapiatratmp[$row->idcicloatencion]) : 0;
                $personalfrecuente = null; 
                $cantidadfrecuente = null;
                $personalterapia = [];

                if(isset($dataterapiatratmp[$row->idcicloatencion])){
                    $i = 1;
                    foreach($dataterapiatratmp[$row->idcicloatencion] as $val){                    
                        if($i === 1)
                            $primerpersonal = $val->nombreterapista;
                        if($i === $cantidadatencion)
                            $ultimopersonal = $val->nombreterapista;
                        if(!isset($personalterapia[$val->idterapista])){
                            $personalterapia[$val->idterapista]['cantidad'] = 0;
                            $personalterapia[$val->idterapista]['nombreterapista'] = $val->nombreterapista;
                        }
                        $personalterapia[$val->idterapista]['cantidad'] += 1;                        
                        $i++;
                    } 

                    $mayor = 0;
                    foreach($personalterapia as $val){
                        if($val['cantidad'] > $mayor){
                            $personalfrecuente = $val['nombreterapista'];
                            $cantidadfrecuente = $val['cantidad'];
                            $mayor = $val['cantidad'];
                        }
                    }
                } 

                $row->primerpersonal = $primerpersonal; 
                $row->ultimopersonal = $ultimopersonal;                 
                $row->cantidadatencion = $cantidadatencion;                 
                $row->cantidadpersonal = count($personalterapia);  
                $row->personalfrecuente = $personalfrecuente; 
                $row->cantidadfrecuente = $cantidadfrecuente;                 

                //Añadir nros dias a OSI 
                $tmpdia = null;
                foreach($diasasistencia as $val){
                    if($val->idcicloatencion === $row->idcicloatencion){
                        $tmpdia = $val; 
                        break;
                    }
                } 
                $row->nrodia = $tmpdia ? $tmpdia->nrodia : null; 

                //Añadir coaseguro de FISIOTERAPIA  
                $tmpcoa = null;
                foreach($coaseguos as $val){
                    if($val->idcicloatencion === $row->idcicloatencion){
                        $tmpcoa = $val; 
                        break;
                    }
                } 

                $row->nombreaseguradora = $tmpcoa ? $tmpcoa->nombreaseguradora : null;
                $row->deducible = $tmpcoa ? $tmpcoa->deducible : null;
                $row->coaseguro = $tmpcoa ? $tmpcoa->coaseguro : null;


                //*Andrés: Datos de envío a Facturacion 03.02.2020*/
                $envioADF = '';
                $envioADFfecha = ''; 
                if (!empty($row->logenvios)) {  
                    $str = explode('|', $row->logenvios);                
                    foreach($str as $value) {
                        $texto = explode(';', $value); 
                        if ($texto[1] === 'Ciclo "enviado" a contabilidad') { 
                            $envioADF = $texto[0];
                            $envioADFfecha = $this->formatFecha(substr($texto[2], 0 , 10));
                        }
                    }
                    // explode('|', $cicloatencion->logenvios);
                } 
                // dd($envioADF, $envioADFfecha);
                // Chong Santa Cruz De Estrella, Luisa Emilia

                // $strlog = $entidad->entidad .  ';'. 'Ciclo "enviado" a contabilidad' . ';' . date('Y-m-d H:i:s');
                // $update['logenvios'] = (!empty($cicloatencion->logenvios) ? ($cicloatencion->logenvios . '|') : '') . $strlog;                
                $row->factura = $tmpcoa && $tmpcoa->acronimo ? ('('. $tmpcoa->acronimo .') '. $tmpcoa->serie . '-' . $tmpcoa->serienumero) : null;
                $row->facturafecha = $tmpcoa && $tmpcoa->fechaventa ? $this->formatFecha($tmpcoa->fechaventa) : null;
                $row->facturatotal = $tmpcoa && $tmpcoa->total ? $tmpcoa->total : null;
                $row->envioADF = $envioADF;
                $row->envioADFfecha = $envioADFfecha;
                // dd($row->logenvios);
                //Añadir ultima consulta medica
                $tmpcita = null;
                foreach($citasmedicas as $val){
                    if($val->idcicloatencion === $row->idcicloatencion){
                        $tmpcita = $val; 
                        break;
                    }
                } 
                $row->medico = $tmpcita ? $tmpcita->medico : null;
                $row->nombretipo = $tmpcita ? $tmpcita->nombretipo : null;

                $row->diagnostico = $tmpcita ? $tmpcita->diagnostico : null;
                $row->diagnosticos = !empty($diagnosticosmedicos) && isset($diagnosticosmedicos[$row->idcicloatencion]) ? implode("|", $diagnosticosmedicos[$row->idcicloatencion]): NULL;
                //Añadir tratamientos
               
                foreach($gruposProducto[1] as $val){ 
                    $cantcliente = $val.'cantcliente';
                    $cantefectivo = $val.'cantefectivo';
                    $cantclientecosto = $val.'cantclientecosto';
                    $cantefectivocosto = $val.'cantefectivocosto';

                    $row->$cantcliente = null; 
                    $row->$cantefectivo = null;
                    $row->$cantclientecosto = null;
                    $row->$cantefectivocosto = null;

                    if(isset($data[$row->idcicloatencion])){
                        $row->$cantcliente = $data[$row->idcicloatencion][$cantcliente];
                        $row->$cantefectivo = $data[$row->idcicloatencion][$cantefectivo];
                        $row->$cantclientecosto = $data[$row->idcicloatencion][$cantclientecosto];
                        $row->$cantefectivocosto = $data[$row->idcicloatencion][$cantefectivocosto];
                    } 
                }
               
            } 

        }

        if (isset($paramsTMP['tra'])  && $paramsTMP['tra'] === '1' && !empty($dataciclo)) {

            $whereIdcicloatencionIn = [];

            foreach($dataciclo as $row){ 
                $whereIdcicloatencionIn[] = $row->idcicloatencion;
                $row->tratamientos = [];
            } 
 
            $productos = \DB::table('presupuestodet')
                        ->join('presupuesto', 'presupuestodet.idpresupuesto', '=', 'presupuesto.idpresupuesto')
                        ->join('producto', 'presupuestodet.idproducto', '=', 'producto.idproducto')
                        ->select('presupuesto.idcicloatencion','presupuesto.tipotarifa', 'presupuestodet.idproducto', 'presupuestodet.cantcliente', 'presupuestodet.cantefectivo',   
                                'presupuestodet.preciounitregular', 'presupuestodet.preciounittarjeta', 'presupuestodet.preciounitefectivo', 'producto.codigo', 'producto.nombre')
                        ->whereNull('presupuestodet.deleted')
                        ->whereNull('presupuesto.deleted')
                        ->whereIn('presupuesto.idcicloatencion', $whereIdcicloatencionIn)  
                        ->get()->all();
 
            foreach($dataciclo as $row){
                foreach($productos as $row2){
                    if($row->idcicloatencion === $row2->idcicloatencion)    {   //dd($row2);
                        $row->tratamientos[] = $row2; }
                } 
            } 
        }

        if (isset($paramsTMP['seguimiento'])  && $paramsTMP['seguimiento'] === '1' && !empty($dataciclo)) {

            $whereIdcicloatencionIn = [];

            foreach($dataciclo as $row){ 
                $whereIdcicloatencionIn[] = $row->idcicloatencion;
                $row->inicio_de_terapia_tarea = 'No';
                $row->inicio_de_terapia_estado = '';
                $row->inicio_de_terapia_acciones = '';
                $row->inicio_de_terapia_fechacreacion = '';
                $row->inicio_de_terapia_agendocita = ''; 
                $row->inicio_de_terapia_agendoasitio = '';
                
            }  

            $tarea = new tarea();
            $param = array(
                'tarea.idempresa' => $idempresa,
                'tarea.idautomatizacion' => '2'
            );

            $datatarea = $tarea->grid($param, [], '', '', '', '', [],  $whereIdcicloatencionIn);

            $datatarea = $this->camposAdicionales($datatarea, $idempresa);
            
            // dd($datatarea);
            foreach ($dataciclo as $row){
                foreach ($datatarea as $row2){
                    if ($row->idcicloatencion === $row2->idcicloatencion)    {    
                        $row->inicio_de_terapia_tarea = 'Si';
                        $row->inicio_de_terapia_estado = $row2->nombreestado;
                        $row->inicio_de_terapia_acciones = $row2->cantacciones;
                        $row->inicio_de_terapia_fechacreacion = $row2->created_at;
                        $row->inicio_de_terapia_agendocita = $row2->agendocita; 
                        $row->inicio_de_terapia_agendoasitio = $row2->agendoasitio;
                    }
                } 
            } 
        }

        if (isset($paramsTMP['posts'])  && $paramsTMP['posts'] === '1' && !empty($dataciclo)) {

            $whereIdcicloatencionIn = []; 

            foreach($dataciclo as $row){ 
                $whereIdcicloatencionIn[] = $row->idcicloatencion; 
                $row->nro_de_seguimientos = 0;
                $row->fecha_seguimiento = '';
                $row->contesto_nocontesto_seguimiento = '';
                $row->motivo_seguimiento = '';
                $row->comentario_seguimiento = ''; 
                $row->usuario_seguimiento = '';                
            }  

            $post = new post();
            $param = array(
                'post.idempresa' => $idempresa 
            );

            $datapost = $post->grid($param, '', '', 'post.fecha', 'asc', $whereIdcicloatencionIn); 
            // dd($datapost);
            foreach ($dataciclo as $row){
                foreach ($datapost as $row2){
                    if ($row->idcicloatencion === $row2->idcicloatencion)    {
                    // dd($row); 
                        $row->nro_de_seguimientos += 1;
                        $row->fecha_seguimiento = $row2->fecha;
                        $row->contesto_nocontesto_seguimiento = $row2->nombrellamada;
                        $row->motivo_seguimiento = $row2->nombreitem;
                        $row->comentario_seguimiento = $row2->mensaje; 
                        $row->usuario_seguimiento = $row2->created;
                    }
                } 
            } 
        }

        // dd($this->objTtoken->my);
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){
                $data = array(); 

                if (!in_array($this->objTtoken->myperfilid, [1, 5, 6 ,19])) {
                    return $this->crearRespuesta('No tienes permiso para descargar Excel.', [200, 'info']);
                }

                foreach($dataciclo as $row){  

                    // dd($row);

                    $TF = $row->TFcantcliente ? $row->TFcantcliente.' TF + ' : NULL;
                    $AC = $row->ACcantcliente ? $row->ACcantcliente.' AC + ' : NULL;
                    $QT = $row->QTcantcliente ? $row->QTcantcliente.' QT + ' : NULL;
                    $OCH = $row->OCHcantcliente ? $row->OCHcantcliente.' OCH + ' : NULL;
                    $ESP = $row->ESPcantcliente ? $row->ESPcantcliente.' ESP + ' : NULL;
                    $BL = $row->BLcantcliente ? $row->BLcantcliente.' BL + ' : NULL;
                    $BMG = $row->BMGcantcliente ? $row->BMGcantcliente.' BMG + ' : NULL;
                    $AGUJA = $row->AGUJAcantcliente ? $row->AGUJAcantcliente.' AGUJA + ' : NULL;
                    $OTROS = $row->OTROScantcliente ? $row->OTROScantcliente.' OTROS + ' : NULL; 
                    $pack = $TF . $AC . $QT . $OCH . $ESP . $BL . $BMG . $AGUJA . $OTROS;
                    $pack = $pack ? substr($pack, 0, -2) : NULL; 

                    $itemArray = array(
                        'SEDE' => $row->sedenombre, 
                        'CICLO' => $row->idcicloatencion,                        
                        'PACIENTE' => $row->paciente, 
                        'N°HC' => $row->hc,
                        'TIPO' => $row->nombretipo,
                        'MEDICO' => $row->medico,
                        'FECHA APERTURA' => $row->fecha,
                        'FECHA CIERRE' => $row->fechacierre,
                        'ESTADO CICLO' => $row->estadociclo,
                        'CREACION' => $row->created,
                        'SEGURO' => $row->nombreaseguradora,
                        'DEDUCIBLE' => $row->deducible,
                        'COASEGURO' => $row->coaseguro,                        
                        'PRIMERA ASISTENCIA' => $row->primert,
                        'PRIMER PERSONAL' => $row->primerpersonal,
                        'ULTIMA ASISTENCIA' => $row->ultimot,
                        'ULTIMO PERSONAL' => $row->ultimopersonal,

                        'CANTIDAD ATENCION' => $row->cantidadatencion,
                        'CANTIDAD PERSONAL' => $row->cantidadpersonal,
                        'PERSONAL FRECUENTE' => $row->personalfrecuente,
                        'CANTIDAD FRECUENTE' => $row->cantidadfrecuente,

                        'DIAS ASISTIDOS' => $row->nrodia,
                        'PRIMER DIAGNÓSTICO' => $row->diagnostico,
                        'TODOS DIAGNÓSTICOS' => $row->diagnosticos,
                        'PACK DE TTO.' => $pack,
                        'TFCLIENTE' => $row->TFcantcliente,
                        'TFCLIENTECOSTO' => $row->TFcantclientecosto,
                        'TFEFECTIVO' => $row->TFcantefectivo,
                        'TFEFECTIVOCOSTO' => $row->TFcantefectivocosto,
                        'ACCLIENTE' => $row->ACcantcliente,
                        'ACCLIENTECOSTO' => $row->ACcantclientecosto,
                        'ACEFECTIVO' => $row->ACcantefectivo,
                        'ACEFECTIVOCOSTO' => $row->ACcantefectivocosto,
                        'QTCLIENTE' => $row->QTcantcliente,
                        'QTCLIENTECOSTO' => $row->QTcantclientecosto,
                        'QTEFECTIVO' => $row->QTcantefectivo,
                        'QTEFECTIVOCOSTO' => $row->QTcantefectivocosto,
                        'OCHCLIENTE' => $row->OCHcantcliente,
                        'OCHCLIENTECOSTO' => $row->OCHcantclientecosto,
                        'OCHEFECTIVO' => $row->OCHcantefectivo,
                        'OCHEFECTIVOCOSTO' => $row->OCHcantefectivocosto,
                        'ESPCLIENTE' => $row->ESPcantcliente,
                        'ESPCLIENTECOSTO' => $row->ESPcantclientecosto,
                        'ESPEFECTIVO' => $row->ESPcantefectivo,
                        'ESPEFECTIVOCOSTO' => $row->ESPcantefectivocosto,
                        'BLCLIENTE' => $row->BLcantcliente,
                        'BLCLIENTECOSTO' => $row->BLcantclientecosto,
                        'BLEFECTIVO' => $row->BLcantefectivo,
                        'BLEFECTIVOCOSTO' => $row->BLcantefectivocosto,
                        'BMGCLIENTE' => $row->BMGcantcliente,
                        'BMGCLIENTECOSTO' => $row->BMGcantclientecosto,
                        'BMGEFECTIVO' => $row->BMGcantefectivo,
                        'BMGEFECTIVOCOSTO' => $row->BMGcantefectivocosto,
                        'AGUJACLIENTE' => $row->AGUJAcantcliente,
                        'AGUJACLIENTECOSTO' => $row->AGUJAcantclientecosto,
                        'AGUJAEFECTIVO' => $row->AGUJAcantefectivo,
                        'AGUJAEFECTIVOCOSTO' => $row->AGUJAcantefectivocosto,
                        'OTROSCLIENTE' => $row->OTROScantcliente,
                        'OTROSCLIENTECOSTO' => $row->OTROScantclientecosto,
                        'OTROSEFECTIVO' => $row->OTROScantefectivo,
                        'OTROSEFECTIVOCOSTO' => $row->OTROScantefectivocosto,
                        'MONTO PAGO' => $row->montopago,
                        'INICIO_DE_TERAPIA_tarea' => $row->inicio_de_terapia_tarea,
                        'INICIO_DE_TERAPIA_estado' => $row->inicio_de_terapia_estado,
                        'INICIO_DE_TERAPIA_acciones' => $row->inicio_de_terapia_acciones,
                        'INICIO_DE_TERAPIA_fechacreacion' => substr($row->inicio_de_terapia_fechacreacion, 0, 10),
                        'INICIO_DE_TERAPIA_proximacita' => $row->inicio_de_terapia_agendocita, 
                        'INICIO_DE_TERAPIA_asitio?' => $row->inicio_de_terapia_agendoasitio,
                        'NACIMIENTO_PACIENTE' => $row->fechanacimiento,
                        'EDAD_DIA_CICLO' => $row->edaddiaciclo,
                        'NRO_DE_SEGUIMIENTOS' => $row->nro_de_seguimientos,
                        'FECHA_SEGUIMIENTO' => $row->fecha_seguimiento,
                        'CONTESTO_NOCONTESTO_SEGUIMIENTO' => $row->contesto_nocontesto_seguimiento,
                        'MOTIVO_SEGUIMIENTO' => $row->motivo_seguimiento,
                        'COMENTARIO_SEGUIMIENTO' => $row->comentario_seguimiento,
                        'USUARIO_SEGUIMIENTO' => $row->usuario_seguimiento,
                        'DISTRITO' => $row->distrito,
                        'ENVIO_ADF_RESPONSABLE' => $row->envioADF,
                        'ENVIO_ADF_FECHA' => $row->envioADFfecha                   
                    );

                    if (in_array($this->objTtoken->myperfilid, [1, 5])) {
                        $itemArray['FACTURA_SEGURO'] = $row->factura;
                        $itemArray['FACTURA_FECHA'] = $row->facturafecha;
                        $itemArray['FACTURA_TOTAL'] = $row->facturatotal; 
                        $itemArray['PDF_HA'] = ''; 
                        $itemArray['PDF_SITED'] = ''; 

                        if (!empty($row->pdfs)) {
                            $archivos = explode(",", $row->pdfs);
                            foreach ($archivos as $archivo) {
                                if (substr($archivo, 0, 2) === 'HA'){
                                    $itemArray['PDF_HA'] = 'SI';
                                }
                                
                                if (substr($archivo, 0, 2) === 'AU'){
                                    $itemArray['PDF_SITED'] = 'SI'; 
                                }
                            } 
                        }
                    }

                     $data[] = $itemArray; 
                }  

                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
            }
        
        } else {
            return $this->crearRespuesta($dataciclo, 200, $total);
        }
    }

    public function cobroCitasmedicas(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $objentidad = new entidad(); 

        $idempresa = $empresa->idempresa($enterprise);

         
        //Producto 1: Consulta Medica
        
        switch ($idempresa) {
            case 2: 
                $idproducto = 57;
                break; 
            default:
                $idproducto = 1;
                break;
        }

        $tarifario = tarifario::where(['idproducto' => $idproducto])->get()->all();
        $producto = producto::find($idproducto);

        $param = array();
        $param['cicloatencion.idempresa'] = $idempresa;
        $param['cliente.numerodoc'] = $paramsTMP['numerodoc'];
        $param['cicloatencion.idestado'] = 20;
        $param['citamedica.idestadopago'] = 72; //Añadido ultimo 31.10.2017

        $whereInCita = [4, 5, 6]; //4: Pendiente 5: Confirmada 6: Atendido 7: Cancelada
        $whereInCiclo = [20, 21];  //20: Aperturado 21: En curso

        
        $citasmedicas = $cicloatencion->cicloCitasmedicas($param, $whereInCita, $whereInCiclo);
         
        $idcicloatenciones = [];
        foreach ($citasmedicas as $row) {
            if (!in_array($row->idcicloatencion, $idcicloatenciones)) {
                array_push($idcicloatenciones, $row->idcicloatencion);
            }
        }

        $autorizaciones = $cicloatencion->cicloAutorizaciones(['cicloatencion.idempresa' => $idempresa], $idcicloatenciones);
 
        $Arraycitasmedicas = [];
        foreach ($citasmedicas as $row) {

            if ($row->costocero === '1') {
                $array['tipo'] = 5;
            } else {
                $preciounit = $objentidad->tarifariomedicoproducto(array(
                    'tarifamedico.idsede' => $row->idsede,
                    'tarifamedico.idmedico' => $row->idmedico,
                    'tarifamedico.idproducto' => 1 //CM
                ));

                if ($preciounit) {
                    $array['tipo'] = 4;
                } else {
                    $array = $this->getAutorizacion($autorizaciones, $row->idcicloatencion);
                }
            }
 
            $descripcion = '';
            switch ($array['tipo']):
                case 1:
                    //Precio deducible, de autorizacion 'Valida'.
                    //$nota = 'Ciclo N°"'.$array['idcicloatencion'].'", con autoriz. v&aacute;lida';
                    $nota = 'Deducible (Cód. ciclo: ' . $array['idcicloatencion'] . ')';
                    $descripcion = '(Deducible)';
                    $idcicloautorizacion = $array['idcicloautorizacion'];
                    $importe = $array['deducible'];
                    break;
                case 2:
                    //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.
                    //$nota = 'Ciclo N°"'.$array['idcicloatencion'].'", con seguro no cubierto';
                    $nota = 'Tarifario seguro no cubierto';
                    $idcicloautorizacion = $array['idcicloautorizacion'];
                    $importe = $this->getPrecio($tarifario, $idproducto, $row->idsede, 'SINCOBERTURA');
                    break;
                case 3:
                    //Precio de tarifario, no tiene autorizacion.
                    $nota = 'Tarifario particular';
                    $idcicloautorizacion = NULL;
                    $importe = $this->getPrecio($tarifario, $idproducto, $row->idsede, 'PARTICULAR');
                    break;
                case 4:
                    //Precio de tarifario del medico para cm.
                    $nota = 'Tarifario médico';
                    $idcicloautorizacion = NULL;
                    $importe = $preciounit;
                    break;
                case 5:
                    //Precio de tarifario del medico para cm.
                    $nota = 'Costo cero';
                    $idcicloautorizacion = NULL;
                    $importe = 0;
                    break;
            endswitch;


            $valor = array(
                'nombresede' => $row->sedenombre,
                'idproducto' => 1,
                'cantidad' => 1,
                'nombreproducto' => $producto->nombre,
                'fechahora' => $row->fecha . ' '.$row->inicio,
                'precio' => $importe,
                'nota' => $nota,
                'idcicloatencion' => $row->idcicloatencion,
                'descripcion' => $descripcion,
                'idcicloautorizacion' => $idcicloautorizacion,
                'idcitamedica' => $row->idcitamedica,
                'nombrecliente' => $row->entidad,
                'numerodoccliente' => $row->numerodoc,
                'idcliente' => $row->idcliente,
                'tipo' => $array['tipo'],
                'tipodecobro' => 'cmedica'//Consulta Medica
            );

            $Arraycitasmedicas[] = $valor;
        }

        $param = array();
        $param['cicloatencion.idempresa'] = $idempresa;
        $param['cliente.numerodoc'] = $paramsTMP['numerodoc'];
        $param['cicloatencion.idestado'] = 20;
        $ciclosdeatenciones = $cicloatencion->grid($param, '', '', '', '', '', true);

        foreach ($ciclosdeatenciones as $row) {

            switch ($idempresa) {
                case 2: 
                    $txttmp1 = "Programa: ". $row->nombrepaquete;
                    break; 
                default:
                    $txttmp1 = "Ciclo: " . $row->idcicloatencion;
                    break;
            }

            $valor = array(
                'nombresede' => $row->sedenombre,
                'idproducto' => $row->idcicloatencion,
                'cantidad' => 1,
                'nombreproducto' => 'Presupuesto de terapias',
                'fechahora' => NULL,
                'precio' => NULL,
                'nota' => $txttmp1,
                'idcicloatencion' => $row->idcicloatencion,
                'descripcion' => NULL,
                'idcicloautorizacion' => NULL,
                'idcitamedica' => NULL,
                'nombrecliente' => $row->paciente,
                'numerodoccliente' => $row->numerodoc,
                'idcliente' => $row->idcliente,
                'tipo' => NULL,
                'tipodecobro' => 'presupuesto'//Presupuesto
            );
            $Arraycitasmedicas[] = $valor;
        }

        $entidad = $objentidad->entidad(['entidad.numerodoc' => $paramsTMP['numerodoc']]);
        
        $notascreditos = [];
         
        $listcombox = array('notascreditos' => $notascreditos);
        //No Pagina, debo enviar el total de registros.
        return $this->crearRespuesta($Arraycitasmedicas, 200, count($Arraycitasmedicas), '', $listcombox);
    }

    private function getAutorizacion($autorizaciones, $idcicloatencion) {

        //Precio de tarifario, no tiene autorizacion.
        $return = ['tipo' => 3];

        foreach ($autorizaciones as $row) {
            /* Obtiene el "idcicloautorizacion" y "deducible" de la autorizacion 'Principal' o 'Valida'  
             * idproducto: 2: El precio va a ser el deducible de "Fisioterapia"        
             */
            // && $row->principal === '1'
            if ($row->idcicloatencion === $idcicloatencion && $row->idproducto === 2) {
                //Precio deducible, de autorizacion 'Valida'.
                $return = array(
                    'tipo' => 1,
                    'idcicloatencion' => $row->idcicloatencion,
                    'idcicloautorizacion' => $row->idcicloautorizacion,
                    'deducible' => $row->deducible
                );
                break;
            }

            /* Obtiene el "idcicloautorizacion" de la autorizacion 'No valida' y seguro 'No cubierto'.
             * Al no haber fecha en una autorizacion no valida, entonces toma el ultimo de orden descendente.
             * 
             * && $row->principal === '0' : Colo cuando autorizacion no es valida. Si es no cubierto estara vacio.
             */
            if ($row->idcicloatencion === $idcicloatencion && in_array($row->idaseguradoraplan, [5, 8, 13, 18, 21])) {
                //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.
                $return = array(
                    'tipo' => 2,
                    'idcicloatencion' => $row->idcicloatencion,
                    'idcicloautorizacion' => $row->idcicloautorizacion
                );
            }
        }

        return $return;
    }

    private function getPrecio($tarifario, $idproducto, $idsede, $tipo) {

        $precio = NULL;

        foreach ($tarifario as $row) {
            //Precio de tarifario con SEGURO NO CUBIERTO
            if ($row->idproducto === $idproducto && $row->idsede === $idsede && $tipo === 'SINCOBERTURA') {
                $precio = $row->sscoref;
                break;
            }

            //Precio de tarifario PARTICULAR
            if ($row->idproducto === $idproducto && $row->idsede === $idsede && $tipo === 'PARTICULAR') {
                $precio = $row->partref;
                break;
            }
        }

        return $precio;
    }

    public function show(Request $request, $enterprise, $id) {

        $objCicloatencion = new cicloatencion();
        $objPresupuesto = new presupuesto();
        $objCitamedica = new citamedica();
        $producto = new producto();
        $empresa = new empresa();
        $sede = new sede();
        $terapia = new terapia();
        $venta = new venta();
        $ciclomovimiento = new ciclomovimiento();  
        $entidad = new entidad();
        $objPaquete = new paquete();
        $ventadet = new ventadet();
        $grupodx = new grupodx();

        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $terapiasrealizadas = array();
        $pagosrealizadas = array(); 
        $pagosconsultas = array(); 

        $tmpempresa = $empresa->empresa(['url' => $enterprise]);
        $idempresa = $tmpempresa->idempresa;         

        $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $id]);

        $fieldsProducto = ['producto.idproducto', 'producto.nombre', 'producto.categoria'];
        if ($cicloatencion) { 
            $param = array(
                'ventadet.idcicloatencion' => $cicloatencion->idcicloatencion,
                'venta.idestadodocumento' => 27,
            );

            $dataventadet = $ventadet->grid($param);

            $fields = ['sccocien', 'scconoventacinco', 'scconoventa', 'sccoochentacinco', 'sccoochenta',
                'sccosetentacinco', 'sccosetenta', 'sccosesentacinco', 'sccosesenta', 'sccocincuentacinco', 'sccocincuenta',
                'sccocuarentacinco', 'sccocuarenta', 'sccotreintacinco', 'sccotreinta', 'sccoveintecinco', 'sccoveinte',
                'sccoquince', 'sccodiez', 'sccocero'];

            $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);
            $presupuestodet = [];
            if ($presupuesto) {
                $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
            }

            //Ventas, notas de credito y terapias
            //Optimizar
            //$ventas = $venta->grid(['venta.idcicloatencion' => $id, 'venta.idestadodocumento' => 27],'','','','','','', TRUE); 
            $ventas = $venta->grid(['venta.idcicloatencion' => $id, 'venta.idestadodocumento' => 27]);
                   
            $saldos = $ciclomovimiento->movimiento(['idcicloatencion' => $id], ['idcicloatencionref' => $id]);
            
            $terapiasrealizadas = array();

            switch ($idempresa) {
                case 2: 
                    //Inicio Terapias
                    $terapiasrealizadas = $terapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38], array('terapia.idterapia', 'terapia.fecha',  'terapia.inicio', 'terapia.fin', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad', 'terapista.entidad as nombreterapista', 'producto.codigo', 'producto.nombre as nombreproducto'), TRUE); 
                    break; 
                default:
                    // dd();
                    if (count($gruposDx) > 0) { 
                        foreach($gruposDx as $row){ 
                            // dd($row);
                            //Inicio Terapias
                            $dataterapia = $terapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38, 'terapiatratamiento.idgrupodx' => $row->idgrupodx], array('terapia.idterapia', 'terapia.fecha',  'terapia.inicio', 'terapia.fin', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad', 'terapista.entidad as nombreterapista', 'sede.sedeabrev as nombresede', 'terapia.firma', 'terapia.fechafirma', 'respfirma.entidad as personalfirma', 'terapiatratamiento.idgrupodx', 'terapia.firmaterapia'), TRUE, false, '', '', '', [], '', [], true);
                            // dd($dataterapia);

                            $quiebre = array('idterapia' => 'idterapia');            
                            $campoextra = array('fecha' => 'fecha', 'inicio' => 'inicio', 'fin' => 'fin', 'idterapista' => 'nombreterapista', 'nombresede' => 'nombresede', 'firma' => 'firma', 'idterapia' => 'idterapia', 'fechafirma' => 'fechafirma', 'personalfirma' => 'personalfirma', 'idgrupodx' => 'idgrupodx', 'firmaterapia' => 'firmaterapia');  
                            $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA','*'=>'OTROS')];

                            $datatratxterapista = $this->agruparPorColumna($dataterapia, '', $quiebre, $campoextra, $gruposProducto);    
                            // dd($dataterapia, $datatratxterapista);
                                   
                            $precios = array();
                            foreach($presupuestodet as $row) { 
                                $precios[$row->idproducto] = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);
                            }

                            $preciosTrat = array();
                            $subtotales = array();
                            foreach($dataterapia as $row) {
                                if(!isset($preciosTrat[$row->idterapia]))
                                    $preciosTrat[$row->idterapia] = 0;  

                                if(isset($precios[$row->idproducto]))    
                                    $preciosTrat[$row->idterapia] += ($row->cantidad * $precios[$row->idproducto]);
                                
                                //subtotales
                                $idproducto = '*';
                                 
                                if(isset($gruposProducto[1][$row->idproducto])){
                                    $idproducto = $row->idproducto;
                                }
                                
                                if(!isset($subtotales[$row->idterapia]))
                                    $subtotales[$row->idterapia] = array();  

                                if(!isset($subtotales[$row->idterapia][$idproducto])){
                                    $subtotales[$row->idterapia][$idproducto] = 0;
                                }

                                if(isset($precios[$row->idproducto])) 
                                    $subtotales[$row->idterapia][$idproducto] += ($row->cantidad * $precios[$row->idproducto]);
                            } 
                             
                            $realizados = array();
                            foreach($datatratxterapista as $row){                
                                if(!isset($realizados[$row['idquiebre']])) {
                                    $realizados[$row['idquiebre']]['idterapia'] = $row['idterapia'];
                                    $realizados[$row['idquiebre']]['fecha'] = $row['fecha'];
                                    $realizados[$row['idquiebre']]['inicio'] = $row['inicio'];
                                    $realizados[$row['idquiebre']]['fin'] = $row['fin'];
                                    $realizados[$row['idquiebre']]['total'] = $preciosTrat[$row['idquiebre']];
                                    $realizados[$row['idquiebre']]['nombreterapista'] = $row['nombreterapista'];
                                    $realizados[$row['idquiebre']]['nombresede'] = $row['nombresede'];
                                    $realizados[$row['idquiebre']]['firma'] = $row['firma'];
                                    $realizados[$row['idquiebre']]['fechafirma'] = $row['fechafirma'];
                                    $realizados[$row['idquiebre']]['personalfirma'] = $row['personalfirma'];
                                    $realizados[$row['idquiebre']]['idgrupodx'] = $row['idgrupodx'];
                                    $realizados[$row['idquiebre']]['firmaterapia'] = $row['firmaterapia'];
                                    
                                    foreach($gruposProducto[1] as $ind => $val){
                                        $realizados[$row['idquiebre']][$val] = 0;
                                    }
                                } 
                                 
                                $realizados[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : '';  
                                $realizados[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'costo'] = $row['cantidad'] > 0  ? $subtotales[$row['idquiebre']][$row['idgrupo']] : '';
                            }
                            // dd($realizados);
                            foreach ($realizados as $i => $row) { 
                                $terapiasrealizadas[] = $row;
                            } 
                        } 
                    } else {
                        //Inicio Terapias
                        $dataterapia = $terapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38], array('terapia.idterapia', 'terapia.fecha',  'terapia.inicio', 'terapia.fin', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad', 'terapista.entidad as nombreterapista', 'sede.sedeabrev as nombresede', 'terapia.firma', 'terapia.fechafirma', 'respfirma.entidad as personalfirma', 'terapia.firmaterapia'), TRUE, false, '', '', '', [], '', [], true);
                        // dd($dataterapia);

                        $quiebre = array('idterapia' => 'idterapia');            
                        $campoextra = array('fecha' => 'fecha', 'inicio' => 'inicio', 'fin' => 'fin', 'idterapista' => 'nombreterapista', 'nombresede' => 'nombresede', 'firma' => 'firma', 'idterapia' => 'idterapia', 'fechafirma' => 'fechafirma', 'personalfirma' => 'personalfirma', 'firmaterapia' => 'firmaterapia');  
                        $gruposProducto = ['idproducto', array(2=>'TF',3=>'AC',4=>'QT',5=>'OCH',6=>'ESP',11=>'BL',17=>'BMG', 23=>'AGUJA','*'=>'OTROS')];

                        $datatratxterapista = $this->agruparPorColumna($dataterapia, '', $quiebre, $campoextra, $gruposProducto);    
                        // dd($dataterapia, $datatratxterapista);
                               
                        $precios = array();
                        foreach($presupuestodet as $row) { 
                            $precios[$row->idproducto] = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);
                        }

                        $preciosTrat = array();
                        $subtotales = array();
                        foreach($dataterapia as $row) {
                            if(!isset($preciosTrat[$row->idterapia]))
                                $preciosTrat[$row->idterapia] = 0;  

                            if(isset($precios[$row->idproducto]))    
                                $preciosTrat[$row->idterapia] += ($row->cantidad * $precios[$row->idproducto]);
                            
                            //subtotales
                            $idproducto = '*';
                             
                            if(isset($gruposProducto[1][$row->idproducto])){
                                $idproducto = $row->idproducto;
                            }
                            
                            if(!isset($subtotales[$row->idterapia]))
                                $subtotales[$row->idterapia] = array();  

                            if(!isset($subtotales[$row->idterapia][$idproducto])){
                                $subtotales[$row->idterapia][$idproducto] = 0;
                            }

                            if(isset($precios[$row->idproducto])) 
                                $subtotales[$row->idterapia][$idproducto] += ($row->cantidad * $precios[$row->idproducto]);
                        } 
                         
                        $realizados = array();
                        foreach($datatratxterapista as $row){                
                            if(!isset($realizados[$row['idquiebre']])) {
                                $realizados[$row['idquiebre']]['idterapia'] = $row['idterapia'];
                                $realizados[$row['idquiebre']]['fecha'] = $row['fecha'];
                                $realizados[$row['idquiebre']]['inicio'] = $row['inicio'];
                                $realizados[$row['idquiebre']]['fin'] = $row['fin'];
                                $realizados[$row['idquiebre']]['total'] = $preciosTrat[$row['idquiebre']];
                                $realizados[$row['idquiebre']]['nombreterapista'] = $row['nombreterapista'];
                                $realizados[$row['idquiebre']]['nombresede'] = $row['nombresede'];
                                $realizados[$row['idquiebre']]['firma'] = $row['firma'];
                                $realizados[$row['idquiebre']]['fechafirma'] = $row['fechafirma'];
                                $realizados[$row['idquiebre']]['personalfirma'] = $row['personalfirma'];
                                $realizados[$row['idquiebre']]['firmaterapia'] = $row['firmaterapia'];
                                
                                foreach($gruposProducto[1] as $ind => $val){
                                    $realizados[$row['idquiebre']][$val] = 0;
                                }
                            } 
                             
                            $realizados[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0  ? $row['cantidad'] : '';  
                            $realizados[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']].'costo'] = $row['cantidad'] > 0  ? $subtotales[$row['idquiebre']][$row['idgrupo']] : '';
                        }
                        // dd($realizados);
                        foreach ($realizados as $i => $row) { 
                            $terapiasrealizadas[] = $row;
                        }
                    } 
                    //Fin Terapias  
                    break;
            }          

            // dd($terapiasrealizadas);

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
                }else{
                    // $whereIdventaCmIn[] = $row->idventa;
                    $pagosconsultas[] = array(
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
            //Fin a optimizar
            
            $diagnosticosmedicos = $objCitamedica->diagnosticomedico(['citamedica.idcicloatencion' => $id]);
            $tratamientosmedicos = $objCitamedica->tratamientomedico(['citamedica.idcicloatencion' => $id]); 
            // dd($terapiasrealizadas);
            $listcombox = array(
                'citasmedicas' => $objCicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $id]),
                'autorizaciones' => $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]), 
                'terapiasrealizadas' => $terapiasrealizadas,
                'pagosrealizadas' => $pagosrealizadas, 
                'diagnosticosmedicos' => $diagnosticosmedicos,
                'tratamientosmedicos' => $tratamientosmedicos,
                /* combos para crear autorizaciones */
                'tipoautorizaciones' => $empresa->estadodocumentos(7),
                'aseguradoras' => $empresa->aseguradoras($idempresa),
                'aseguradorasplanes' => $empresa->aseguradorasplanes($idempresa),
                'coaseguros' => $empresa->coaseguros(),
                'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']),
                'productos' => $producto->grid(['producto.idempresa' => $idempresa, 'autorizacionseguro' => '1'], '', '', '', '', $fieldsProducto), //autorizacionseguro: 0: No 1: Si, producto con autorizacion de seguro.
                'presupuesto' => $presupuesto,
                'presupuestodet' => $presupuestodet,
                'tarifafisioterapia' => tarifario::select($fields)->where(['idsede' => $cicloatencion->idsede, 'idproducto' => 2])->first(),
                'pagosconsultas' => $pagosconsultas,
                'ventadetalle' => $dataventadet 
            );

            if (isset($request['others'])) {
                $others = explode(',', $request['others']);
                
                //Para añadir mas tratamientos 
                if (in_array('servicios', $others)) {
                    $listcombox['servicios'] = $producto->grid(['producto.idempresa' => $idempresa, 'producto.tratamientoind' => '1'], '', '', '', '', $fieldsProducto);
                    // dd($listcombox['servicios']);
                }

                if (in_array('medicos', $others)) {   
                    $entidad = new entidad();
                    $param = array(
                        'entidadsede.idsede' => $cicloatencion->idsede,
                        'entidad.tipomedico' => '1'
                    );
                    $listcombox['medicos'] = $entidad->entidades($param, true);
                }
                
                if (in_array('terapistas', $others)) {
                    $entidad = new entidad();
                    $param3 = array();
                    $param3['entidad.idempresa'] = $idempresa;
                    $param3['sede.idsede'] = $cicloatencion->idsede;
                    $param3['tipopersonal'] = '1';
                    $param3['idcargoorg'] = $tmpempresa->codecargo;
                    $listcombox['terapistas'] = $entidad->entidadesSedes($param3);
                }

                if (in_array('adicionales', $others)) {
                    $listcombox['adicionalestrat'] = $objCicloatencion->cicloTratamientos(array('ciclotratamiento.idcicloatencion' => $cicloatencion->idcicloatencion
                    ));
                }

                if (in_array('gruposdx', $others)) { 
                    $listcombox['gruposdx'] = $gruposDx;
                }
            }

            if(isset($cicloatencion->idpaquete) && !empty($cicloatencion->idpaquete)) {
                $listcombox['zonas'] = $objPaquete->zonas(['paquetezona.idpaquete' => $cicloatencion->idpaquete]);
            }
             
            if(isset($request['getpersonal']) && $request['getpersonal'] === '1'){
                $listcombox['personal'] = $entidad->entidades(['idempresa' => $idempresa, 'entidad.tipopersonal' => '1']);
            }

            // dd($cicloatencion);
            return $this->crearRespuesta($cicloatencion, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Ciclo atención no encotrado', 404);
    }

    public function showPrevioEnvio(Request $request, $enterprise, $id) {

        $autorizacionimagen = new autorizacionimagen();
        $objCicloatencion = new cicloatencion();
        $objCitamedica = new citamedica();
        $terapia = new terapia();
        $grupodx = new grupodx();

        $cicloatencion = $objCicloatencion->cicloatencion($id);
        
        $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $id]);
        foreach ($gruposDx as $row) {  
            $row->cantasistencias = 0;
            $row->cantfirmas = 0;
            $row->url_archivo = null;

            if ($cicloatencion->pdfs) {
                $cadena = explode(",", $cicloatencion->pdfs);

                foreach($cadena as $filename) {
                    $strfile = substr($filename, 0, -4);
                    $strfile = explode("_", $strfile); 
                    if ($strfile[0] === 'HA'
                        && (integer) $strfile[1] === (integer) $id 
                        && (integer) $strfile[2] === $row->idgrupodx
                    ) { 
                        $row->url_archivo = '/atenciones/' . $filename;
                    }
                }
            }
        }

        $autorizaciones = $objCicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id, 'cicloautorizacion.idproducto' => 2], [], ['cicloautorizacion.idcicloautorizacion', 'cicloautorizacion.fecha', 'producto.nombre as nombreproducto', 'aseguradoraplan.nombre as nombreaseguradoraplan']);
 
        

        foreach ($autorizaciones as $row) {
            $row->url_archivo = null;
            $row->cantimagen = count($autorizacionimagen->grid(['autorizacionimagen.idcicloautorizacion' => $row->idcicloautorizacion]));

            if ($cicloatencion->pdfs) {
                $cadena = explode(",", $cicloatencion->pdfs); 

                foreach($cadena as $filename) {
                    $strfile = substr($filename, 0, -4);
                    $strfile = explode("_", $strfile); 
                    if ($strfile[0] === 'AU'
                        && (integer) $strfile[1] === (integer) $id 
                        && (integer) $strfile[2] === $row->idcicloautorizacion
                    ) { 
                        $row->url_archivo = '/atenciones/' . $filename;
                    }
                }
            }


        }

        if ($cicloatencion) { 
            $param = array(
                'terapia.idempresa' => 1,
                'terapia.idestado' => 38,
                'terapiatratamiento.idcicloatencion' => $id
            );

            $dataterapiatratmp = [];
            $dataterapiatra = $terapia->terapiatratamientoslight($param, ['terapia.idterapia', 'terapia.fecha', 'terapiatratamiento.idgrupodx', 'terapia.idterapista', 'terapista.entidad as nombreterapista', 'terapia.firma'], TRUE, '', [], true, [], true);

            foreach ($dataterapiatra as $row) {
                foreach ($gruposDx as $grupo){
                    if ($grupo->idgrupodx === $row->idgrupodx) { 
                        $grupo->cantasistencias += 1;
                        $grupo->cantfirmas += !empty($row->firma) ? 1 : 0;
                        break;
                    }
                }
            }

            $citasmedicas = $objCicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $id, 'citamedica.idestado' => 6], [], [], [], ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.idmedico', 'entidad.entidad as medico', 'entidad.audittrail']);

            foreach ($citasmedicas as $row) { 
                $informes = $objCitamedica->informes(['citamedica.idcitamedica' => $row->idcitamedica]);
                $informes = $this->ordenarMultidimension($informes, 'idinforme', SORT_DESC);

                $parteurl = !empty($informes) &&  !empty($informes[0]->identidad_firma)? '/informes_medicos/firmados/' : '/informes_medicos/';

                $row->audittrail = !empty($row->audittrail) ? '1' : '0';
                $row->idinforme = !empty($informes) ? $informes[0]->idinforme : NULL;
                $row->url_archivo = !empty($informes) ? ($parteurl . $informes[0]->archivo) : NULL;
                $row->identidad_firma = !empty($informes) ? $informes[0]->identidad_firma : NULL;
            }

            $tmpciclo = array(
                'idcicloatencion' => $cicloatencion->idcicloatencion,
                'paciente' => $cicloatencion->entidad,
                'fecha' => $cicloatencion->fecha,
                'pdfs' => $cicloatencion->pdfs,
                'idestado' => $cicloatencion->idestado,
                'idestadofactura' => $cicloatencion->idestadofactura
            );

            //Fin a optimizar
            $listcombox = array(
                'citasmedicas' => $citasmedicas,
                'autorizaciones' => $autorizaciones,
                'gruposdx' => $gruposDx
            );

            return $this->crearRespuesta($tmpciclo, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Ciclo atención no encotrado', 404);
    }

    public function generarfactura(Request $request, $enterprise, $id) {

        $request = $request->all();

        $objCicloatencion = new cicloatencion();
        $objCicloautorizacion = new cicloautorizacion(); 
        $empresa = new empresa();
        $modelo = new modelo(); 
        $sede = new sede();
        
        $idempresa = $empresa->idempresa($enterprise);
        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $cicloautorizacion = $objCicloautorizacion->cicloautorizacion($request['idcicloautorizacion']);
        
        $paramDocu = array(
            'documentoserie.idempresa' => $idempresa, 
            'documentoserie.iddocumentofiscal' => 1 
        );                
        $dataf = $sede->documentoSeries($paramDocu);
        
        foreach ($dataf as $row) {                
            $serienumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT);
            $row->documentoSerieNumero = $serienumero;     
        }  

        //Citas médicas
        $diagnosticos = \DB::table('diagnosticomedico') 
                ->select('citamedica.idcitamedica', 'citamedica.fecha',  'medico.entidad as medico', 'diagnostico.iddiagnostico', 'diagnostico.nombre as diagnostico', 'zona.idzona', 'zona.nombre as nombrezona')
                ->join('citamedica', 'diagnosticomedico.idcitamedica', '=', 'citamedica.idcitamedica')                
                ->join('diagnostico', 'diagnosticomedico.iddiagnostico', '=', 'diagnostico.iddiagnostico') 
                ->leftJoin('zona', 'diagnosticomedico.idzona', '=', 'zona.idzona') 
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
                ->where('citamedica.idestado', 6)
                ->where('citamedica.idcicloatencion', $id)
                ->whereNull('citamedica.deleted')                
                ->orderBy('citamedica.fecha', 'DESC')                
                ->get()->all();

        $zonas = \DB::table('zona') 
                ->select('zona.idzona', 'zona.nombre') 
                ->where('zona.idempresa', $idempresa) 
                ->whereNull('zona.deleted')                      
                ->get()->all();
 
        foreach ($diagnosticos as $row) {                
                $row->fecha = $this->formatFecha($row->fecha);
        }   

        //Presupuestodet
        $where = array(
            'cicloatencion.idcicloatencion' => $id,
            'presupuestodet.idproducto' => $cicloautorizacion->idproducto
        );
        $cantefectivo = \DB::table('presupuestodet')                
                ->join('presupuesto', 'presupuestodet.idpresupuesto', '=', 'presupuesto.idpresupuesto')
                ->join('cicloatencion', 'presupuesto.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select('presupuestodet.cantefectivo')
                ->where($where)  
                ->whereNull('presupuestodet.deleted')
                ->whereNull('presupuesto.deleted')
                ->whereNull('cicloatencion.deleted')
                ->first(); 

        //Cliente
        $clientes = \DB::table('clienteplan') 
                ->join('entidad as cliente', 'clienteplan.idcliente', '=', 'cliente.identidad')                
                ->select('cliente.entidad as cliente', 'clienteplan.idcliente')                
                ->where('clienteplan.idaseguradoraplan', $cicloautorizacion->idaseguradoraplan)
                ->whereNull('cliente.deleted')                       
                ->get()->all(); 

        //Modelos
        $modelos = [];
        $tmpmodelos = $modelo->grid(['modelo.idempresa' => $idempresa]); 
        foreach($tmpmodelos as $row){
            foreach($row->modeloseguro as $row2){
                if($row2->idaseguradoraplan === $cicloautorizacion->idaseguradoraplan) {
                    $modelos[] = $row;
                    break;
                }                
            }
        } 
        
        $periodo = null;
        if($cicloatencion->primert && $cicloatencion->ultimot)
            $periodo = $cicloatencion->primert . ' al '. $cicloatencion->ultimot;
 
        $factura = array(); 
        $factura['idcicloautorizacion'] = $cicloautorizacion->idcicloautorizacion;    
        $factura['idsede'] = $cicloautorizacion->idsede;            
        $factura['codigo'] = $cicloautorizacion->codigo;
        $factura['fecha'] = $cicloautorizacion->fecha;
        $factura['idpaciente'] = $cicloautorizacion->idpaciente;
        $factura['paciente'] = $cicloautorizacion->paciente;
        $factura['parentesco'] = $cicloautorizacion->parentesco;
        $factura['seguro'] = $cicloautorizacion->nombreaseguradora;//nombreaseguradora  
        $factura['empresa'] = $cicloautorizacion->nombrecompania;        
        $factura['autorizacion'] = $cicloautorizacion->nombretipo;  //nombretipo              
        $factura['coaseguro'] = $cicloautorizacion->valor; //valor        
        $factura['deducible'] = $cicloautorizacion->deducible;
        $factura['seguroplan'] = $cicloautorizacion->nombreaseguradoraplan;//nombreaseguradoraplan  
        $factura['sedeabrev'] = $cicloatencion->sedeabrev;        
        $factura['hc'] = $cicloatencion->hc;
        $factura['periodo'] = $periodo; 
        $factura['sesiones'] = $cantefectivo ? $cantefectivo->cantefectivo : null; //cantefectivo
        $factura['idcliente'] = count($clientes) === 1 ? $clientes[0]->idcliente : null;
        $factura['cliente'] = count($clientes) === 1 ? $clientes[0]->cliente : null;
                        
        $listcombox = array(
            'diagnosticos' => $diagnosticos,
            'modelos' => $modelos,
            'fact' => $dataf,
            'zonas' => $zonas
        );
        //dd($factura);
        return $this->crearRespuesta($factura, 200, '', '', $listcombox);
    }

    public function newcicloatencion(Request $request, $enterprise) {
        
        $producto = new producto();
        $empresa = new empresa();
        $sede = new sede();

        $row = (object) array();
        $idempresa = $empresa->idempresa($enterprise);

        

        if (isset($request['idsede'])) {
            $row = sede::find($request['idsede']);
        }

        $fieldsProducto = ['producto.idproducto', 'producto.nombre'];
        $listcombox = array(
            /* combos para crear autorizaciones */
            'tipoautorizaciones' => $empresa->estadodocumentos(7),
            'aseguradoras' => $empresa->aseguradoras($idempresa),
            'aseguradorasplanes' => $empresa->aseguradorasplanes($idempresa),
            'coaseguros' => $empresa->coaseguros(),
            'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']),
            'productos' => $producto->grid(['producto.idempresa' => $idempresa, 'autorizacionseguro' => '1'], '', '', '', '', $fieldsProducto), //autorizacionseguro: 0: No 1: Si, producto con autorizacion de seguro.
            'fechaactual' => date('d/m/Y')
        );

        return $this->crearRespuesta($row, 200, '', '', $listcombox);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $objEntidad = new entidad();
        $objPaquete = new paquete();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        //return $this->crearRespuesta('existe.', [200, 'info'], '','', $request);
        //VALIDACIONES   
        /* 1.- GenerarHC, caso no tenga. */
        $entidad = $objEntidad->entidadHC(array('identidad' => $request['cicloatencion']['idpaciente']), $request['cicloatencion']['idsede']);
        if (empty($entidad->hc)) { 
            $entidad->hc = $objEntidad->generaHC($entidad->identidad, $request['cicloatencion']['idsede'], true, $this->objTtoken->my);
        } 

        //FIN VALIDACIONES

        $request['cicloatencion']['idempresa'] = $idempresa;

        if(isset($request['ciclocitamedica']) && $request['ciclocitamedica']) {
            $request['cicloatencion']['idmedico'] = $request['ciclocitamedica'][0]['idmedico'];
        }

        $request['cicloatencion']['fecha'] = !empty($request['cicloatencion']['fecha']) ? $this->formatFecha($request['cicloatencion']['fecha'], 'yyyy-mm-dd') : date('Y-m-d');
        $request['cicloatencion']['terminot'] = '0';
        $request['cicloatencion']['idestadofactura'] = 75;  

        /* Campos auditores */
        $request['cicloatencion']['created_at'] = date('Y-m-d H:i:s');
        $request['cicloatencion']['id_created_at'] = $this->objTtoken->my;

        
        /* Campos auditores */  
        $paramGrupo = array(
            'idempresa' => $idempresa,
            'nombre' => 'Grupo 1',
            'created_at' => date('Y-m-d H:i:s'),
            'id_created_at' => $this->objTtoken->my
        );

        \DB::beginTransaction();
        try {
            // Crea ciclo
            $cicloatencion = cicloatencion::create($request['cicloatencion']);

            // Crea grupodx
            $paramGrupo['idcicloatencion'] = $cicloatencion->idcicloatencion;
            grupodx::create($paramGrupo);  

            //Graba en tabla 'ciclocitamedica'
            if(isset($request['ciclocitamedica']) && $request['ciclocitamedica']){
                foreach ($request['ciclocitamedica'] as $row) {
                    $auditoria = array(
                        'idcicloatencion' => $cicloatencion->idcicloatencion,
                        'tipocm' => 1, //1:Consulta 2:Interconsulta
                        'horaespera' => date('H:i:s'), 
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    );

                    \DB::table('citamedica')
                        ->where('idcitamedica', $row['idcitamedica'])
                        ->update($auditoria);

                    //Las CM por Portal Paciente ya están pagados, falta asignar ciclo a venta
                    $citamedica = citamedica::find($row['idcitamedica']);

                    if ($citamedica->idestadopago === 71 && !empty($citamedica->idventa)) {
                        \DB::table('venta')
                            ->where('idventa', $citamedica->idventa)
                            ->update(['idcicloatencion' => $cicloatencion->idcicloatencion]);

                        \DB::table('ventadet')
                            ->where([
                                'idventa' => $citamedica->idventa, 
                                'idproducto' => 1 //CM
                            ])
                            ->update(['idcicloatencion' => $cicloatencion->idcicloatencion]);
                    }
                }
            }

            if(isset($request['cicloatencion']['idcitamed']) && $request['cicloatencion']['idcitamed']){
                $auditoria = array(
                    'idestado' => 6, //6:Atendido 
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my
                );

                \DB::table('citamedica')->where(['idcitamedica' => $request['cicloatencion']['idcitamed']])->update($auditoria);
            }
            
            //Graba en tabla 'cicloautorizacion'
            if(isset($request['cicloautorizacion']) && $request['cicloautorizacion']){
                $dataCicloAut = [];
                foreach ($request['cicloautorizacion'] as $row) {
                    $param = array(
                        'idempresa' => $idempresa,
                        'idcicloatencion' => $cicloatencion->idcicloatencion,
                        'idpaciente' => $cicloatencion->idpaciente,
                        'idsede' => $row->idsede,
                        'fecha' => $this->formatFecha($row['fecha'], 'yyyy-mm-dd'),
                        'idaseguradora' => $row['idaseguradora'],
                        'idaseguradoraplan' => $row['idaseguradoraplan'],
                        'deducible' => $row['deducible'],
                        'idcoaseguro' => $row['idcoaseguro'],
                        'idtipo' => $row['idtipo'],
                        'codigo' => $row['codigo'],
                        'idestadoimpreso' => 83,
                        // 'principal' => $row['principal'],
                        'idproducto' => $row['idproducto'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my
                    );

                    if (isset($row['descripcion']) && !empty($row['descripcion'])) {
                        $param['descripcion'] = $row['descripcion'];
                    }

                    $dataCicloAut[] = $param;
                }
                if (!empty($dataCicloAut))
                    \DB::table('cicloautorizacion')->insert($dataCicloAut); 
            }
 
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Ciclo #"' . $cicloatencion->idcicloatencion . '" ha sido creado.', 201, '', '', $cicloatencion->idcicloatencion);
    }

    public function storeanadir(Request $request, $enterprise) {
        //NO tiene sentido hacer update el campo idcicloatencion 
        $request = $request->all();

        $param = array('idcicloatencion' => $request['idcicloatencion'], 'idcitamedica' => $request['idcitamedica']);
                 
        $data = \DB::table('citamedica')->where($param)->get()->all();
        
        if (empty($data)) {
            \DB::table('citamedica')
                    ->where(['idcitamedica' => $request['idcitamedica']])
                    ->update(['idcicloatencion' => $request['idcicloatencion'], 'tipocm' => 2, 'horaespera' => date('H:i:s')]);
   
            return $this->crearRespuesta('Ciclo de atención N° "' . $request['idcicloatencion'] . '" ha sido editado', 200);
        }

        return $this->crearRespuestaError('Ciclo atención no encotrado', 404);
    }

    public function updateAbrirCerrar(Request $request, $enterprise, $id) {

        $objPresupuesto = new presupuesto();
        $cicloatencion = cicloatencion::find($id);

        $request = $request->all();

        //VALIDACIONES 
        /* 1.- Validar que paciente no tenga deudas.
         */ 
        $presupuesto = $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);

        if ($presupuesto) {
            $creditodisp = $presupuesto->montocredito - $presupuesto->montoefectuado;
            if ($creditodisp < 0 && $cicloatencion->idestado === 20) { //20: abierto
                return $this->crearRespuesta('Cliente tiene cuenta por pagar. No puede cerrarse.', [200, 'info']);
            }
        }

        if ($cicloatencion->idestado === 21 && ($cicloatencion->idestadofactura === 76 || $cicloatencion->idestadofactura == 77)) {
            return $this->crearRespuesta('Ciclo ya fue "enviado" a contabilidad. Para abrir, el ciclo debe volver ha estado "Por enviar".', [200, 'info']);
        }
        //FIN VALIDACIONES

        /* Campos auditores */
        $request['cicloatencion']['updated_at'] = date('Y-m-d H:i:s');
        $request['cicloatencion']['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if ($cicloatencion) { 

            $request['cicloatencion']['fechacierre'] = $request['cicloatencion']['idestado'] === 21 ? date('Y-m-d') : null;

            $cicloatencion->fill($request['cicloatencion']);
            $cicloatencion->save();

            $mensaje = $request['cicloatencion']['idestado'] === 21 ? 'cerrado' : 'abierto';
            return $this->crearRespuesta('Ciclo de atención código "' . $cicloatencion->idcicloatencion . '" ha sido ' . $mensaje, 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un ciclo', 404);
    }

    public function updateContabilidad(Request $request, $enterprise) {
        
        $empresa = new empresa();
        $grupodx = new grupodx();
        $objentidad = new entidad();
        $autorizacionimagen = new autorizacionimagen();

        $request = $request->all(); 

        $idempresa = $empresa->idempresa($enterprise);

        //return $this->crearRespuesta(' ha sido ', 200, '', '', $request);
        $whereIn = [];

        foreach($request['ciclos'] as $row) {
            $whereIn[] = $row['idcicloatencion'];            
        }

        if (!$whereIn) {
            return $this->crearRespuesta('Especifique al menos un ciclo', [200, 'info']);
        }

        if (isset($request['idestadofactura']) && $request['idestadofactura'] === 75) { //Por enviar a contabilidad

        } 

        $entidad = $objentidad->entidad(['entidad.identidad' => $this->objTtoken->my]);

        /* Campos auditores */
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;

        if (isset($request['idestadofactura']))
            $update['idestadofactura'] = $request['idestadofactura'];

        if (isset($request['idestado']))
            $update['idestado'] = $request['idestado'];

        foreach($whereIn as $idcicloatencion) { 
            $cicloatencion = cicloatencion::find($idcicloatencion); 
                        
            if (isset($request['idestadofactura']) && $request['idestadofactura'] === 76) { //Enviado a contabilidad

                $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $idcicloatencion]);                                
                $strNombreFile = '';

                foreach($gruposDx as $row) {
                    $hojaatencion = new hojadeatencionController();
                    $hojaatencion->reporte('osi', $idcicloatencion, $row->idgrupodx, $this->objTtoken->my);

                    $nombreFile = 'HA' . '_' . $idcicloatencion . '_' . $row->idgrupodx . '.pdf';

                    $strNombreFile .= empty($strNombreFile) ? $nombreFile : (',' . $nombreFile); 
                } 

                
                $imagenes = $autorizacionimagen->autorizacionconimagenes($idcicloatencion); 
                foreach ($imagenes as $row) {
                    $imagenautorizacion = new imagenautorizacionController();
                    $imagenautorizacion->reporte('osi', $row->idcicloautorizacion);

                    $nombreFile = 'AU' . '_' . $idcicloatencion . '_' . $row->idcicloautorizacion . '.pdf';

                    $strNombreFile .= empty($strNombreFile) ? $nombreFile : (',' . $nombreFile);
                    // \Log::info(print_r($nombreFile, true)); 
                } 

                if (!empty($strNombreFile)) {
                    $update['pdfs'] = $strNombreFile;
                }

                \Log::info(print_r($cicloatencion->idestado, true)); 
                \Log::info(print_r($cicloatencion->logenvios, true)); 

                $strlog = $entidad->entidad .  ';'. 'Ciclo "enviado" a contabilidad' . ';' . date('Y-m-d H:i:s');
                $update['logenvios'] = (!empty($cicloatencion->logenvios) ? ($cicloatencion->logenvios . '|') : '') . $strlog;
            }


            if (isset($request['idestadofactura']) && $request['idestadofactura'] === 75) { //Por enviar a contabilidad
                
                if (!empty($cicloatencion->pdfs)) {
                    $pdfs = explode(',', $cicloatencion->pdfs);
                    foreach ($pdfs as $nombreFile) {
                        if (file_exists($this->pathImg . $nombreFile)) {
                            if (unlink($this->pathImg . $nombreFile)) {  
                                //Eliminada
                            }
                        }
                    }
                }

                $strlog = $entidad->entidad .  ';'. 'Ciclo "por enviar" a contabilidad' . ';' . date('Y-m-d H:i:s');
                $update['logenvios'] = (!empty($cicloatencion->logenvios) ? ($cicloatencion->logenvios . '|') : '') . $strlog;
                $update['pdfs'] = NULL; 
            }

            $cicloatencion->fill($update);
            $cicloatencion->save();             
        }

        return $this->crearRespuesta('Ciclos actualizado', 200, '', '', '');
    }

    public function enviarAContabilidad(Request $request, $enterprise, $id) {
        
        $cicloatencion = cicloatencion::find($id);

        $grupodx = new grupodx();
        $objentidad = new entidad();
        $autorizacionimagen = new autorizacionimagen();

        $request = $request->all();

        if (!$cicloatencion) {
            return $this->crearRespuesta('No existe ciclo de atención.', [200, 'info']);
        }

        if ($cicloatencion->idestadofactura === 76 || $cicloatencion->idestadofactura === 77) {
            return $this->crearRespuesta('Ciclo ya se encuentra en Contabilidad.', [200, 'info']);
        }

        $entidad = $objentidad->entidad(['entidad.identidad' => $this->objTtoken->my]);

        if (!$entidad) {
            return $this->crearRespuesta('No existe personal responsable.', [200, 'info']);
        }

        /* Campos auditores */
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;
        $update['idestadofactura'] = 76; // Enviado
        $update['idestado'] = 21; // Cerrado

        \DB::beginTransaction();
        try {
            $strNombreFile = '';

            $noGenerado = false;
            $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $id]);
            foreach($gruposDx as $row) {
                $hojaatencion = new hojadeatencionController();

                $returnPdf = $hojaatencion->reporte('osi', $id, $row->idgrupodx, $this->objTtoken->my);

                if ($returnPdf['generado'] === 0) {
                    $noGenerado = true;
                    break;
                }

                $nombreFile = 'HA' . '_' . $id . '_' . $row->idgrupodx . '.pdf';

                $strNombreFile .= empty($strNombreFile) ? $nombreFile : (',' . $nombreFile); 
            } 

            if ($noGenerado) {
                return $this->crearRespuesta('No se generó Hoja de atención. Comunicarse con sistemas.', [200, 'info']);
            }
            
            $noGenerado = false;
            $imagenes = $autorizacionimagen->autorizacionconimagenes($id); 
            foreach ($imagenes as $row) {
                $imagenautorizacion = new imagenautorizacionController();

                $returnPdf = $imagenautorizacion->reporte('osi', $row->idcicloautorizacion);

                if ($returnPdf['generado'] === 0) {
                    $noGenerado = true;
                    break;
                }

                $nombreFile = 'AU' . '_' . $id . '_' . $row->idcicloautorizacion . '.pdf';

                $strNombreFile .= empty($strNombreFile) ? $nombreFile : (',' . $nombreFile);
            }

            if ($noGenerado) {
                return $this->crearRespuesta('No se generó Sited. Comunicarse con sistemas.', [200, 'info']);
            }

            if (!empty($strNombreFile)) {
                $update['pdfs'] = $strNombreFile;
            }

            \Log::info(print_r($cicloatencion->idestado, true)); 
            \Log::info(print_r($cicloatencion->logenvios, true)); 

            $strlog = $entidad->entidad .  ';'. 'Ciclo "enviado" a contabilidad' . ';' . date('Y-m-d H:i:s');
            $update['logenvios'] = (!empty($cicloatencion->logenvios) ? ($cicloatencion->logenvios . '|') : '') . $strlog;
          
            $cicloatencion->fill($update);
            $cicloatencion->save();                       

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();

        return $this->crearRespuesta('Ciclo #'.$id.' enviado a Contabilidad', 200);
    }

    public function anularEnvioAContabilidad(Request $request, $enterprise, $id) {
        
        $cicloatencion = cicloatencion::find($id);

        $grupodx = new grupodx();
        $objentidad = new entidad();
        $autorizacionimagen = new autorizacionimagen(); 

        $request = $request->all();  
  

        if (!$cicloatencion) {
            return $this->crearRespuesta('No existe ciclo de atención.', [200, 'info']);
        }

        if ($cicloatencion->idestadofactura === 75) {
            return $this->crearRespuesta('Ciclo ya se encuentra enviado a Contabilidad.', [200, 'info']);
        }

        $entidad = $objentidad->entidad(['entidad.identidad' => $this->objTtoken->my]);

        if (!$entidad) {
            return $this->crearRespuesta('No existe personal responsable.', [200, 'info']);
        }

        /* Campos auditores */
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;
        $update['idestadofactura'] = 75; // Por enviar
        $update['idestado'] = 20; // Aperturado

        
        \DB::beginTransaction();
        try {
                
            if (!empty($cicloatencion->pdfs)) {
                $pdfs = explode(',', $cicloatencion->pdfs);
                foreach ($pdfs as $nombreFile) {
                    if (file_exists($this->pathImg . $nombreFile)) {
                        if (unlink($this->pathImg . $nombreFile)) {  
                            //Eliminada
                        }
                    }
                }
            }

            $strlog = $entidad->entidad .  ';'. 'Ciclo "por enviar" a contabilidad' . ';' . date('Y-m-d H:i:s');
            $update['logenvios'] = (!empty($cicloatencion->logenvios) ? ($cicloatencion->logenvios . '|') : '') . $strlog;
            $update['pdfs'] = NULL;

            $cicloatencion->fill($update);
            $cicloatencion->save();

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();

        return $this->crearRespuesta('Anulado el envío a Contabilidad', 200);
    }

    public function previsualizacionHA ($enterprise, $id, $idgrupodx) {
        $hojaatencion = new hojadeatencionController();
        $hojaatencion->reporte('osi', $id, $idgrupodx, $this->objTtoken->my, true); 
    }

    public function previsualizacionSITED ($enterprise, $idcicloautorizacion) {
        $imagenautorizacion = new imagenautorizacionController();
        $imagenautorizacion->reporte('osi', $idcicloautorizacion, true);
    }

    public function regenerarHAyAU(Request $request, $enterprise, $idcicloatencion) {
        
        $grupodx = new grupodx();
        $objentidad = new entidad();
        $autorizacionimagen = new autorizacionimagen();

        $request = $request->all(); 

        $cicloatencion = cicloatencion::find($idcicloatencion); 
  
        if ($cicloatencion->idestadofactura === 75) {
            return $this->crearRespuesta('Ciclo pendiente por enviar a Contabilidad', [200, 'info']);
        }

        if ($cicloatencion->idestadofactura === 77) {
            return $this->crearRespuesta('Ciclo facturado en Contabilidad', [200, 'info']);
        }

        $entidad = $objentidad->entidad(['entidad.identidad' => $this->objTtoken->my]);

        /* Campos auditores */
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;

             
        $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $idcicloatencion]);                                
        $strNombreFile = '';

        foreach($gruposDx as $row) {
            $hojaatencion = new hojadeatencionController();
            $hojaatencion->reporte('osi', $idcicloatencion, $row->idgrupodx, $this->objTtoken->my);

            $nombreFile = 'HA' . '_' . $idcicloatencion . '_' . $row->idgrupodx . '.pdf';

            $strNombreFile .= empty($strNombreFile) ? $nombreFile : (',' . $nombreFile); 
        }
        
        $imagenes = $autorizacionimagen->autorizacionconimagenes($idcicloatencion); 
        foreach ($imagenes as $row) {
            $imagenautorizacion = new imagenautorizacionController();
            $imagenautorizacion->reporte('osi', $row->idcicloautorizacion);

            $nombreFile = 'AU' . '_' . $idcicloatencion . '_' . $row->idcicloautorizacion . '.pdf';

            $strNombreFile .= empty($strNombreFile) ? $nombreFile : (',' . $nombreFile);
            // \Log::info(print_r($nombreFile, true)); 
        } 

        if (!empty($strNombreFile)) {
            $update['pdfs'] = $strNombreFile;
        }

        \Log::info(print_r($cicloatencion->idestado, true)); 
        \Log::info(print_r($cicloatencion->logenvios, true)); 

        $strlog = $entidad->entidad .  ';'. 'Regenerado Hoja de atención' . ';' . date('Y-m-d H:i:s');
        $update['logenvios'] = (!empty($cicloatencion->logenvios) ? ($cicloatencion->logenvios . '|') : '') . $strlog;
        
        $cicloatencion->fill($update);
        $cicloatencion->save();             
   
        return $this->crearRespuesta('Regenerado Hoja de atención', 200, '', '', '');
    }


    public function updateMovercm(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $terapia = new terapia();
        $venta = new venta();       

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $cicloatencion = cicloatencion::find($id); 
        $presupuesto = presupuesto::where('idcicloatencion', '=', $id)->first();
        $citamedica = citamedica::find($request['citamedica']['idcitamedica']); 
        $citasmedicas = $cicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $id]);

        $tratamientosmedicos = $citamedica->tratamientomedico(['citamedica.idcitamedica' => $request['citamedica']['idcitamedica']]);

        $presupuestodet = array(); 
        if ($presupuesto)
            $presupuesto->presupuestodet($presupuesto->idpresupuesto);
        
        //, 'cicloautorizacion.principal' => '1'
        $autorizaciones = $cicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);
        $terapias = $terapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38], array('terapiatratamiento.idcicloatencion', 'terapiatratamiento.idterapiatratamiento', 'terapiatratamiento.idproducto', 'terapiatratamiento.cantidad'), TRUE);

        $newpresupuestodet = []; //Nuevo presupuesto en el nuevo ciclo.
        $regular = 0;
        $tarjeta = 0;
        $efectivo = 0;
        $montoefectuado = 0;
        $regularNP = 0;
        $tarjetaNP = 0;
        $efectivoNP = 0;
        $montoefectuadoNP = 0;
        $monto = $presupuesto->montopago;
        $montopago = 0;
        $montopagoNP = 0;
        $test = [];
        $presupuestodetTMP = [];

        foreach ($presupuestodet as $ind => $row) {
            $fila = null;
            $new = (object) array(
                        'idpresupuesto' => null,
                        'idproducto' => $row->idproducto,
                        'cantmedico' => $row->cantmedico,
                        'cantcliente' => $row->cantcliente,
                        'cantpagada' => $row->cantpagada,
                        'cantefectivo' => $row->cantefectivo,
                        'tipoprecio' => $row->tipoprecio,
                        'preciounitregular' => $row->preciounitregular,
                        'totalregular' => $row->totalregular,
                        'preciounittarjeta' => $row->preciounittarjeta,
                        'totaltarjeta' => $row->totaltarjeta,
                        'preciounitefectivo' => $row->preciounitefectivo,
                        'totalefectivo' => $row->totalefectivo,
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my
            );
            $cantmedico = $row->cantmedico;
            foreach ($tratamientosmedicos as $row2) {
                if ($row2->idproducto === $row->idproducto) {
                    if (!empty($row2->parentcantidad)) {
                        $row2->cantidad = $row2->cantidad * $row2->parentcantidad;
                    }
                    $fila = $row2;
                    break;
                }
            }

            if ($fila) {
                $cantmedico -= $fila->cantidad;

                $row->cantmedico = $cantmedico;
                if ($cantmedico === 0) {
                    //unset($presupuestodet[$ind]);
                } else {
                    //Disminucion en item presupuesto
                    $row->cantcliente = $row->cantcliente > $cantmedico ? $cantmedico : $row->cantcliente;
                    $row->cantefectivo = $row->cantefectivo > $cantmedico ? $cantmedico : $row->cantefectivo;
                    $row->cantpagada = $row->cantpagada > $cantmedico ? $cantmedico : $row->cantpagada;

                    //Edicion  de item a añadir
                    $new->cantmedico = $fila->cantidad;
                    $new->cantcliente = $new->cantcliente > $cantmedico ? ($new->cantcliente - $cantmedico) : 0;
                    $new->cantefectivo = $new->cantefectivo > $cantmedico ? ($new->cantefectivo - $cantmedico) : 0;
                    $new->cantpagada = $new->cantpagada > $cantmedico ? ($new->cantpagada - $cantmedico) : 0;
                }

                $new->totalregular = $new->preciounitregular * $new->cantcliente;
                $new->totaltarjeta = $new->preciounittarjeta * $new->cantcliente;
                $new->totalefectivo = $new->preciounitefectivo * $new->cantcliente;
                $regularNP += $new->totalregular;
                $tarjetaNP += $new->totaltarjeta;
                $efectivoNP += $new->totalefectivo;
                $montoefectuadoNP += ($presupuesto->tipotarifa === 1 ? $new->preciounitregular : ($presupuesto->tipotarifa === 2 ? $new->preciounittarjeta : $new->preciounitefectivo)) * $new->cantefectivo;
                $test[] = $new->totalefectivo;

                //Adicion de item en nuevo presupuesto
                array_push($newpresupuestodet, $new);
            }

            if ($cantmedico !== 0) {
                $row->totalregular = $row->preciounitregular * $row->cantcliente;
                $row->totaltarjeta = $row->preciounittarjeta * $row->cantcliente;
                $row->totalefectivo = $row->preciounitefectivo * $row->cantcliente;
                $regular += $row->totalregular;
                $tarjeta += $row->totaltarjeta;
                $efectivo += $row->totalefectivo;
                $montoefectuado += ($presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantefectivo;
            }
        }

        //Distribucion de los montos de pago en cada presupuesto.
        $totalSelec = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));
        $totalNPSelec = ($presupuesto->tipotarifa === 1 ? $regularNP : ($presupuesto->tipotarifa === 2 ? $tarjetaNP : $efectivoNP));

        $montopago = $monto >= $montoefectuado ? $montoefectuado : $monto;
        $monto -= $montopago;

        $montopagoNP = $monto >= $montoefectuadoNP ? $montoefectuadoNP : $monto;
        $monto -= $montopagoNP;

        if ($monto > 0) {
            $totalSelec -= $montopago;
            $temp = ($monto > $totalSelec) ? $totalSelec : $monto;
            $montopago += $temp;
            $monto -= $temp;
            $montopagoNP += $monto;
        }

        $newautorizaciones = []; //Obtener las nuevas autorizaciones solo para los coberturados
        foreach ($newpresupuestodet as $row) {
            if ($row->tipoprecio === 1) { //1: Cubierto 2: NoCubierto 3: Particular
                foreach ($autorizaciones as $row2) {
                    if ($row2->idproducto === $row->idproducto) {
                        $newautorizaciones[] = (object) array(
                                    'idempresa' => $idempresa,
                                    'idcicloatencion' => null,
                                    'idsede' => $row2->idsede,
                                    'fecha' => date('Y-m-d'),
                                    'idaseguradora' => $row2->idaseguradora,
                                    'idpaciente' => $row2->idpaciente,
                                    'idproducto' => $row2->idproducto,
                                    'idaseguradoraplan' => $row2->idaseguradoraplan,
                                    'deducible' => $row2->deducible,
                                    'idcoaseguro' => $row2->idcoaseguro,
                                    'coaseguro' => $row2->coaseguro,
                                    'idtipo' => $row2->idtipo,
                                    'codigo' => null,
                                    'descripcion' => null,
                                    'idestadoimpreso' => 83,
                                    // 'principal' => $row2->principal,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'id_created_at' => $this->objTtoken->my
                        );
                    }
                    break;
                }
            }

            if ($row->cantefectivo > 0) {
                $cantidad = $row->cantefectivo;
                $terapias = $this->ordenarMultidimension($terapias, 'cantidad', SORT_DESC); //Ordenamiento descendente
                foreach ($terapias as $row2) {
                    if ($row2->idproducto === $row->idproducto && $cantidad >= $row2->cantidad) {
                        $cantidad -= $row2->cantidad;
                        $row2->idcicloatencion = null;
                    }
                    if ($cantidad === 0)
                        break;
                }
            }
        }

        /* Campos auditores */
        $request['cicloatencion']['updated_at'] = date('Y-m-d H:i:s');
        $request['cicloatencion']['id_updated_at'] = $this->objTtoken->my;
        $request['cicloatencion']['regular'] = $regular;
        $request['cicloatencion']['tarjeta'] = $tarjeta;
        $request['cicloatencion']['efectivo'] = $efectivo;
        $request['cicloatencion']['montoefectuado'] = $montoefectuado;

        /* Campos auditores */

        if ($cicloatencion) {
            \DB::beginTransaction();
            try {
                /* Crear nuevo ciclo
                 * Crear nuevo presupuesto
                 * Crear nuevo presupuestodetalle
                 * Crear nuevas autorizaciones
                 * Actualizar detallepresupuesto  
                 * Actualizar terapias (historia clinica)
                 * Actualizar citamedica
                 * Actualizar presupuesto
                 *  
                 * Actualizar LOG DE CAMBIOS de presupuesto                 
                 * Actualizar Total/Acuenta de presupuesto                  
                 */

                //Crear nuevo ciclo
                $createCiclo = array(
                    'idempresa' => $idempresa,
                    'idsede' => $cicloatencion->idsede,
                    'idmedico' => $citamedica->idmedico,
                    'idpaciente' => $cicloatencion->idpaciente,
                    'idestado' => $cicloatencion->idestado, //20:Aperturado 21:Cerrado 22:Cancelado
                    'idestadofactura' => 75, 
                    'fecha' => date('Y-m-d'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my
                );
                $newcicloatencion = cicloatencion::create($createCiclo);
  
                //Crear presupuesto 
                $createPre = array(
                    'idempresa' => $idempresa,
                    'idsede' => $presupuesto->idsede,
                    'idcliente' => $presupuesto->idcliente,
                    'idcicloatencion' => $newcicloatencion->idcicloatencion,
                    'idestado' => $presupuesto->idestado,                    
                    'fecha' => date('Y-m-d'),
                    'tipotarifa' => $presupuesto->tipotarifa,
                    'regular' => $regularNP,
                    'tarjeta' => $tarjetaNP,
                    'efectivo' => $efectivoNP,
                    'montoefectuado' => $montoefectuadoNP,
                    'montopago' => $montopagoNP,
                    'montocredito' => $montopagoNP,
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my,
                    'idestadopago' => $montopagoNP >= $totalNPSelec && $totalNPSelec > 0  ? 68 : ( $montopagoNP > 0 && $montopagoNP < $totalNPSelec ? 67 : 66),
                    'total' => $totalNPSelec 
                );
                $newpresupuesto = presupuesto::create($createPre);

                //Crear presupuestodetalle 
                if (!empty($newpresupuestodet)) {
                    foreach ($newpresupuestodet as $row) {
                        $row->idpresupuesto = $newpresupuesto->idpresupuesto;
                    }
                    \DB::table('presupuestodet')->insert($this->convertArray($newpresupuestodet));
                }

                //Crear autorizaciones                
                if (!empty($newautorizaciones)) {
                    foreach ($newautorizaciones as $row) {
                        $row->idcicloatencion = $newcicloatencion->idcicloatencion;
                    }
                    \DB::table('cicloautorizacion')->insert($this->convertArray($newautorizaciones));
                }

                //Actualizar detallepresupuesto  
                foreach ($presupuestodet as $fila) {
                    if ($fila->cantmedico === 0) {
                        $update = array('deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my);
                        \DB::table('presupuestodet')->where(['idpresupuestodet' => $fila->idpresupuestodet])->update($update);
                    } else {
                        $update = array(
                            'cantcliente' => $fila->cantcliente,
                            'cantefectivo' => $fila->cantefectivo,
                            'cantmedico' => $fila->cantmedico,
                            'cantpagada' => $fila->cantpagada,
                            'totalefectivo' => $fila->totalefectivo,
                            'totalregular' => $fila->totalregular,
                            'totaltarjeta' => $fila->totaltarjeta,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'id_updated_at' => $this->objTtoken->my
                        );
                        \DB::table('presupuestodet')->where(['idpresupuestodet' => $fila->idpresupuestodet])->update($update);
                    }
                }

                //Actualizar terapias (historia clinica)  
                if (!empty($terapias)) {
                    $whereIn = [];
                    foreach ($terapias as $row) {
                        if (is_null($row->idcicloatencion))
                            $whereIn[] = $row->idterapiatratamiento;
                    }
                    if (!empty($whereIn)) {
                        $update = array('idcicloatencion' => $newcicloatencion->idcicloatencion);
                        \DB::table('terapiatratamiento')->whereIn('idterapiatratamiento', $whereIn)->update($update);
                    }
                }

                //Actualizar citamedica
                $fillCm = array('idcicloatencion' => $newcicloatencion->idcicloatencion);
                $citamedica->fill($fillCm);
                $citamedica->save();

                //Actualizar presupuesto
                $total = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));
                $fillPre = array(
                    'regular' => $regular,
                    'tarjeta' => $tarjeta,
                    'efectivo' => $efectivo,
                    'montoefectuado' => $montoefectuado,
                    'montopago' => $montopago,
                    'montocredito' => $montopago,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my,
                    'idestadopago' => $montopago >= $total && $total > 0 ? 68 : ($montopago > 0 && $montopago < $total ? 67 : 66),
                    'total' => $total                      
                );

                $presupuesto->fill($fillPre);
                $presupuesto->save();

                //LogPresupuesto  
                $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                $this->actualizarPagopresupuestoCitaMedica($presupuesto);

                $uno = null;
                //Actualizar ventas de TRATAMIENTOS
                if(count($citasmedicas) === 1){
                    $ventas = $venta->grid(['venta.idcicloatencion' => $id, 'venta.idestadodocumento' => 27]);
                    foreach($ventas as $row){
                        $uno = $row->idventa;
                        \DB::table('venta')->where(['idventa' => $row->idventa])->update(['idcicloatencion' => $newcicloatencion->idcicloatencion]);
                    }
                }

                $dos = null;
                //Actualizar ventas de CM
                $ventas = $venta->grid(['venta.idcicloatencion' => $id, 'venta.idestadodocumento' => 27]);
                foreach($ventas as $row){
                    $ventadet = $venta->ventadet($row->idventa);
                    foreach($ventadet as $row2){
                        if($row2->idcitamedica === $request['citamedica']['idcitamedica']){
                            $dos = $row->idventa;
                            \DB::table('venta')->where(['idventa' => $row->idventa])->update(['idcicloatencion' => $newcicloatencion->idcicloatencion]);
                            break 2;
                        }
                    } 
                }

                // return $this->crearRespuesta('Abc', [200, 'info'], '', '', array($uno, $dos));
                // return $this->crearRespuesta('Vac', [200, 'info'], '', '', [$createCiclo, $createPre, $newpresupuestodet, $newautorizaciones, $presupuestodet, $fillCm, $fillPre]);
                // return $this->crearRespuesta('Vac', [200, 'info'], '', '', [$newpresupuestodet, $montopago, $montopagoNP, $monto]); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Ciclo de atención código "' . $cicloatencion->idcicloatencion . '" ha sido actualizado.', 200, '', '', $newcicloatencion->idcicloatencion);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un ciclo', 404);
    }

    public function updateMoverciclo(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $objEntidad = new entidad();
        
        $cicloatencion = cicloatencion::find($id);
        
        $presupuesto = presupuesto::where('idcicloatencion', '=', $id)->first(); 

        //return $this->crearRespuesta('Presu: '.$presupuesto->idpresupuesto, [200, 'info']);

        $presupuestodet = array();
        if($presupuesto)
            $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if ($cicloatencion) {
            \DB::beginTransaction();
            try {
                $regular = 0;
                $tarjeta = 0;
                $efectivo = 0;
                $montoefectuado = 0;

                //Actualizar detallepresupuesto  
                foreach ($presupuestodet as $fila) {

                    if ($fila->tipoprecio === 3) {

                        $data = \DB::table('tarifario')
                                ->select('partref', 'partcta', 'partsta')
                                ->where(['idproducto' => $fila->idproducto, 'idsede' => $request['idsede']])
                                ->first();

                        if ($data) {
                            $fila->preciounitefectivo = $data->partsta;
                            $fila->preciounitregular = $data->partref;
                            $fila->preciounittarjeta = $data->partcta;

                            $fila->totalregular = $fila->preciounitregular * $fila->cantcliente;
                            $fila->totaltarjeta = $fila->preciounittarjeta * $fila->cantcliente;
                            $fila->totalefectivo = $fila->preciounitefectivo * $fila->cantcliente;
                        }

                        $update = array(
                            'preciounitefectivo' => $fila->preciounitefectivo,
                            'preciounitregular' => $fila->preciounitregular,
                            'preciounittarjeta' => $fila->preciounittarjeta,
                            'totalefectivo' => $fila->totalefectivo,
                            'totalregular' => $fila->totalregular,
                            'totaltarjeta' => $fila->totaltarjeta,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'id_updated_at' => $this->objTtoken->my
                        );
                        \DB::table('presupuestodet')->where(['idpresupuestodet' => $fila->idpresupuestodet])->update($update);
                    }

                    if ($fila->cantcliente !== 0) {
                        $regular += $fila->totalregular;
                        $tarjeta += $fila->totaltarjeta;
                        $efectivo += $fila->totalefectivo;
                        $montoefectuado += ($presupuesto->tipotarifa === 1 ? $fila->preciounitregular : ($presupuesto->tipotarifa === 2 ? $fila->preciounittarjeta : $fila->preciounitefectivo)) * $fila->cantefectivo;
                    }
                }

                //Actualizar presupuesto
                if($presupuesto){
                    $fillPre = array(
                        'idsede' => $request['idsede'],
                        'regular' => $regular,
                        'tarjeta' => $tarjeta,
                        'efectivo' => $efectivo,
                        'montoefectuado' => $montoefectuado,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    );                
                    $presupuesto->fill($fillPre);
                    $presupuesto->save();
                }

                $fillCiclo = array(
                    'fechatraslado' => date('Y-m-d'),
                    'identidadtraslado' => $this->objTtoken->my,
                    'idsedetraslado' => $cicloatencion->idsede,
                    'idsede' => $request['idsede'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my
                );                
                $cicloatencion->fill($fillCiclo);
                $cicloatencion->save();
                
                /* 1.- GenerarHC, caso no tenga. */
                $entidad = $objEntidad->entidadHC(array('identidad' => $cicloatencion->idpaciente), $request['idsede']);
                if (empty($entidad->hc)) {
                    $entidad->hc = $objEntidad->generaHC($cicloatencion->idpaciente, $request['idsede'], true, $this->objTtoken->my); 
                }  
                
                //return $this->crearRespuesta('Vac', [200, 'info'], '', '', [$fillPre, $fillCiclo]);

                //LogPresupuesto  
                if($presupuesto){
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                    $this->actualizarPagopresupuestoCitaMedica($presupuesto);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Ciclo de atención "' . $cicloatencion->idcicloatencion . '" ha sido trasladado.', 200, '', '', $cicloatencion->idcicloatencion);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un ciclo', 404);
    }

    public function update(Request $request, $enterprise, $id) {
        // [2020-04-28 16:06:48] local.ERROR: Undefined index: cicloautorizacion {"exception":"[object] (ErrorException(code: 0): Undefined index: cicloautorizacion at /home/centromedico/public_html/apiosi/app/Http/Controllers/cicloatencionController.php:2518)
        
        /* El ciclo de atencion no se modifica. 
         * Lo que se se hace es borrar C.M., añadir autorizaciones, editar presupuesto etc. Cuando el ciclo este abierto.          
         */
        $cicloatencion = cicloatencion::find($id);

        $empresa = new empresa();
        $citamedica = new citamedica();
        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise);

        //VALIDACIONES 
        /* 1.- Validar que ciclo no se encuentre cerrado.
         */  
        if ($cicloatencion->idestado === 21) {
            return $this->crearRespuesta('Ciclo de atención se encuentra cerrado. No puede editarse.', [200, 'info']);
        }
        
        if (isset($request['ciclocitamedica']) && !empty($request['ciclocitamedica'])) {
            $cantcmautorizada = 0;
            foreach ($request['ciclocitamedica'] as $row2) {                
                if ($row2['tipocm'] === 1) {
                    $cantcmautorizada++;
                }
            }

            if ($request['ciclocitamedica'] && $cantcmautorizada !== 1) {
                return $this->crearRespuesta('Ciclo debe tener una consulta autorizada.', [200, 'info']);
            }
        }
        

        /* 2.- Validar no haya mas de una autorizacion para un tratamiento */
        $autorizacionCant = [];
        foreach ($request['cicloautorizacion'] as $row) {
            if (!isset($autorizacionCant[$row['idproducto']])) {
                $autorizacionCant[$row['idproducto']] = 0; 
            }

            $autorizacionCant[$row['idproducto']] += 1; 
        }

        $cantAutorizacion = false;
        foreach ($autorizacionCant as $cantidad) { 
            if ($cantidad > 1) {
                $cantAutorizacion = true;
                break;
            }
        }

        if ($cantAutorizacion) {
            return $this->crearRespuesta('Solo debe haber una Autorización por tratamiento.', [200, 'info']);
        }
        //FIN VALIDACIONES         
  
        /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'cicloautorizacion'.
         */
        $cicloautorizacionInsert = [];
        $cicloautorizacionUpdate = [];
        $cicloautorizacionDelete = [];

        $dataAutoriz = $cicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);
        foreach ($request['cicloautorizacion'] as $indice => $row) {
            $request['cicloautorizacion'][$indice]['fecha'] = $this->formatFecha($request['cicloautorizacion'][$indice]['fecha'], 'yyyy-mm-dd');

            $nuevo = true;
            $update = false;
            foreach ($dataAutoriz as $indice => $row2) {
                if (isset($row['idcicloautorizacion']) && $row['idcicloautorizacion'] === $row2->idcicloautorizacion) {
                    $nuevo = false;
                    $update = true;
                    unset($dataAutoriz[$indice]);
                    break 1;
                }
            }

            $tmp = array(
                'idsede' => $row['idsede'],
                'fecha' => $this->formatFecha($row['fecha'], 'yyyy-mm-dd'),
                'idaseguradora' => $row['idaseguradora'],
                'idaseguradoraplan' => $row['idaseguradoraplan'],
                'deducible' => $row['deducible'],
                'idcoaseguro' => isset($row['idcoaseguro']) ? $row['idcoaseguro'] : NULL,
                'coaseguro' => isset($row['coaseguro']) ? $row['coaseguro'] : NULL,
                'idtipo' => $row['idtipo'],
                'codigo' => $row['codigo'],
                // 'principal' => $row['principal'],
                'idproducto' => isset($row['idproducto']) ? $row['idproducto'] : NULL,
                'idtitular' => isset($row['idtitular']) ? $row['idtitular'] : NULL,
                'parentesco' => isset($row['parentesco']) ? $row['parentesco'] : NULL, 
                //'idcia' => isset($row['idcia']) ? $row['idcia'] : NULL
                'nombrecompania' => isset($row['nombrecompania']) ? $row['nombrecompania'] : NULL,
                'hora' => isset($row['hora']) ? $row['hora'] : NULL,
                'numero' => isset($row['numero']) ? $row['numero'] : NULL,
            );

            if (isset($row['descripcion']))
                $tmp['descripcion'] = $row['descripcion'];

            if ($nuevo) {
                $tmp['idempresa'] = $idempresa;
                $tmp['idcicloatencion'] = $id;
                $tmp['idpaciente'] = $cicloatencion->idpaciente;                
                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['id_created_at'] = $this->objTtoken->my;
                $tmp['idestadoimpreso'] = 83; 

                $cicloautorizacionInsert[] = $tmp;
            }

            if ($update) {
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $cicloautorizacionUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['idcicloautorizacion' => $row['idcicloautorizacion']]
                );
            }
        }

        if (!empty($dataAutoriz)) {
            $tmp = array();
            $tmp['deleted'] = '1';
            $tmp['deleted_at'] = date('Y-m-d H:i:s');
            $tmp['id_deleted_at'] = $this->objTtoken->my;

            foreach ($dataAutoriz as $row) {
                $cicloautorizacionDelete[] = array(
                    'data' => $tmp,
                    'where' => array(
                        'idcicloautorizacion' => $row->idcicloautorizacion
                    )
                );
            }
        }

        // Validacion
        // 3. No podemos eliminar una autorización que tiene comprobante en tabla venta.
        $idautorizacionIn = array();
        foreach ($cicloautorizacionDelete as $row) {
            $idautorizacionIn[] = $row['where']['idcicloautorizacion'];
        }

        if (!empty($idautorizacionIn)) {
            $dataValidacion = \DB::table('venta')                
                ->whereIn('idcicloautorizacion', $idautorizacionIn)
                ->whereNull('deleted')
                ->get()->all();

            if (!empty($dataValidacion)) {
                return $this->crearRespuesta('Autorización no puede eliminarse, tiene comprobantes de venta.', [200, 'info']);
            }
        }

        // 4. No podemos eliminar una autorización que tiene llenado campo idventa.
        // Esto es opcional porque mas importancia tiene el campo idcicloautorizacion en tabla venta.
        $idautorizacionIn = array();
        foreach ($cicloautorizacionDelete as $row) {
            $idautorizacionIn[] = $row['where']['idcicloautorizacion'];
        }

        if (!empty($idautorizacionIn)) {
            $dataValidacion = \DB::table('cicloautorizacion')                
                ->whereIn('idcicloautorizacion', $idautorizacionIn)
                ->whereNotNull('idventa')
                ->whereNull('deleted')
                ->get()->all();

            if (!empty($dataValidacion)) {
                return $this->crearRespuesta('Autorización no puede eliminarse, tiene comprobantes de venta.', [200, 'info']);
            }
        }
        // Fin Validacion
        

        if ($cicloatencion) {

            \DB::beginTransaction();
            try {        
                    
                // $fillCiclo = array( 
                //     'idestadofactura' => $request['cicloatencion']['idestadofactura'],
                //     'updated_at' => date('Y-m-d H:i:s'),
                //     'id_updated_at' => $this->objTtoken->my
                // ); 
                // $cicloatencion->fill($fillCiclo);
                // $cicloatencion->save(); 

                if (isset($request['entidad']) && !empty($request['entidad'])) {
                    \DB::table('entidad')
                        ->where('identidad', $cicloatencion->idpaciente)
                        ->update($request['entidad']);
                }
                
                $citaprincipal = null;

                if (isset($request['ciclocitamedica']) && !empty($request['ciclocitamedica'])) {

                    //idestado: 4:pendiente, 5:confirmada, 6:atendida, 7:cancelada, 48:noasistio
                    $datacitamedica = $citamedica->grid(['citamedica.idcicloatencion' => $id], '', '', '', '', '', [4, 5, 6, 48]);

                    $auditoria = array(
                        'idcicloatencion' => NULL,
                        'horaespera' => NULL,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my);

                    foreach ($datacitamedica as $row1) {
                        $noEncontrado = true;
                        $update = [];
                        foreach ($request['ciclocitamedica'] as $row2) {
                            if ($row1->idcitamedica === $row2['idcitamedica']) {
                                $noEncontrado = false;
                                if ($row1->tipocm !== $row2['tipocm'] || $row1->tipocmcomentario !== $row2['tipocmcomentario']) {
                                    $update = array(
                                        'tipocm' => $row2['tipocm'],
                                        'tipocmcomentario' => $row2['tipocmcomentario']
                                    );
                                }

                                if ($row2['tipocm'] === 1) {
                                    $citaprincipal = $row2['idcitamedica'];
                                }

                                break;
                            }
                        }

                        //Podemos quitar la cita 
                        if ($noEncontrado && in_array($row1->idestado, [4, 5, 48])) {
                            \DB::table('citamedica')
                                ->where(['idcitamedica' => $row1->idcitamedica])
                                ->update($auditoria);
                        }

                        if (!empty($update)) {
                            \DB::table('citamedica')
                                ->where(['idcitamedica' => $row1->idcitamedica])
                                ->update($update);

                            $citamedica->grabarLog($row1->idcitamedica, $this->objTtoken->my);
                        }
                    } 
                }  

                $fillCiclo = array( 
                    //'idcitamed' => $request['cicloatencion']['idcitamed'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my
                ); 

                if(isset($request['cicloatencion']['idcitamed']) && !empty($request['cicloatencion']['idcitamed'])){
                     $fillCiclo['idcitamed'] =  $request['cicloatencion']['idcitamed'];
                }

                if(isset($request['cicloatencion']['idzona']) && !empty($request['cicloatencion']['idzona'])){
                     $fillCiclo['idzona'] =  $request['cicloatencion']['idzona'];
                }

                if ($citaprincipal) {
                    $fillCiclo['idcitamedica'] = $citaprincipal;
                }

                $cicloatencion->fill($fillCiclo);
                $cicloatencion->save();

                /* Insertar, actualizar, eliminar en tabla 'cicloautorizacion'.
                 */

                if (!empty($cicloautorizacionInsert))
                    \DB::table('cicloautorizacion')->insert($cicloautorizacionInsert);

                foreach ($cicloautorizacionUpdate as $fila) {
                    \DB::table('cicloautorizacion')->where($fila['where'])->update($fila['data']);
                }
                foreach ($cicloautorizacionDelete as $fila) {
                    \DB::table('cicloautorizacion')->where($fila['where'])->update($fila['data']);
                }

                /* Actualizar presupuesto 
                 * 04.05.2016
                 */
                $presupuesto = presupuesto::where('idcicloatencion', '=', $id)->first();
                 
                if ($presupuesto) {
 
                    //SI Existe presupuesto.                    
                    $presupuesto->montopago = (double) $presupuesto->montopago; 
                    if (isset($request['presupuesto']['tipotarifa'])) {
                        $presupuesto->tipotarifa = $request['presupuesto']['tipotarifa'];
                        $paramPresupuesto['tipotarifa'] = $request['presupuesto']['tipotarifa'];
                    }

                    switch ($idempresa) {
                        case 2: 
                            $unificado = $this->compararTratamiento($id, $presupuesto, $request);
                            break; 
                        default:
                            $unificado = $this->compararTratamientoUnificadoVSPresupuesto($id, $presupuesto, $request);
                            break;
                    } 

                    // return $this->crearRespuesta('Infox.', [200, 'info'], '', '', $unificado);

                    if (!empty($unificado['tratamientomedicoInsert']))
                        \DB::table('presupuestodet')->insert($unificado['tratamientomedicoInsert']);

                    foreach ($unificado['tratamientomedicoUpdate'] as $fila) {
                        \DB::table('presupuestodet')->where($fila['where'])->update($fila['data']);
                    }

                    foreach ($unificado['tratamientomedicoDelete'] as $fila) {
                        \DB::table('presupuestodet')->where($fila['where'])->update($fila['data']);
                        \DB::table('presupuestodetcant')->where($fila['where'])->update($fila['data']);
                    }

                    if (isset($unificado['tratamientocantidadInsert']) && !empty($unificado['tratamientocantidadInsert']))
                        \DB::table('presupuestodetcant')->insert($unificado['tratamientocantidadInsert']);


                    if (isset($unificado['tratamientocantidadInsertAfter']) && !empty($unificado['tratamientocantidadInsertAfter'])) {

                        $dataPresupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

                        foreach($unificado['tratamientocantidadInsertAfter'] as $index => $fila) {
                            foreach($dataPresupuestodet as $value) {
                                if ($value->idproducto === $fila['idproducto']) {
                                    $unificado['tratamientocantidadInsertAfter'][$index]['idpresupuestodet'] = $value->idpresupuestodet;
                                    break;
                                }
                            }
                        }
                        \DB::table('presupuestodetcant')->insert($unificado['tratamientocantidadInsertAfter']);                                    
                    }
                     
                    /* Campos auditores */
                    $paramPresupuesto['updated_at'] = date('Y-m-d H:i:s');
                    $paramPresupuesto['id_updated_at'] = $this->objTtoken->my;
                    /* Campos auditores */

                    $paramPresupuesto['regular'] = $unificado['regular'];
                    $paramPresupuesto['tarjeta'] = $unificado['tarjeta'];
                    $paramPresupuesto['efectivo'] = $unificado['efectivo'];
                    $paramPresupuesto['montoefectuado'] = $unificado['montoefectuado'];
 

                    $total = ($presupuesto->tipotarifa === 1 ? $unificado['regular'] : ($presupuesto->tipotarifa === 2 ? $unificado['tarjeta'] : $unificado['efectivo']));                    
                    $paramPresupuesto['idestadopago'] = $presupuesto->montopago >= $total && $total > 0 ? 68 : ($presupuesto->montopago > 0 && $presupuesto->montopago < $total ? 67 : 66);
                    $paramPresupuesto['total'] = $total;

                    $presupuesto->fill($paramPresupuesto);
                    $presupuesto->save();

                    //LogPresupuesto  
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);

                    $this->actualizarPagopresupuestoCitaMedica($presupuesto);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Ciclo de atención N° "' . $cicloatencion->idcicloatencion . '" ha sido editado', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un ciclo', 404);
    }

    private function actualizarPagopresupuestoCitaMedica($presupuesto) {
        /* Setear 'todo A', 'Acuenta B', 'Todo B', 'Acuenta C', 'Todo C' en tabla CITAMEDICA;
         * Se considera primera cita, por orden de fecha y hora de inicio de cita.
         */
        $cicloatencion = new cicloatencion();
        $citamedica = new citamedica();

        $montopago = $presupuesto->montopago;
        $citasmedicas = $cicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $presupuesto->idcicloatencion]);
        $citasmedicas = $this->ordenarMultidimension($citasmedicas, 'fecha', SORT_ASC, 'inicio', SORT_ASC);
        $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
        $tmp = [];
        foreach ($citasmedicas as $row) {
            $CMCosto = 0;

            $tratamientosmedicos = $citamedica->tratamientomedico(['citamedica.idcitamedica' => $row->idcitamedica]);
            foreach ($tratamientosmedicos as $tratamiento) {
                $costo = 0;
                foreach ($presupuestodet as $rowpres) {
                    if ($tratamiento->idproducto === $rowpres->idproducto) {
                        $cantidad = $tratamiento->cantidad;
                        if (!empty($tratamiento->parentcantidad)) {
                            $cantidad = $tratamiento->cantidad * $tratamiento->parentcantidad;
                        }
                        $preciounit = $presupuesto->tipotarifa === 1 ? $rowpres->preciounitregular : ($presupuesto->tipotarifa === 2 ? $rowpres->preciounittarjeta : $rowpres->preciounitefectivo);
                        $costo = $preciounit * $cantidad;
                        break;
                    }
                }
                $CMCosto += $costo;
            }

            if ($montopago > 0) {

                $dinero = 0;
                if ($CMCosto <= $montopago) {
                    if ($row->presupuesto === 'Acuenta C' || empty($row->presupuesto)) {
                        $row->presupuesto = 'Todo C';
                    }
                    if ($row->presupuesto === 'Acuenta B') {
                        $row->presupuesto = 'Todo B';
                    }
                    $dinero = $CMCosto;
                }

                if ($CMCosto > $montopago) {
                    switch ($row->presupuesto) {
                        case 'Todo A':
                        case 'Todo B':
                            $row->presupuesto = 'Acuenta B';
                            break;
                        default: //'Todo C' o '' o null
                            $row->presupuesto = 'Acuenta C';
                            break;
                    }
                    $dinero = $montopago;
                }
                $montopago = $montopago - $dinero;
            } else {
                $row->presupuesto = '';
            }
//            array_push($tmp, ['idcitamedica' => $row->idcitamedica, 'presupuesto' => $row->presupuesto]);
            \DB::table('citamedica')->where(['idcitamedica' => $row->idcitamedica])->update(['presupuesto' => $row->presupuesto]);
        }
//                return $this->crearRespuesta('=>', [200, 'info'],'','', $tmp);
    }

    public function destroy($enterprise, $id) {

        $cicloatencion = cicloatencion::find($id);

        //VALIDACIONES
        //FIN VALIDACIONES 

        if ($cicloatencion) {

            $return = $cicloatencion->validadorDataRelacionada($id);
            if ($return['validator']) {
                return $this->crearRespuesta($return['message'], [200, 'info']);
            }

            if($cicloatencion->primert) {
                return $this->crearRespuesta('Realizo tratamiento no puede ser eliminado', [200, 'info']);
            }

            \DB::beginTransaction();
            try {
                /* Desactivamos tabla 'cicloatencion' en campo deleted.
                 */
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $cicloatencion->fill($auditoria);
                $cicloatencion->save();

                $auditoria = ['idcicloatencion' => NULL, 'updated_at' => date('Y-m-d H:i:s'), 'id_updated_at' => $this->objTtoken->my];
                \DB::table('citamedica')->where(['idcicloatencion' => $id])->update($auditoria);

                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                \DB::table('presupuesto')->where(['idcicloatencion' => $id])->update($auditoria);

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Ciclo de atención N° "' . $cicloatencion->idcicloatencion . '" ha sido eliminado', 200);
        }
        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }

    private function compararTratamiento($idcicloatencion, $presupuesto, $request = []) {
 
        //$paramPresupuestodet = $unificado['paramPresupuestodet'];
 
        $efectivo = 0;
        $montoefectuado = 0;
        /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'tratamientomedico'.
         */
        $tratamientomedicoInsert = [];
        $tratamientomedicoUpdate = [];
        $tratamientomedicoDelete = [];

        $tratamientocantidadInsert = [];
        $tratamientocantidadInsertAfter = [];

        $dataPresupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
        //return $request['presupuestodet'];

        foreach ($request['presupuestodet'] as $row) {
        //foreach ($paramPresupuestodet as $indice => $row) {//Tratamiento 
            $totalefectivo = round($row['cantcliente'] * $row['preciounitefectivo'], 2);
            $totalefectuado = round($row['cantefectivo'] * $row['preciounitefectivo'], 2);

            $nuevo = true;
            $update = false;
            foreach ($dataPresupuestodet as $indice => $row2) {//Presupuesto
                if ($row['idproducto'] === $row2->idproducto) {
                    $nuevo = false;

                    if($row['cantcliente'] !== $row2->cantcliente || (double) $row['preciounitefectivo'] !== (double) $row2->preciounitefectivo){
                        $update = true;
                    }
 
                    $efectivo = $efectivo + $totalefectivo;
                    $montoefectuado = $montoefectuado + $totalefectuado;

                    unset($dataPresupuestodet[$indice]);
                    break 1;
                }
            }

            $tmp = array(
                'idproducto' => $row['idproducto'],
                'cantmedico' => $row['cantcliente'],
                'cantcliente' => $row['cantcliente'],
                'tipoprecio' => 3
            ); 
            

            $tmp = array(
                'cantmedico' => $row['cantcliente'],
                'cantcliente' => $row['cantcliente'], 
                'preciounitefectivo' => $row['preciounitefectivo'],
                'totalefectivo' => $totalefectivo 
            );

            if ($nuevo) {
                $tmp['idpresupuesto'] = $presupuesto->idpresupuesto;                  
                $tmp['idproducto'] = $row['idproducto']; 
                $tmp['tipoprecio'] = 3; 
                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['id_created_at'] = $this->objTtoken->my;

                $tratamientomedicoInsert[] = $tmp; 
 
                $efectivo = $efectivo + $totalefectivo; 

                $tratamientocantidadInsertAfter[] = array(
                    // 'idpresupuestodet' => $row2->idpresupuestodet, 
                    'idproducto' => $row['idproducto'], 
                    'idpersonal' => $row['idpersonal'], 
                    'cantidad' => $row['cantcliente'], 
                    'fecha' => date('Y-m-d'), 
                    'created_at' => date('Y-m-d H:i:s'), 
                    'id_created_at' => $this->objTtoken->my 
                ); 
            }

            if ($update) {    
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $tratamientomedicoUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['idpresupuestodet' => $row2->idpresupuestodet]
                );  

                $cantidaddiferencia = (integer) $row['cantcliente'] - (integer) $row2->cantcliente;

                if($cantidaddiferencia !== 0) {
                    $tratamientocantidadInsert[] = array(
                        'idpresupuestodet' => $row2->idpresupuestodet,
                        'idproducto' => $row2->idproducto,
                        'idpersonal' => $row['idpersonal'],
                        'cantidad' => $cantidaddiferencia,
                        'fecha' => date('Y-m-d'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my 
                    ); 
                }
            } 
        }

        if (!empty($dataPresupuestodet)) {
            $tmp = array(
                'deleted' => '1',
                'deleted_at' => date('Y-m-d H:i:s'),
                'id_deleted_at' => $this->objTtoken->my
            ); 


            foreach ($dataPresupuestodet as $row) {
                $tratamientomedicoDelete[] = array(
                    'data' => $tmp,
                    'where' => array(
                        'idpresupuestodet' => $row->idpresupuestodet
                    )
                );
            }
        }

        $return = array(
            'tratamientomedicoInsert' => $tratamientomedicoInsert,
            'tratamientomedicoUpdate' => $tratamientomedicoUpdate,
            'tratamientomedicoDelete' => $tratamientomedicoDelete,
            'regular' => 0,
            'tarjeta' => 0,
            'efectivo' => $efectivo,
            'montoefectuado' => $montoefectuado,
            'tratamientocantidadInsert' => $tratamientocantidadInsert, 
            'tratamientocantidadInsertAfter' => $tratamientocantidadInsertAfter 
        );

        return $return;
    }

    private function compararTratamientoUnificadoVSPresupuesto($idcicloatencion, $presupuesto, $request = []) {

        $unificado = $this->obtenerTratamientoUnificado($idcicloatencion, $presupuesto);
              
        $paramPresupuestodet = $unificado['paramPresupuestodet'];
        $regular = 0;
        $tarjeta = 0;
        $efectivo = 0;
        $montoefectuado = 0;
        /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'tratamientomedico'.
         */
        $tratamientomedicoInsert = [];
        $tratamientomedicoUpdate = [];
        $tratamientomedicoDelete = [];

        $dataPresupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

        foreach ($paramPresupuestodet as $indice => $row) { // Tratamiento
            $nuevo = true;
            $update = false;
            foreach ($dataPresupuestodet as $indice => $row2) { // Presupuesto
                if ($row['idproducto'] === $row2->idproducto) {
                    $nuevo = false;
                    $update = true;

                    unset($dataPresupuestodet[$indice]);
                    break 1;
                }
            }

            $tmp = array(
                'idproducto' => $row['idproducto'],
                'cantmedico' => $row['cantmedico'],
                'cantcliente' => $row['cantmedico'],
                'tipoprecio' => $row['tipoprecio']
            );

            if ($nuevo) {
                $tmp['idpresupuesto'] = $presupuesto->idpresupuesto;
                $tmp['cantpagada'] = NULL;
                $tmp['cantefectivo'] = NULL;
                $tmp['preciounitregular'] = $row['preciounitregular'];
                $tmp['totalregular'] = $row['totalregular'];
                $tmp['preciounittarjeta'] = $row['preciounittarjeta'];
                $tmp['totaltarjeta'] = $row['totaltarjeta'];
                $tmp['preciounitefectivo'] = $row['preciounitefectivo'];
                $tmp['totalefectivo'] = $row['totalefectivo'];
                $tmp['observacion'] = $row['observacion'];

                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['id_created_at'] = $this->objTtoken->my;

                $tratamientomedicoInsert[] = $tmp;

                $totalregular = $row['totalregular'];
                $totaltarjeta = $row['totaltarjeta'];
                $totalefectivo = $row['totalefectivo'];
            }

            if ($update) {
                /* Ahora se recibe: cantcliente, idproducto, preciounitregular, preciounittarjeta, preciounitefectivo */
                if (isset($request['presupuestodet'])) {
                    foreach ($request['presupuestodet'] as $fila) {
                        if ($fila['idproducto'] === $row['idproducto']) {
                            // $row2->cantcliente = $fila['cantcliente'];                         
                            // $row2->cantmedico = $fila['cantcliente']; 
                            $row2->preciounitregular = $fila['preciounitregular'];
                            $row2->preciounittarjeta = $fila['preciounittarjeta'];
                            $row2->preciounitefectivo = $fila['preciounitefectivo'];       
                            $row2->observacion = $fila['observacion'];
                            break;
                        }
                    }
                }

                $tmp['cantpagada'] = $row2->cantpagada;
                $tmp['cantefectivo'] = $row2->cantefectivo; 
                $tmp['preciounitregular'] = $row2->preciounitregular; 
                $tmp['totalregular'] = $row2->preciounitregular * $row['cantmedico']; // $row2->totalregular;
                $tmp['preciounittarjeta'] = $row2->preciounittarjeta; 
                $tmp['totaltarjeta'] = $row2->preciounittarjeta * $row['cantmedico']; // $row2->totaltarjeta;
                $tmp['preciounitefectivo'] = $row2->preciounitefectivo; 
                $tmp['totalefectivo'] = $row2->preciounitefectivo * $row['cantmedico'];//$row2->totalefectivo;                
                $tmp['observacion'] = $row2->observacion; 
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $tratamientomedicoUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['idpresupuestodet' => $row2->idpresupuestodet]
                );

                $preciounit = $presupuesto->tipotarifa === 1 ? $row2->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row2->preciounittarjeta : $row2->preciounitefectivo);

                $montoefectuado = $montoefectuado + $preciounit * $row2->cantefectivo;

                $totalregular = $row2->preciounitregular * $row['cantmedico'];
                $totaltarjeta = $row2->preciounittarjeta * $row['cantmedico']; 
                $totalefectivo = $row2->preciounitefectivo * $row['cantmedico'];
            }

            $regular = $regular + $totalregular;
            $tarjeta = $tarjeta + $totaltarjeta;
            $efectivo = $efectivo + $totalefectivo;
        }

        if (!empty($dataPresupuestodet)) {
            $tmp = array();
            $tmp['deleted'] = '1';
            $tmp['deleted_at'] = date('Y-m-d H:i:s');
            $tmp['id_deleted_at'] = $this->objTtoken->my;

            foreach ($dataPresupuestodet as $row) {
                $tratamientomedicoDelete[] = array(
                    'data' => $tmp,
                    'where' => array(
                        'idpresupuestodet' => $row->idpresupuestodet
                    )
                );
            }
        }

        $return = array(
            'tratamientomedicoInsert' => $tratamientomedicoInsert,
            'tratamientomedicoUpdate' => $tratamientomedicoUpdate,
            'tratamientomedicoDelete' => $tratamientomedicoDelete,
            'regular' => $regular,
            'tarjeta' => $tarjeta,
            'efectivo' => $efectivo,
            'montoefectuado' => $montoefectuado,
            'montopago' => 0
        );

        return $return;
    }

    private function obtenerTratamientoUnificado($idcicloatencion, $presupuesto) {
        /* Junta los tratamientos de las N consultas, perteneciente del mismo ciclo de atencion
         * Osea sumas las cantidades indicadas por los medicos. "$citamedica->tratamientomedico()"        
         */

        $citamedica = new citamedica();
        $cicloatencion = new cicloatencion();

        $tratamientos = $citamedica->tratamientomedicoAdicionales($idcicloatencion);
        $autorizaciones = $cicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $idcicloatencion]);

        $regular = 0;
        $tarjeta = 0;
        $efectivo = 0;
        $paramPresupuestodet = [];
        foreach ($tratamientos as $row) {

            //Precio de tarifario, no tiene autorizacion.
            $array['tipo'] = 3;

            if ($row['idtipoproducto'] === 1) {
                //Producto                
                $preciounitregular = $row['valorventa'];
                $totalregular = round($row['valorventa'] * $row['cantidad'], 2);
                $preciounittarjeta = $row['valorventa'];
                $totaltarjeta = round($row['valorventa'] * $row['cantidad'], 2);
                $preciounitefectivo = $row['valorventa'];
                $totalefectivo = round($row['valorventa'] * $row['cantidad'], 2);
            } else {
                //Servicio
                $array = $this->getAutorizacionProducto($autorizaciones, $row['idproducto']);
                switch ($array['tipo']):
                    case 1:
                        //Precio deducible, de autorizacion 'Valida'.
                        $preciounit = 0;
                        if (in_array($row['idproducto'], [2])) {
                            //Fisioterapia
                            $preciounit = $array['coaseguro'];
                        } else {
                            $preciounit = $array['deducible'];
                        }
                        $preciounitregular = $preciounit;
                        $totalregular = round($preciounit * $row['cantidad'], 2);
                        $preciounittarjeta = $preciounit;
                        $totaltarjeta = round($preciounit * $row['cantidad'], 2);
                        $preciounitefectivo = $preciounit;
                        $totalefectivo = round($preciounit * $row['cantidad'], 2);
                        break;
                    case 2:
                        //Caso no se haya definido un precio en el tarifario, entonces punit sera "0".
                        //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.                                
                        $preciounitregular = !empty($row['sscoref']) ? $row['sscoref'] : 0;
                        $totalregular = round($row['sscoref'] * $row['cantidad'], 2);
                        $preciounittarjeta = !empty($row['sscocta']) ? $row['sscocta'] : 0;
                        $totaltarjeta = round($row['sscocta'] * $row['cantidad'], 2);
                        $preciounitefectivo = !empty($row['sscosta']) ? $row['sscosta'] : 0;
                        $totalefectivo = round($row['sscosta'] * $row['cantidad'], 2);
                        break;
                    case 3:
                        //Precio de tarifario, no tiene autorizacion.                               
                        $preciounitregular = !empty($row['partref']) ? $row['partref'] : 0;
                        $totalregular = round($row['partref'] * $row['cantidad'], 2);
                        $preciounittarjeta = !empty($row['partcta']) ? $row['partcta'] : 0;
                        $totaltarjeta = round($row['partcta'] * $row['cantidad'], 2);
                        $preciounitefectivo = !empty($row['partsta']) ? $row['partsta'] : 0;
                        $totalefectivo = round($row['partsta'] * $row['cantidad'], 2);
                        break;
                endswitch;
            }

            $paramPresupuestodet[] = array(
                'idpresupuesto' => $presupuesto->idpresupuesto,
                'idproducto' => $row['idproducto'],
                'cantmedico' => $row['cantidad'],
                'cantcliente' => $row['cantidad'],
                'cantpagada' => NULL,
                'cantefectivo' => NULL,
                'tipoprecio' => $array['tipo'], 
                'preciounitregular' => $preciounitregular,
                'totalregular' => $totalregular,
                'preciounittarjeta' => $preciounittarjeta,
                'totaltarjeta' => $totaltarjeta,
                'preciounitefectivo' => $preciounitefectivo,
                'totalefectivo' => $totalefectivo,
                'observacion' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'id_created_at' => $this->objTtoken->my
            );

            $regular = $regular + $totalregular;
            $tarjeta = $tarjeta + $totaltarjeta;
            $efectivo = $efectivo + $totalefectivo;
        }

        $total = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));

        $paramPresupuesto = array(
            'regular' => $regular,
            'tarjeta' => $tarjeta,
            'efectivo' => $efectivo,
            'montoefectuado' => 0,
            'montopago' => 0, 
            'montocredito' => 0,
            'total' => $total
        );

        return array('paramPresupuesto' => $paramPresupuesto, 'paramPresupuestodet' => $paramPresupuestodet);
    }

    private function getAutorizacionProducto($autorizaciones, $idproducto) {

        //Precio de tarifario, no tiene autorizacion.
        $return = ['tipo' => 3];

        foreach ($autorizaciones as $row) {
            /* Obtiene el "idcicloautorizacion" y "deducible" de la autorizacion 'Principal' o 'Valida'  
             * idproducto: 2: El precio va a ser el deducible de "Fisioterapia"        
             */

            // $row->principal === '1' && 
            if ($row->idproducto === $idproducto) {
                //Precio deducible, de autorizacion 'Valida'.
                $return = array(
                    'tipo' => 1,
                    'idcicloatencion' => $row->idcicloatencion,
                    'idcicloautorizacion' => $row->idcicloautorizacion,
                    'deducible' => $row->deducible,
                    'idcoaseguro' => $row->idcoaseguro,
                    'coaseguro' => $row->coaseguro
                );
                break;
            }

            /* Obtiene el "idcicloautorizacion" de la autorizacion 'No valida' y seguro 'No cubierto'.
             * Al no haber fecha en una autorizacion no valida, entonces toma el ultimo de orden descendente.
             * 
             * && $row->principal === '0' : Solo cuando autorizacion no es valida. Si es no cubierto estara vacio.
             */
            if (in_array($row->idaseguradoraplan, [5, 8, 13, 18, 21])) {
                //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.
                $return = array(
                    'tipo' => 2,
                    'idcicloatencion' => $row->idcicloatencion,
                    'idcicloautorizacion' => $row->idcicloautorizacion
                );
            }
        }

        return $return;
    }

    private function camposAdicionales($data, $idempresa) {

        $tareadet = new tareadet();

        $dataTmp = array();

        $fechaIF = $this->fechaInicioFin(date('d/m/Y'), date('H:i:s'), date('H:i:s'));
        $fechaIF_s_actual = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        // dd($fechaIF, date('d/m/Y H:i:s', $fechaIF_s_actual));

        $whereIn = [];
        foreach($data as $row) {
            $row->tareadet = [];
            $row->cantacciones = null;
            $row->resultado = null;
            $row->respuesta = null;        
            // $row->cantdiastrans = null;
            // $row->cantdiasrest = null;
            $row->cantdiasultc = null;
            $row->agendocita = null;
            $row->agendoasitio = null;
            $whereIn[] = $row->idtarea;
        } 

        $datatmp = [];
        if ($whereIn) {
            $datatmp = $tareadet->grid(array('tareadet.idempresa' => $idempresa), [], $whereIn);
        }

        foreach($data as $row) {  
            foreach($datatmp as $row2) { 
                if ($row2->idtarea === $row->idtarea) {
                    $row->tareadet[] = $row2;                      
                }
            }          
        }

        foreach($data as $row) { 

            if (count($row->tareadet) > 0) {
                $row->cantacciones = count($row->tareadet); 
                if ($row->tareadet[0]->tiporesultado)
                    $row->resultado = $row->tareadet[0]->tiporesultado === '1' ? 'Contestó' : 'No contestó';

                if ($row->tareadet[0]->tiporespuesta)
                    $row->respuesta = $row->tareadet[0]->tiporespuesta === '1' ? 'No va a venir' : ($row->tareadet[0]->tiporespuesta === '2' ? 'Si va a venir' : 'Llamar mas tarde');   

                $datetime1 = date_create($this->formatFecha(substr($row->tareadet[0]->created_at, 0, 10), 'yyyy-mm-dd'));
                $datetime2 = date_create(date('Y-m-d'));
                $interval = $datetime1->diff($datetime2);  
                $dias = $interval->format('%a');

                if($dias > 0)
                    $row->cantdiasultc = (integer) $dias;
            } 

            // $datetime1 = date_create($this->formatFecha(substr($row->created_at, 0, 10), 'yyyy-mm-dd'));
            // $datetime2 = date_create(date('Y-m-d'));
            // $interval = $datetime1->diff($datetime2);  
            // $dias = $interval->format('%a');

            // $row->cantdiastrans = (integer) $dias; 
            // $row->cantdiasrest =  (5 - $dias) >= 0 ? (5 - $dias) : 0; 

            /* Próxima cita mas cercana */
            $fecha = $this->formatFecha(substr($row->created_at, 0, 10), 'yyyy-mm-dd');
            $inicio = substr($row->created_at, 11, 8); 

            $citarow = null;
            if ($row->idcitamedica) {   //4:pendiente, 5:confirmada, 6:atendida
                $citarow = \DB::table('citamedica')  
                            ->select('citamedica.fecha', 'citamedica.inicio', 'citamedica.idestado')                            
                            ->whereRaw("CONCAT(fecha,' ',inicio) > '".$fecha." ".$inicio."'") 
                            ->where(['citamedica.idpaciente' => $row->identidad, 
                                     'citamedica.idsede' => $row->idsede,
                                    ]) 
                            ->whereIn('citamedica.idestado', [4, 5, 6])   
                            ->whereNull('citamedica.deleted')
                            ->first();  
            }

            if ($row->idcitaterapeutica || $row->idcicloatencion) { //32:pendiente, 33:confirmada, 34:atendida, 
                
                $citarow = \DB::table('citaterapeutica') 
                            ->select('citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.idestado')
                            ->whereRaw("CONCAT(fecha,' ',inicio) > '".$fecha." ".$inicio."'") 
                            ->where(['citaterapeutica.idpaciente' => $row->identidad, 
                                     'citaterapeutica.idsede' => $row->idsede,
                                    ]) 
                            ->whereIn('citaterapeutica.idestado', [32, 33, 34])   
                            ->whereNull('citaterapeutica.deleted')
                            ->first();  
            }

            if($citarow) {
                $fecha = $this->formatFecha($citarow->fecha); 
                $inicio = $citarow->inicio;

                if($citarow->idestado === 34) {
                    $agendoasitio = 'Si';
                } else {  
                    $fechaIF = $this->fechaInicioFin($fecha, $inicio, $inicio);
                    $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

                    if ($fechaIF_s_actual > $start_s)
                        $agendoasitio = 'No';
                    else {
                        $agendoasitio = 'Por asistir';
                    }
                } 

                $row->agendocita = $fecha . ' ' .$inicio;
                $row->agendoasitio = $agendoasitio;
            }
        }
        
        return $data;
    }

    public function updatetratamientoextra(Request $request, $enterprise, $id) {

        $objPresupuesto = new presupuesto();
        $empresa = new empresa();

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        //VALIDACIONES 
        /* 1.- Validar que ciclo no se encuentre cerrado. Caso ya tenga un idcicloatencion
         */
        $cicloatencion = cicloatencion::find($id);
        $presupuesto = presupuesto::where('idcicloatencion', '=', $id)->first();
        $autorizaciones = $cicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);

        if (isset($cicloatencion->idestado) && $cicloatencion->idestado === 21) {
            //6.2.2019 Temporal para que pruebe AMAC la nueva cita médica del
            return $this->crearRespuesta('Ciclo de atención se encuentra cerrado. No puede editarse.', [200, 'info']);
        }    

        if (isset($request['tratamientos'])) {
            $datatratamientos = [];
            foreach ($request['tratamientos'] as $row) {            
                $datatratamientos[] = [
                    'idcicloatencion' => $row['idcicloatencion'],
                    'idproducto' => $row['idproducto'], 
                    'identidad' => $row['identidad'],
                    'cantidad' => $row['cantidad'],
                    'idgrupodx' => $row['idgrupodx'],
                    'idempresa' => $idempresa,
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my 
                ];
            }
        }

        if ($cicloatencion) {

            \DB::beginTransaction();
            try {

                \DB::table('ciclotratamiento')->insert($datatratamientos);  
                              
                if ($presupuesto) { 

                    $dataPresupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

                    foreach ($datatratamientos as $row) {

                        $existe = false;

                        foreach ($dataPresupuestodet as $value) {
                            if ($value->idproducto === $row['idproducto']) {
                                $existe = true;
                                // $cant = (integer) $value->cantcliente + (integer) $row['cantidad']; //IMPORTA LA DEL MEDICO 
                                $cant = (integer) $value->cantmedico + (integer) $row['cantidad'];

                                if ($existe) {
                                    // Actualizar presupuestodet 
                                    $totalregular = $value->preciounitregular * $cant;
                                    $totaltarjeta = $value->preciounittarjeta * $cant;
                                    $totalefectivo = $value->preciounitefectivo * $cant;  

                                    \DB::table('presupuestodet') 
                                        ->where('idpresupuestodet', $value->idpresupuestodet)
                                        ->update([
                                            'totalregular' => $totalregular,
                                            'totaltarjeta' => $totaltarjeta,
                                            'totalefectivo' => $totalefectivo,
                                            'cantmedico' => $cant,
                                            'cantcliente' => $cant,
                                            'updated_at' => date('Y-m-d H:i:s'), 
                                            'id_updated_at' => $this->objTtoken->my 
                                        ]);                        
                                }
                                break;
                            }
                        }

                        if (!$existe) {
 
                            $precio = $this->detallePrecio($autorizaciones, $row, $cicloatencion->idsede);
                            
                            \DB::table('presupuestodet')
                                ->insert([
                                    'idpresupuesto' => $presupuesto->idpresupuesto,
                                    'idproducto' => $row['idproducto'],
                                    'cantmedico' => (integer) $row['cantidad'],
                                    'cantcliente' => (integer) $row['cantidad'],
                                    'cantpagada' => 0,
                                    'cantefectivo' => 0,
                                    'tipoprecio' => $precio['tipo'],                                    
                                    'preciounitregular' => $precio['preciounitregular'],
                                    'totalregular' => $precio['totalregular'],
                                    'preciounittarjeta' => $precio['preciounittarjeta'],
                                    'totaltarjeta' => $precio['totaltarjeta'],
                                    'preciounitefectivo' => $precio['preciounitefectivo'],
                                    'totalefectivo' => $precio['totalefectivo'],                                    
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'id_created_at' => $this->objTtoken->my 
                                ]); 
                        }
                    } 
                    
                    $dataTemp = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

                    $regular = 0;
                    $tarjeta = 0;
                    $efectivo = 0;
                    $total = 0;                
                    foreach($dataTemp as $value) {
                        $regular += (float) $value->totalregular;
                        $tarjeta += (float) $value->totaltarjeta;
                        $efectivo += (float) $value->totalefectivo;
                    }

                    // /* Campos auditores */
                    $paramPresupuesto = array();
                    $paramPresupuesto['created_at'] = date('Y-m-d H:i:s');
                    $paramPresupuesto['id_created_at'] = $this->objTtoken->my;
                    // /* Campos auditores */

                    $paramPresupuesto['regular'] = $regular;
                    $paramPresupuesto['tarjeta'] = $tarjeta;
                    $paramPresupuesto['efectivo'] = $efectivo;
                    // $paramPresupuesto['montoefectuado'] = $unificado['montoefectuado'];

                    // //Estos tres se actualizan en NUEVA VENTA           
                    $total = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));

                    $paramPresupuesto['idestadopago'] = $presupuesto->montopago >= $total && $total > 0 ? 68 : ($presupuesto->montopago > 0 && $presupuesto->montopago < $total ? 67 : 66);

                    $paramPresupuesto['total'] = $total;

                    $presupuesto->fill($paramPresupuesto);
                    $presupuesto->save();

                }

                if($idempresa === 1) {
                    //LogPresupuesto  
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Presupuesato ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un ciclo de atención', 404);
    }

    public function deletetratamientoextra(Request $request, $enterprise, $id) {

        $objPresupuesto = new presupuesto();
        $empresa = new empresa();

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        //VALIDACIONES 
        /* 1.- Validar que ciclo no se encuentre cerrado. Caso ya tenga un idcicloatencion
         */
        $cicloatencion = cicloatencion::find($id);
        $presupuesto = presupuesto::where('idcicloatencion', '=', $id)->first(); 

        if (isset($cicloatencion->idestado) && $cicloatencion->idestado === 21) {
            //6.2.2019 Temporal para que pruebe AMAC la nueva cita médica del
            return $this->crearRespuesta('Ciclo de atención se encuentra cerrado. No puede editarse.', [200, 'info']);
        }    

        if (!isset($request['idproducto']) || empty($request['idproducto'])) { 
            return $this->crearRespuesta('Seleccione item a eliminar.', [200, 'info']);
        }

        if (!isset($request['idciclotratamiento']) || empty($request['idciclotratamiento'])) { 
            return $this->crearRespuesta('Seleccione id', [200, 'info']);
        }

        $cicloTratamiento = \DB::table('ciclotratamiento')                
            ->where('idciclotratamiento', $request['idciclotratamiento']) 
            ->whereNull('deleted_at')
            ->first();

        if (empty($cicloTratamiento)) {
            return $this->crearRespuesta('Tratamiento adicional ya fue eliminado.', [200, 'info']);
        } 
        // return $this->crearRespuesta('XD.', [200, 'info'], '', '');

        if ($cicloatencion) {

            \DB::beginTransaction();
            try { 
                              
                if ($presupuesto) { 
                    // return $this->crearRespuesta('XD', [200, 'info'], '', '', $presupuesto); 
                    // Actualizar presupuestodet
                    $presupuestodet = \DB::table('presupuestodet')
                        ->where([
                            'idproducto' => $request['idproducto'], 
                            'idpresupuesto' => $presupuesto->idpresupuesto 
                        ])
                        ->whereNull('deleted')
                        ->first();  

                    $presupuestodet->cantmedico -= $request['cantidad']; 
                    // return $this->crearRespuesta('XDD', [200, 'info'], '', '', $presupuestodet); 
                    $totalregular = $presupuestodet->preciounitregular * $presupuestodet->cantmedico;
                    $totaltarjeta = $presupuestodet->preciounittarjeta * $presupuestodet->cantmedico;
                    $totalefectivo = $presupuestodet->preciounitefectivo * $presupuestodet->cantmedico;
                     
                    $data = [       
                        'cantmedico' => $presupuestodet->cantmedico,
                        'cantcliente' => $presupuestodet->cantmedico,
                        'totalregular' => $totalregular,
                        'totaltarjeta' => $totaltarjeta,
                        'totalefectivo' => $totalefectivo,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    ];

                    \DB::table('presupuestodet')
                        ->where([
                            'idproducto' => $request['idproducto'], 
                            'idpresupuesto' => $presupuesto->idpresupuesto
                        ])
                        ->whereNull('deleted')
                        ->update($data);   

                    // Actualizar tratamientoadicional
                    $data = [
                        'deleted_at' => date('Y-m-d H:i:s'), 
                        'id_deleted_at' => $this->objTtoken->my
                    ];

                    \DB::table('ciclotratamiento')
                        ->where(['idciclotratamiento' => $request['idciclotratamiento']])
                        ->update($data);   

                    // Actualizar presupuesto
                    $dataTemp = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
                    $regular = 0;
                    $tarjeta = 0;
                    $efectivo = 0;
                    $total = 0;                
                    foreach($dataTemp as $value) {
                        $regular += (float) $value->totalregular;
                        $tarjeta += (float) $value->totaltarjeta;
                        $efectivo += (float) $value->totalefectivo;
                    }

                    // /* Campos auditores */
                    $paramPresupuesto = array();
                    $paramPresupuesto['created_at'] = date('Y-m-d H:i:s');
                    $paramPresupuesto['id_created_at'] = $this->objTtoken->my;
                    // /* Campos auditores */

                    $paramPresupuesto['regular'] = $regular;
                    $paramPresupuesto['tarjeta'] = $tarjeta;
                    $paramPresupuesto['efectivo'] = $efectivo;
                    // $paramPresupuesto['montoefectuado'] = $unificado['montoefectuado'];

                    // //Estos tres se actualizan en NUEVA VENTA           
                    $total = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));

                    $paramPresupuesto['idestadopago'] = $presupuesto->montopago >= $total && $total > 0 ? 68 : ($presupuesto->montopago > 0 && $presupuesto->montopago < $total ? 67 : 66);

                    $paramPresupuesto['total'] = $total;

                    $presupuesto->fill($paramPresupuesto);
                    $presupuesto->save(); 
                }

                if($idempresa === 1) {
                    //LogPresupuesto  
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Presupuesato ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un ciclo de atención', 404);
    }

    private function detallePrecio($autorizaciones, $row, $idsede) {
        $objProducto = new Producto();

        $producto = $objProducto->producto($row['idproducto'], $idsede);
        //Precio de tarifario, no tiene autorizacion.
        $array['tipo'] = 3;

        if ($producto->idtipoproducto === 1) {
            //Producto                
            $preciounitregular = $producto->valorventa;
            $totalregular = round($producto->valorventa * $row['cantidad'], 2);
            $preciounittarjeta = $producto->valorventa;
            $totaltarjeta = round($producto->valorventa * $row['cantidad'], 2);
            $preciounitefectivo = $producto->valorventa;
            $totalefectivo = round($producto->valorventa * $row['cantidad'], 2);
        } else {
            //Servicio
            $array = $this->getAutorizacionProducto($autorizaciones, $row['idproducto']);
            switch ($array['tipo']):
                case 1:
                    //Precio deducible, de autorizacion 'Valida'.
                    $preciounit = 0;
                    if (in_array($producto->idproducto, [2])) {
                        //Fisioterapia
                        $preciounit = $array['coaseguro'];
                    } else {
                        $preciounit = $array['deducible'];
                    }
                    $preciounitregular = $preciounit;
                    $totalregular = round($preciounit * $row['cantidad'], 2);
                    $preciounittarjeta = $preciounit;
                    $totaltarjeta = round($preciounit * $row['cantidad'], 2);
                    $preciounitefectivo = $preciounit;
                    $totalefectivo = round($preciounit * $row['cantidad'], 2);
                    break;
                case 2:
                    //Caso no se haya definido un precio en el tarifario, entonces punit sera "0".
                    //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.                                
                    $preciounitregular = !empty($producto->sscoref) ? $producto->sscoref : 0;
                    $totalregular = round($producto->sscoref * $row['cantidad'], 2);
                    $preciounittarjeta = !empty($producto->sscocta) ? $producto->sscocta : 0;
                    $totaltarjeta = round($producto->sscocta * $row['cantidad'], 2);
                    $preciounitefectivo = !empty($producto->sscosta) ? $producto->sscosta : 0;
                    $totalefectivo = round($producto->sscosta * $row['cantidad'], 2);
                    break;
                case 3:
                    //Precio de tarifario, no tiene autorizacion.                               
                    $preciounitregular = !empty($producto->partref) ? $producto->partref : 0;
                    $totalregular = round($producto->partref * $row['cantidad'], 2);
                    $preciounittarjeta = !empty($producto->partcta) ? $producto->partcta : 0;
                    $totaltarjeta = round($producto->partcta * $row['cantidad'], 2);
                    $preciounitefectivo = !empty($producto->partsta) ? $producto->partsta : 0;
                    $totalefectivo = round($producto->partsta * $row['cantidad'], 2);
                    break;
            endswitch;
        }

        return array(
            'tipo' => $array['tipo'],
            'preciounitregular' => $preciounitregular,
            'totalregular' => $totalregular,
            'preciounittarjeta' => $preciounittarjeta,
            'totaltarjeta' => $totaltarjeta,
            'preciounitefectivo' => $preciounitefectivo,
            'totalefectivo' => $totalefectivo
        );
    }

}
