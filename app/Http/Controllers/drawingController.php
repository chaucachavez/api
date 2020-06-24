<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\llamada;  
use App\Models\horariomedico; 
use App\Models\citamedica; 
use App\Models\cicloatencion; 

class drawingController extends Controller {
    
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
             
    public function dashboard(Request $request, $enterprise) {        
        $empresa = new empresa();        
        $llamada = new llamada();
        $horariomedico = new horariomedico();
        $citamedica = new citamedica();
        $cicloatencion = new cicloatencion();

        $request = $request->all();         
        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = [];
        $param['llamada.idempresa'] = $idempresa; 
        $param['llamadadet.tipo'] = 'entrante';         

        $i = 0; 
        $fecha = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
        do {  
            $tiempo[$i] = date ( 'd/m/Y' , strtotime ( '+'.$i.' day' , strtotime ( $fecha ) ) ); 
            $i++;
        } while ($tiempo[$i -1] !== $request['hasta']);
        
        $between = [];
        if (isset($request['desde']) && isset($request['hasta']) && !empty($request['desde']) && !empty($request['hasta'])) {
            $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
            $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');
            $between = [$request['desde'], $request['hasta']]; 
        } 
           
        $param2 = array('horariomedico.idempresa' => $idempresa,'perfil.idsuperperfil' => 3);  
        $param3 = array('citamedica.idempresa' => $idempresa); 
        $param4 = array('cicloatencion.idempresa' => $idempresa); 
        if (isset($request['idsede']) && !empty($request['idsede'])) {
            $param2['horariomedico.idsede'] = $request['idsede'];
            $param3['citamedica.idsede'] = $request['idsede'];
            $param4['cicloatencion.idsede'] = $request['idsede'];
        }
         
        /* dataperdidacontestada */  
        $fields = ['llamadadet.fecha', 'llamadadet.estado', 'llamadadet.idanexo'];
        $fieldbetween = 'llamadadet.fecha'; 
        $callcenter = $empresa->callcenter(['callcenter.idempresa' => $idempresa, 'callcenter.idcallcenter' => 1]); 
        $betweenHour = []; 

        if (!empty($callcenter->inicio) && !empty($callcenter->fin)) 
            $betweenHour = [$callcenter->inicio, $callcenter->fin];

        $anexos = $empresa->anexos(['anexo.idempresa'=> $idempresa, 'anexo.activo' => '1']);
        $whereInAnexo = [];
        foreach($anexos as $row){
            $whereInAnexo[] = $row->idanexo;
        } 

        $quiebre = array('fecha' => 'fecha');               
        $gruposProducto = ['estado', array('Perdida'=>'Perdida','Contestada'=>'Contestada') ];        
        $dataCitas = $llamada->llamadadet($param, $between, $fields, $fieldbetween, $betweenHour, $whereInAnexo, true);     
        $dataperdidacontestada = $this->agruparPorColumna($dataCitas, '', $quiebre, '', $gruposProducto); 

        /* dataperdidacontestadaanexo */ 
        $param['estado'] = 'Contestada'; 
        $quiebre = array('fecha' => 'fecha');   
        $gruposProducto = ['idanexo', array('1,7'=>'MIRAFLORES','5,10'=>'CHACARILLA','2,6'=>'OLIVOS','3,4'=>'BENAVIDES','14'=>'OFICINA CALL 1','19'=>'OFICINA CALL 2', '9'=>'-') ];   
        // $dataCitas = $llamada->llamadadet($param, $between, $fields, $fieldbetween, $betweenHour, $whereInAnexo, true);     
        $dataperdidacontestadaanexo = []; //$this->agruparPorColumna($dataCitas, '', $quiebre, '', $gruposProducto);     
         
        /*  */
        $matrizhorario = $horariomedico->grid($param2, $between);
        $dataCitas = $citamedica->grid($param3, $between, '', '', '', '', [4, 5, 6, 48]);
        $sedehorario = $empresa->sedehorarios(1);

        $datacitasestado = $this->datacitasestado($matrizhorario, $dataCitas, $sedehorario, $tiempo);
        $TmpDisponibilidad = $datacitasestado[0];
        $TmpAgendadas = $datacitasestado[1]; 
        $datacitaagendadisponible = [];
        foreach ($TmpDisponibilidad as $key => $row) {
            if ($key !== 'Indicador' && $key !== 'Total' && $key !== 'Porcentaje') { 
                $datacitaagendadisponible[] = array(
                    'idquiebre' => $key,
                    'quiebre' => $key,
                    'idgrupo' => 'A',
                    'grupo' => 'Agendadas',
                    'cantidad' => $TmpAgendadas[$key] 
                );
                $datacitaagendadisponible[] = array(
                    'idquiebre' =>$key,
                    'quiebre' => $key,
                    'idgrupo' => 'D',
                    'grupo' => 'Disponibilidad',
                    'cantidad' => $TmpDisponibilidad[$key] 
                );
            }
        }
        $datacitaagendadisponible = $this->acumulativo($datacitaagendadisponible); 
        
        /* datacitaagendaatendida */ 
        $quiebre = array('fecha' => 'fecha');               
        $gruposProducto = ['idestado', array('4,5,6,48'=>'Agendadas','6'=>'Atendidas') ];        
        $dataCitas = $citamedica->grid($param3, $between, '', '', 'citamedica.fecha', 'asc', [4, 5, 6, 48]);        
        $datacitaagendaatendida = $this->agruparPorColumna($dataCitas, '', $quiebre, '', $gruposProducto);         
        $datacitaagendaatendida = $this->acumulativo($datacitaagendaatendida); 

        
        /* datacitapagadasxdia */ 
        $quiebre = array('fecha' => 'fecha');               
        $gruposProducto = ['idestadopago', array('66,67,68'=>'Atendidas','67,68'=>'Pagadas') ];        
        $dataCitas = $citamedica->citasatendidas($param3, $between, [4, 5, 6, 48]);          
        $datacitapagadasxdia = $this->agruparPorColumna($dataCitas, '', $quiebre, '', $gruposProducto);               
        $datacitapagadasxdia = $this->acumulativo($datacitapagadasxdia); 

        /* datacitaasistencia */ 
        $quiebre = array('id_created_at' => 'created'); 
        $campoextra = array('id_created_at' => 'created');             
        $gruposProducto = ['idestado', array('6'=>'ASISTIO','*'=>'NO ASISTIO') ];
        $dataCitas = $citamedica->grid($param3, $between, '', '', '', '', [4, 5, 6, 48]);
        $datacitaasistencia = $this->agruparPorColumna($dataCitas, '', $quiebre, $campoextra, $gruposProducto, ['cantidad'], false, false, true); 
        
        /* datapresupuestos */ 
        $quiebre = array('id_created_at' => 'created'); 
        $campoextra = array('id_created_at' => 'created');              
        $gruposProducto = ['idestadopago', array('66'=>'SIN PAGO','*'=>'CON PAGO') ];        
        $dataCiclos = $cicloatencion->grid($param4, '', $between, '', '', '', TRUE, ['cicloatencion.id_created_at', 'created.entidad as created', 'presupuesto.idestadopago']);
        $datapresupuestos = $this->agruparPorColumna($dataCiclos, '', $quiebre, $campoextra, $gruposProducto, ['cantidad'], false, false, true);

        /* datacitapagadas */ 
        $quiebre = array('idmedico' => 'medico'); 
        $campoextra = array('idmedico' => 'medico');              
        $gruposProducto = ['idestadopago', array('66'=>'SIN PAGO','*'=>'CON PAGO') ];        
        $dataCitas = $citamedica->citasatendidas($param3, $between, [4, 5, 6, 48]);
        $datacitapagadas = $this->agruparPorColumna($dataCitas, '', $quiebre, $campoextra, $gruposProducto, ['cantidad'], false, false, true);
            
        return $this->crearRespuesta([ 
            'dataperdidacontestada' => $dataperdidacontestada,
            'dataperdidacontestadaanexo' => $dataperdidacontestadaanexo,
            'datacitaagendadisponible' => $datacitaagendadisponible, 
            'datacitaasistencia' => $datacitaasistencia,
            'datapresupuestos' => $datapresupuestos,
            'datacitapagadas' => $datacitapagadas,
            'datacitapagadasxdia' => $datacitapagadasxdia,
            'datacitaagendaatendida' => $datacitaagendaatendida
            ], 200);  
    }

    private function dataestado($dataEstado, $tiempo) {
        $matrizEstado = []; 
        $totalEstado = 0;
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

        foreach ($matrizEstado as $pk => $row){
            $matrizEstado[$pk]['Porcentaje'] = round($row['Total'] / $totalEstado * 100);
        }

        $dataEstado = array();
        foreach($matrizEstado as $row){ 
            $dataEstado[] = $row;
        } 
                
        return  $dataEstado = $this->ordenarMultidimension($dataEstado, 'Total', SORT_DESC); 
    }

    private function datacitasestado($matrizhorario, $dataCitas, $sedehorario, $tiempo) {
        $matrizDisp = []; 
        $matrizDisp['disponibilidad'] = array('Indicador' => 'Disponibilidad', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['agendada'] = array('Indicador' => 'Agendadas', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['atendida'] = array('Indicador' => 'Atendidas', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['libre'] = array('Indicador' => 'Libres', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['pagopresupuesto'] = array('Indicador' => 'Total/Acuenta de presupuesto', 'Total' => 0, 'Porcentaje' => 0);

        foreach ($tiempo as $time) {
            $matrizDisp['disponibilidad'][$time] = 0;
            $matrizDisp['agendada'][$time] = 0;
            $matrizDisp['atendida'][$time] = 0;
            $matrizDisp['libre'][$time] = 0;
            $matrizDisp['pagopresupuesto'][$time] = 0;
        } 

        foreach ($dataCitas as $row) {
                     
            $Indicador = $row->fecha; //$this->formatFecha($row->fecha);      
            
            $row->idestado = ($row->idestado === 4 || $row->idestado === 5) ? 5 : $row->idestado;

            $matrizDisp['agendada'][$Indicador] = $matrizDisp['agendada'][$Indicador] + 1;
            $matrizDisp['agendada']['Total'] = $matrizDisp['agendada']['Total'] + 1;

            if ($row->idestado === 6) { 
                $matrizDisp['atendida'][$Indicador] = $matrizDisp['atendida'][$Indicador] + 1;
                $matrizDisp['atendida']['Total'] = $matrizDisp['atendida']['Total'] + 1;
                
                if(!empty($row->presupuesto)){                    
                    $matrizDisp['pagopresupuesto'][$Indicador] += + 1;
                    $matrizDisp['pagopresupuesto']['Total'] += + 1;               
                }
            }
        } 

        /* TRUE: 30 CONSULTAS en 4 HORAS(Papá de Andres)
         * FALSE: 4 CONSULTAS en 1 HORAS(Default, recomendable)
         */  
        $opcion = false; 
        foreach ($matrizhorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($opcion) {
                /* Obtiene las horas y lo multiplica por el factor
                 * Anado 60 s porque es equivalente a 1 min. y obtengo por ejemplo 4 horas
                 * fACTOR 7.4 = 30 CONSULTAS en 4 HORAS
                 */
                $row->disponibles = ceil((($row->end_s + 60 - $row->start_s ) / 3600) * 7.5);
                $matrizDisp['disponibilidad'][$row->fecha] = $matrizDisp['disponibilidad'][$row->fecha] + $row->disponibles;
                $matrizDisp['disponibilidad']['Total'] = $matrizDisp['disponibilidad']['Total'] + $row->disponibles;
            }
        }
        
        if (!$opcion) {
            $interconsultas = $this->configurarInterconsultas($matrizhorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta));
            foreach ($interconsultas as $row) {
                $fecha = explode(' ', $row['inicio'])[0];
                $matrizDisp['disponibilidad'][$fecha] = $matrizDisp['disponibilidad'][$fecha] + 1;
                $matrizDisp['disponibilidad']['Total'] = $matrizDisp['disponibilidad']['Total'] + 1;
            }
        } 

        $acum = 0;
        foreach ($matrizDisp['libre'] as $key => $row) {
            if ($key !== 'Indicador' && $key !== 'Total') {
                $resta = $matrizDisp['disponibilidad'][$key] - $matrizDisp['agendada'][$key];
                $matrizDisp['libre'][$key] = $resta > 0 ? $resta : 0;
                $acum = $acum + ($resta > 0 ? $resta : 0 );
            }
        }
        $matrizDisp['libre']['Total'] = $acum;

        foreach ($matrizDisp as $key => $row) {
            if ($key === 'atendida') {
                if ($matrizDisp['agendada']['Total'] > 0)
                    $matrizDisp[$key]['Porcentaje'] = round(($matrizDisp[$key]['Total'] / $matrizDisp['agendada']['Total']) * 100, 0);
            } else if ($key === 'pagopresupuesto') {
                if ($matrizDisp['atendida']['Total'] > 0)
                    $matrizDisp[$key]['Porcentaje'] = round(($matrizDisp[$key]['Total'] / $matrizDisp['atendida']['Total']) * 100, 0);                
            } else {
                if ($matrizDisp['disponibilidad']['Total'] > 0)
                    $matrizDisp[$key]['Porcentaje'] = round(($matrizDisp[$key]['Total'] / $matrizDisp['disponibilidad']['Total']) * 100, 0);
            }
        }
        
        $matrizDisponibilidad = array();
        foreach ($matrizDisp as $key => $row) {
            $matrizDisponibilidad[] = $row;
        }  
        
        return $matrizDisponibilidad;
    }   
    
}
