<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use App\Models\calls;
use App\Models\tarea;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\terapia;
use App\Models\producto;
use App\Models\tareadet;
use App\Models\tarifario;
use App\Exports\DataExport;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\horariomedico;
use App\Models\citaterapeutica;

class citaterapeuticaController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    private function validarDisponibilidad($data, $inicio, $fin, $maxcamilla, $idpaciente = '') {
        $inValid = false;
        $cantcitas = 0;
        $cantcitasTerapista = 0;
        foreach ($data as $cita) {
            if ($cita->start_s === $inicio && $cita->end_s === $fin)                            
                $cantcitas = $cantcitas + 1;        

            if ($cita->start_s === $inicio && $cita->end_s === $fin && $cita->idterapista === (int)$maxcamilla['identidad'])                             
                $cantcitasTerapista = $cantcitasTerapista + 1;                        
        }

        if((int)$maxcamilla['cantidadcamilla'] <= $cantcitas || (int)$maxcamilla['maxcamilla'] <= $cantcitasTerapista){  
           $inValid = true;
        }
        
        return $inValid;
    }

    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario 
         * Obtiene dias laborales, hora inicio de labores, hora inicio de refrigerio,
         * tiempo de cita medica, tiempo de terapia.
         */
        $sede = new sede();
        $empresa = empresa::select('idempresa', 'laborinicio', 'laborfin', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
                            ->where('url', '=', $enterprise)->first();
        $idempresa = $empresa->idempresa; 
        
        $param = array(
            'sede.idempresa' => $empresa->idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $data = array(
            'estadoscita' => $empresa->estadodocumentos(10),
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
            'sedehorarios' => $empresa->listaSedeshorarios($idempresa),
            'diasferiados' => $empresa->diasferiados(['idempresa' => $idempresa]), 
            'diasporhoras' => $empresa->diasporhoras(['diaxhora.idempresa' => $idempresa]),
            'turnos' => $empresa->turnosterapeuticas(['idempresa' => $idempresa]),
            'empresahorario' => $empresa,
            'aseguradoras' => $empresa->aseguradoras($idempresa)
        );
        
        return $this->crearRespuesta($data, 200);
    } 

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica(); 

        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa; 

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['fecha']) && !empty($paramsTMP['fecha'])) {
            $param['citaterapeutica.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        }

        if (isset($paramsTMP['idterapista']) && !empty($paramsTMP['idterapista'])) {
            $param['citaterapeutica.idterapista'] = $paramsTMP['idterapista'];
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
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'citaterapeutica.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['citaterapeutica.idestado'] = $paramsTMP['idestado'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['citaterapeutica.idpaciente'] = $paramsTMP['idpaciente'];
        }
                    
        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }else{
            $whereIn = [32, 33, 34, 88];
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';                      
        
        $datacita = $citaterapeutica->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);
        
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacita->total();
            $datacita = $datacita->items();
        }
 

        if (isset($paramsTMP['seguimiento'])  && $paramsTMP['seguimiento'] === '1' && !empty($datacita)) {
          
            $whereIdcitaterapeuticaIn = [];

            foreach($datacita as $row){ 
                $whereIdcitaterapeuticaIn[] = $row->idcitaterapeutica;
                $row->asistencia_a_terapia_tarea = 'No';
                $row->asistencia_a_terapia_estado = '';
                $row->asistencia_a_terapia_acciones = '';
                $row->asistencia_a_terapia_fechacreacion = '';
                $row->asistencia_a_terapia_agendocita = ''; 
                $row->asistencia_a_terapia_agendoasitio = ''; 
            }    

            $tarea = new tarea();
            $param = array(
                'tarea.idempresa' => $idempresa,
                'tarea.idautomatizacion' => '3'
            );

            $datatarea = $tarea->grid($param, [], '', '', '', '', [], [], $whereIdcitaterapeuticaIn);

            $datatarea = $this->camposAdicionales($datatarea, $idempresa);
            
            // dd($datatarea);
            foreach ($datacita as $row){
                foreach ($datatarea as $row2){
                    if ($row->idcitaterapeutica === $row2->idcitaterapeutica)    {    
                        $row->asistencia_a_terapia_tarea = 'Si';
                        $row->asistencia_a_terapia_estado = $row2->nombreestado;
                        $row->asistencia_a_terapia_acciones = $row2->cantacciones;
                        $row->asistencia_a_terapia_fechacreacion = $row2->created_at;
                        $row->asistencia_a_terapia_agendocita = $row2->agendocita; 
                        $row->asistencia_a_terapia_agendoasitio = $row2->agendoasitio;
                    }
                } 
            } 
        } 
        
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){ 
                $data = array(); 
                $i = 0;
                foreach($datacita as $row){
                    $data[$i] = array(
                        'SEDE' => $row->sedenombre,                        
                        'FECHA CITA' => $row->fecha,
                        'HORA CITA' => $row->inicio,
                        'PACIENTE' => $row->paciente,
                        'FECHA RESERVA' => $row->createdat,
                        'RESERVACION' => $row->created, 
                        'TERAPISTA' => $row->terapista,
                        'INICIO_DE_TERAPIA_tarea' => $row->asistencia_a_terapia_tarea,
                        'ASISTENCIA_A_TERAPIA_estado' => $row->asistencia_a_terapia_estado,
                        'ASISTENCIA_A_TERAPIA_acciones' => $row->asistencia_a_terapia_acciones,
                        'ASISTENCIA_A_TERAPIA_fechacreacion' => substr($row->asistencia_a_terapia_fechacreacion, 0, 10),
                        'ASISTENCIA_A_TERAPIA_proximacita' => $row->asistencia_a_terapia_agendocita, 
                        'ASISTENCIA_A_TERAPIA_asitio?' => $row->asistencia_a_terapia_agendoasitio
                    );  
                    $i++;
                }  
                
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($datacita, 200, $total);            
        }
        
    }

    public function indexreservas(Request $request, $enterprise) {

        $paramsTMP = $request->all(); 

        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica(); 

        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa; 

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['fecha']) && !empty($paramsTMP['fecha'])) {
            $param['citaterapeutica.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        }

        if (isset($paramsTMP['idterapista']) && !empty($paramsTMP['idterapista'])) {
            $param['citaterapeutica.idterapista'] = $paramsTMP['idterapista'];
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
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'citaterapeutica.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'asc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['citaterapeutica.idestado'] = $paramsTMP['idestado'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['citaterapeutica.idpaciente'] = $paramsTMP['idpaciente'];
        }
                    
        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';                      
        
        $datacita = $citaterapeutica->gridreservas($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);
        
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacita->total();
            $datacita = $datacita->items();
        } 

        return $this->crearRespuesta($datacita, 200, $total); 
        
    }

    public function indexturnos(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica(); 

        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa; 

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
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

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['citaterapeutica.idestado'] = $paramsTMP['idestado'];
        } 
                    
        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }else{
            $whereIn = [32, 33, 34];
        }
        
        $like = '';                      
        
        $datacita = $citaterapeutica->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn);
        
        $total = ''; 
        
        return $this->crearRespuesta(['data' => $datacita, 'camilla' => []], 200, $total);
        
    }
    
    public function agenda(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $horariomedico = new horariomedico();

        $idempresa = $empresa->idempresa($enterprise);
        
        $param2 = array();
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param2['perfil.idsuperperfil'] = 4; //tipo terapista
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];

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
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'citaterapeutica.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['citaterapeutica.idestado'] = $paramsTMP['idestado'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['citaterapeutica.idpaciente'] = $paramsTMP['idpaciente'];
        }

        if (isset($paramsTMP['idterapista']) && !empty($paramsTMP['idterapista'])) {
            $param['citaterapeutica.idterapista'] = $paramsTMP['idterapista'];
        }

        if (isset($paramsTMP['idaseguradora']) && !empty($paramsTMP['idaseguradora'])) {
            $param['citaterapeutica.idaseguradora'] = $paramsTMP['idaseguradora'];
        }
                    
        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }else{
            //32:pendiente, 33:confirmada, 34:atendida, 35:cance…
            $whereIn = [32, 33, 34]; 
        }
        
        // dd($whereIn);
        $whereInMed = [];
        if (isset($paramsTMP['inMedico']) && !empty($paramsTMP['inMedico'])) {
            $whereInMed = explode(',', $paramsTMP['inMedico']);
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        
        if (isset($paramsTMP['inMedico']) && empty($paramsTMP['inMedico'])){
            $datacita = [];
            $datahorario = [];
        }else{

            // if (isset($paramsTMP['idaseguradora']) && !empty($paramsTMP['idaseguradora'])) {
            //     $param['cicloautorizacion.idaseguradora'] = $paramsTMP['idaseguradora'];
            //     $datacita = $citaterapeutica->griddistinct($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn, $whereInMed); 
            // } else {
                $datacita = $citaterapeutica->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn, $whereInMed);
            //}
            
            $datahorario = $horariomedico->grid($param2, $between, $whereInMed);            
        }                

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacita->total();
            $datacita = $datacita->items();
        }
        
        $meddisponibles = $horariomedico->grid($param2, $between);
        
        return $this->crearRespuesta(['horarios' => $datahorario, 'citas' => $datacita, 'disponibles' => $meddisponibles], 200, $total);
    }

    public function aseguradorasPaciente(Request $request, $enterprise) {

        $citaterapeutica = new citaterapeutica();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        
        $paramsTMP = $request->all(); 
        
        $param = array(
            'cicloautorizacion.idempresa' => $idempresa,
            'cicloautorizacion.idpaciente' => $paramsTMP['idpaciente'],
            'cicloatencion.idsede' => $paramsTMP['idsede'],
            'cicloatencion.idestado' => 20 
        ); 


        $data = $citaterapeutica->segurosPaciente($param);

        return $this->crearRespuesta($data, 200);   

    }
    public function show(Request $request, $enterprise, $id) {

        $objCitaterapeutica = new citaterapeutica();
        $horariomedico = new horariomedico();
        $terapia = new terapia(); 
        
        $empresa = empresa::where('url', '=', $enterprise)->first();
        
        $paramsTMP = $request->all();
        $citaterapeutica = $objCitaterapeutica->citaterapeutica($id);

        if ($citaterapeutica) { 
            $listcombox = null;
            if(isset($paramsTMP['others']) && $paramsTMP['others'] === '1'){
               
                if ($citaterapeutica->idestado === 34) { //Cita Atentido
                    $param = array(
                        'terapia.idempresa' => $empresa->idempresa,
                        'terapia.idsede' => $citaterapeutica->idsede,
                        'terapia.idpaciente' => $citaterapeutica->idpaciente,
                        'terapia.idestado' => '38', //36: Espera  37: Sala 38: Atendido  39: Cancelada
                        'terapia.idcitaterapeutica' => $id
                    );
                    $listcombox = array(
                        'historialterapeutico' => $terapia->terapiatratamientos($param, [], true) 
                    );
                } else {
                    /* Medicos disponibles en horario */
                    $param2 = array();
                    $param2['horariomedico.idsede'] = $citaterapeutica->idsede;
                    $param2['horariomedico.fecha'] = $this->formatFecha($citaterapeutica->fecha, 'yyyy-mm-dd');
                    $param2['perfil.idsuperperfil'] = 4; //tipo terapista

                    $medicos = $horariomedico->medicosPorHorario($param2, $citaterapeutica->inicio, $citaterapeutica->fin);
                    /* Medicos disponibles en horario */
                    

                    $param = array(
                        'cicloautorizacion.idempresa' => $empresa->idempresa,
                        'cicloautorizacion.idpaciente' => $citaterapeutica->idpaciente,
                        'cicloatencion.idsede' => $citaterapeutica->idsede,
                        'cicloatencion.idestado' => 20 
                    ); 

                    $listcombox = array(
                        'estadoscita' => $empresa->estadodocumentos(10), 
                        'terapistas' => $medicos,
                        'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, 14),
                        'aseguradoras' => $objCitaterapeutica->segurosPaciente($param)  
                    );
                }
            }
            return $this->crearRespuesta($citaterapeutica, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }
 
    public function newcitaterapeutica(Request $request, $enterprise) {

        $horariomedico = new horariomedico();
        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all();
        $idempresa = $empresa->idempresa;

        $paramsTMP['fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');

        /* Terapistas disponibles en horario */
        $param2 = array();
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param2['horariomedico.fecha'] = $paramsTMP['fecha'];
        $param2['perfil.idsuperperfil'] = 4; //tipo terapista
 
        $medicos = $horariomedico->medicosPorHorario($param2, $paramsTMP['inicio'], $paramsTMP['fin']);
        /* Terapistas disponibles en horario */
  
        $listcombox = array(
            'estadoscita' => $empresa->estadodocumentos(10), 
            'terapistas' => $medicos, 
            'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, 14)
            //'aseguradoras' => $empresa->aseguradoras($idempresa)
        );

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }
    
    public function disponibilidadHora(Request $request, $enterprise) {
        /* Horas disponibles en horario.
         */        
        $horariomedico = new horariomedico();
        $citaterapeutica = new citaterapeutica();
        $entidad = new entidad();
        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all();
        
        $idempresa = $empresa->idempresa;
        $sedehorario = $empresa->sedehorarios($paramsTMP['idsede']);
        $entidad = $entidad->entidad(['entidad.identidad' => $paramsTMP['idterapista']]);
        $ddmmyy = explode( '/', $paramsTMP['fecha']);   
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        //$param['citaterapeutica.idterapista'] = $paramsTMP['idterapista'];
        $param['citaterapeutica.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
                
        $param2 = [];
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param2['horariomedico.idmedico'] = $paramsTMP['idterapista'];
        $param2['horariomedico.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        $param2['perfil.idsuperperfil'] = 4;

        $param3 = [];
        $param3['turnoterapia.idempresa'] = $idempresa;
        $param3['turnoterapia.idsede'] = $paramsTMP['idsede'];                      
        $param3['turnoterapia.dia'] = date('N', mktime(0, 0, 0, (int)$ddmmyy[1], (int)$ddmmyy[0], (int)$ddmmyy[2])); //php date('N')(Lu=1,...,Do=7)
        
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34]);        
        $datahorario = $horariomedico->grid($param2);
        $turnos = $empresa->turnosterapeuticas($param3);      
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        foreach ($datahorario as $row) {
            //$row->fin = date ( 'H:i:s', strtotime('-30 minute', strtotime(date('Y-m-j'.' '.$row->fin))) );
            //Comentado: Debe de descomentarse cuando TIPOAGENDA es escalonado.
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        } 
        
        foreach ($turnos as $row) { 
            $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        } 

        $obviar = NULL;
        if (isset($paramsTMP['inicio']) && isset($paramsTMP['fin'])) {
            $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $paramsTMP['inicio'], $paramsTMP['fin']);
            $obviar = array(
                'inicio_s' => mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
                'fin_s' => mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y'])
            );
        }
                
        $maxcamilla = array('identidad' => $paramsTMP['idterapista'],'cantidadcamilla' => $sedehorario->cantidadcamilla, 'maxcamilla' => $entidad->maxcamilla);
        $horas = $this->horasdisponibles($datahorario, $this->horaaSegundos($sedehorario->intervaloterapia), $datacita, $obviar, $turnos, $maxcamilla);
 
        // $horasdisp = [];
        // foreach ($horas as $id => $row) {  
        //     $horasdisp[] = array(
        //         'idhora' => $row['inicio']
        //     );
        // }

        //Anadiendo
        /* Días por hora */
        $datahorasbloqueo = $empresa->diasporhoras(['diaxhora.idempresa' => $idempresa, 'diaxhora.idsede' => $paramsTMP['idsede']]);

        foreach ($datahorasbloqueo as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);

            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }
        /**/

        /* Días feriado */ 
        $dataFeriado = array(
            'diasFeriados' => $empresa->diasferiados(['idempresa' => $idempresa])
        );

        $tiempoNohabil = $this->configurarFeriados($dataFeriado, $empresa->laborinicio, $empresa->laborfin);
        /**/
        // dd($dataFeriado);
        $horasdisp = [];
        foreach ($horas as $row) { 
            if (
                // $this->existeCupo($row['inicio'], $row['fin'], $cuposTurno) &&
                    !$this->estaBloqueado($row['start_s'], $row['end_s'], $datahorasbloqueo) && 
                    !$this->validarFeriado($tiempoNohabil, $row['start_s'], $row['end_s']) 
                ) {

                // $horasdisp[] = array(
                //     'idmedico' => $medico->idmedico,
                //     'nombre' => $medico->entidad,
                //     'inicio' => $row['inicio'],
                //     'fin' => $row['fin'],
                // );
                $horasdisp[] = array(
                    'idhora' => $row['inicio']
                );
            }                
        }
        
        return $this->crearRespuesta($horasdisp, 200);
    }

    public function disponibilidadHoraPostCovid(Request $request, $enterprise) {
        /* Horas disponibles en horario.
         */
        $horariomedico = new horariomedico();
        $citaterapeutica = new citaterapeutica();
        $entidad = new entidad();
        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all();
        
        $idempresa = $empresa->idempresa;
        $sedehorario = $empresa->sedehorarios($paramsTMP['idsede']);
        $entidad = $entidad->entidad(['entidad.identidad' => $paramsTMP['idterapista']]);
        $ddmmyy = explode( '/', $paramsTMP['fecha']);   
        $fecha = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');

        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        $param['citaterapeutica.fecha'] = $fecha;
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34]);

        $param = [];
        $param['horariomedico.idempresa'] = $idempresa;
        $param['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param['horariomedico.idmedico'] = $paramsTMP['idterapista'];
        $param['horariomedico.fecha'] = $fecha;
        $datahorario = $horariomedico->grid($param);
        
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }  


        // Receso
        $refrigerio = array();
        if ($entidad->breakinicio && $entidad->breakfin) {               
            $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $entidad->breakinicio, $entidad->breakfin); 

            $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $refrigerio = array(
                'fecha' => $fecha,
                'inicio' => $entidad->breakinicio,
                'fin' => $entidad->breakfin,
                'start_s' => $start_s,
                'end_s' => $end_s
            ); 
        }

        // Medico y camilla
        $maxcamilla = array(
            'identidad' => $entidad->identidad,
            'cantidadcamilla' => $sedehorario->cantidadcamilla, 
            'maxcamilla' => $entidad->maxcamilla
        );

        $citasBloqueosFeriado = $this->CitasBloqueosFeriado($idempresa, $paramsTMP['idsede'], $fecha);

        $feriado = $citasBloqueosFeriado['feriado'];
        $datacita = $citasBloqueosFeriado['citas'];
        $datahorasbloqueo = $citasBloqueosFeriado['bloqueos'];
        
        $horarios = $this->filtraroHorario($datahorario, $feriado, $datahorasbloqueo, $refrigerio);  

        $horas = $this->horasdisponiblesPostCovid($horarios, $datacita, NULL, $maxcamilla);

        $horasdisp = [];
        foreach ($horas as $row) {
                $horasdisp[] = array(
                    'idhora' => $row['inicio']
                );           
        }
         
        return $this->crearRespuesta($horasdisp, 200);
    }
    
    public function disponibilidadTerapista(Request $request, $enterprise) {
        /* Horas disponibles en horario.
         */
        $horariomedico = new horariomedico();
        $citaterapeutica = new citaterapeutica();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all(); 

        if (!(isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede']))) {
            return $this->crearRespuesta('Seleccione sede', [200, 'info']);
        }

        $idempresa = $empresa->idempresa; 
        $sedehorario = $empresa->sedehorarios($paramsTMP['idsede']);
        $fecha = $paramsTMP['fecha'];
        $ddmmyy = explode( '/', $paramsTMP['fecha']);          
        $fechaTmp = $paramsTMP['fecha'];
        $paramsTMP['fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        
        /* Medicos disponibles en horario */
        $param = array();
        $param['horariomedico.idempresa'] = $idempresa;
        $param['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param['horariomedico.fecha'] = $paramsTMP['fecha'];
        $param['perfil.idsuperperfil'] = 4; //tipo terapista
         
        $param3 = array();
        $param3['turnoterapia.idempresa'] = $idempresa;
        $param3['turnoterapia.idsede'] = $paramsTMP['idsede'];                      
        $param3['turnoterapia.dia'] = date('N', mktime(0, 0, 0, (int)$ddmmyy[1], (int)$ddmmyy[0], (int)$ddmmyy[2])); //php date('N')(Lu=1,...,Do=7)
        $turnos = $empresa->turnosterapeuticas($param3);
       
        foreach ($turnos as $row) { 
            $fechaIF = $this->fechaInicioFin($fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        } 
         
        $medicos = $horariomedico->medicosPorHorario($param);
        /* Medicos disponibles en horario */
        
        //unset($paramsTMP['idaseguradora']);

        $nroagenda = null;
        $aseguradoras = $empresa->aseguradoras($idempresa); 
        foreach ($aseguradoras as $row) {
            //dd($paramsTMP);
            if(isset($paramsTMP['idaseguradora']) && !empty($paramsTMP['idaseguradora']) && (int)$paramsTMP['idaseguradora'] === $row->idaseguradora) {
                $nroagenda = $row;
                break;
            }
        }

        /* Citas del dia */  
        $param2 = array();
        $param2['citaterapeutica.idempresa'] = $idempresa;
        $param2['citaterapeutica.idsede'] = $paramsTMP['idsede'];
        //$param['citaterapeutica.idterapista'] = $medico->idmedico;
        $param2['citaterapeutica.fecha'] = $paramsTMP['fecha'];
        $datacita = $citaterapeutica->grid($param2, '', '', '', '', '', [32, 33, 34]);                 
        
        $cuposTurno = [];
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if($nroagenda && $row->idaseguradora === $nroagenda->idaseguradora) {
                if(!isset($cuposTurno[$row->start_s . '-' .$row->end_s]['cantidad'])) {
                    $cuposTurno[$row->start_s . '-' .$row->end_s]['cantidad'] = 0;    
                }
                $cuposTurno[$row->start_s . '-' .$row->end_s]['idaseguradora'] = $row->idaseguradora;
                $cuposTurno[$row->start_s . '-' .$row->end_s]['turno'] = array($row->fecha, $row->inicio, $row->fin);
                $cuposTurno[$row->start_s . '-' .$row->end_s]['cantidad'] += 1;
            }
        }
        /* Citas del dia */  

        /* Días por hora */
        $datahorasbloqueo = $empresa->diasporhoras(['diaxhora.idempresa' => $idempresa, 'diaxhora.idsede' => $paramsTMP['idsede']]);

        foreach ($datahorasbloqueo as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);

            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }
        /**/

        /* Días feriado */ 
        $dataFeriado = array(
            'diasFeriados' => $empresa->diasferiados(['idempresa' => $idempresa])
        );

        $tiempoNohabil = $this->configurarFeriados($dataFeriado, $empresa->laborinicio, $empresa->laborfin);

        if(isset($paramsTMP['idaseguradora']) && !empty($paramsTMP['idaseguradora']) && $nroagenda) {
            foreach ($cuposTurno as $index => $row) {
                $cuposTurno[$index]['disponible'] = true;
                //$nroagenda->nroagenda: NULL ilimitado
                if ( isset($nroagenda->nroagenda) && $row['cantidad'] >= $nroagenda->nroagenda ) {
                    $cuposTurno[$index]['disponible'] = false;
                }
            } 
        }

        $disponibilidad = array();
        foreach ($medicos as $medico) {                        
            $param2 = [];
            $param2['horariomedico.idempresa'] = $idempresa;
            $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
            $param2['horariomedico.idmedico'] = $medico->idmedico;
            $param2['horariomedico.fecha'] = $paramsTMP['fecha'];
            $param2['perfil.idsuperperfil'] = 4; //tipo medico                                
                       
            $datahorario = $horariomedico->grid($param2);

            foreach ($datahorario as $row) {
                //$row->fin = date ( 'H:i:s', strtotime('-30 minute', strtotime(date('Y-m-j'.' '.$row->fin))) );
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            } 
            
            $obviar = NULL;
            if (isset($paramsTMP['inicio']) && isset($paramsTMP['fin'])) {
                $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $paramsTMP['inicio'], $paramsTMP['fin']);
                $obviar = array(
                    'inicio_s' => mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
                    'fin_s' => mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y'])
                );
            }
            
            $maxcamilla = array('identidad' => $medico->idmedico,'cantidadcamilla' => $sedehorario->cantidadcamilla, 'maxcamilla' => $medico->maxcamilla);
            $horas = $this->horasdisponibles($datahorario, $this->horaaSegundos($sedehorario->intervaloterapia), $datacita, $obviar, $turnos, $maxcamilla);  

            /*Refrigerio*/
            $break = array();
            if ($medico->breakinicio && $medico->breakfin) { 
                $fechaBreakIF = $this->fechaInicioFin($fechaTmp, $medico->breakinicio, $medico->breakfin);

                $startBreak = mktime((int) $fechaBreakIF['Hi'], (int) $fechaBreakIF['Mi'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);

                $endBreak = mktime((int) $fechaBreakIF['Hf'], (int) $fechaBreakIF['Mf'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);
                
                $break[] = array('start_s' => $startBreak, 'end_s' => $endBreak); 
            }
            /*End refrigerio*/

            $horasdisp = [];
            foreach ($horas as $row) { 
                if ($this->existeCupo($row['inicio'], $row['fin'], $cuposTurno) &&
                		!$this->estaBloqueado($row['start_s'], $row['end_s'], $datahorasbloqueo) && 
                        !$this->validarFeriado($tiempoNohabil, $row['start_s'], $row['end_s']) && 
                        !$this->validarFeriado($break, $row['start_s'], $row['end_s'])   
            		) {


                    $horasdisp[] = array(
                        'idmedico' => $medico->idmedico,
                        'nombre' => $medico->entidad,
                        'inicio' => $row['inicio'],
                        'fin' => $row['fin'],
                    );
                }                
            }
             
            $disponibilidad[] = array(
                'idmedico' => $medico->idmedico, 
                'nombre' => $medico->entidad, 
                'horas' => $horasdisp
            );
        }
 		
 		$filtrar = [];
 		foreach ($disponibilidad as $value) {
 			if ($value['horas']) {
 				$filtrar[] = $value;
 			}
 		}

        //Ultima cita
        $data = [];
        if (isset($paramsTMP['idpaciente'])) {
            $param = array(
                'citaterapeutica.idempresa' => $idempresa,
                'citaterapeutica.idsede' => $paramsTMP['idsede'],
                'citaterapeutica.idpaciente' => $paramsTMP['idpaciente']
            );

            $data = \DB::table('citaterapeutica')
                    ->where($param)
                    ->whereIn('idestado', [32, 33, 34])
                    ->whereNull('deleted')
                    ->orderBy('idcitaterapeutica', 'DESC')
                    ->first();
        }

        return $this->crearRespuesta($filtrar, 200);
    }

    public function disponibilidadTerapistaPostCovid(Request $request, $enterprise) {
        /* Horas disponibles en horario.
         */
        $horariomedico = new horariomedico();
        $citaterapeutica = new citaterapeutica();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all(); 

        if (!(isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede']))) {
            return $this->crearRespuesta('Seleccione sede', [200, 'info']);
        }

        $idempresa = $empresa->idempresa; 
        $fecha = $paramsTMP['fecha'];
        $sedehorario = $empresa->sedehorarios($paramsTMP['idsede']);        
        $paramsTMP['fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');

        /* Medicos disponibles */
        $param = array();
        $param['horariomedico.idempresa'] = $idempresa;
        $param['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param['horariomedico.fecha'] = $paramsTMP['fecha'];
        $param['perfil.idsuperperfil'] = 4; //tipo terapista
        $medicos = $horariomedico->medicosPorHorario($param);
        $disponibilidad = array();
        
        foreach ($medicos as $medico) {                        
            $param = array();
            $param['horariomedico.idempresa'] = $idempresa;
            $param['horariomedico.idsede'] = $paramsTMP['idsede'];
            $param['horariomedico.idmedico'] = $medico->idmedico;
            $param['horariomedico.fecha'] = $paramsTMP['fecha']; 
            $datahorario = $horariomedico->grid($param);  

            // Receso
            $refrigerio = array();
            if ($medico->breakinicio && $medico->breakfin) {               
                $fechaIF = $this->fechaInicioFin($fecha, $medico->breakinicio, $medico->breakfin); 

                $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

                $refrigerio = array(
                    'fecha' => $fecha,
                    'inicio' => $medico->breakinicio,
                    'fin' => $medico->breakfin,
                    'start_s' => $start_s,
                    'end_s' => $end_s
                ); 
            }

            // Medico y camilla
            $maxcamilla = array(
                'identidad' => $medico->idmedico,
                'cantidadcamilla' => $sedehorario->cantidadcamilla, 
                'maxcamilla' => $medico->maxcamilla
            );
            

            $citasBloqueosFeriado = $this->CitasBloqueosFeriado($idempresa, $paramsTMP['idsede'], $paramsTMP['fecha']);
            // dd($citasBloqueosFeriado);
            
            $datacita = $citasBloqueosFeriado['citas'];
            $feriado = $citasBloqueosFeriado['feriado'];
            $datahorasbloqueo = $citasBloqueosFeriado['bloqueos'];
            
            $horarios = $this->filtraroHorario($datahorario, $feriado, $datahorasbloqueo, $refrigerio);  
            
            // dd($horarios);            
            $horas = $this->horasdisponiblesPostCovid($horarios, $datacita, NULL, $maxcamilla);  
            // dd($horas);
            if ($horas) {

                $horasdisp = [];
                foreach ($horas as $row) {   
                    $horasdisp[] = array(
                        'idmedico' => $medico->idmedico,
                        'nombre' => $medico->entidad,
                        'inicio' => $row['inicio'],
                        'fin' => $row['fin'],
                    );             
                }

                $disponibilidad[] = array(
                    'idmedico' => $medico->idmedico, 
                    'nombre' => $medico->entidad,
                    'maxcamilla' => $medico->maxcamilla,
                    'horas' => $horasdisp
                );
            }
        }  
         
        return $this->crearRespuesta($disponibilidad, 200);
    }

    private function CitasBloqueosFeriado($idempresa, $idsede, $fecha) {

        $empresa = new empresa(); 
        $citaterapeutica = new citaterapeutica();

        /* Citas del dia */
        $param = array();
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $idsede;
        $param['citaterapeutica.fecha'] = $fecha;
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34]);                             
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        /* Bloqueos por hora */
        $param = array();
        $param['diaxhora.idempresa'] = $idempresa;
        $param['diaxhora.idsede'] = $idsede;
        $param['diaxhora.fecha'] = $fecha;
        $datahorasbloqueo = $empresa->diasporhoras($param);
        foreach ($datahorasbloqueo as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        /* Días feriado */
        $param = array();    
        $param['idempresa'] = $idempresa;
        $param['fecha'] = $fecha;
        $dataFeriado = $empresa->diasferiados($param);
        $feriado = array();
        if ($dataFeriado) {
            $ddmmyy = $dataFeriado[0]->fecha;
            // dd($ddmmyy);
            $fechaIF = $this->fechaInicioFin($fecha, '00:00:00', '23:59:00');           
            $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $feriado = array(
                'fecha' => $ddmmyy,
                'inicio' => '00:00:00',
                'fin' => '23:59:00',
                'start_s' => $start_s,
                'end_s' => $end_s
            );            
        }

        return array(
            'citas' => $datacita,
            'bloqueos' => $datahorasbloqueo,
            'feriado' => $feriado
        );
    }

    private function filtraroHorario($datahorario, $feriado, $datahorasbloqueo, $refrigerio) {
        /*
            Definimos intervalos cortos en base a un minimo de 14 minutos. 
            A cada intervalo asignamos si se trata de un intervarlo valido o no.
            Resultado: Matriz de invertalos con indicador de valido(TRUE|FALSE)
        */
        $horas = [];
        $intervalo = 840; // 14min. = 840 seg 
        $i = 0;
        foreach ($datahorario as $row) {

            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);

            $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            while ($start_s < $end_s) {
              
                $horas[$i] = array(
                    'fecha' => $row->fecha,
                    'inicio' => date('H:i:s', $start_s),
                    'fin' => date('H:i:s', $start_s + $intervalo), //14 minutos
                    'start_s' => $start_s,
                    'end_s' => $start_s + $intervalo, //14 minutos
                    'valido' => true
                );

                //1. Dias feriado
                if ($feriado) { 
                    if ($horas[$i]['start_s'] >= $feriado['start_s'] && $horas[$i]['end_s'] <= $feriado['end_s']) {
                        $horas[$i]['valido'] = false; //Bloqueado
                    }
                }   

                //2. Bloqueos por hora
                if ($datahorasbloqueo && $horas[$i]['valido']) {
                    foreach ($datahorasbloqueo as $value) {
                        if ($horas[$i]['start_s'] >= $value->start_s && $horas[$i]['end_s'] <= $value->end_s) {
                            $horas[$i]['valido'] = false; //Bloqueado
                        }
                    }
                }

                //3. Refrigerio
                if ($refrigerio && $horas[$i]['valido']) { 
                    if ($horas[$i]['start_s'] >= $refrigerio['start_s'] && $horas[$i]['end_s'] <= $refrigerio['end_s']) {
                        $horas[$i]['valido'] = false; //Bloqueado
                    }
                }

                $start_s = $start_s + ($intervalo + 60); // 14min. + 1min. = 15 min.    

                $i++;                              
            } 
        }
        
        

        /*
            Filtramos los intervarlos validos.
            Resultado: Matriz de horarios
        */
        $horarios = array();
        $fecha;
        $inicio;
        $start_s;
        $fin;
        $end_s;        
        $banderin = true;

        // dd($horas);
        foreach ($horas as $row) { 
            $fecha = $row['fecha'];

            if ($row['valido'] && $banderin === true) {                
                $inicio = $row['inicio'];
                $start_s = $row['start_s'];               
                $banderin = false;
            }

            if ($row['valido']) {
                $fin = $row['fin'];
                $end_s = $row['end_s'];
            } 

            if (!$row['valido'] && $banderin === false) { 
                //Insertar Item
                $horarios[] = array(
                    'fecha' => $fecha,
                    'inicio' => $inicio,  
                    'fin' => $fin,                  
                    'start_s' => $start_s,                    
                    'end_s' => $end_s
                );

                $banderin = true;
            }
        }

        if ($banderin === false)  {
            $horarios[] = array(
                'fecha' => $fecha,
                'inicio' => $inicio,
                'fin' => $fin,
                'start_s' => $start_s,
                'end_s' => $end_s
            );
        }
        // dd($horarios);
        // dd($datahorario, $feriado, $datahorasbloqueo, $refrigerio);
        return $horarios;
    }

    private function estaBloqueado($inicio, $fin, $horasbloqueo) {
    	$bloqueado = false;
        foreach($horasbloqueo as $row) { 
            if (($row->start_s >= $inicio && $row->start_s <= $fin) || ($row->end_s >= $inicio && $row->end_s <= $fin) || ($row->start_s < $inicio && $row->end_s > $fin)) {
                $bloqueado = true;
                break;
            }
        }
 
        return $bloqueado;
    } 

    private function esDiaFeriado($inicio, $fin, $horasbloqueo) {
        $bloqueado = false;
        foreach($horasbloqueo as $row) { 
            if (($row->start_s >= $inicio && $row->start_s <= $fin) || ($row->end_s >= $inicio && $row->end_s <= $fin) || ($row->start_s < $inicio && $row->end_s > $fin)) {
                $bloqueado = true;
                break;
            }
        }
 
        return $bloqueado;
    } 
    
    private function existeCupo($inicio, $fin, $cuposTurno) {

        $disponible = true;
        foreach($cuposTurno as $row) {
            if ($inicio === $row['turno'][1] && $fin === $row['turno'][2]) {
                $disponible = $row['disponible'];
                break;
            }
        }
 
        return $disponible;
    }

    private function horasdisponiblesPostCovid($datahorario, $datacitas, $obviar = NULL, $maxcamilla = []) {
        // dd($maxcamilla);
        $horas = [];         
        $intervalo = 3540; // 59min.

        $cantCamilla = empty($maxcamilla['maxcamilla']) ? 1 : $maxcamilla['maxcamilla'];
        if ($cantCamilla > 4 ) {
            $cantCamilla = 4;
        }
        // $cantCamilla = 1;
        // dd($cantCamilla);
        
        $i = 1;
        $j = 0;
        while ($i <= $cantCamilla) {

            foreach ($datahorario as $row) {
                $start_s = $row['start_s'] + $j;
                $end_s = $row['end_s'];

                while ($start_s < $end_s) {
                    $inicioTSE = $start_s + 3540; // 59 minutos (TIEMPO DE SESION TERAPIA)
                    if ($inicioTSE <=  $end_s) {
                        $horas[] = array(
                            'inicio' => date('H:i:s', $start_s),
                            'fin' => date('H:i:s', $inicioTSE), 
                            'start_s' => $start_s,
                            'end_s' => $inicioTSE,
                        );
                    }
                    $start_s = $start_s + ($intervalo + 60); // 29min. + 1min. = 30 min. (INTERVALO)
                }
            }

            $j += 900;
            $i++;
        }

        $horas = $this->ordenarMultidimension($horas, 'start_s', SORT_ASC);
        // dd($horas);  
        //FIltrar 
        if (!empty($datacitas)) {        
            foreach ($horas as $indice => $row) {
                if (!empty($obviar) && $obviar['inicio_s'] === $row['start_s'] && $obviar['fin_s'] === $row['end_s']) {
                    //Como se trata de un rango de hora a obviar no aplica que deba ser suprimido
                } else {
                    $cantcitas = 0;
                    $cantcitasTerapista = 0;
                    foreach ($datacitas as $cita) {                 
                        if ($cita->start_s === $row['start_s'] && $cita->end_s === $row['end_s']) {
                            $cantcitas = $cantcitas + 1;
                        }

                        if ($cita->start_s === $row['start_s'] && $cita->end_s === $row['end_s'] && $cita->idterapista === (int)$maxcamilla['identidad']) {
                            $cantcitasTerapista = $cantcitasTerapista + 1;
                        }
                    }

                    if ((int)$maxcamilla['cantidadcamilla'] <= $cantcitas || 1 <= $cantcitasTerapista) {
                        unset($horas[$indice]);
                    }     
                }
            }
        }
        // dd($horas);
        return $horas;        
    }
    
    private function horasdisponibles($datahorario, $tiempoconsultamedica, $datacitas, $obviar = '', $turnos = [], $maxcamilla = []) {

        $horas = [];
         
        if(empty($turnos)){
            foreach ($datahorario as $row) {
                $start_s = $row->start_s;
                $end_s = $row->end_s;

                while ($start_s < $end_s) {
                    $horas[] = array(
                        'inicio' => date('H:i:s', $start_s),
                        'fin' => date('H:i:s', $start_s + $tiempoconsultamedica), //14 minutos
                        'start_s' => $start_s,
                        'end_s' => $start_s + $tiempoconsultamedica, //14 minutos
                        'numCitas' => 0
                    );

                    $start_s = $start_s + ($tiempoconsultamedica + 60); // 14min. + 1min. = 15 min.                                    
                }
            }
        }else{
            foreach ($turnos as $row) {
                $valido = false; 
                foreach ($datahorario as $horario) {
                    if($row->start_s >= $horario->start_s && $row->end_s <= $horario->end_s){
                        $valido = true;
                        break;
                    }
                }
                if($valido){
                    unset($row->idsede);
                    unset($row->dia);
                    $row->numCitas = 0; 
                    $horas[] = (array)$row;
                }
            }
        }
        //dd($turnos);
        if (!empty($datacitas)) {
            
            foreach ($horas as $indice => $row) {
                if (!empty($obviar) && $obviar['inicio_s'] === $row['start_s'] && $obviar['fin_s'] === $row['end_s']) {
                    //Como se trata de un rango de hora a obviar no aplica que deba ser suprimido
                } else { 
                    $cantcitas = 0;
                    $cantcitasTerapista = 0;
                    foreach ($datacitas as $cita) {
                        if ($cita->start_s === $row['start_s'] && $cita->end_s === $row['end_s'])                            
                            $cantcitas = $cantcitas + 1;        
                        
                        if ($cita->start_s === $row['start_s'] && $cita->end_s === $row['end_s'] && $cita->idterapista === (int)$maxcamilla['identidad'])                             
                            $cantcitasTerapista = $cantcitasTerapista + 1;                        
                    }
                    
                    if((int)$maxcamilla['cantidadcamilla'] <= $cantcitas || (int)$maxcamilla['maxcamilla'] <= $cantcitasTerapista){  
                       unset($horas[$indice]);
                    }                    
                }
            }
        }
        
        return $horas;
    }
    
    public function store(Request $request, $enterprise) {
        //moutecfb | Exito@2016
        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $horariomedico = new horariomedico();

        $entidad = entidad::find($request['citaterapeutica']['idpaciente']);
        $terapista = $entidad->entidad(['entidad.identidad' => $request['citaterapeutica']['idterapista']]);
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        //VALIDACIONES 
        
        if(empty($request['citaterapeutica']['inicio']) || empty($request['citaterapeutica']['fin'])){
            return $this->crearRespuesta('Tiempos inválidos', [200, 'info']);
        }
        
        /* 1.- Validar que cita no sea mayor a los 15 dias, no se considera las horas.
         */
        $fechaMaxima = strtotime('+30 day', strtotime(date('Y-m-j')));
        $fechausuario = strtotime($this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd'));
        if ($fechausuario > $fechaMaxima) {
            return $this->crearRespuesta('Fecha de cita terapeutica no debe ser mayor a +30 dias', [200, 'info']);
        }

        /* 2.- Validar que cita para el medico este disponible.
         *     Validar que cita no se trate del mismo paciente.
         *     Validar que cita pueda ser una interconsulta.
         */
        $sedehorario = $empresa->sedehorarios($request['citaterapeutica']['idsede']);
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $request['citaterapeutica']['idsede']; 
        $param['citaterapeutica.fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');

        $param2 = [];
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $request['citaterapeutica']['idsede'];
        $param2['horariomedico.idmedico'] = $request['citaterapeutica']['idterapista'];
        $param2['horariomedico.fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');
        $param2['perfil.idsuperperfil'] = 4;

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34]);
        $datahorario = $horariomedico->grid($param2);
        
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        foreach ($datahorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        $fechaIF = $this->fechaInicioFin($request['citaterapeutica']['fecha'], $request['citaterapeutica']['inicio'], $request['citaterapeutica']['fin']);
        $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                                
        $validation = ['inValid' => true, 'message' => 'Cita est&aacute; fuera del horario del terapista'];
        foreach ($datahorario as $row) {
            if ($row->start_s <= $start && $row->end_s >= $end) {
                //Cita esta dentro del horario del medico
                $validation['inValid'] = false;
                $validation['message'] = ''; 
                break;
            }
        }

        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        }

        $validation = ['inValid' => false, 'message' => ''];
        foreach ($datacita as $row) {
            if ($start === $row->start_s && $end === $row->end_s && $request['citaterapeutica']['idpaciente'] === $row->idpaciente) {
                $validation['inValid'] = true;
                $validation['message'] = 'Paciente ya tiene cita.';
                break;
            }
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        }
        
        $maxcamilla = array('identidad' => $terapista->identidad,'cantidadcamilla' => $sedehorario->cantidadcamilla, 'maxcamilla' => $terapista->maxcamilla);
        
        $validation = ['inValid' => false, 'message' => '']; 
        if ($this->validarDisponibilidad($datacita, $start, $end, $maxcamilla)) {
            $validation['inValid'] = true;
            $validation['message'] = 'M&aacute;x. de citas para terapista &Oacute; m&aacute;x. de camillas ocupadas.!';
        }
         
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $maxcamilla);
        }

        /* 3.- Validar que exista paciente */
        if (!$entidad) {
            return $this->crearRespuesta('No existe persona, registrarlo.', [200, 'info']);
        }
        
        /* 4.- Validar cambio de numero de documento que no exista */ 
        if (isset($request['entidad']['numerodoc']) && $entidad->numerodoc !== $request['entidad']['numerodoc']) {
            $numerodoc = $request['entidad']['numerodoc'];
            $count = entidad::select('numerodoc')->where(['numerodoc' => $numerodoc, 'idempresa' => $idempresa])->count();
            if ($count > 0) {
                return $this->crearRespuesta('No puede registrarse, el n&uacute;mero de documento "' . $numerodoc . '" ya existe.', [200, 'info']);
            }
        }
        
        /* 5.- Validar que el tiempo de cita medica. */
        // if (!empty($sedehorario->intervaloterapia)) {
        //     $Ini = explode(":", $request['citaterapeutica']['inicio']);
        //     $Fin = explode(":", $request['citaterapeutica']['fin']);   //h:m:s             
        //     $CitaSegundos = mktime($Fin[0], $Fin[1], $Fin[2]) - mktime($Ini[0], $Ini[1], $Ini[2]);

        //     $FinT = explode(":", $sedehorario->intervaloterapia);
        //     $H = $Ini[0] + $FinT[0];
        //     $M = $Ini[1] + $FinT[1];
        //     $S = $Ini[2] + $FinT[2];
        //     if ($M > 59) {
        //         $M = $M - 60;
        //         $H = $H + 1;
        //     } 
            
        //     $tiempoconsultamedica = mktime($H, $M, $S) - mktime($Ini[0], $Ini[1], $Ini[2]); 
        //     if ($tiempoconsultamedica !== $CitaSegundos) {
        //         return $this->crearRespuesta("Tiempo de cita invalido. Debe ser '" . $sedehorario->intervaloterapia .'|'. $tiempoconsultamedica."' Hrs.".$CitaSegundos, [200, 'info']);
        //     }
        // }

        /* 6.- Validar por número de aseguradoras */
        // $aseguradoras = $empresa->aseguradoras($idempresa); 
        // $nroreservas = 0;
        // $nroagenda = null;
        // foreach ($datacita as $row) {
        //     if ($start === $row->start_s && $end === $row->end_s && $request['citaterapeutica']['idaseguradora'] === $row->idaseguradora) {
        //         $nroreservas += 1;
        //     }
        // }

        // foreach ($aseguradoras as $row) {
        //     if($request['citaterapeutica']['idaseguradora'] === $row->idaseguradora) {
        //         $nroagenda = $row;
        //         break;
        //     }
        // } 
        
        // if ($nroagenda && $nroagenda->nroagenda && ($nroreservas + 1) > $nroagenda->nroagenda) {
        //     return $this->crearRespuesta('Seguro "' . $nroagenda->nombre . '" ya tiene copado '. $nroreservas . " reservaciones.", [200, 'info']);
        // }

        /* 7.- Validar refrigerio */
        if ($terapista->breakinicio && $terapista->breakfin) {
            $fechaBreakIF = $this->fechaInicioFin($request['citaterapeutica']['fecha'], $terapista->breakinicio, $terapista->breakfin);

            $startBreak = mktime((int) $fechaBreakIF['Hi'], (int) $fechaBreakIF['Mi'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);

            $endBreak = mktime((int) $fechaBreakIF['Hf'], (int) $fechaBreakIF['Mf'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);
 
            if (($startBreak >= $start && $startBreak <= $end) || ($endBreak >= $start && $endBreak <= $end) || ($startBreak < $start && $endBreak > $end)) {
                return $this->crearRespuesta("Refrigerio de personal de " . $this->transformHora($terapista->breakinicio) ." - ". $this->transformHora($terapista->breakfin), [200, 'info']);
            }
        }

        if($idempresa === 1) {
            $validador = $this->saldoMinimoCiclo($idempresa, $request['citaterapeutica']['idsede'], $request['citaterapeutica']['idpaciente']);
            if ($validador['inValid']) {
                return $this->crearRespuesta($validador['mensaje'], [200, 'info'], '', '', $validador['extra']);
            }
        }
        //FIN VALIDACIONES

        //return $this->crearRespuesta("Tiempo", [200, 'info'],'','',date('d/m/Y h:i:s', mktime($Fin[0], $Fin[1], $Fin[2]))) ;
        $request['entidad']['tipocliente'] = '1'; 
        $request['citaterapeutica']['idempresa'] = $idempresa;
        $request['citaterapeutica']['fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');
        /* Campos auditores */
        $request['citaterapeutica']['created_at'] = date('Y-m-d H:i:s');
        $request['citaterapeutica']['id_created_at'] = $this->objTtoken->my;
        /* Campos auditores */

        \DB::beginTransaction();
        try {
            //Graba en 2 tablas(citaterapeutica, entidad)            
            $citaterapeutica = citaterapeutica::create($request['citaterapeutica']);
            $citaterapeutica->grabarLog($citaterapeutica->idcitaterapeutica, $this->objTtoken->my);            
            $entidad->fill($request['entidad']);
            $entidad->save();

            if (isset($request['llamadacliente']) && $request['llamadacliente'] === '1') {
                $paramCall = array(
                    'idempresa' => $idempresa, 
                    'idcitaterapeutica' => $citaterapeutica->idcitaterapeutica,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'cliente' => $entidad->entidad,
                    'motivo' => 'Reservación',
                    'tipo' => 'Reservación - Terapia',                        
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my
                );
                calls::create($paramCall);   
            } 
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Cita terapeuta para "' . $entidad->entidad . '" ha sido creado.', 201, '', '', $citaterapeutica->idcitaterapeutica);
    }

    public function storeprogramacion(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $citaterapeutica = new citaterapeutica();

        $entidad = new entidad();
        
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $terapista = $entidad->entidad(['entidad.identidad' => $request['idmedico']]);

        foreach($request['dia'] as $fecha) {
            $whereInFecha[] = $this->formatFecha($fecha, 'yyyy-mm-dd');
        }

        $betweenhora = [$request['inicio'], $request['fin']];
 
        // VALIDACIONES
        if (empty($request['dia'])) {
            return $this->crearRespuesta('Especifique fechas.', [200, 'info']);
        }

        if (empty($request['camilla'])) {
            return $this->crearRespuesta('Especifique camillas.', [200, 'info']);
        }

        // 1. Existe citas agendadas en camilla
        $param = array(
            'citaterapeutica.idempresa' => $idempresa, 
            'citaterapeutica.idsede' => $request['idsede']
        );

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34, 35, 88], [], $betweenhora, [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha'], [], [], false, $whereInFecha, $request['camilla']); 

        if (!empty($datacita)) {
            return $this->crearRespuesta('Camilla ya tiene programación.', [200, 'info'], '', '', $datacita);
        }

        // 2. Existe dias agendado a terapeuta
        $param = array(
            'citaterapeutica.idempresa' => $idempresa,
            'citaterapeutica.idterapista' => $request['idmedico'],
            'citaterapeutica.idsede' => $request['idsede']
        );  

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34, 35, 88], [], $betweenhora, [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.idcamilla'], [], [], false, $whereInFecha); 

        if (!empty($datacita)) {
            $cantidadCamillas = []; 
            foreach($datacita as $row) {
                if (!in_array($row->idcamilla, $cantidadCamillas)) {
                    $cantidadCamillas[] = $row->idcamilla;
                }
            }  

            if ($request['tiempo'] === 45 && count($cantidadCamillas) === 3) {
                return $this->crearRespuesta('Personal ya tiene programación.', [200, 'info'], '', '', [$cantidadCamillas, $datacita]);
            }

            if ($request['tiempo'] === 90 && count($cantidadCamillas) === 6) {
                return $this->crearRespuesta('Personal ya tiene programación.', [200, 'info'], '', '', [$cantidadCamillas, $datacita]);
            }
        }

        // 3. Existe dias agendado a terapeuta en otra sede.
        $param = array(
            'citaterapeutica.idempresa' => $idempresa,
            'citaterapeutica.idterapista' => $request['idmedico'] 
        );  

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34, 35, 88], [], $betweenhora, [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.idcamilla', 'citaterapeutica.idsede', 'sede.nombre as sedenombre'], [], [], false, $whereInFecha); 

        if (!empty($datacita)) {
            $sedeExisteProgramacion = null; 

            foreach($datacita as $row) {
                if ($row->idsede !== $request['idsede']) {
                    $sedeExisteProgramacion = $row;
                }
            }

            if ($sedeExisteProgramacion) {
                return $this->crearRespuesta('Personal ya tiene programación en sede '.$sedeExisteProgramacion->sedenombre, [200, 'info'], '', '', $sedeExisteProgramacion);
            }
        }  

        $cupos = array();
        $recesos = array();
        foreach($request['dia'] as $fecha) {

            $fechaIF = $this->fechaInicioFin($fecha, $request['inicio'], $request['fin']);
            $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $anidado = 0;

            sort($request['camilla']);
            foreach($request['camilla'] as $idcamilla) {

                $start_s += $anidado;

                $inicio = $start_s;
                while ($inicio < $end_s) {

                    $fin = $inicio + (($request['tiempo'] - 1) * 60);   

                    if ( $fin <= $end_s) {
                        $cupos[] = array( 
                            'inicio_s' => date('d/m/Y H:i:s', $inicio),
                            'fin_s' => date('d/m/Y H:i:s', $fin),
                            'start_s' => $inicio,
                            'end_s' => $fin,  
                            //Otros
                            'idempresa' => $idempresa,
                            'idsede' => $request['idsede'],
                            'idterapista' => $request['idmedico'],
                            'idpaciente' => NULL,
                            'idestado' => 88,
                            'fecha' => $this->formatFecha($fecha, 'yyyy-mm-dd'),
                            'inicio' => date('H:i:s', $inicio),
                            'fin' => date('H:i:s', $fin),
                            'idcamilla' => $idcamilla,
                            'created_at' => date('Y-m-d H:i:s'),
                            'id_created_at' => $this->objTtoken->my
                        );
                    }

                    $inicio += $request['tiempo'] * 60; 
                } 

                $anidado = (15 * 60);
            }    


            //Anadir receso
            if ($terapista->breakinicio && $terapista->breakfin) {
                $fechaBreakIF = $this->fechaInicioFin($fecha, $terapista->breakinicio, $terapista->breakfin);
                $startbreak_s = mktime((int) $fechaBreakIF['Hi'], (int) $fechaBreakIF['Mi'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);
                $endbreak_s = mktime((int) $fechaBreakIF['Hf'], (int) $fechaBreakIF['Mf'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);

                $recesos[] = array(
                    'start_s' => $startbreak_s,
                    'end_s' => $endbreak_s
                );
            }
        }

        // return $this->crearRespuesta('XD.', [200, 'info'], '', '', $cupos);

        if (empty($cupos)) {
            return $this->crearRespuesta('Seleccione rango de duración mayor.', [200, 'info'], '', '', $datacita);
        }

        /* Días por hora */
        $datahorasbloqueo = $empresa->diasporhoras(['diaxhora.idempresa' => $idempresa, 'diaxhora.idsede' => $request['idsede']]);

        foreach ($datahorasbloqueo as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);

            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        /* Días feriado */ 
        $dataFeriado = array(
            'diasFeriados' => $empresa->diasferiados(['idempresa' => $idempresa])
        );

        $tiempoNohabil = $this->configurarFeriados($dataFeriado, $empresa->laborinicio, $empresa->laborfin);

        $data = [];
        foreach($cupos as $row) {
            if (!$this->estaBloqueado($row['start_s'], $row['end_s'], $datahorasbloqueo) && 
                !$this->validarFeriado($tiempoNohabil, $row['start_s'], $row['end_s']) && 
                !$this->validarFeriado($recesos, $row['start_s'], $row['end_s'])) {
                unset($row['inicio_s']);
                unset($row['fin_s']);
                unset($row['start_s']);
                unset($row['end_s']);
                $data[] = $row;
            }
        }

        if (empty($data)) {
            return $this->crearRespuesta('Cupos vacíos.', [200, 'info']);
        }

        // return $this->crearRespuesta('XD', [200, 'info'], '', '', [
        //     'data' => $data, 
        //     'cupos' => $cupos,
        //     'datahorasbloqueo' => $datahorasbloqueo, 
        //     'tiempoNohabil' => $tiempoNohabil, 
        //     'recesos' => $recesos
        // ]);  

        \DB::beginTransaction();
        try {
            \DB::table('citaterapeutica')->insert($data);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        
        \DB::commit();
        
        return $this->crearRespuesta('Programacion creado.', 201, '', '', $cupos);
    }

    public function reprogramacion(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $citaterapeutica = new citaterapeutica();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if (empty($request['de']) || empty($request['a'])) {
            return $this->crearRespuesta('Especifique De y A', [200, 'info']);
        } 
        // VALIDACIONES

        $de = $citaterapeutica->citaterapeutica($request['de']);
        $a = $citaterapeutica->citaterapeutica($request['a']);

        if (empty($de)) {
            return $this->crearRespuesta('Cita origen no existe.', [200, 'info']);
        }

        if ($de->idestado === 88) {
            return $this->crearRespuesta('Cita origen esta disponible.', [200, 'info']);
        }

        if ($de->idestado === 34) {
            return $this->crearRespuesta('Cita origen ya esta atendida.', [200, 'info']);
        }

        if (empty($a) || $a->idestado !== 88) {
            return $this->crearRespuesta('Cita destino no está disponible.', [200, 'info'], '', '', $de);
        }

        // return $this->crearRespuesta('XD', [200, 'info'], '', '', [$de, $a]);

        
        // return $this->crearRespuesta('XD.', [200, 'info']); 
        \DB::beginTransaction();
        try {
              
            //Origen
            $update = [
                'idestado' => 88, //88:disponible
                'idpaciente' => null, 
                'updated_at' => date('Y-m-d H:i:s'), 
                'id_updated_at' => $this->objTtoken->my 
            ]; 

            \DB::table('citaterapeutica')
                  ->where('citaterapeutica.idcitaterapeutica', $request['de'])  
                  ->update($update);


            $update = [
                'idestado' => 32, //32:pendiente
                'idpaciente' => $request['idpaciente'], 
                'updated_at' => date('Y-m-d H:i:s'), 
                'id_updated_at' => $this->objTtoken->my 
            ]; 

            \DB::table('citaterapeutica')
                  ->where('citaterapeutica.idcitaterapeutica', $request['a'])  
                  ->update($update);



        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Reprogramacion realizado.', 201);
    }
    
    public function calificacion(Request $request, $enterprise, $id) {

        $citaterapeutica = citaterapeutica::find($id); 

        if (empty($citaterapeutica)) {
            return $this->crearRespuesta('Cita a terapia no existe.', [200, 'info']);
        }

        if (empty($request['puntajeatencion']) || empty($request['puntajeantes']) || empty($request['puntajedespues'])) {
            return $this->crearRespuesta('Debe responder el cuestionario de preguntas.', [200, 'info']);
        }  
        
        \DB::beginTransaction();
        try {            
            //Origen
            $update = [
                'puntajeatencion' => $request['puntajeatencion'],
                'puntajeantes' => $request['puntajeantes'], 
                'puntajedespues' => $request['puntajedespues']
            ]; 

            \DB::table('citaterapeutica')
                  ->where('citaterapeutica.idcitaterapeutica', $id)  
                  ->update($update);  

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Calificación enviada exitósamente.', 201);
    }

    public function deleteprogramacion(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $citaterapeutica = new citaterapeutica();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if (empty($request['dia'])) {
            return $this->crearRespuesta('Especifique fechas.', [200, 'info']);
        }

        foreach($request['dia'] as $fecha) {
            $whereInFecha[] = $this->formatFecha($fecha, 'yyyy-mm-dd');
        }

        // VALIDACIONES
        // Existe citas agendadas
        $param = array(
            'citaterapeutica.idempresa' => $idempresa,
            'citaterapeutica.idterapista' => $request['idmedico'],
            'citaterapeutica.idsede' => $request['idsede']
        ); 

        if (isset($request['idcamilla']) && !empty($request['idcamilla'])) {
            $param['citaterapeutica.idcamilla'] = $request['idcamilla'];
        }

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34, 35], [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha'], [], [], false, $whereInFecha);

        if (!empty($datacita)) {
            return $this->crearRespuesta('Existe citas agendadas.', [200, 'info']);
        }
        
        // Existe citas disponibles
        $param = array(
            'citaterapeutica.idempresa' => $idempresa,
            'citaterapeutica.idterapista' => $request['idmedico'],
            'citaterapeutica.idsede' => $request['idsede'],
            'citaterapeutica.idestado' => 88 //Disponible
        ); 

        if (isset($request['idcamilla']) && !empty($request['idcamilla'])) {
            $param['citaterapeutica.idcamilla'] = $request['idcamilla'];
        }

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [], [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha'], [], [], false, $whereInFecha);

        if (empty($datacita)) {
            return $this->crearRespuesta('No hay citas disponibles.', [200, 'info']);
        }
        
        // return $this->crearRespuesta('XD.', [200, 'info']); 
        \DB::beginTransaction();
        try {
             
            $param = array(
                'citaterapeutica.idempresa' => $idempresa,
                'citaterapeutica.idterapista' => $request['idmedico'],
                'citaterapeutica.idsede' => $request['idsede'],
                'citaterapeutica.idestado' => 88 //disponible
            );

            if (isset($request['idcamilla']) && !empty($request['idcamilla'])) {
                $param['citaterapeutica.idcamilla'] = $request['idcamilla'];
            }

            $update = array(
                'deleted' => '1', 
                'deleted_at' => date('Y-m-d H:i:s'), 
                'id_deleted_at' => $this->objTtoken->my
            );

            \DB::table('citaterapeutica')
                  ->where($param) 
                  ->whereIn('citaterapeutica.fecha', $whereInFecha) 
                  ->update($update);

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Programacion eliminado.', 201, '', '', $whereInFecha);
    }

    public function storemasivo(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $citaterapeutica = new citaterapeutica();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if (empty($request['idpaciente'])) {
            return $this->crearRespuesta('Especifique paciente', [200, 'info']);
        }

        $whereInId = [];
        foreach($request['citaterapeutica'] as $idcitaterapeutica) {
            $whereInId[] = $idcitaterapeutica;
        } 

        // VALIDACIONES
        // Existe citas disponibles
        $param = array(
            'citaterapeutica.idempresa' => $idempresa,  
            'citaterapeutica.idestado' => 88
        );  

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [], [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.idsede'], [], [], false, [], [], $whereInId);

        if (empty($datacita)) {
            return $this->crearRespuesta('No hay citas disponibles.', [200, 'info'], '', '', [$datacita, $whereInId]);
        }

        if (count($datacita) !== count($whereInId)) {
            return $this->crearRespuesta('Citas ya no están disponibles.', [200, 'info'], '', '', [$datacita, $whereInId]);
        }  
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $datacita[0]);
        $validador = $this->saldoMinimoCiclo($idempresa, $datacita[0]->idsede, $request['idpaciente']);
        if ($validador['inValid']) {
            return $this->crearRespuesta($validador['mensaje'], [200, 'info'], '', '', $validador['extra']);
        } 

        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $datacita[0]);

        \DB::beginTransaction();
        try {
             
            $param = array(
                'citaterapeutica.idempresa' => $idempresa, 
                'citaterapeutica.idestado' => 88 
            ); 

            $update = array(
                'idpaciente' => $request['idpaciente'], 
                'idestado' => 32, 
                'updated_at' => date('Y-m-d H:i:s'), 
                'id_updated_at' => $this->objTtoken->my
            );

            if (isset($request['reservaportal'])) {
                $update['reservaportal'] = $request['reservaportal'];
            }

            \DB::table('citaterapeutica')
                  ->where($param) 
                  ->whereIn('citaterapeutica.idcitaterapeutica', $whereInId) 
                  ->update($update);

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Citas agendadas exitósamente.', 201, '', '', $whereInId);
    }

    public function reasignacionprogramacion(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $citaterapeutica = new citaterapeutica();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if (empty($request['dia'])) {
            return $this->crearRespuesta('Especifique fechas.', [200, 'info']);
        }

        foreach($request['dia'] as $fecha) {
            $whereInFecha[] = $this->formatFecha($fecha, 'yyyy-mm-dd');
        }

        // VALIDACIONES 
        // return $this->crearRespuesta('XD.', [200, 'info']);

        \DB::beginTransaction();
        try { 
            $param = array(
                'citaterapeutica.idempresa' => $idempresa,
                'citaterapeutica.idterapista' => $request['idterapistaorigen'],
                'citaterapeutica.idsede' => $request['idsede'] 
            ); 

            $update = array(
                'idterapista' => $request['idterapistadestino'],  
                'updated_at' => date('Y-m-d H:i:s'), 
                'id_updated_at' => $this->objTtoken->my
            );

            \DB::table('citaterapeutica')
                  ->where($param) 
                  ->whereIn('citaterapeutica.fecha', $whereInFecha) 
                  ->whereIn('citaterapeutica.idestado', [32,33,88]) //Pendiente, confirmada, disponible
                  ->update($update);

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Programacion reasignada.', 201, '', '', $whereInFecha);
    }

    public function copiarprogramacion(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first(); 
        $citaterapeutica = new citaterapeutica();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if (empty($request['copia'])) {
            return $this->crearRespuesta('Especifique fechas.', [200, 'info']);
        }

        foreach($request['copia'] as $row) { 
            foreach($row['diacopia'] as $fecha) {
                $whereInFecha[] = $this->formatFecha($fecha, 'yyyy-mm-dd');  
            }
        }

        // VALIDACIONES 
        $param = array(
            'citaterapeutica.idempresa' => $idempresa,              
            'citaterapeutica.idterapista' => $request['idterapista']  
        ); 
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34, 35], [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.idsede'], [], [], false, $whereInFecha); 
        if (!empty($datacita)) {
            return $this->crearRespuesta('Ya tiene horario para el mes destino.', [200, 'info'], '', '', $datacita);
        }  
        // VALIDACIONES 

        $cupos = [];
        foreach($request['copia'] as $row) {
            $param = array(
                'citaterapeutica.idempresa' => $idempresa,              
                'citaterapeutica.idterapista' => $request['idterapista'],
                'citaterapeutica.fecha' => $this->formatFecha($row['dia'], 'yyyy-mm-dd')
            ); 

            $datacita = $citaterapeutica->grid($param); 

            // return $this->crearRespuesta('XS.', [200, 'info'], '', '', $datacita); 

            if (!empty($datacita)) {
                foreach($row['diacopia'] as $fecha) {
                    foreach($datacita as $value) {
                        if ($value->idcamilla) {
                            $cupos[] = array(
                                'idempresa' => $idempresa,
                                'idsede' => $value->idsede,
                                'idterapista' => $value->idterapista,
                                'idpaciente' => NULL,
                                'idestado' => 88,
                                'fecha' => $this->formatFecha($fecha, 'yyyy-mm-dd'),
                                'inicio' => $value->inicio,
                                'fin' => $value->fin,
                                'idcamilla' => $value->idcamilla,
                                'created_at' => date('Y-m-d H:i:s'),
                                'id_created_at' => $this->objTtoken->my
                            );
                        }
                    }
                }
            } 
        }

        if (empty($cupos)) {
            return $this->crearRespuesta('No hay citas.', [200, 'info']);
        }
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $cupos); 

        \DB::beginTransaction();
        try { 
            \DB::table('citaterapeutica')->insert($cupos);  
        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Programacion masiva generada.', 201, '', '', $whereInFecha);
    }

    public function log($enterprise, $id) {
         
        $citaterapeutica = new citaterapeutica();  
        
        $data = $citaterapeutica->listaLog($id);
                
        return $this->crearRespuesta($data, 200); 
    } 

    private function saldoMinimoCiclo($idempresa, $idsede, $idpaciente) {
        
        $citaterapeutica = new citaterapeutica();
        $objPresupuesto = new presupuesto();
        $cicloatencion = new cicloatencion();

        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $idsede;
        $param['citaterapeutica.idpaciente'] = $idpaciente;
        $whereIn = [32, 33]; //32:Pendiente 33:Confirmada

        $param2 = [];
        $param2['cicloatencion.idempresa'] = $idempresa;
        $param2['cicloatencion.idsede'] = $idsede;
        $param2['cicloatencion.idpaciente'] = $idpaciente;
        $param2['cicloatencion.idestado'] = 20; 
         
        
        $fechaAct = $this->fechaInicioFin(date('d/m/Y'), date('H:i:s'), date('H:i:s'));
        $start_s = mktime((int) $fechaAct['Hi'], (int) $fechaAct['Mi'], 0, (int) $fechaAct['m'], (int) $fechaAct['d'], (int) $fechaAct['y']);

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], date('Y-m-j')); 

        $dataciclo = $cicloatencion->grid($param2, '', '', '', 'cicloatencion.fecha', 'asc', false);

        if(count($dataciclo) === 0) {
            return array('inValid' => true, 'mensaje' => 'No tiene ciclos abiertos', 'extra' => null);
        }

        // return array('inValid' => true, 'mensaje' => 'No tiene ciclos abiertos.', 'extra' => $dataciclo);

        $creditodisponible = 0;
        $strciclo = '';
        foreach($dataciclo as $row) { 

            $montopago = 0;
            $montoefectuado = 0;

            if(!empty($row->montopago)) {
                $montopago = (double)$row->montopago;
            }

            if(!empty($row->montoefectuado)) {
                $montoefectuado = (double)$row->montoefectuado; 
            }

            $disponible = round($montopago - $montoefectuado, 2);

            $creditodisponible += $disponible;

            $strciclo .= ($strciclo ? ',' : '') . $row->idcicloatencion;
        } 

        // return array('inValid' => true, 'mensaje' => 'XD', 'extra' => $dataciclo);

        $datacitaporasistir = [];
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);            
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($row->start_s > $start_s) {
                $datacitaporasistir[] = $row;
            }
        }

        $i = 0;
        $preciominimo = 0;    
        $costocero = false;    
        $presupuestosdetalles = $objPresupuesto->presupuestodetalle($param2);         
        foreach ($presupuestosdetalles as $index => $row) {

            $cantporrealizar = $row->cantcliente - $row->cantefectivo;

            if ($row->idproducto !== 23 && $cantporrealizar > 0) { //23:Aguja

                $precio = (float)($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo));

                if ($row->idproducto === 3) { //3:Acunpuntura
                    $precio = $precio + ( 6 * 1.5); //6 unidades de aguja
                }

                if($precio === (float) 0) {
                    $costocero = true;
                }

                if ($i === 0) {
                    $preciominimo = $precio;
                    $i++;
                }  

                if ($precio < $preciominimo) {
                    $preciominimo = $precio;
                }
            }
        }

        $texto = count($dataciclo) === 1 ? 'ciclo' : 'ciclos'; 

        if($costocero) {
            return array('inValid' => false, 'mensaje' => 'Tratamiento a costo cero', 'extra' => $preciominimo);
        }  

        if(count($datacitaporasistir) === 0) {
            return array('inValid' => false, 'mensaje' => 'Primera reserva', 'extra' => $datacitaporasistir);
        } 

        if($creditodisponible == 0) {
            return array('inValid' => true, 'mensaje' => 'No tiene saldo disponible en '.$texto.' '.$strciclo, 'extra' => $datacitaporasistir);
        }          

        // return array('inValid' => true, 'mensaje' => 'Testing '.$texto.' '.$strciclo, 'extra' => [$creditodisponible, $preciominimo, $datacitaporasistir]);
        
        $cociente = round($creditodisponible/$preciominimo);

        if($cociente < count($datacitaporasistir)) {
            return array('inValid' => true, 'mensaje' => 'Paciente tiene '.count($datacitaporasistir).' reserva(s) y saldo disponible de S/.' . $creditodisponible . ' en '.$texto.' '.$strciclo . '. Deberá tener mas saldo para seguir reservando.', 'extra' => $preciominimo);
        }
    } 

    public function update(Request $request, $enterprise, $id) {
 
        $empresa = empresa::where('url', '=', $enterprise)->first();
        $horariomedico = new horariomedico();

        $citaterapeutica = citaterapeutica::find($id);
        $entidad = entidad::find($request['citaterapeutica']['idpaciente']);
        $terapista = $entidad->entidad(['entidad.identidad' => $request['citaterapeutica']['idterapista']]);
        $sedehorario = $empresa->sedehorarios($citaterapeutica->idsede);

        $request = $request->all();
        $idempresa = $empresa->idempresa;
        
        
        //VALIDACIONES  
        /* 0.- Validar que cita no se encuentre atendido. 
         */  
        if ($citaterapeutica->idestado === 34) {
            return $this->crearRespuesta('Cita se encuentra atendido. No puede editarse.', [200, 'info']);
        }
        
        /* 1.- Validar que cita no sea mayor a los 15 dias, no se considera las horas.
         */
        $fechaMaxima = strtotime('+30 day', strtotime(date('Y-m-j')));
        $fechausuario = strtotime($this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd'));
        if ($fechausuario > $fechaMaxima) {
            return $this->crearRespuesta('Fecha de cita m&eacute;dica no debe ser mayor a +30 dias', [200, 'info']);
        }

        /* 1.1.- Validar que fecha y hora cita sea mayor a fecha y hora actual.
         */
        $a = date('Y-m-j H:i:s'); 
        $b = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd').' '.date('H:i:s');
        
        $fechahoraActual = strtotime($a);
        $fechahoraUsuario = strtotime($b);        
        
        if ($fechahoraUsuario < $fechahoraActual){
            return $this->crearRespuesta('Fecha y hora de cita debe ser mayor a fecha y hora actual.', [200, 'info'], '','', array($fechahoraActual, $fechahoraUsuario, $a, $b));
        }

        /* 2.- Validacion
         * Valida que la fecha y hora de horario a guardar, NO este en 'Dias feriados' o 'Dias laborables por hora' de Sede.
         * Para 'Dias feriados', se tomara lahora de inicio y fin laboral
         * Para 'Dias laborables por hora' se tomara como hora no habil, lo que no a seleccionado el usuario. 
         */
        $validation = ['inValid' => false, 'message' => ''];

        $data = array(
            'diasFeriados' => $empresa->diasferiados(['idempresa' => $idempresa]) 
        );

        $tiempoNohabil = $this->configurarFeriados($data, $empresa->laborinicio, $empresa->laborfin);

        $fechaIF = $this->fechaInicioFin($request['citaterapeutica']['fecha'], $request['citaterapeutica']['inicio'], $request['citaterapeutica']['fin']);
        $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

        if ($this->validarFeriado($tiempoNohabil, $start, $end)) {
            $validation['inValid'] = true;
            $validation['message'] = 'Horario no disponible. Feriado!';
        }
        
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $data);
        }
        /**/

        /* 3.- Validar que cita para el medico este disponible.
         *     Validar que cita no se trate del mismo paciente.
         *     Validar que cita pueda ser una interconsulta.
         */
        $ddmmyy = explode( '/', $request['citaterapeutica']['fecha']); 
        
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $citaterapeutica->idsede;
        //$param['citaterapeutica.idterapista'] = $request['citaterapeutica']['idterapista'];
        $param['citaterapeutica.fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');

        $param2 = [];
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $citaterapeutica->idsede;
        $param2['horariomedico.idmedico'] = $request['citaterapeutica']['idterapista'];
        $param2['horariomedico.fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');
        $param2['perfil.idsuperperfil'] = 4;
        
        $param3 = [];
        $param3['turnoterapia.idempresa'] = $idempresa;
        $param3['turnoterapia.idsede'] = $citaterapeutica->idsede;                      
        $param3['turnoterapia.dia'] = date('N', mktime(0, 0, 0, (int)$ddmmyy[1], (int)$ddmmyy[0], (int)$ddmmyy[2])); //php date('N')(Lu=1,...,Do=7)
         
        
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', [32, 33, 34]);
        $datahorario = $horariomedico->grid($param2);        
        // $turnos = $empresa->turnosterapeuticas($param3);
        
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }
        foreach ($datahorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }
        
        // foreach ($turnos as $row) { 
        //     $fechaIF = $this->fechaInicioFin($request['citaterapeutica']['fecha'], $row->inicio, $row->fin);
        //     $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        //     $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        // } 
        
        $fechaIF = $this->fechaInicioFin($request['citaterapeutica']['fecha'], $request['citaterapeutica']['inicio'], $request['citaterapeutica']['fin']);
        $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

        //return $this->crearRespuesta('doctor: '.$request['citaterapeutica']['idterapista'], [200, 'info'],'','',$datahorario);
        $validation = ['inValid' => false, 'message' => ''];
        if (empty($datahorario)) {
            $validation['inValid'] = true;
            $validation['message'] = 'Cita est&aacute; fuera del horario del terapista';
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        }
         
        $validation = ['inValid' => true, 'message' => 'Cita est&aacute; fuera del horario del terapista'];
        foreach ($datahorario as $row) {
            if ($row->start_s <= $start && $row->end_s >= $end) {
                //Cita esta dentro del horario del medico
                $validation['inValid'] = false;
                $validation['message'] = ''; 
                break;
            }
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        }
                
        // $validation = ['inValid' => true, 'message' => 'Cita est&aacute; fuera de un turno v&aacute;lido'];
        // foreach ($turnos as $row) {
        //     if($row->start_s === $start && $row->end_s <= $end){
        //         $validation['inValid'] = false;
        //         break;
        //     }
        // }
        // if ($validation['inValid']) {
        //     return $this->crearRespuesta($validation['message'], [200, 'info']);
        // }
        
        $validation = ['inValid' => false, 'message' => ''];
        $num = 0;
        foreach ($datacita as $row) {
            if ($request['citaterapeutica']['idcitaterapeutica'] !== $row->idcitaterapeutica && $start === $row->start_s && $end === $row->end_s && $request['citaterapeutica']['idpaciente'] === $row->idpaciente) {
                $validation['inValid'] = true;
                $validation['message'] = 'Paciente ya tiene cita.';
                $num = $num + 1;
                //break;
            }
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $num);
        }
                
        $validar =  ($request['citaterapeutica']['fecha'] !== $this->formatFecha($citaterapeutica->fecha) || ($citaterapeutica->inicio !== $request['citaterapeutica']['inicio'] && $citaterapeutica->fin !== $request['citaterapeutica']['fin']))? true: false;
        $maxcamilla = array('identidad' => $terapista->identidad,'cantidadcamilla' => $sedehorario->cantidadcamilla, 'maxcamilla' => $terapista->maxcamilla);
        $validation = ['inValid' => false, 'message' => ''];         
        if ($validar && $this->validarDisponibilidad($datacita, $start, $end, $maxcamilla)) {
            $validation['inValid'] = true;
            $validation['message'] = 'M&aacute;x. de citas para terapista &Oacute; m&aacute;x. de camillas ocupadas.!';
        }
         
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        } 

        /* 4.- Validar que exista paciente */
        if (!$entidad) {
            return $this->crearRespuesta('No existe persona, registrarlo.', [200, 'info']);
        }

        /* 6.- Validar por número de aseguradoras */
        $aseguradoras = $empresa->aseguradoras($idempresa); 
        $nroreservas = 0;
        $nroagenda = null;
        foreach ($datacita as $row) {
            if ($request['citaterapeutica']['idcitaterapeutica'] !== $row->idcitaterapeutica && $start === $row->start_s && $end === $row->end_s && $request['citaterapeutica']['idaseguradora'] === $row->idaseguradora) {
                $nroreservas += 1;
            }
        }

        foreach ($aseguradoras as $row) {
            if($request['citaterapeutica']['idaseguradora'] === $row->idaseguradora) {
                $nroagenda = $row;
                break;
            }
        }
        
        // return $this->crearRespuesta('X', [200, 'info'], '', '', $request['citaterapeutica']['idaseguradora'] . '===' . $row->idaseguradora);
        if ($nroagenda && $nroagenda->nroagenda && ($nroreservas + 1) > $nroagenda->nroagenda) {
            return $this->crearRespuesta('Seguro "' . $nroagenda->nombre . '" ya tiene '. $nroreservas . ' reservaciones en el horario especificado.', [200, 'info']);
        }

        if ($terapista->breakinicio && $terapista->breakfin) {
            $fechaBreakIF = $this->fechaInicioFin($request['citaterapeutica']['fecha'], $terapista->breakinicio, $terapista->breakfin); 

            $startBreak = mktime((int) $fechaBreakIF['Hi'], (int) $fechaBreakIF['Mi'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);

            $endBreak = mktime((int) $fechaBreakIF['Hf'], (int) $fechaBreakIF['Mf'], 0, (int) $fechaBreakIF['m'], (int) $fechaBreakIF['d'], (int) $fechaBreakIF['y']);
 
            if (($startBreak >= $start && $startBreak <= $end) || ($endBreak >= $start && $endBreak <= $end) || ($startBreak < $start && $endBreak > $end)) {
                return $this->crearRespuesta("Refrigerio de personal de " . $this->transformHora($terapista->breakinicio) ." - ". $this->transformHora($terapista->breakfin), [200, 'info']);
            }
        }

        //FIN VALIDACIONES

        if ($citaterapeutica) {
                                
            $request['citaterapeutica']['fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');
            /* Campos auditores */
            $request['citaterapeutica']['updated_at'] = date('Y-m-d H:i:s');
            $request['citaterapeutica']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */ 


            \DB::beginTransaction();
            try {

                $citaterapeutica->grabarLog($id, $this->objTtoken->my, $request['citaterapeutica']);
                $citaterapeutica->fill($request['citaterapeutica']);

                //Graba en 2 tablaa(citaterapeutica, entidad)
                $citaterapeutica->save();
                if (isset($request['entidad'])) {
                    $entidad->fill($request['entidad']);
                    $entidad->save();
                }

                if (isset($request['llamadacliente']) && $request['llamadacliente'] === '1') {
                    $paramCall = array(
                        'idempresa' => $idempresa, 
                        'idcitaterapeutica' => $citaterapeutica->idcitaterapeutica,
                        'fecha' => date('Y-m-d'),
                        'hora' => date('H:i:s'),
                        'cliente' => $entidad->entidad,
                        'motivo' => 'Reprogramación',
                        'tipo' => 'Reprogramación - Terapia',                        
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my
                    );
                    calls::create($paramCall);   
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita terapeuta para "' . $entidad->entidad . '" ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una cita terapista', 404);
    }

    public function destroy($enterprise, $id) {

        $citaterapeutica = citaterapeutica::find($id);

        //VALIDACIONES

        if ($citaterapeutica) {
            \DB::beginTransaction();
            try {
                //Graba en 1 tablaa(citaterapeutica)                 
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $citaterapeutica->fill($auditoria);
                $citaterapeutica->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita terapeuta a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Cita terapeuta no encotrado', 404);
    }

    public function destroyreservacion($enterprise, $id) {

        $citaterapeutica = citaterapeutica::find($id);

        //VALIDACIONES

        if ($citaterapeutica) {
            \DB::beginTransaction();
            try {
                //Graba en 1 tablaa(citaterapeutica)                 
                $param = [
                    'idestado' => 88, //88:disponible
                    'idpaciente' => null, 
                    'reservaportal' => 0,
                    'updated_at' => date('Y-m-d H:i:s'), 
                    'id_updated_at' => $this->objTtoken->my 
                ];
                $citaterapeutica->fill($param);
                $citaterapeutica->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita terapeuta a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Cita terapeuta no encotrado', 404);
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

}