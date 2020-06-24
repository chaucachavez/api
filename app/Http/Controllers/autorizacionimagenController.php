<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\galeria;
use App\Models\autorizacionimagen;

class autorizacionimagenController extends Controller {

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\img_autorizaciones\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/img_autorizaciones/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/img_autorizaciones/';

    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();
 
        $autorizacionimagen = new autorizacionimagen();

        $param = array(); 
        $param['autorizacionimagen.idcicloautorizacion'] = $paramsTMP['idcicloautorizacion'];
           
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'autorizacionimagen.orden';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'asc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $data = $autorizacionimagen->grid($param, $pageSize, $orderName, $orderSort, []);

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
        $objAutorizacionimagen = new autorizacionimagen();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $autorizacionimagen = $objAutorizacionimagen->autorizacionimagen($id);

        if ($autorizacionimagen) {  

            return $this->crearRespuesta($autorizacionimagen, 200);
        }

        return $this->crearRespuestaError('Galería no encotrado', 404);
    }


    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
  
        $request['autorizacionimagen']['created_at'] = date('Y-m-d H:i:s');
        $request['autorizacionimagen']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {           
            $autorizacionimagen = autorizacionimagen::create($request['autorizacionimagen']);   
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Imagen ha sido guardado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
      
        $autorizacionimagen = autorizacionimagen::find($id);

        if ($autorizacionimagen) {
            $request = $request->all();
 
            $request['autorizacionimagen']['updated_at'] = date('Y-m-d H:i:s');
            $request['autorizacionimagen']['id_updated_at'] = $this->objTtoken->my;
            
            $autorizacionimagen->fill($request['autorizacionimagen']);

            \DB::beginTransaction();
            try {                              
                $autorizacionimagen->save(); 
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

        $autorizacionimagen = autorizacionimagen::find($id);

        if ($autorizacionimagen) {  
                         
            \DB::beginTransaction();
            try {   

                if (file_exists($this->pathImg . $autorizacionimagen->nombre)) {
                    if (unlink($this->pathImg . $autorizacionimagen->nombre)) {  
                        //Eliminada
                    }
                }

                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];

                $autorizacionimagen->fill($auditoria);
                $autorizacionimagen->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Imagen a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Imagen no encotrado', 404);
    }

}
