<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\categoria; 

class categoriaController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $categoria = new categoria();

        $param = array();
        $param['categoria.idempresa'] = $empresa->idempresa($enterprise); 
        
        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'categoria.nombre';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;        
        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : ''; 

        $datacategoria = $categoria->grid($param, $like, $pageSize, $orderName, $orderSort);
 
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacategoria->total();
            $data = $datacategoria->items();
        }

        return $this->crearRespuestaError('Categoria no encontrado', 404);
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['categoria']['idempresa'] = $idempresa; 

        //VALIDACIONES  
 
        //Graba en 1 tablaa(producto)            
        $categoria = categoria::create($request['categoria']);  

        return $this->crearRespuesta('"' . $categoria->nombre . '" ha sido creado.', 201, '', '', $categoria->idcategoria);
    }

    public function destroy($enterprise, $id) {

        $categoria = categoria::find($id);

        if ($categoria) {

            $return = $categoria->validadorDataRelacionada($id);
            if ($return['validator']) {
                return $this->crearRespuesta($return['message'], [200, 'info']);
            }

            \DB::beginTransaction();
            try { 
                $categoria->delete();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Categoria "' . $categoria->nombre . '" a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Categoria no encotrado', 404);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise);

        $categoria = categoria::find($id);

        if ($categoria) {
            $request = $request->all(); 

            $producto->fill($request['categoria']);

            \DB::beginTransaction();
            try {                
                $categoria->save();                                
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Categoria "' . $categoria->nombre . '" ha sido editado. ', 200,'','',$idtarifario);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una categoria', 404);
    } 
}
