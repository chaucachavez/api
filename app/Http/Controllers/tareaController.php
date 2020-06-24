<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\sede;
use App\Models\tarea;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\tareadet;

class tareaController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }  

    public function construct(Request $request, $enterprise) { 

        $empresa = new empresa();
        $entidad = new entidad();
        $sede = new sede();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'automatizacion.idempresa' => $idempresa
        ); 

        $param2 = array(
            'entidad.idempresa' => $idempresa, 
            'entidad.tipopersonal' => '1',
            'entidad.acceso' => '1'
        );

        $data = array(
            'automatizaciones' => $empresa->automatizaciones($param),
            'respuestas' => $empresa->respuestas(['respuesta.idempresa' => $idempresa]),
            'personal' => $entidad->entidades($param2),
            'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre'])
        ); 

        return $this->crearRespuesta($data, 200); 
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

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $tarea = new tarea();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['tarea.idempresa'] = $idempresa;
        
        if (isset($paramsTMP['idautomatizacion']) && !empty(trim($paramsTMP['idautomatizacion']))) {
            $param['tarea.idautomatizacion'] = trim($paramsTMP['idautomatizacion']); 
        } 

        if (isset($paramsTMP['identidad']) && !empty(trim($paramsTMP['identidad']))) {
            switch ($param['tarea.idautomatizacion']) {
                case 1: 
                    $param['citamedica.id_created_at'] = $paramsTMP['identidad'];                
                    break; 
                case 2: 
                    $param['cicloatencion.id_created_at'] = $paramsTMP['identidad'];                
                    break; 
                case 3: 
                    $param['citaterapeutica.id_created_at'] = $paramsTMP['identidad'];                
                    break; 
                case 4: 
                    $param['cicloatencion.id_created_at'] = $paramsTMP['identidad'];                
                    break; 
            }
        }

        if (isset($paramsTMP['idestado']) && !empty(trim($paramsTMP['idestado']))) {
            $param['tarea.idestado'] = trim($paramsTMP['idestado']);
        }

        if (isset($paramsTMP['idsede']) && !empty(trim($paramsTMP['idsede']))) {
            $param['tarea.idsede'] = trim($paramsTMP['idsede']);
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'tarea.idtarea';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $between = array();
        $betweenHora = array(); 

        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $likepaciente = !empty($paramsTMP['likepaciente']) ? trim($paramsTMP['likepaciente']) : '';
                        
        $data = $tarea->grid($param, $between, $like, $pageSize, $orderName, $orderSort);

        $data = $this->camposAdicionales($data, $idempresa);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }
                        
        return $this->crearRespuesta($data, 200, $total); 
    } 

    public function show(Request $request, $enterprise, $id) {

        $objTarea = new tarea();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $tarea = $objTarea->tarea($id);

        $params = $request->all();

        if ($tarea) {

            $listcombox = [];

            if (isset($params['proximacita']) && $params['proximacita'] === '1') { 

                $fechaIF = $this->fechaInicioFin(date('d/m/Y'), date('H:i:s'), date('H:i:s'));
                $fechaIF_s_actual = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

                /* Próxima cita mas cercana */                
                $fecha = $this->formatFecha(substr($tarea->created_at, 0, 10), 'yyyy-mm-dd');
                $inicio = substr($tarea->created_at, 11, 8); 

                $citarow = null;
                if ($tarea->idcitamedica) {   //4:pendiente, 5:confirmada, 6:atendida
                    $citarow = \DB::table('citamedica') 
                                ->join('entidad as created', 'citamedica.id_created_at', '=', 'created.identidad')  
                                ->select(['citamedica.fecha', 'citamedica.inicio', 'citamedica.idestado', 'created.entidad as created', 'citamedica.created_at as createdat'])                            
                                ->whereRaw("CONCAT(fecha,' ',inicio) > '".$fecha." ".$inicio."'") 
                                ->where(['citamedica.idpaciente' => $tarea->identidad, 
                                         'citamedica.idsede' => $tarea->idsede,
                                        ]) 
                                ->whereIn('citamedica.idestado', [4, 5, 6])   
                                ->whereNull('citamedica.deleted')
                                ->first();  
                }

                if ($tarea->idcitaterapeutica || $tarea->idcicloatencion) { //32:pendiente, 33:confirmada, 34:atendida, 
                    
                    $citarow = \DB::table('citaterapeutica') 
                                ->join('entidad as created', 'citaterapeutica.id_created_at', '=', 'created.identidad')  
                                ->select(['citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.idestado', 'created.entidad as created', 'citaterapeutica.created_at as createdat'])
                                ->whereRaw("CONCAT(fecha,' ',inicio) > '".$fecha." ".$inicio."'") 
                                ->where(['citaterapeutica.idpaciente' => $tarea->identidad, 
                                         'citaterapeutica.idsede' => $tarea->idsede,
                                        ]) 
                                ->whereIn('citaterapeutica.idestado', [32, 33, 34])   
                                ->whereNull('citaterapeutica.deleted')
                                ->first();  
                }

                $tarea->agendocita = null;
                $tarea->agendoasitio = null;
                $tarea->agendocreacion = null;
                $tarea->agendofechacreacion = null;
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
                    
                    
                    if(isset($citarow->createdat)){
                        $citarow->createdtimeat = substr($citarow->createdat, 11, 8);
                        $citarow->createdat = $this->formatFecha(substr($citarow->createdat, 0, 10));
                    }

                    $tarea->agendocita = $fecha . ' ' .$inicio;
                    $tarea->agendoasitio = $agendoasitio;
                    $tarea->agendocreacion = $citarow->created;
                    $tarea->agendofechacreacion = $citarow->createdat; 
                }

            }



 
            return $this->crearRespuesta($tarea, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Tarea no encotrado', 404);
    }
    
}
