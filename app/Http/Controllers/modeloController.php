<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\sede; 
use App\Models\modelo;
use App\Models\arbol; 
use Excel;

class modeloController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $modelo = new modelo();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        
        $param['modelo.idempresa'] = $idempresa;

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'modelo.orden';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        $data = $modelo->grid($param, $like, $pageSize, $orderName, $orderSort); 

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        return $this->crearRespuesta($data, 200, $total);
    }            
    
    public function show(Request $request, $enterprise, $id) {

        $objModelo = new modelo();
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise);  
         
        $modelo = $objModelo->modelo(array('modelo.idmodelo' =>$id));

        if ($modelo) {
            $listcombox = array(
                'planes' => $objModelo->planes(['modeloseguro.idmodelo' => $id]),
                'aseguradoras' => $empresa->aseguradoras($idempresa),
                'aseguradorasplanes' => $empresa->aseguradorasplanes($idempresa),
                'modelodet' => $objModelo->modelodet(['modelodet.idmodelo' => $id]),
            );

            return $this->crearRespuesta($modelo, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Modelo no encotrado', 404);
    }
    
    public function newmodelo(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        $listcombox = array(
            'aseguradoras' => $empresa->aseguradoras($idempresa),
            'aseguradorasplanes' => $empresa->aseguradorasplanes($idempresa)
        );

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $request = $request->all(); 
        $idempresa = $empresa->idempresa($enterprise); 

        /*Campos auditores*/
        $request['modelo']['idempresa'] = $idempresa;
        $request['modelo']['created_at'] = date('Y-m-d H:i:s');
        $request['modelo']['id_created_at'] = $this->objTtoken->my;
        /*Campos auditores*/        

        \DB::beginTransaction();
        try {      
            $modelo = modelo::create($request['modelo']);

            if (isset($request['planes'])) {
                $dataPlanes = [];
                foreach ($request['planes'] as $row) {
                    $dataPlanes[] = ['idmodelo' => $modelo->idmodelo, 'idaseguradoraplan' => $row['idaseguradoraplan']];
                }
                $modelo->GrabarPlanes($dataPlanes, $modelo->idmodelo);
            }

            if (isset($request['modelodet'])) {
                $dataModelodet = [];
                foreach ($request['modelodet'] as $row) {
                    $dataModelodet[] = ['idmodelo' => $modelo->idmodelo, 'idproducto' => $row['idproducto'], 'codigo' => $row['codigo'], 'descripcion' => $row['descripcion'], 'cantidad' => $row['cantidad'], 'precio' => $row['precio']];
                }
                $modelo->GrabarModelodet($dataModelodet, $modelo->idmodelo);
            }

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();


        return $this->crearRespuesta('Modelo ha sido creado.', 201);
    }
     
    public function update(Request $request, $enterprise, $id) {

        $modelo = modelo::find($id);
        
        $request = $request->all();

        if ($modelo) {
 
            \DB::beginTransaction();
            try {
                $modelo->fill($request['modelo']);
                $modelo->save();

                if (isset($request['planes'])) {
                    $dataPlanes = [];
                    foreach ($request['planes'] as $row) {
                        $dataPlanes[] = ['idmodelo' => $modelo->idmodelo, 'idaseguradoraplan' => $row['idaseguradoraplan']];
                    }
                    $modelo->GrabarPlanes($dataPlanes, $modelo->idmodelo);
                }

                if (isset($request['modelodet'])) {
                    $dataModelodet = [];
                    foreach ($request['modelodet'] as $row) {
                        $dataModelodet[] = ['idmodelo' => $modelo->idmodelo, 'idproducto' => $row['idproducto'], 'codigo' => $row['codigo'], 'descripcion' => $row['descripcion'], 'cantidad' => $row['cantidad'], 'precio' => $row['precio']];
                    }
                    $modelo->GrabarModelodet($dataModelodet, $modelo->idmodelo);
                }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Modelo ha sido editado.', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un perfil', 404);
    }
    
    public function destroy($enterprise, $id) {         

        $modelo = modelo::find($id);
        
        if ($modelo) {

            $modelo->GrabarPlanes([], $modelo->idmodelo);
            $modelo->GrabarModelodet([], $modelo->idmodelo);

            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my];
            $modelo->fill($auditoria);
            $modelo->save();

            return $this->crearRespuesta($modelo->nombre.' ha sido eliminado', 200);
        }
        
        return $this->crearRespuestaError('modelo no encotrado', 404);
    }
}
