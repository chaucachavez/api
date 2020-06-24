<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\especialidad; 

class especialidadController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {
 
        $empresa = new empresa();
        $especialidad = new especialidad();

        $idempresa = $empresa->idempresa($enterprise);
        
        $param = array(); 
        $param['especialidad.idempresa'] = $idempresa;
        
        $orderName = !empty($request['orderName']) ? $request['orderName'] : 'especialidad.nombre';
        $orderSort = !empty($request['orderSort']) ? $request['orderSort'] : 'ASC';
        $pageSize = !empty($request['pageSize']) ? $request['pageSize'] : 25;
         
         
        $data = $especialidad->grid($param, '', $pageSize, $orderName, $orderSort);

        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total());
        }

        return $this->crearRespuestaError('Especialidad no encontrado', 404);
    }
     
}
