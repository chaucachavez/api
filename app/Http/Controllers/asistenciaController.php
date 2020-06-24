<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\asistencia;
use App\Exports\DataExport;
use Illuminate\Http\Request;


class asistenciaController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) {
       
        $sede = new sede(); 
        $empresa = new empresa();
        $entidad = new entidad();
        
        $idempresa = $empresa->idempresa($enterprise); 
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );
        
        $param2 = array(
            'entidad.idempresa' => $idempresa, 
            'entidad.tipopersonal' => '1'
        );

        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
            'personal' => $entidad->entidades($param2, true)
        ); 
        
        return $this->crearRespuesta($data, 200);        
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $asistencia = new asistencia();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        $param['asistencia.idempresa'] = $idempresa;

        if (isset($paramsTMP['identidad']) && !empty($paramsTMP['identidad'])) {
            $param['asistencia.identidad'] = $paramsTMP['identidad'];
        }

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['asistencia.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {            
            $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
            $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
            $between = [$paramsTMP['desde'], $paramsTMP['hasta']]; 
        }
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'asistencia.laborfechainicio';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $data = $asistencia->grid($param, $between, $like, $pageSize, $orderName, $orderSort); 

        $total = '';
        if (isset($request['pageSize']) && !empty($request['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        if (isset($request['formato']) && !empty($request['formato'])) {
            if(in_array($request['formato'], ['xls', 'xlsx'])){ 

                $dataexcel = array();   
                $i = 0; 
                foreach($data as $row){  

                    switch ($row->tipo) {
                        case '1':
                            $tipo = 'Asistió';
                            break; 
                        case '0':
                            $tipo = 'Falta';
                            break; 
                        case '2':
                            $tipo = 'Feriado';
                            break; 
                        default:
                            $tipo = '';
                            break;
                    }

                    $laborfechainicio = $this->formatFecha($row->laborfechainicio, 'yyyy-mm-dd');

                    $dataexcel[$i] = array(
                        'SEDE' => $row->nombresede, 
                        'PERSONAL' => $row->personal,
                        'TURNO' => $row->nombre,
                        'FECHA LABORAL' => $row->laborfechainicio,
                        'DIA' => $this->_data_dayweek_month_day($laborfechainicio, true),
                        'INICIO LABORAL' => $row->laborinicio,
                        'FIN LABORAL' => $row->laborfin,
                        'TIEMPO LABORAL' => $row->tiempoprogramado,
                        'TIPO' => $tipo,
                        'INGRESO' => $row->horai,
                        'SALIDA' => $row->horao,                        
                        'TIEMPO TRABAJADO' => $row->tiempo,
                        'TIEMPO TARDANZA' => $row->tiempotardanza,
                        'TIEMPO EXTRA' => $row->tiempoextra,
                        'TARDANZA' => $row->tardanza === '1'?'Justificado':($row->tardanza === '2'?'Injustificado':''),
                        'SANCION' => $row->sancion === '1'?'Descuento':($row->sancion === '2'?'Sin descuento':''),
                        'PLAN 60' => $row->plan60 === '1'?'Aplica':($row->plan60 === '2'?'No aplica':''),
                        'ADJUNTO' => $row->adjunto,
                        'MOTIVO FALTA' => $row->nombremotivo,
                        'OBSERVACION' => $row->observacion,
                        'ACTUALIZADO' => $row->updated,
                    );
                    $i++;
                } 

                return Excel::download(new DataExport($dataexcel), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200, $total); 
        }  
         
    }

    public function consolidado(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $asistencia = new asistencia();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        $param['asistencia.idempresa'] = $idempresa;

        if (isset($paramsTMP['identidad']) && !empty($paramsTMP['identidad'])) {
            $param['asistencia.identidad'] = $paramsTMP['identidad'];
        }

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['asistencia.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {            
            $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
            $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
            $between = [$paramsTMP['desde'], $paramsTMP['hasta']]; 
        }
        
        $pageSize = '';
        $orderName = 'asistencia.laborfechainicio';
        $orderSort = 'asc'; 
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $data = $asistencia->grid($param, $between, $like, $pageSize, $orderName, $orderSort); 

        $personasIds = [];
        $personasData = [];
        foreach ($data as $row) { 
            if  (!in_array($row->identidad, $personasIds)){                
                $personasIds[] = $row->identidad;
                $personasData[] = array('identidad' => $row->identidad, 'ENTIDAD' => $row->personal);
            }

            if (isset($request['validacion']) && $request['validacion'] === '1' && !in_array($row->tipo, ['0', '1', '2'])) { 
                return $this->crearRespuesta($row->personal . ' no tiene asistencia el ' . $row->laborfechainicio. '. Ingresar', [200, 'info']); 
            }
        }

        if (isset($request['validacion']) && $request['validacion'] === '1' && empty($data)) {
            return $this->crearRespuesta('No tiene horario laboral', [200, 'info']); 
        }

        foreach ($data as $row) { 
            foreach ($personasData as $i => $persona) {  
                if($persona['identidad'] === $row->identidad) {
                    $personasData[$i]['data'][] = $row;
                }
            }
        } 

        foreach ($personasData as $i => $row) { 

            $length = count($row['data']);
            $diaslaborales = [];
            $tiempolaboral = '';
            $tiempotrabajado = '';
            $tiempotardanza = '';
            $tiempoextra = '';
            $diasasistidos = 0;            
            $diasferiado = 0;
            $diasfalta = 0;

            foreach ($row['data'] as $fila) {
                if (!in_array($fila->laborfechainicio, $diaslaborales)){
                    $diaslaborales[] = $fila->laborfechainicio;
                }

                if (!empty($tiempolaboral)) { 
                    if ($fila->tiempoprogramado)
                        $tiempolaboral = $this->sumahoras($tiempolaboral, $fila->tiempoprogramado);
                } else { 
                    $tiempolaboral = $fila->tiempoprogramado;
                }

                if (!empty($tiempotrabajado)) { 
                    if ($fila->tiempo)
                        $tiempotrabajado = $this->sumahoras($tiempotrabajado, $fila->tiempo);
                } else { 
                    $tiempotrabajado = $fila->tiempo;
                }

                if (!empty($tiempotardanza)) { 
                    if ($fila->tiempotardanza)
                        $tiempotardanza = $this->sumahoras($tiempotardanza, $fila->tiempotardanza);
                } else { 
                    $tiempotardanza = $fila->tiempotardanza;
                }

                if (!empty($tiempoextra)) { 
                    if ($fila->tiempoextra)
                        $tiempoextra = $this->sumahoras($tiempoextra, $fila->tiempoextra);
                } else { 
                    $tiempoextra = $fila->tiempoextra;
                }

                switch ($fila->tipo) {
                    case '0': // Falto
                        $diasfalta += 1;
                        break; 
                    case '1': // Asistió
                        $diasasistidos += 1;
                        break; 
                    case '2': // Feriado
                        $diasferiado += 1;
                        break; 
                }
            }   

            $personasData[$i]['FECHA LABORAL'] = $row['data'][0]->laborfechainicio . ' al ' . $row['data'][($length-1)]->laborfechainicio;
            $personasData[$i]['DIAS LABORALES'] = count($diaslaborales);
            $personasData[$i]['TIEMPO LABORAL'] = $tiempolaboral;
            $personasData[$i]['DIAS ASISTIDOS'] = $diasasistidos;
            $personasData[$i]['DIAS FERIADOS'] = $diasferiado;
            $personasData[$i]['DIAS FALTA'] = $diasfalta;
            $personasData[$i]['TIEMPO TRABAJADO'] = $tiempotrabajado;
            $personasData[$i]['TIEMPO TARDANZA'] = $tiempotardanza;
            $personasData[$i]['TIEMPO EXTRA'] = $tiempoextra;
        }
 
        foreach ($personasData as $key => $value) {
            unset($personasData[$key]['data']);
        } 

        $total = '';
        if (isset($request['pageSize']) && !empty($request['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        if (isset($request['formato']) && !empty($request['formato'])) {
            if(in_array($request['formato'], ['xls', 'xlsx'])){ 

                $dataexcel = $personasData;   
                return Excel::download(new DataExport($dataexcel), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200, $total); 
        }  
         
    }

    public function newasistencia(Request $request, $enterprise) {
         
        $empresa = empresa::where('url', '=', $enterprise)->first();
        $entidad = new entidad(); 
        $sede = new sede(); 

        $idempresa = $empresa->idempresa;  

        $param = array( 
            'entidad.idempresa' => $idempresa,
            'entidad.tipopersonal' => '1' 
        );
         
        $mminicioI = (int) explode(':', $empresa->laborinicio)[1]; 
        $mminicioF = $mminicioI + 14;

        $listcombox = array( 
            'personal' => $entidad->entidades($param, true),    
            'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']), 
            'horasi' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioI, 0),
            'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioF, 14),
            'motivos' => $empresa->motivosexcepciones($idempresa)
        ); 

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function updatehorario(Request $request, $enterprise, $id) {
        $asistencia = asistencia::find($id); 

        $save = array( 
            'idsede' => $request['idsede'],
            'laborinicio' =>  $request['laborinicio'], 
            'laborfin' =>  $request['laborfin']
        ); 

        if (isset($request['nombre'])) {
            $save['nombre'] = $request['nombre'];
        }

        /* Campos auditores */
        $save['updated_at'] = date('Y-m-d H:i:s');
        $save['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if ($asistencia) {   
 
            \DB::beginTransaction();
            try {      
                $asistencia->fill($save);
                $asistencia->save();               
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
             
            return $this->crearRespuesta('Registro ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una asistencia', 404);

    }

    public function update(Request $request, $enterprise, $id) {
        
        $empresa = new empresa();  
        $asistencia = asistencia::find($id); 
        
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all(); 

        $fecha = isset($request['fecha']) ? $this->formatFecha($request['fecha'], 'yyyy-mm-dd') : null;

        if (isset($request['fecha']) && isset($request['horao'])){
            $a = $fecha.' '.$request['horai'];    
            $b = $fecha.' '.$request['horao'];
            if (strtotime($b) < strtotime($a)){
                return $this->crearRespuesta('Hora ingreso no puede ser mayor a hora salida.', [200, 'info'], '','', array($a, $b, date('Y-m-j H:i:s')));
            }   
        } 

        $tiempolaboral = null;
        $tiempotardanza = null;
        $tiempoextra = null;

        if (isset($request['fecha']) && isset($request['horao'])) {
            // $tiempolaboral = $this->convertDiff($fecha, $request['horai'], $request['horao'], '%H:%i:%s');
            $tiempolaboral = $this->convertDiff($fecha, $asistencia->laborinicio, $request['horao'], '%H:%i:%s');
            $tiempolaboralprogramado = $this->convertDiff($fecha , $asistencia->laborinicio, $asistencia->laborfin, '%H:%i:%s');

            if ($this->horaaSegundos($tiempolaboral) > $this->horaaSegundos($tiempolaboralprogramado)) {
                $tiempoextra = $this->convertDiff($fecha, $tiempolaboralprogramado, $tiempolaboral, '%H:%i:%s');
            } 
        }

        if (isset($request['fecha']) && isset($request['horai']) && $this->horaaSegundos($request['horai']) > $this->horaaSegundos($asistencia->laborinicio)) {
            $tiempotardanza = $this->convertDiff($fecha, $asistencia->laborinicio, $request['horai'], '%H:%i:%s');            
        }

        if (isset($request['fecha']) && isset($request['tipo']) && $request['tipo'] === '2') { //Feriado
            $tiempolaboral = $this->convertDiff($fecha, $asistencia->laborinicio, $asistencia->laborfin, '%H:%i:%s');
        }

        $save = array( 
            'fecha' => $fecha,
            'horai' =>  isset($request['horai'])?$request['horai']:null, 
            'horao' =>  isset($request['horao'])?$request['horao']:null,             
            'tiempo' => $tiempolaboral,
            'tiempotardanza' => $tiempotardanza,
            'tiempoextra' => $tiempoextra,
            'tipo' => isset($request['tipo']) ? $request['tipo'] : null, 
            'registro' => isset ($request['registro']) ? $request['registro'] : null, 
            'tardanza' => isset ($request['tardanza']) ? $request['tardanza'] : null, 
            'sancion' => isset ($request['sancion']) ? $request['sancion'] : null, 
            'plan60' => isset ($request['plan60']) ? $request['plan60'] : null,
            'idexcepcion' => isset ($request['idexcepcion']) ? $request['idexcepcion'] : null,
            'observacion' => isset ($request['observacion']) ? $request['observacion'] : null,
            'adjunto' => isset ($request['adjunto']) ? $request['adjunto'] : null
        ); 

        if (isset($request['idsede'])) 
            $save['idsede'] = $request['idsede'];        

        if (isset($request['adjunto'])) 
            $save['adjunto'] = $request['adjunto']; 

        /* Campos auditores */
        $save['updated_at'] = date('Y-m-d H:i:s');
        $save['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {   

            $whereIn = [];

            $param = array(); 
            $between = array(); 
            $param['asistencia.idempresa'] = $idempresa; 
            $param['asistencia.identidad'] = $asistencia->identidad;    

            $between = [$this->formatFecha($request['desde'], 'yyyy-mm-dd'), $this->formatFecha($request['hasta'], 'yyyy-mm-dd')]; 

            $data = $asistencia->grid($param, $between); 
            // return $this->crearRespuesta('XD', [200, 'info'], '', '', $data);

            foreach($data as $row) {
                if (is_null($row->tipo) || $row->tipo === '') {
                    $whereIn[] = $row->idasistencia;
                } 
            }

            if (empty($whereIn)) { 
                    return $this->crearRespuesta('No hay horario laboral para marcar asistencia en el rango de fechas.', [200, 'info']);
                }
        } 

        if ($asistencia) {   
 
            \DB::beginTransaction();
            try {      

                if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {

                    $update = array(
                        'deleted' => '1', 
                        'deleted_at' => date('Y-m-d H:i:s'), 
                        'id_deleted_at' => $this->objTtoken->my
                    ); 

                    if (!empty($whereIn)) {
                        \DB::table('asistencia')
                          ->whereIn('asistencia.idasistencia', $whereIn) 
                          ->update($save);
                    }
                } else {
                    $asistencia->fill($save);
                    $asistencia->save();
                }                
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
             
            return $this->crearRespuesta('Registro ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una asistencia', 404);
    }
    
    public function destroy($enterprise, $id) { 
          
        $asistencia = asistencia::find($id);          

        if ($asistencia->tipo === '1' || $asistencia->tipo === '0') {

            $descripcion = $asistencia->tipo === '1' ? 'asistencia' : 'falta';

            switch ($asistencia->tipo) {
                case '0':
                    $descripcion = 'falta';
                    break; 
                case '1':
                    $descripcion = 'asistencia';
                    break;  
                default:
                    $descripcion = '';
                    break;
            }
            $laborfechainicio = $this->formatFecha($asistencia->laborfechainicio);

            $horaexiste = $laborfechainicio. ' ('.$asistencia->laborinicio.' - '.$asistencia->laborfin.')';

            return $this->crearRespuesta('Horario del '.$horaexiste.' tiene '.$descripcion, [200, 'info']);
        } 
        
        if ($asistencia) {     
            
            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my];
            $asistencia->fill($auditoria);                
            $asistencia->save();
             
            return $this->crearRespuesta('Registro ha sido eliminado', 200);
        }
        
        return $this->crearRespuestaError('Registro no encotrado', 404);
    }

    public function destroymasivo(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $asistencia = new asistencia();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all(); 
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $request);
 
        $whereIn = array();
        // VALIDACIONES   
        // 1. No exista en fecha y intervalo de hora
        foreach ($request as $row) {
            $param = array(
                'asistencia.idempresa' => $idempresa, 
                'asistencia.identidad' => $row['identidad'],
                'asistencia.laborfechainicio' => $this->formatFecha($row['laborfechainicio'], 'yyyy-mm-dd')
            ); 

            $dataasistencia = $asistencia->grid($param); 

            if (empty($dataasistencia)) {
                return $this->crearRespuesta('No hay horario en día '.$row['laborfechainicio'], [200, 'info'], '', '', $dataasistencia);
            }

            if (!empty($dataasistencia)) {

                foreach ($dataasistencia as $row) {

                    if ($row->tipo === '1' || $row->tipo === '0') {// || $row->tipo === '2'

                        $descripcion = $row->tipo === '1' ? 'asistencia' : 'falta';

                        switch ($row->tipo) {
                            case '0':
                                $descripcion = 'falta';
                                break; 
                            case '1':
                                $descripcion = 'asistencia';
                                break; 
                            // case '2':
                            //     $descripcion = 'feriado';
                            //     break;  
                            default:
                                $descripcion = '';
                                break;
                        }

                        $horaexiste = $row->laborfechainicio. ' ('.$row->laborinicio.' - '.$row->laborfin.')';

                        return $this->crearRespuesta('Horario '.$horaexiste.' tiene '.$descripcion, [200, 'info'], '', '', $dataasistencia);
                    }

                    $whereIn[] = $row->idasistencia;
                } 
            }
        }
        
        // return $this->crearRespuesta('XD.', [200, 'info']); 
        \DB::beginTransaction();
        try {
             
            $update = array(
                'deleted' => '1', 
                'deleted_at' => date('Y-m-d H:i:s'), 
                'id_deleted_at' => $this->objTtoken->my
            );

            \DB::table('asistencia')
                  ->whereIn('asistencia.idasistencia', $whereIn) 
                  ->update($update);

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Horario eliminado.', 201, '', '', $whereIn);
    }

     public function storemarcacion(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        
        $entidad = entidad::where(['idempresa' => $idempresa,'numerodoc' => $request['numerodoc'], 'iddocumento' => $request['iddocumento']])->first();

        if(empty($entidad)) {
            return $this->crearRespuesta('No existe documento', [200, 'info']);
        }

        $asistencia = asistencia::where(['identidad' => $entidad->identidad, 'idsede' => $request['idsede']])->orderBy('created_at', 'desc')->first();  

        //Retraso VPS de 10s
        $hora = date('H:i:s', strtotime('-10 second', strtotime(date('Y-m-d H:i:s'))));
        $horai = $hora;
        $horao = $hora;

        if(!empty($asistencia) && $asistencia->fecha === date('Y-m-d') && empty($asistencia->horao)) {
            $mensaje = 'Adiós '.$entidad->nombre; 
            $horai = $asistencia->horai;
            //asistencia existe por tanto se actualiza
            $update = array(
                'horao' =>  $horao,
                'laborfin' =>  $horao,
                'tiempo' => $this->convertDiff($asistencia->fecha, $asistencia->horai, $horao, '%H:%i:%s'), 
                'ipo' => isset($request['ipi']) ? $request['ipi'] : null, 
                'updated_at' => date('Y-m-d H:i:s'),
                //'id_updated_at' => isset($this->objTtoken) ? $this->objTtoken->my : $entidad->identidad
            );
            \DB::table('asistencia')->where('idasistencia', $asistencia->idasistencia)->update($update);
        } else {     
        
            $mensaje = ($entidad->sexo === 'M' ? 'Bienvenido ' : 'Bienvenida '). $entidad->nombre;
            $horao = '';
            //nueva asistencia 
            $save = array(
                'idempresa' => $idempresa,
                'idsede' => $request['idsede'],
                'identidad' => $entidad->identidad,
                'laborfechainicio' => date('Y-m-d'),
                'laborfechafin' => date('Y-m-d'),
                'fecha' => date('Y-m-d'),
                'horai' => $horai, 
                'estado' => '0', 
                'tipo' => '1', 
                'laborinicio' => $horai, 
                'registro' => $request['registro'], 
                'ipi' => isset($request['ipi']) ? $request['ipi'] : null, 
                'created_at' => date('Y-m-d H:i:s'), 
                'id_created_at' => isset($this->objTtoken) ? $this->objTtoken->my : $entidad->identidad                  
            );
            $obj = asistencia::create($save);
        }

        return $this->crearRespuesta($mensaje, 201, '', '', ['entidad' => $entidad, 'fecha' => date('d/m/Y'), 'horai' => $horai, 'horao' => $horao]);
    }

    public function storemarcacionnueva(Request $request, $enterprise) {

        $empresa = new empresa();
        $asistencia = new asistencia();

        $idempresa = $empresa->idempresa($enterprise);

        $request = $request->all();

        //   
        
        /* Validaciones */
        $entidad = entidad::where(['idempresa' => $idempresa,'numerodoc' => $request['numerodoc'], 'iddocumento' => $request['iddocumento']])->first();
        if(empty($entidad)) {
            return $this->crearRespuesta('No existe documento', [200, 'info']);
        }

        if ($entidad->horario === '1') { // Con horario laboral
            $param = array(
                'asistencia.idempresa' => $idempresa,
                'asistencia.identidad' => $entidad->identidad,
                'asistencia.idsede' => $request['idsede'],
                'asistencia.laborfechainicio' => date('Y-m-d')
            );

            $data = $asistencia->grid($param);  

            if (empty($data)) {
                return $this->crearRespuesta('No tiene horarios a marcar en sede.', [200, 'info']);
            }

            $cantParaMarcar = 0;
            $cantMarcados = 0; 
            foreach ($data as $value) {
                if (is_null($value->tipo) || $value->tipo === '' || $value->tipo === '1') { //Vacios y asistencia
                    $cantParaMarcar += 1;
                    if (!empty($value->horai) && !empty($value->horao)) {
                        $cantMarcados += 1;
                    }
                }
            }

            if ($cantParaMarcar === 0) {
                return $this->crearRespuesta('No tiene horarios a marcar en sede.', [200, 'info']);
            }

            if ($cantParaMarcar > 0 && $cantParaMarcar === $cantMarcados) {
                return $this->crearRespuesta('Usted ya registró marcación.', [200, 'info']);
            }
            /* Validaciones */

            $asistencia = null;
            foreach ($data as $value) {
                if (is_null($value->tipo) || $value->tipo === '' || $value->tipo === '1') { //Vacios y asistencia
                    if (empty($value->horai) || empty($value->horao)) {                    
                        $asistencia = $value;
                        break;
                    }
                }
            }

            if (empty($asistencia)) {
                return $this->crearRespuesta('Error, comunicarse con área de Sistemas.', [200, 'info'], '', '', $asistencia);
            }



            //Retraso VPS de 10s
            $hora = date('H:i:s', strtotime('-10 second', strtotime(date('Y-m-d H:i:s'))));
            $fecha = $this->formatFecha($asistencia->laborfechainicio, 'yyyy-mm-dd');
     
            $tiempolaboral = null;
            $tiempotardanza = null;
            $tiempoextra = null; 

            if (!empty($asistencia->horai) && empty($asistencia->horao)) {
                // $tiempolaboral = $this->convertDiff($fecha, $asistencia->horai, $hora, '%H:%i:%s');             
                $tiempolaboral = $this->convertDiff($fecha, $asistencia->laborinicio, $hora, '%H:%i:%s');
                $tiempolaboralprogramado = $this->convertDiff($fecha , $asistencia->laborinicio, $asistencia->laborfin, '%H:%i:%s');

                if ($this->horaaSegundos($tiempolaboral) > $this->horaaSegundos($tiempolaboralprogramado)) {
                    $tiempoextra = $this->convertDiff($fecha, $tiempolaboralprogramado, $tiempolaboral, '%H:%i:%s');
                } 
            }

            if (empty($asistencia->horai) && $this->horaaSegundos($hora) > $this->horaaSegundos($asistencia->laborinicio)) {
                $tiempotardanza = $this->convertDiff($fecha, $asistencia->laborinicio, $hora, '%H:%i:%s');            
            } 

            $update = array(   
                'tipo' => '1', 
                'registro' => '1',
                'updated_at' => date('Y-m-d H:i:s'),
                'id_updated_at' => $entidad->identidad
            );  

            if (!empty($tiempolaboral)) {
                $update['tiempo'] = $tiempolaboral;
            }

            if (!empty($tiempotardanza)) {
                $update['tiempotardanza'] = $tiempotardanza;
            }

            if (!empty($tiempoextra)) {
                $update['tiempoextra'] = $tiempoextra;
            }

            $horai = $asistencia->horai;
            $horao = $asistencia->horao;
            if (empty($asistencia->horai)) {
                $update['horai'] = $hora;
                $mensaje = ($entidad->sexo === 'M' ? 'Bienvenido ' : 'Bienvenida '). $entidad->nombre;
                $horai = $hora;
            }

            if (!empty($asistencia->horai) && empty($asistencia->horao)) {
                $update['horao'] = $hora;
                $mensaje = 'Adiós '.$entidad->nombre;
                $horao = $hora;
            }

            \DB::table('asistencia')
                ->where('idasistencia', $asistencia->idasistencia)
                ->update($update);

        } else { // Sin horario laboral

            $asistencia = asistencia::where(['identidad' => $entidad->identidad, 'idsede' => $request['idsede']])->orderBy('created_at', 'desc')->first();  
 
            $hora = date('H:i:s', strtotime('-10 second', strtotime(date('Y-m-d H:i:s'))));
            $horai = $hora;
            $horao = $hora;

            if(!empty($asistencia) && $asistencia->fecha === date('Y-m-d') && empty($asistencia->horao)) {
                $mensaje = 'Adiós '.$entidad->nombre; 
                $horai = $asistencia->horai;
                //asistencia existe por tanto se actualiza
                $update = array(
                    'horao' =>  $horao,
                    'laborfin' =>  $horao,
                    'tiempo' => $this->convertDiff($asistencia->fecha, $asistencia->horai, $horao, '%H:%i:%s'), 
                    'ipo' => isset($request['ipi']) ? $request['ipi'] : null, 
                    'updated_at' => date('Y-m-d H:i:s'),
                    //'id_updated_at' => isset($this->objTtoken) ? $this->objTtoken->my : $entidad->identidad
                );
                \DB::table('asistencia')->where('idasistencia', $asistencia->idasistencia)->update($update);

            } else {                 
                $mensaje = ($entidad->sexo === 'M' ? 'Bienvenido ' : 'Bienvenida '). $entidad->nombre;
                $horao = '';
                //nueva asistencia 
                $save = array(
                    'idempresa' => $idempresa,
                    'idsede' => $request['idsede'],
                    'identidad' => $entidad->identidad,
                    'laborfechainicio' => date('Y-m-d'),
                    'laborfechafin' => date('Y-m-d'),
                    'fecha' => date('Y-m-d'),
                    'horai' => $horai, 
                    'estado' => '0', 
                    'tipo' => '1', 
                    'laborinicio' => $horai, 
                    'registro' => $request['registro'], 
                    'ipi' => isset($request['ipi']) ? $request['ipi'] : null, 
                    'created_at' => date('Y-m-d H:i:s'), 
                    'id_created_at' => isset($this->objTtoken) ? $this->objTtoken->my : $entidad->identidad                  
                );
                $obj = asistencia::create($save);
            }
        } 
        

        return $this->crearRespuesta($mensaje, 201, '', '', ['entidad' => $entidad, 'fecha' => date('d/m/Y'), 'horai' => $horai, 'horao' => $horao]); 
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        
        // return $this->crearRespuesta('Función obsoleta. Usar calendario de asistencia.', [200, 'info']);

        $entidad = entidad::where(['identidad' => $request['identidad']])->first();
        if(empty($entidad)) {
            return $this->crearRespuesta('No existe persona', [200, 'info']);
        }       

        $fecha = $this->formatFecha($request['fecha'], 'yyyy-mm-dd');

        $save = array(
            'idempresa' => $idempresa,
            'idsede' => $request['idsede'],
            'identidad' => $entidad->identidad,
            'laborfechainicio' => $fecha,
            'laborfechafin' => $fecha,
            'fecha' => $fecha,
            'estado' => '0',
            'tipo' => '1', 
            'laborinicio' => $request['horai'], 
            'laborfin' => $request['horao'], 
            'horai' =>  $request['horai'], 
            'horao' =>  $request['horao'], 
            'tiempo' => isset($request['horao']) ? $this->convertDiff($fecha , $request['horai'], $request['horao'], '%H:%i:%s') : null,
            'registro' => $request['registro'], 
            'ipi' => isset($request['ipi']) ? $request['ipi'] : null, 
            'created_at' => date('Y-m-d H:i:s'), 
            'id_created_at' => $this->objTtoken->my                  
        );  
 
        $obj = asistencia::create($save); 

        return $this->crearRespuesta('Asistencia ha sido creado.', 201);
    }

    public function storemasivo(Request $request, $enterprise) {

        $empresa = new empresa();
        $asistencia = new asistencia();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();  
 
        // VALIDACIONES   
        // 1. No exista en fecha y intervalo de hora
        foreach ($request as $row) {
            $param = array(
                'asistencia.idempresa' => $idempresa, 
                'asistencia.identidad' => $row['identidad'],
                'asistencia.laborfechainicio' => $this->formatFecha($row['laborfechainicio'], 'yyyy-mm-dd')
            );
            $betweenhora = array($row['laborinicio'], $row['laborfin']);

            $dataasistencia = $asistencia->grid($param, '', '', '', '', '', $betweenhora);  
            // return $this->crearRespuesta('XD', [200, 'info'], '', '', $param);  
            if (!empty($dataasistencia)) {
                $horaexiste = $dataasistencia[0]->laborfechainicio . 
                            ' (' . 
                            $dataasistencia[0]->laborinicio.
                            ' - '.
                            $dataasistencia[0]->laborfin.
                            ')';

                return $this->crearRespuesta('Horario '.$horaexiste.' ya existe.', [200, 'info'], '', '', $dataasistencia);
            }
        }

        $diasferiados = $empresa->diasferiados(['idempresa' => $idempresa]);
        $feriados = [];
        foreach ($diasferiados as $value) {
            $feriados[] = $value->fecha;
        }

        $data = [];
        $i = 0;
        foreach ($request as $row) { 
            // return $this->crearRespuesta('Xd', [200, 'info'], '', '', [$feriados, $row['laborfechainicio']]);

            $fecha = $this->formatFecha($row['laborfechainicio'], 'yyyy-mm-dd');

            $data[$i] = array(
                'idempresa' => $idempresa,
                // 'nombre' => $row['nombre'],
                'identidad' => $row['identidad'],
                'idsede' => $row['idsede'],
                'laborfechainicio' => $fecha,
                'laborfechafin' => $this->formatFecha($row['laborfechafin'], 'yyyy-mm-dd'),
                'laborinicio' => $row['laborinicio'],
                'laborfin' => $row['laborfin'],
                'tiempoprogramado' => $this->convertDiff($fecha , $row['laborinicio'], $row['laborfin'], '%H:%i:%s'),
                'estado' => $row['estado'], 
                'created_at' => date('Y-m-d H:i:s'), 
                'id_created_at' => $this->objTtoken->my,
                'tipo' => null,
                'fecha' => null,
                'tiempo' => null,
                'registro' => null
            ); 

            if (in_array($row['laborfechainicio'], $feriados)) { 
                $data[$i]['tipo'] = '2'; //Falta
                $data[$i]['fecha'] = $fecha;
                $data[$i]['tiempo'] = $this->convertDiff($fecha , $row['laborinicio'], $row['laborfin'], '%H:%i:%s');
                $data[$i]['registro'] = '2'; //Mantenimiento
            }

            $i++;
        }

        if (empty($data)) {
            return $this->crearRespuesta('Horario vacío.', [200, 'info']);
        } 

        \DB::beginTransaction();
        try {

            \DB::table('asistencia')->insert($data);

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Horario creado.', 201, '', '', $data);
    }

    public function show(Request $request, $enterprise, $id) {

        $objAsistencia = new asistencia();          
        $empresa = new empresa();
        $entidad = new entidad();
        $sede = new sede();
        
        $idempresa = $empresa->idempresa($enterprise);  
         
        $asistencia = $objAsistencia->asistencia(array('asistencia.idasistencia' =>$id)); 
        $paramsTMP = $request->all(); 

        $param = array( 
            'entidad.idempresa' => $idempresa,
            'entidad.tipopersonal' => '1',
        );

        if ($asistencia) {   
            $listcombox = array( 
                'personal' => $entidad->entidades($param, true),    
                'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']),   
            ); 
            return $this->crearRespuesta($asistencia, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Asistencia no encotrado', 404);
    }

    public function copiarhorario(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $asistencia = new asistencia();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
 

        foreach($request['copia'] as $row) { 
            foreach($row['diacopia'] as $fecha) {
                $whereInFecha[] = $this->formatFecha($fecha, 'yyyy-mm-dd');  
            }
        }

        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $request);

        // VALIDACIONES 
        $param = array(
            'asistencia.idempresa' => $idempresa,              
            'asistencia.identidad' => $request['identidad']  
        ); 
        $dataasistencia = $asistencia->grid($param, '', '', '', '', '', '', $whereInFecha); 
        if (!empty($dataasistencia)) {
            return $this->crearRespuesta('Ya tiene horario para el mes destino.', [200, 'info']);
        }  
        // VALIDACIONES 

        $data = [];
        foreach($request['copia'] as $row) {
            $param = array(
                'asistencia.idempresa' => $idempresa,              
                'asistencia.identidad' => $request['identidad'],
                'asistencia.laborfechainicio' => $this->formatFecha($row['dia'], 'yyyy-mm-dd')
            ); 

            $dataasistencia = $asistencia->grid($param); 

            if (!empty($dataasistencia)) {
                foreach($row['diacopia'] as $fecha) {
                    foreach($dataasistencia as $value) {     
                        if ($value->laborinicio && $value->laborfin) {
                            $data[] = array(
                                'idempresa' => $idempresa,
                                'idsede' =>$value->idsede,
                                'nombre' =>$value->nombre,
                                'identidad' =>$value->identidad,
                                'laborfechainicio' => $this->formatFecha($fecha, 'yyyy-mm-dd'),
                                'laborfechafin' => $this->formatFecha($fecha, 'yyyy-mm-dd'),
                                'laborinicio' => $value->laborinicio,
                                'laborfin' => $value->laborfin,
                                'estado' => '0', 
                                'created_at' => date('Y-m-d H:i:s'), 
                                'id_created_at' => $this->objTtoken->my
                            ); 
                        }
                    }
                }
            } 
        }

        if (empty($data)) {
            return $this->crearRespuesta('No hay horarios.', [200, 'info']);
        }
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $data); 

        \DB::beginTransaction();
        try { 
            \DB::table('asistencia')->insert($data);  
        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Copia de horario realizada.', 201, '', '', $whereInFecha);
    }

}


   
