<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\paquete;
use App\Models\producto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;

class paqueteController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $paquete = new paquete();

        $param = array();
        $param['paquete.idempresa'] = $empresa->idempresa($enterprise);

        if (isset($paramsTMP['idarbol'])) {
            $param['paquete.idcategoria'] = $paramsTMP['idarbol'];
        }  

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'paquete.nombre';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';

        $datapaquete = $paquete->grid($param, $like, $pageSize, $orderName, $orderSort);
         
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datapaquete->total();
            $data = $datapaquete->items();
        } else {
            $data = $datapaquete;
        }

        if (isset($paramsTMP['paquetedet'])  && $paramsTMP['paquetedet'] === '1') {
            $whereIn = [];
            foreach($data as $row){
                $whereIn[] = $row->idpaquete;
                $row->paquetedet = [];
            } 

            $datapaquetedet = $venta->paquetedetalles([], $whereIn);
            $datapaquetedet = $this->ordenarMultidimension($datapaquetedet, 'idpaquete'); 
            foreach($data as $row){
                foreach($datapaquetedet as $row2){
                    if($row->idpaquete === $row2->idpaquete)                       
                        $row->paquetedet[] = $row2; 
                } 
            } 
        } 

        if (isset($paramsTMP['paqueteproto'])  && $paramsTMP['paqueteproto'] === '1') {
            $whereIn = [];
            foreach($data as $row){
                $whereIn[] = $row->idpaquete;
                $row->paqueteproto = [];
            } 
            
            $datapaqueteproto = $venta->paqueteprotocolos([], $whereIn);
            $datapaqueteproto = $this->ordenarMultidimension($datapaquetedet, 'idpaquete'); 
            foreach($data as $row){
                foreach($datapaqueteproto as $row2){
                    if($row->idpaquete === $row2->idpaquete)                       
                        $row->paqueteproto[] = $row2; 
                } 
            } 
        }

        if (isset($paramsTMP['paquetezona'])  && $paramsTMP['paquetezona'] === '1') {
            $whereIn = [];
            foreach($data as $row){
                $whereIn[] = $row->idpaquete;
                $row->paquetezonas = [];
            } 
            
            $datapaquetezonas = $paquete->zonas([], $whereIn);  

            foreach($data as $row){
                foreach($datapaquetezonas as $row2){
                    if($row->idpaquete === $row2->idpaquete)                       
                        $row->paquetezonas[] = $row2; 
                } 
            }  

        }
 
        return $this->crearRespuesta($data, 200, $total); 
    } 
    

    public function show($enterprise, $id) {

        $empresa = new empresa(); 
        $objPaquete = new paquete(); 
        
        $paquete = $objPaquete->paquete($id);

        if ($paquete) {

            //dd( $objPaquete->zonas(['paquetezona.idpaquete' => $id]) );
            $listcombox = array(
                'zonas' => $objPaquete->zonas(['paquetezona.idpaquete' => $id])
            ); 

            return $this->crearRespuesta($paquete, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Paquete no encotrado', 404);
    }
 

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['paquete']['idempresa'] = $idempresa; 
        $request['paquete']['created_at'] = date('Y-m-d H:i:s');
        $request['paquete']['id_created_at'] = $this->objTtoken->my; 

        \DB::beginTransaction();
        try {

            if(isset($request['paqueteproto'])){
                $diasproto = [];
                foreach ($request['paqueteproto'] as $value) {
                    if(!in_array($value['dia'], $diasproto)) {
                        $diasproto[] = $value['dia'];
                    }
                }
                rsort($diasproto);
                $request['paquete']['dias'] = ($diasproto) ? $diasproto[0] : 0; 
                // return $this->crearRespuesta('No zz', [200, 'info'], '', '', $diasproto);
            }
            
            $paquete = paquete::create($request['paquete']); 
            $id = $paquete->idpaquete; 

            if(isset($request['paquetedet'])){
                
                $datapaquetedet = [];
                foreach ($request['paquetedet'] as $value) { 
                    $datapaquetedet[] = array(
                        'idpaquete' => $id,
                        'idproducto' => $value['idproducto'],
                        'punit' => $value['punit'],
                        'cantidad' => $value['cantidad'],
                        'total' => $value['total'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my 
                    );
                }
                //return $this->crearRespuesta('No zz', [200, 'info'], '', '', $request['paquetedet']);
                $paquete->GrabarPaquetedetalles($datapaquetedet);
            } 

            if(isset($request['paqueteproto'])){
                $datapaqueteproto = [];
                foreach ($request['paqueteproto'] as $value) { 
                    $datapaqueteproto[] = array(
                        'idpaquete' => $id,
                        'idproducto' => $value['idproducto'],
                        'dia' => $value['dia'],
                        'cantidad' => $value['cantidad'], 
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my 
                    );
                }
                //return $this->crearRespuesta('No zz', [200, 'info'], '', '', $request['paquetedet']);
                $paquete->GrabarPaqueteprotocolos($datapaqueteproto);  
            }

            if (isset($request['zonas'])) {
                $dataZonas = [];
                foreach ($request['zonas'] as $row) {
                    $dataZonas[] = ['idpaquete' => $id, 'idzona' => $row['idzona']];
                }
                $paquete->GrabarZonas($dataZonas, $id);
            }

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('"' . $paquete->nombre . '" ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise);

        $paquete = paquete::find($id);

        if ($paquete) {
            $request = $request->all();   
            $request['paquete']['updated_at'] = date('Y-m-d H:i:s');
            $request['paquete']['id_updated_at'] = $this->objTtoken->my; 
              
            if(isset($request['paqueteproto'])){
                $diasproto = [];
                foreach ($request['paqueteproto'] as $value) {
                    if(!in_array($value['dia'], $diasproto)) {
                        $diasproto[] = $value['dia'];
                    }
                }
                rsort($diasproto);
                $request['paquete']['dias'] = ($diasproto) ? $diasproto[0] : 0; 
            }

            $paquete->fill($request['paquete']);

            \DB::beginTransaction();
            try {
                //Graba en 2 tablaa(producto, tarifario)                                   
                $paquete->save();
                
                if(isset($request['paquetedet'])){
                    $datapaquetedet = [];
                    foreach ($request['paquetedet'] as $value) { 
                        $datapaquetedet[] = array(
                            'idpaquete' => $id,
                            'idproducto' => $value['idproducto'],
                            'punit' => $value['punit'],
                            'cantidad' => $value['cantidad'],
                            'total' => $value['total'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'id_updated_at' => $this->objTtoken->my 
                        );
                    }
                    $paquete->GrabarPaquetedetalles($datapaquetedet, $id);
                } 

                if(isset($request['paqueteproto'])){
                    $datapaqueteproto = [];
                    foreach ($request['paqueteproto'] as $value) { 
                        $datapaqueteproto[] = array(
                            'idpaquete' => $id,
                            'idproducto' => $value['idproducto'],
                            'dia' => $value['dia'],
                            'cantidad' => $value['cantidad'], 
                            'updated_at' => date('Y-m-d H:i:s'),
                            'id_updated_at' => $this->objTtoken->my 
                        );
                    }
                    //return $this->crearRespuesta('No zz', [200, 'info'], '', '', $request['paquetedet']);
                    $paquete->GrabarPaqueteprotocolos($datapaqueteproto, $id);  
                }

                if (isset($request['zonas'])) {
                    $dataZonas = [];
                    foreach ($request['zonas'] as $row) {
                        $dataZonas[] = ['idpaquete' => $id, 'idzona' => $row['idzona']];
                    }
                    $paquete->GrabarZonas($dataZonas, $id);
                }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Paquete "' . $paquete->nombre . '" ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un producto', 404);
    } 
    
    public function destroy($enterprise, $id) {

        $empresa = new empresa();
        $cicloatencion = new cicloatencion();

        $paquete = paquete::find($id);
        $idempresa = $empresa->idempresa($enterprise);

        if ($paquete) { 
            
            $param = array();
            $param['cicloatencion.idempresa'] = $idempresa; 
            $param['cicloatencion.idpaquete'] = $id;
            $ciclos = $cicloatencion->grid($param);

            if ($ciclos) {
                return $this->crearRespuesta("Existe ciclos relacionados con paquete", [200, 'info'], '', '', $ciclos);
            } 

            \DB::beginTransaction();
            try {
                \DB::table('paquetedet')->where('idpaquete', $id)->delete();
                \DB::table('paqueteproto')->where('idpaquete', $id)->delete(); 
                $paquete->GrabarZonas([], $id);

                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $paquete->fill($auditoria);
                $paquete->save();

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Paquete "' . $paquete->nombre . '" a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Paquete no encotrado', 404);
    }

}
