<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\galeria;
use App\Models\citamedicaarchivo;

class citamedicaarchivoController extends Controller {

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\adjuntos_citasmedicas\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/adjuntos_citasmedicas/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/adjuntos_citasmedicas/';

    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();
        
        $citamedicaarchivo = new citamedicaarchivo();

        $param = array(); 
        $param['citamedicaarchivo.idcitamedica'] = $paramsTMP['idcitamedica'];

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'citamedicaarchivo.idcitamedicaarchivo';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'asc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $data = $citamedicaarchivo->grid($param, $pageSize, $orderName, $orderSort, []);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        return $this->crearRespuesta($data, 200, $total);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
  
        $request['citamedicaarchivo']['created_at'] = date('Y-m-d H:i:s');
        $request['citamedicaarchivo']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {           
            $citamedicaarchivo = citamedicaarchivo::create($request['citamedicaarchivo']);   
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Imagen ha sido guardado.', 201);
    }
    
    public function destroy($enterprise, $id) {

        $citamedicaarchivo = citamedicaarchivo::find($id);

        if ($citamedicaarchivo) {  
                         
            \DB::beginTransaction();
            try {   

                if (file_exists($this->pathImg . $citamedicaarchivo->nombre)) {
                    if (unlink($this->pathImg . $citamedicaarchivo->nombre)) {  
                        //Eliminada
                    }
                }

                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];

                $citamedicaarchivo->fill($auditoria);
                $citamedicaarchivo->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Imagen a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Imagen no encotrado', 404);
    }

}
