<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\sede;
use App\Models\proceso; 

class procesoController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) { 

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'automatizacion.idempresa' => $idempresa
        ); 

        $data = array(
            'automatizaciones' => $empresa->automatizaciones($param)
        ); 

        return $this->crearRespuesta($data, 200); 
    }
    


    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $proceso = new proceso();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['proceso.idempresa'] = $idempresa;
        
        if (isset($paramsTMP['idautomatizacion']) && !empty(trim($paramsTMP['idautomatizacion']))) {
            $param['proceso.idautomatizacion'] = trim($paramsTMP['idautomatizacion']);
        }  
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'proceso.idproceso';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'asc';
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
                        
        $data = $proceso->grid($param, $between, $like, $pageSize, $orderName, $orderSort);
 
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }
                        
        return $this->crearRespuesta($data, 200, $total); 
    } 

    public function show(Request $request, $enterprise, $id) {

        $objProceso = new proceso();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $proceso = $objProceso->proceso($id);

        $params = $request->all();

        if ($proceso) {

            $listcombox = []; 
 
            return $this->crearRespuesta($proceso, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Proceso no encotrado', 404);
    }

    public function update(Request $request, $enterprise, $id) {

        $proceso = proceso::find($id);

        if ($proceso) {
            $request = $request->all();
            $request['updated_at'] = date('Y-m-d H:i:s');  
            $request['id_updated_at'] = $this->objTtoken->my;  

            $proceso->fill($request);
            $proceso->save();

            return $this->crearRespuesta('El proceso ' . $proceso->nombre . ' ha sido editado. ', 200, '', '', $request);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un proceso', 404);
    }
    
}
