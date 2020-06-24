<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\llamada; 
use \Firebase\JWT\JWT;
use SoapClient;

class llamadaController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario 
         * Obtiene dias laborales, hora inicio de labores, hora inicio de refrigerio,
         * tiempo de cita medica, tiempo de terapia.
         */
        $request = $request->all();
        
        $empresa = new empresa(); 
                
        $idempresa = $empresa->idempresa($enterprise);   
        
        $data = array();
        if(isset($request['get']) && !empty($request['get'])){
            switch ($request['get']):
                case 'grupostimbrado':
                    $data = array('grupostimbrado' => $empresa->listaGrupotimbrado($idempresa));
                    break; 
                case 'week':
                    $data = array('week' => $empresa->semanasAno($request['ano']));
                    break;
            endswitch;
        }        

        return $this->crearRespuesta($data, 200);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $llamada = new llamada();

        $param = array();
        $param['llamada.idempresa'] = $empresa->idempresa($enterprise);

        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'llamada.fecharegistro';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;

        $between = array();

        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        
        $data = $llamada->grid($param, $between, $like, $pageSize, $orderName, $orderSort);
        
        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total());
        }

        return $this->crearRespuestaError('Llamada no encontrado', 404);
    }
    
    public function anexos(Request $request, $enterprise) {
                
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise);

        $data = $empresa->anexos(['anexo.idempresa'=> $idempresa]);
                
        $param = array(
            'idempresa' => $idempresa 
        ); 

        $listcombox = array(
            'callcenter' => $empresa->configcallcenter($param)
        );
        
        return $this->crearRespuesta($data, 200, '', '', $listcombox);
        
    }
    
    public function reporte1(Request $request, $enterprise) {
        
        $empresa = new empresa();        
        $llamada = new llamada();
       
        $request = $request->all();         
        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = array('llamada.idempresa' => $idempresa);
        
        if (isset($request['ano']) && !empty($request['ano'])){
            $param['llamadadet.ano'] = $request['ano'];
        }
        
        if (isset($request['idgrupotimbrado']) && !empty($request['idgrupotimbrado'])){
            $param['llamadadet.idgrupotimbrado'] = $request['idgrupotimbrado'];
        }
        
        if (isset($request['tipo']) && !empty($request['tipo'])){
            $param['llamadadet.tipo'] = $request['tipo'];
        }
        
        $between = array();
        if (isset($request['desde']) && isset($request['hasta'])) {
            if (!empty($request['desde']) && !empty($request['hasta'])) {
                
                if($request['tiempo'] === 'day' || $request['tiempo'] === 'hour'){ 
                    $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
                    $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');
                }
                
                $between = [$request['desde'], $request['hasta']];
            }
        }
        
        switch ($request['tiempo']) {
            case 'hour': 
            case 'day': 
                $fieldbetween = 'llamadadet.fecha';    
                break;
            case 'week': 
                $fieldbetween = 'llamadadet.semana';
                break;
            case 'month': 
                $fieldbetween = 'llamadadet.mes';
                break; 
        } 
        
        $anexos = $empresa->anexos(['anexo.idempresa'=> $idempresa, 'anexo.activo' => '1']);
        $whereInAnexo = [];
        foreach($anexos as $row){
            $whereInAnexo[] = $row->idanexo;
        } 
        
        $callcenter = $empresa->callcenter(['callcenter.idempresa' => $idempresa]); 
        $betweenHour = []; 
        if (!empty($callcenter->inicio) && !empty($callcenter->fin)) { 
            $betweenHour = [$callcenter->inicio, $callcenter->fin];
        } 
        
        $fields = ['llamadadet.hora', 'llamadadet.fecha', 'llamadadet.semana', 'llamadadet.mes', 'llamadadet.estado'];
        $data = $llamada->llamadadet($param, $between, $fields, $fieldbetween, $betweenHour, $whereInAnexo);         
        
        $matriz = [];
        foreach ($data as $row){
            switch ($request['tiempo']) {
                case 'hour': 
                    $Indicador = substr($row->hora, 0, 2).' hrs';
                    $agrupador = substr($row->hora, 0, 2);
                    break;
                case 'day': 
                    $Indicador = $this->formatFecha($row->fecha); 
                    $agrupador = $row->fecha;
                    break;
                case 'week': 
                    $Indicador = $row->semana;
                    $agrupador = $row->semana;
                    break;
                case 'month': 
                    $Indicador = $row->mes;
                    $agrupador = $row->mes;
                    break; 
            }            
            if(!isset($matriz[$agrupador])){                
                $matriz[$agrupador]['Indicador'] = $Indicador;
                $matriz[$agrupador]['Contestada'] = 0;
                $matriz[$agrupador]['Perdida'] = 0;
            }
            if($row->estado === 'Contestada'){
                $matriz[$agrupador]['Contestada'] = $matriz[$agrupador]['Contestada'] + 1;
            }
            if($row->estado === 'Perdida'){
                $matriz[$agrupador]['Perdida'] = $matriz[$agrupador]['Perdida'] + 1;
            }            
            $matriz[$agrupador]['Total'] = $matriz[$agrupador]['Contestada'] + $matriz[$agrupador]['Perdida'];
        }
        
        if($request['tiempo'] === 'hour')
            ksort($matriz);
        
        $data = array();
        foreach($matriz as $row){
            $data[] = $row;
        } 
        
        return $this->crearRespuesta($data, 200);  
    }
                
    public function dashboard(Request $request, $enterprise) {
        
        $empresa = new empresa();        
        $llamada = new llamada();
       
        $request = $request->all();         
        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = [];
        $param['llamada.idempresa'] = $idempresa; 
        $param['llamadadet.tipo'] = 'entrante';
        
        //Obtencion de rango de tiempo
        if(isset($request['year']) && !empty($request['year']) && isset($request['week']) && !empty($request['week']))
            $semana = $empresa->semanasAno($request['year'], $request['week']);
        else
            $semana = $empresa->semanasAno(date('Y'), date('W'));
                
        if(empty($semana))
            $semana = $empresa->semanasAno($request['year'] + 1, 1);        
                
        $request['desde'] = $semana['inicio'];
        $request['hasta'] = $semana['fin']; 
        
        $fecha = $this->formatFecha($semana['inicio'], 'yyyy-mm-dd');
        
        $tiempo = array();   
        $tiempo[] = date ( 'd/m/Y' , strtotime ( '+0 day' , strtotime ( $fecha ) ) );
        $tiempo[] = date ( 'd/m/Y' , strtotime ( '+1 day' , strtotime ( $fecha ) ) );
        $tiempo[] = date ( 'd/m/Y' , strtotime ( '+2 day' , strtotime ( $fecha ) ) );
        $tiempo[] = date ( 'd/m/Y' , strtotime ( '+3 day' , strtotime ( $fecha ) ) );
        $tiempo[] = date ( 'd/m/Y' , strtotime ( '+4 day' , strtotime ( $fecha ) ) );
        $tiempo[] = date ( 'd/m/Y' , strtotime ( '+5 day' , strtotime ( $fecha ) ) );
        $request['hasta'] = date ( 'd/m/Y' , strtotime ( '+5 day' , strtotime ( $fecha ) ) );
        // 
        
        $between = array();
        if (isset($request['desde']) && isset($request['hasta'])) {
            if (!empty($request['desde']) && !empty($request['hasta'])) {
                $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
                $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');  
                $between = [$request['desde'], $request['hasta']];
            }
        }
        
        $fieldbetween = 'llamadadet.fecha'; 
        
        $anexos = $empresa->anexos(['anexo.idempresa'=> $idempresa, 'anexo.activo' => '1']);
        $whereInAnexo = [];
        foreach($anexos as $row){
            $whereInAnexo[] = $row->idanexo;
        } 
        
        $callcenter = $empresa->callcenter(['callcenter.idempresa' => $idempresa]); 
        $betweenHour = []; 
        if (!empty($callcenter->inicio) && !empty($callcenter->fin)) { 
            $betweenHour = [$callcenter->inicio, $callcenter->fin];
        }  
        
        if(isset($request['turno']) && !empty($request['turno'])){
            if( $request['turno'] === '07:00:00'){                    
                $betweenHour[1] = '15:59:00';
            }
            if( $request['turno'] === '16:00:00'){                    
                $betweenHour[0] = '16:00:00';
            }
        }
        
        $fields = ['llamadadet.fecha', 'llamadadet.estado', 'llamadadet.idanexo'];
                 
        
        $matrizEstado = []; 
        $totalEstado = 0;
        $dataEstado = $llamada->llamadadet($param, $between, $fields, $fieldbetween, $betweenHour, $whereInAnexo);
        foreach ($dataEstado as $row){
            
            $Indicador = $this->formatFecha($row->fecha);
                
                
            
            if(!isset($matrizEstado[$row->estado])){                
                $matrizEstado[$row->estado]['Indicador'] = $row->estado;                
                foreach($tiempo as $time){
                    $matrizEstado[$row->estado][$time] = 0;
                }
                $matrizEstado[$row->estado]['Total'] = 0;
                $matrizEstado[$row->estado]['Porcentaje'] = 0;
            }
                
            $matrizEstado[$row->estado][$Indicador] = $matrizEstado[$row->estado][$Indicador] + 1;
            $matrizEstado[$row->estado]['Total'] = $matrizEstado[$row->estado]['Total'] + 1; 
            $totalEstado = $totalEstado + 1; 
        } 
        
        
        $matrizAnexo = [];
        $totalAnexo = 0;
        $param['estado'] = 'Contestada'; 
        $dataAnexo = $llamada->llamadadet($param, $between, $fields, $fieldbetween, $betweenHour, $whereInAnexo);
        foreach ($dataAnexo as $row){
            
            $Indicador = $this->formatFecha($row->fecha);
            
            foreach($anexos as $anexo){
                if($anexo->idanexo === $row->idanexo){
                    $row->idanexo = $anexo->nombre;
                    break;
                }
            }
            
            if(!isset($matrizAnexo[$row->idanexo])){
                $matrizAnexo[$row->idanexo]['Indicador'] = $row->idanexo;                
                foreach($tiempo as $time){
                    $matrizAnexo[$row->idanexo][$time] = 0;
                }
                $matrizAnexo[$row->idanexo]['Total'] = 0;
                $matrizAnexo[$row->idanexo]['Porcentaje'] = 0;
            } 
            
            $matrizAnexo[$row->idanexo][$Indicador] = $matrizAnexo[$row->idanexo][$Indicador] + 1;
            $matrizAnexo[$row->idanexo]['Total'] = $matrizAnexo[$row->idanexo]['Total'] + 1;        
            $totalAnexo = $totalAnexo + 1; 
        } 
        
        foreach ($matrizEstado as $pk => $row){
            $matrizEstado[$pk]['Porcentaje'] = round($row['Total'] / $totalEstado * 100);
        }
        
        foreach ($matrizAnexo as $pk => $row){            
            $cadena = '';
            foreach($anexos as $anexo){
                if($anexo->nombre === $pk)
                    $cadena = $cadena . (strlen($cadena)?', ':'').$anexo->clave;                
            }
            
            $matrizAnexo[$pk]['Anexos'] = $cadena; 
            $matrizAnexo[$pk]['Porcentaje'] = round($row['Total'] / $totalAnexo * 100);
            
        } 
        //Para salvar ECHART completar con cero
        foreach($anexos as $row){
            $existe = false;
            foreach($matrizAnexo as $idanexo => $row2){
                if($row->nombre === (string)$idanexo){
                    $existe = true;
                }
            }
            if(!$existe){
                $matrizAnexo[$row->nombre]['Indicador'] = $row->nombre;                
                foreach($tiempo as $time){
                    $matrizAnexo[$row->nombre][$time] = 0;
                }
                
                $cadena = '';
                foreach($anexos as $pk => $anexo){
                    if($anexo->nombre === $pk)
                        $cadena = $cadena . (strlen($cadena)?', ':'').$anexo->clave;                
                }
                
                $matrizAnexo[$row->nombre]['Total'] = 0;
                $matrizAnexo[$row->nombre]['Anexos'] = $cadena;
                $matrizAnexo[$row->nombre]['Porcentaje'] = 0;
            }
        }
        //
        
        //dd($matriz);
        $dataAnexo = array();
        $dataEstado = array();
        foreach($matrizAnexo as $row){
            $dataAnexo[] = $row; 
        } 
        foreach($matrizEstado as $row){ 
            $dataEstado[] = $row;
        } 
                
        $dataEstado = $this->ordenarMultidimension($dataEstado, 'Total', SORT_DESC); 
        $dataAnexo = $this->ordenarMultidimension($dataAnexo, 'Total', SORT_DESC);
                        
        return $this->crearRespuesta(['dataanexo' => $dataAnexo, 'dataestado' => $dataEstado, 'week' => $semana, 'day' => date('d/m/Y')], 200);  
    }
    
    public function reporte2(Request $request, $enterprise) {
        
        $empresa = new empresa();        
        $llamada = new llamada();
       
        $request = $request->all();         
        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = array('llamada.idempresa' => $idempresa);
        
        if (isset($request['ano']) && !empty($request['ano'])){
            $param['llamadadet.ano'] = $request['ano'];
        }
        
        if (isset($request['idgrupotimbrado']) && !empty($request['idgrupotimbrado'])){
            $param['llamadadet.idgrupotimbrado'] = $request['idgrupotimbrado'];
        }
        
        if (isset($request['tipo']) && !empty($request['tipo'])){
            $param['llamadadet.tipo'] = $request['tipo'];
        }
        
        if (isset($request['estado']) && !empty($request['estado'])){
            $param['llamadadet.estado'] = $request['estado'];
        }
        
        $between = array();
        if (isset($request['desde']) && isset($request['hasta'])) {
            if (!empty($request['desde']) && !empty($request['hasta'])) {
                
                if($request['tiempo'] === 'day' || $request['tiempo'] === 'hour'){ 
                    $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
                    $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');
                }                
                $between = [$request['desde'], $request['hasta']];
            }
        }
        
        switch ($request['tiempo']) {
            case 'hour':
            case 'day': 
                $fieldbetween = 'llamadadet.fecha';    
                break;
            case 'week': 
                $fieldbetween = 'llamadadet.semana';
                break;
            case 'month': 
                $fieldbetween = 'llamadadet.mes';
                break; 
        } 
        //dd($fieldbetween);
        $fields = ['llamadadet.hora', 'llamadadet.fecha', 'llamadadet.semana', 'llamadadet.mes', 'llamadadet.estado', 'anexo.idanexo'];
                
        $anexos = $empresa->anexos(['anexo.idempresa'=> $idempresa, 'anexo.activo' => '1']);
        $whereInAnexo = [];
        foreach($anexos as $row){
            $whereInAnexo[] = $row->idanexo;
        } 
        
        $callcenter = $empresa->callcenter(['callcenter.idempresa' => $idempresa]); 
        $betweenHour = []; 
        if (!empty($callcenter->inicio) && !empty($callcenter->fin)) { 
            $betweenHour = [$callcenter->inicio, $callcenter->fin];
        } 
        
        $data = $llamada->llamadadet($param, $between, $fields, $fieldbetween, $betweenHour, $whereInAnexo);             
        
        $matriz = [];
        $tiempo = [];
        foreach ($data as $row){ 
            switch ($request['tiempo']) {
                case 'hour': 
                    $Indicador = substr($row->hora, 0, 2).' hrs'; 
                    break;
                case 'day': 
                    $Indicador = $this->formatFecha($row->fecha);  
                    break;
                case 'week': 
                    $Indicador = $row->semana; 
                    break;
                case 'month': 
                    $Indicador = $row->mes; 
                    break; 
            }
            
            if(!in_array($Indicador, $tiempo) ){
                $tiempo[] = $Indicador;
            }
        } 
        
        if($request['tiempo'] === 'hour'){
            asort($tiempo);
        }
        
        foreach ($data as $row){
            foreach($anexos as $anexo){
                if($anexo->idanexo === $row->idanexo){
                    $row->idanexo = $anexo->nombre;
                    break;
                }
            }
            
            switch ($request['tiempo']) {
                case 'hour': 
                    $Indicador = substr($row->hora, 0, 2).' hrs'; 
                    break;
                case 'day': 
                    $Indicador = $this->formatFecha($row->fecha); 
                    break;
                case 'week': 
                    $Indicador = $row->semana; 
                    break;
                case 'month': 
                    $Indicador = $row->mes; 
                    break; 
            }
                
            if(!isset($matriz[$row->idanexo])){
                $matriz[$row->idanexo]['Indicador'] = $row->idanexo;                
                foreach($tiempo as $time){
                    $matriz[$row->idanexo][$time] = 0;
                }
                $matriz[$row->idanexo]['Total'] = 0;
            }
            //dd($row->idanexo);
            $matriz[$row->idanexo][$Indicador] = $matriz[$row->idanexo][$Indicador] + 1;
            $matriz[$row->idanexo]['Total'] = $matriz[$row->idanexo]['Total'] + 1;
        }
                
        //Para salvar ECHART completar con cero
        foreach($anexos as $row){
            $existe = false;
            foreach($matriz as $idanexo => $row2){
                if($row->nombre === (string)$idanexo){
                    $existe = true;
                }
            }
            if(!$existe){
                $matriz[$row->nombre]['Indicador'] = $row->nombre;                
                foreach($tiempo as $time){
                    $matriz[$row->nombre][$time] = 0;
                }
                $matriz[$row->nombre]['Total'] = 0;
            }
        }
        //
        
        $data = array();
        foreach($matriz as $row){
            $data[] = $row;
        } 
        //dd($data);
        return $this->crearRespuesta($data, 200);  
    }
    
    public function enviarsms(Request $request, $enterprise) {

        $request = $request->all();

        try { 
            //Envio de SMS 
            $soapclient = new SoapClient("http://servicio.smsmasivos.com.ar/ws/SMSMasivosAPI.asmx?WSDL");
        
            $respuestasms = $soapclient->EnviarSMS(array( 
                'usuario' => 'MOUTEC', 
                'clave' => 'MOUTEC243', 
                'numero' => $request['numero'], 
                'texto' => $request['mensaje'], 
                'test' => '0', 
                'api' => '1' 
            ));  
        } catch (SoapFault $exception) {
            
        }

        return $this->crearRespuesta('SMS a sido enviado.'.$respuestasms->EnviarSMSResult, 201); 
    }

    public function storeanexos(Request $request, $enterprise) {
        
        $request = $request->all();
        
        if (empty($request['anexo'])) {
            return $this->crearRespuesta('No hay datos por guardar.', [200, 'info']);
        }
        
        \DB::beginTransaction();
        try {
            foreach ($request['anexo'] as $row) { 
                \DB::table('anexo')->where('idanexo', $row['idanexo'])->update(['nombre' => $row['nombre'], 'activo' => $row['activo']]);
            }
            
            if(isset($request['callcenter']['idcallcenter'])){
                \DB::table('callcenter')
                    ->where('idcallcenter', $request['callcenter']['idcallcenter'])
                    ->update(['inicio' => $request['callcenter']['inicio'], 'fin' => $request['callcenter']['fin']]);
            }            
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();
        
        return $this->crearRespuesta('Configuraci&oacute;n guardada', 201);
    } 
    
    public function store(Request $request, $enterprise) { 
        
        $empresa = new empresa(); 
                
        $idempresa = $empresa->idempresa($enterprise);   
        $request = $request->all();
        
        $param = array();
        $param['idempresa'] = $idempresa;
        $param['identidad'] = $this->objTtoken->my;
        $param['items'] = 0; 
        $param['fecharegistro'] = date('Y-m-d');
        $param['horaregistro'] = date('H:i:s'); 
        /*Campos auditores*/
        $param['created_at'] = date('Y-m-d H:i:s');  
        $param['id_created_at'] = $this->objTtoken->my; 
        
        //GuposTimbrados
        $grupostimbrado = $empresa->listaGrupotimbrado($idempresa);
        $Grupotimbrado = array();
        foreach($grupostimbrado as $row){
            $Grupotimbrado[$row->clave] = $row->idgrupotimbrado;
        }   
        
        //Anexos
        $anexos = $empresa->anexos(['anexo.idempresa'=> $idempresa]);
        $Anexo = array();
        foreach($anexos as $row){
            $Anexo[$row->clave] = $row->idanexo;
        }    
        
        $InTimbrado = array();
        $InAnexo = array();
        foreach ($request['llamadadet'] as $row) {
            if ( !empty($row['Grupo_Timbrado']) && !in_array($row['Grupo_Timbrado'], $InTimbrado) && !isset($Grupotimbrado[$row['Grupo_Timbrado']])) {
                $InTimbrado[] = $row['Grupo_Timbrado'];
            }

            if ( !empty($row['Anexo']) && !in_array($row['Anexo'], $InAnexo) && !isset($Anexo[$row['Anexo']])) {
                $InAnexo[] = $row['Anexo'];
            }
        }
        
        $mensaje = '';
        
        \DB::beginTransaction();
        try {         
            //1.- Insercion de Gruposdetimbrado y Anexos 
            if(count($InTimbrado) > 0){
                foreach ($InTimbrado as $row) {
                    \DB::table('grupotimbrado')->insert(array('idempresa'=> $idempresa, 'clave'=> $row,'nombre'=> $row));
                }
                //GuposTimbrados
                $grupostimbrado = $empresa->listaGrupotimbrado($idempresa);
                $Grupotimbrado = array();
                foreach($grupostimbrado as $row){
                    $Grupotimbrado[$row->clave] = $row->idgrupotimbrado;
                }
            }
            
            if(count($InAnexo) > 0){
                foreach ($InAnexo as $row) {
                    \DB::table('anexo')->insert(array('idempresa'=> $idempresa, 'clave'=> $row,'nombre'=> $row));
                }
                //Anexos
                $anexos = $empresa->anexos(['anexo.idempresa'=> $idempresa]);
                $Anexo = array();
                foreach($anexos as $row){
                    $Anexo[$row->clave] = $row->idanexo;
                } 
            }
            
            
            //2.- Insercion de Llamada y Llamadadet 
            $llamada = llamada::create($param);            
            
            $dataLlamadadet = array();            
            $dataRepetidos = array(); 
            $dataDuplicados = array(); 
            $claveIn = array();

            foreach ($request['llamadadet'] as $row) {
                //Importante: formato(yyy-mm-dd).
                $fechahora = explode(" ", $row['Fecha']);
                $fecha = $fechahora[0]; 
                $hora = $fechahora[1];
                
                $idgrupotimbrado = null;
                if(isset($Grupotimbrado[$row['Grupo_Timbrado']])){
                    $idgrupotimbrado = $Grupotimbrado[$row['Grupo_Timbrado']];
                }
                
                $idanexo = null;
                if(isset($Anexo[$row['Anexo']])){
                    $idanexo = $Anexo[$row['Anexo']];
                } 
                
                $clave = $fecha.$hora.$row['Origen'].$idanexo;
                

                $ymd =  explode('-', $fecha);
                $semana = date('W', mktime(0,0,0,(int)$ymd[1],(int)$ymd[2],(int)$ymd[0]));
                

                if (!in_array($clave, $claveIn)) {
                    $item = array(
                        'clave' => $clave,
                        'idllamada' => $llamada->idllamada,
                        'fechahora' => $fecha.' '.$hora, 
                        'fecha' => $fecha, 
                        'hora' => $hora, 
                        'ano' => (int)$ymd[0], 
                        'mes' => (int)$ymd[1], 
                        'semana' => $semana,  
                        'tipo' => $row['Tipo'], 
                        'origen' => $row['Origen'], 
                        'idgrupotimbrado' => $idgrupotimbrado, 
                        'destino' => $row['Destino'], 
                        'idanexo' => $idanexo, 
                        'desvio' => $row['Desvio'], 
                        'estado' => $row['Estado'], 
                        'duracion' => $row['Duraci_n'], 
                        'costominuto' => $row['Costo_Minuto'], 
                        'costobolsa' => $row['Costo_Bolsa'] === '-' ? 0 : $row['Costo_Bolsa'], 
                        'costototal' => $row['Costo_Total'] 
                    );
                    
                    $llamadadet = \DB::table('llamadadet')->select('idllamadadet')->where('clave', $clave)->first();
                    if(empty($llamadadet)){ 
                        $dataLlamadadet[] = $item;
                    }else{
                        $dataRepetidos[] = array('fechahora' => $fecha.' '.$hora, 'origen' => $row['Origen'], 'anexo' => $row['Anexo']);
                    }

                    $claveIn[] = $clave;
                } else {
                    $dataDuplicados[] = array('fechahora' => $fecha.' '.$hora, 'origen' => $row['Origen'], 'anexo' => $row['Anexo']);; 
                } 
            }
            
            $cargadas = count($dataLlamadadet);
            $repetidos = count($dataRepetidos);
            $duplicados = count($dataDuplicados);
            // dd($cargadas, $repetidos);
            
            // return $this->crearRespuesta('XD', [200, 'info'], '', '', [$cargadas, $repetidos, $duplicados]);

            if ($cargadas === 0) { 
                \DB::rollback();
                return $this->crearRespuesta('Carga no se realiz&oacute;. Los "'.$repetidos.'" items ya existen. "'.$duplicados. '" items duplicados.', [200, 'info']);
            } else {
                if ($repetidos > 0) {
                    $mensaje = 'Carga exitosa. "'.$cargadas.'" items cargados. "'.$repetidos.'" items ya existen. "'.$duplicados. '" items duplicados.';
                } else {
                    $mensaje = 'Carga exitosa. "'.$cargadas.'" items cargados. "'.$duplicados. '" items duplicados.';
                }
                
                foreach ($dataLlamadadet as $row) {
                    \DB::table('llamadadet')->insert($row);
                }
                
                $llamada->fill(array('items' => $cargadas));
                $llamada->save();
            }
            
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();
        
        return $this->crearRespuesta($mensaje, 201,'','',$dataRepetidos);
    }
    
    public function show($enterprise, $id) {
 
        $llamada = llamada::find($id); 
        
        if ($llamada) {                                   
            $listcombox = array( 
                'llamadadet' => $llamada->llamadadet(['llamadadet.idllamada' => $id])
            );

            return $this->crearRespuesta($llamada, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Llamada no encotrado', 404);
    }    
    
}
