<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\galeria; 

class galeriaController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $galeria = new galeria();

        $param = array();
        $param['galeria.idempresa'] = $empresa->idempresa($enterprise);
        $param['galeria.identidad'] = $paramsTMP['idpersonal'];
           
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'galeria.nombre';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';
        $data = $galeria->grid($param, $like, $pageSize, $orderName, $orderSort, []);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        return $this->crearRespuesta($data, 200, $total);
    }  
 

    public function show($enterprise, $id) {

        $empresa = new empresa(); 
        $entidad = new entidad();
        $objGaleria = new galeria();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $galeria = $objGaleria->galeria($id);

        if ($galeria) {  

            return $this->crearRespuesta($galeria, 200);
        }

        return $this->crearRespuestaError('Galería no encotrado', 404);
    }


    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['galeria']['idempresa'] = $idempresa; 
        $request['galeria']['identidad'] = $this->objTtoken->my;
        $request['galeria']['created_at'] = date('Y-m-d H:i:s');
        $request['galeria']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {           
            $galeria = galeria::create($request['galeria']);   
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Imagen ha sido guardado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $galeria = galeria::find($id);

        if ($galeria) {
            $request = $request->all();
 
            $request['galeria']['updated_at'] = date('Y-m-d H:i:s');
            $request['galeria']['id_updated_at'] = $this->objTtoken->my;
            
            $galeria->fill($request['galeria']);

            \DB::beginTransaction();
            try {                              
                $galeria->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Imagen ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un Imagen', 404);
    }
    
    public function destroy($enterprise, $id) {

        $galeria = galeria::find($id);

        if ($galeria) {  
                        

            \DB::beginTransaction();
            try {  
 
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];

                $galeria->fill($auditoria);
                $galeria->save();

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Imagen a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Imagen no encotrado', 404);
    }

}
