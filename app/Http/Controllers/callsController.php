<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\calls;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Exports\DataExport;
use Illuminate\Http\Request; 


class callsController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $calls = new calls();

        $param = array();
        $param['calls.idempresa'] = $empresa->idempresa($enterprise);
        
        if (isset($paramsTMP['identidad']) && !empty($paramsTMP['identidad'])) {
            $param['calls.id_created_at'] = $paramsTMP['identidad'];
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
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'calls.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $like = !empty($paramsTMP['likecliente']) ? trim($paramsTMP['likecliente']) : '';
        $datacall = $calls->grid($param, $between, $like, $pageSize, $orderName, $orderSort, []);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacall->total();
            $datacall = $datacall->items();
        }

        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) { 
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){ 
                $data = array(); 
                $i = 0;
                foreach($datacall as $row){

                    $paciente = '';
                    $fecha = '';
                    $hora = '';
                    $sede = '';

                    if ($row->idcitamedica || $row->idcitaterapeutica) {
                        $paciente = $row->pacientecita . $row->pacienteterapia;
                        $fecha = $row->fechacita . $row->fechaterapia;
                        $hora = $row->iniciocita . $row->inicioterapia;
                        $sede = $row->sedecita . $row->sedeterapia;
                    }

                    $data[$i] = array(
                        'FECHA' => $row->fecha, 
                        'HORA' => $row->hora,
                        'REGISTRADO POR' => $row->created, 
                        'CLIENTE' => $row->cliente,
                        'MOTIVO' => $row->motivo,
                        'RESERVACION / REPROGRAMACION' => $row->tipo,
                        'PACIENTE CITA' => $paciente,
                        'FECHA CITA' => $fecha,
                        'HORA CITA' => $hora, 
                        'SEDE CITA' => $sede 
                    );
                    $i++;
                }  
            
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($datacall, 200, $total);
        }  
    }  
 

    public function show($enterprise, $id) {

        $empresa = new empresa();  
        $objCalls = new calls();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $calls = $objCalls->calls($id);

        if ($calls) {  

            return $this->crearRespuesta($calls, 200);
        }

        return $this->crearRespuestaError('Llamada no encotrado', 404);
    }


    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['calls']['fecha'] = $this->formatFecha($request['calls']['fecha'], 'yyyy-mm-dd');
        $request['calls']['idempresa'] = $idempresa; 
        $request['calls']['created_at'] = date('Y-m-d H:i:s');
        $request['calls']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {
            $calls = calls::create($request['calls']);   
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Llamada ha sido registrado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $calls = calls::find($id);

        if ($calls) {
            $request = $request->all();
        
            $request['calls']['fecha'] = $this->formatFecha($request['calls']['fecha'], 'yyyy-mm-dd');
            $request['calls']['updated_at'] = date('Y-m-d H:i:s');
            $request['calls']['id_updated_at'] = $this->objTtoken->my;
            
            $calls->fill($request['calls']);

            \DB::beginTransaction();
            try {                              
                $calls->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Llamada ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una llamada', 404);
    }
    
    public function destroy($enterprise, $id) {

        $calls = calls::find($id);

        if ($calls) {  

            \DB::beginTransaction();
            try {  
 
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];

                $calls->fill($auditoria);
                $calls->save();

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Llamada a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Llamada no encotrado', 404);
    }

}
