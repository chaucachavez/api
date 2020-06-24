<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\diagnostico; 

class diagnosticoController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $diagnostico = new diagnostico();

        $param = array();
        $param['diagnostico.idempresa'] = $empresa->idempresa($enterprise); 
        
        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'diagnostico.nombre';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        
        $likecodigo = '';
        if (isset($paramsTMP['likecodigo']) && !empty($paramsTMP['likecodigo'])) {
            $likecodigo = $paramsTMP['likecodigo'];
        }
        
        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';
        $data = $diagnostico->grid($param, $like, $pageSize, $orderName, $orderSort, [], $likecodigo);

        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total());
        }

        return $this->crearRespuestaError('Diagnostico no encontrado', 404);
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['diagnostico']['idempresa'] = $idempresa;
        $request['diagnostico']['activo'] = 1;

        //VALIDACIONES 
        $codigo = $request['diagnostico']['codigo'];
        $diagnostico = diagnostico::where(['idempresa' => $idempresa, 'codigo' => $codigo])->first();
        if ($diagnostico) {
            return $this->crearRespuesta('No puede registrarse, el c&oacute;digo "' . $codigo . '" ya existe. Pertenece a ' . $diagnostico->nombre, [200, 'info']);
        } 
 
        //Graba en 1 tablaa(producto)            
        $diagnostico = diagnostico::create($request['diagnostico']);  

        return $this->crearRespuesta('"' . $diagnostico->nombre . '" ha sido creado.', 201, '', '', $diagnostico->iddiagnostico);
    }
}
