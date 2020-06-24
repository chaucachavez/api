<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\etiqueta;

class etiquetaController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $etiqueta = new etiqueta();

        $param = array();
        $param['etiqueta.idempresa'] = $empresa->idempresa($enterprise);
           
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'etiqueta.nombre';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';
        $data = $etiqueta->grid($param, $like, $pageSize, $orderName, $orderSort, []);

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
        $objEtiqueta = new etiqueta();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $etiqueta = $objEtiqueta->etiqueta($id);

        if ($etiqueta) {  

            return $this->crearRespuesta($etiqueta, 200);
        }

        return $this->crearRespuestaError('Publicación no encotrado', 404);
    }


    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['etiqueta']['idempresa'] = $idempresa; 
        $request['etiqueta']['created_at'] = date('Y-m-d H:i:s');
        $request['etiqueta']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {           
            $etiqueta = etiqueta::create($request['etiqueta']);   
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Etiqueta ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $etiqueta = etiqueta::find($id);

        if ($etiqueta) {
            $request = $request->all();
 
            $request['etiqueta']['updated_at'] = date('Y-m-d H:i:s');
            $request['etiqueta']['id_updated_at'] = $this->objTtoken->my;
            
            $etiqueta->fill($request['etiqueta']);

            \DB::beginTransaction();
            try {                              
                $etiqueta->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Etiqueta ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un publicación', 404);
    }
    
    public function destroy($enterprise, $id) {

        $etiqueta = etiqueta::find($id);

        if ($etiqueta) {  
             
            $data = \DB::table('etiqueta_publicacion')                    
                    ->where('etiqueta_publicacion.idetiqueta', $id)
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('Etiqueta está presente en Informes', [200, 'info']);
            }

            $data = \DB::table('entidad_etiqueta')
                    ->where('idetiqueta', $id)
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('Etiqueta está presente en personal ', [200, 'info']);
            }

            \DB::beginTransaction();
            try {  
 
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];

                $etiqueta->fill($auditoria);
                $etiqueta->save();

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Etiqueta a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Etiqueta no encotrado', 404);
    }

}
