<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\publicacion; 

class publicacionController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $publicacion = new publicacion();

        $param = array();
        $param['publicacion.idempresa'] = $empresa->idempresa($enterprise);
                 
        $between = array();
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }        

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'publicacion.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }


        $like = !empty($paramsTMP['liketitulo']) ? trim($paramsTMP['liketitulo']) : '';
        $data = $publicacion->grid($param, $between, $like, $pageSize, $orderName, $orderSort, []);

        $total = '';
        // dd($data);
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            
            $total = $data->total();
            $data = $data->items();
        }

        if ($data) {

            $whereIdpublicacionIn = array();
            foreach($data as $row){
                $row->etiquetas = array();
                $whereIdpublicacionIn[] = $row->idpublicacion; 
            }

            $etiquetas = [];
            if ($whereIdpublicacionIn) {
                $etiquetas = $publicacion->etiquetas([], $whereIdpublicacionIn);
                // dd($etiquetas);
                if ($etiquetas) {

                    foreach($data as $row){
                        $tmp = array();
                        // dd($row);
                        foreach($etiquetas as $etiqueta){ 
                            if ($etiqueta->idpublicacion === $row->idpublicacion) {
                                $tmp[] = $etiqueta;
                            }
                        }     
                        $row->etiquetas = $tmp;
                    }

                    // dd($data);
                }
            } 
            // dd($data);
        }

        return $this->crearRespuesta($data, 200, $total);
        // return $this->crearRespuestaError('Publicacion no encontrado', 404);
    }  

    public function indexPersonal(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $publicacion = new publicacion();

        $param = array();
        $param['publicacion.idempresa'] = $empresa->idempresa($enterprise);
        $param['entidad_etiqueta.identidad'] = $paramsTMP['idpersonal'];
                 
        $between = array();
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }        

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'publicacion.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        } 

        $like = !empty($paramsTMP['liketitulo']) ? trim($paramsTMP['liketitulo']) : '';
        $data = $publicacion->gridPersonal($param, $between, $like, $pageSize, $orderName, $orderSort, []);

        $total = '';
        // dd($data);
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            
            $total = $data->total();
            $data = $data->items();
        }

        if ($data) {

            $whereIdpublicacionIn = array();
            foreach($data as $row){
                $row->etiquetas = array();
                $whereIdpublicacionIn[] = $row->idpublicacion; 
            }

            $etiquetas = [];
            if ($whereIdpublicacionIn) {
                $etiquetas = $publicacion->etiquetas([], $whereIdpublicacionIn);
                // dd($etiquetas);
                if ($etiquetas) {

                    foreach($data as $row){
                        $tmp = array();
                        // dd($row);
                        foreach($etiquetas as $etiqueta){ 
                            if ($etiqueta->idpublicacion === $row->idpublicacion) {
                                $tmp[] = $etiqueta;
                            }
                        }     
                        $row->etiquetas = $tmp;
                    } 
                }
            } 
            // dd($data);
        }

        return $this->crearRespuesta($data, 200, $total);
        // return $this->crearRespuestaError('Publicacion no encontrado', 404);
    }

    public function show($enterprise, $id) {

        $empresa = new empresa(); 
        $entidad = new entidad();
        $objPublicacion = new publicacion();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $publicacion = $objPublicacion->publicacion($id);

        if ($publicacion) {  

            $param = array(
                'entidad.idempresa' => $idempresa, 
                'entidad.tipopersonal' => '1',
                'entidad.acceso' => 1,
            );

            $listcombox = array( 
                'etiquetas' => $objPublicacion->gridetiquetas(['etiqueta.idempresa' => $idempresa]),
                'personal' => $entidad->entidades($param),
                'publicacionEtiquetas' => $objPublicacion->etiquetas(['etiqueta_publicacion.idpublicacion' => $id]),
                'publicacionPersonal' => $objPublicacion->usuarios(['entidad_publicacion.idpublicacion' => $id]),
            );

            return $this->crearRespuesta($publicacion, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Publicación no encotrado', 404);
    }

    public function newpublicacion($enterprise) { 
        
        $empresa = new empresa();
        $publicacion = new publicacion();
        $entidad = new entidad();

        $idempresa = $empresa->idempresa($enterprise);
         
        $param = array(
            'entidad.idempresa' => $idempresa, 
            'entidad.tipopersonal' => '1',
            'entidad.acceso' => 1,
        );
        
        $listcombox = array( 
            'etiquetas' => $publicacion->gridetiquetas(['etiqueta.idempresa' => $idempresa]),
            'personal' => $entidad->entidades($param)
        );        
        
        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['publicacion']['idempresa'] = $idempresa;
        $request['publicacion']['fecha'] = $this->formatFecha($request['publicacion']['fecha'], 'yyyy-mm-dd');
        $request['publicacion']['created_at'] = date('Y-m-d H:i:s');
        $request['publicacion']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {           
            $publicacion = publicacion::create($request['publicacion']);    

            if(isset($request['etiquetas'])){
                $dataEtiquetas = [];
                foreach ($request['etiquetas'] as $row) {
                    $dataEtiquetas[] = ['idpublicacion' => $publicacion->idpublicacion, 'idetiqueta' => $row['idetiqueta']];
                }
                $publicacion->GrabarEtiquetas($dataEtiquetas, $publicacion->idpublicacion);
            }

            if(isset($request['personal'])){
                $dataPersonal = [];
                foreach ($request['personal'] as $row) {
                    $dataPersonal[] = ['idpublicacion' => $publicacion->idpublicacion, 'identidad' => $row['identidad']];
                }
                $publicacion->GrabarPersonal($dataPersonal, $publicacion->idpublicacion);
            }

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Publicación ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $publicacion = publicacion::find($id);

        if ($publicacion) {
            $request = $request->all();

            $request['publicacion']['fecha'] = $this->formatFecha($request['publicacion']['fecha'], 'yyyy-mm-dd');
            $request['publicacion']['updated_at'] = date('Y-m-d H:i:s');
            $request['publicacion']['id_updated_at'] = $this->objTtoken->my;
            
            $publicacion->fill($request['publicacion']);

            \DB::beginTransaction();
            try {                              
                $publicacion->save(); 

                if(isset($request['etiquetas'])){
                    $dataEtiquetas = [];
                    foreach ($request['etiquetas'] as $row) {
                        $dataEtiquetas[] = ['idpublicacion' => $publicacion->idpublicacion, 'idetiqueta' => $row['idetiqueta']];
                    }
                    $publicacion->GrabarEtiquetas($dataEtiquetas, $publicacion->idpublicacion);
                }

                if(isset($request['personal'])){
                    $dataPersonal = [];
                    foreach ($request['personal'] as $row) {
                        $dataPersonal[] = ['idpublicacion' => $publicacion->idpublicacion, 'identidad' => $row['identidad']];
                    }
                    $publicacion->GrabarPersonal($dataPersonal, $publicacion->idpublicacion);
                }
                
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Publicación ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un publicación', 404);
    }
    
    public function destroy($enterprise, $id) {

        $publicacion = publicacion::find($id);

        if ($publicacion) { 

            \DB::beginTransaction();
            try {  
                $publicacion->delete();

                $publicacion->GrabarEtiquetas([], $publicacion->idpublicacion);
                $publicacion->GrabarPersonal([], $publicacion->idpublicacion);
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Publicación a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Publicación no encotrado', 404);
    }

}
