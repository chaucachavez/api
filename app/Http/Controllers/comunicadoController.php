<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\comunicado; 
use App\Exports\DataExport;

class comunicadoController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $comunicado = new comunicado();

        $param = array();
        $param['comunicado.idempresa'] = $empresa->idempresa($enterprise);
                 
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
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'comunicado.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }


        $like = !empty($paramsTMP['liketitulo']) ? trim($paramsTMP['liketitulo']) : '';
        $data = $comunicado->grid($param, $between, $like, $pageSize, $orderName, $orderSort, []);

        $total = '';

        if (isset($paramsTMP['respuestas']) &&  $paramsTMP['respuestas'] === '1') {

            $whereIn = [];
            foreach ($data as $value) {
                $value->respuesta = NULL;
                $whereIn[] = $value->idcomunicado;
            }

            $respuestas = \DB::table('comunicadorespuesta')   
                    ->whereIn('idcomunicado', $whereIn)
                    ->where('identidad', $this->objTtoken->my) 
                    ->get()->all();


            foreach ($data as $value) {
                foreach ($respuestas as $rspta) {

                    if ($rspta->idcomunicado === $value->idcomunicado) { 
                        $value->respuesta = $rspta->respuesta;
                        break;
                    }
                }
            }
        } else {
            $whereIn = [];
            foreach ($data as $value) {
                $value->respuestas = [];
                $whereIn[] = $value->idcomunicado;
            }

            $respuestas = 
                    \DB::table('comunicadorespuesta')   
                    ->join('entidad', 'comunicadorespuesta.identidad', '=', 'entidad.identidad')
                    ->select(['entidad.entidad', 'comunicadorespuesta.idcomunicado', 'comunicadorespuesta.identidad', 'comunicadorespuesta.respuesta'])
                    ->whereIn('idcomunicado', $whereIn) 
                    ->get()->all();

            foreach ($data as $value) {
                foreach ($respuestas as $rspta) {
                    if ($rspta->idcomunicado === $value->idcomunicado) { 
                        $value->respuestas[] = $rspta;
                        // break;
                    }
                }
            }
        }
        
        // dd($data);
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            
            $total = $data->total();
            $data = $data->items();
        }

        return $this->crearRespuesta($data, 200, $total);
    } 

    public function descargaRespuestas(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $comunicado = new comunicado();

        $param = array();
        $param['comunicado.idempresa'] = $empresa->idempresa($enterprise);
                 
        $between = array();
        if (isset($paramsTMP['idcomunicado']) && !empty($paramsTMP['idcomunicado'])) {
            $param['comunicado.idcomunicado'] = $paramsTMP['idcomunicado'];
        }

        $datarspta = $comunicado->gridRespuestas($param);
        
        $data = array();
        $i = 0;
        // dd($datarspta);
        foreach ($datarspta as $row) {

            $respuesta = $row->respuesta;

            $data[$i] = array(
                'COMUNICADO'=> $row->titulo,                
                'DOC.'=> $row->abreviatura,
                'NUMERO_DOC'=> $row->numerodoc,
                'PERSONAL'=> $row->entidad,
                'PREGUNTA'=> $row->pregunta,
                'RESPUESTA'=> $row->$respuesta
            );
            $i++;
        }
        
        return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
    }

    public function calificacion(Request $request, $enterprise) {

        $request = $request->all();

        if (empty($request['idcomunicado'])) {
            return $this->crearRespuesta('Especifique comunicado.', [200, 'info']);
        }

        if (empty($request['identidad'])) {
            return $this->crearRespuesta('Especifique personal.', [200, 'info']);
        }

        if (empty($request['respuesta'])) {
            return $this->crearRespuesta('Conteste encuesta Si/No.', [200, 'info']);
        }

        $data = \DB::table('comunicadorespuesta')   
                    ->where('idcomunicado', $request['idcomunicado'])
                    ->where('identidad', $request['identidad'])
                    ->get()->all();

        if (!empty($data)) {
            return $this->crearRespuesta('Comunicado ya tiene respuesta.', [200, 'info']);
        } 
        
        \DB::beginTransaction();
        try {            
            //Origen
            $insert = [
                'idcomunicado' => $request['idcomunicado'],
                'identidad' => $request['identidad'], 
                'respuesta' => $request['respuesta']
            ]; 

            \DB::table('comunicadorespuesta')->insert($insert); 

        } catch (QueryException $e) {
            \DB::rollback();
        }

        \DB::commit();
        
        return $this->crearRespuesta('Respuesta enviada exitósamente.', 201);
    }

    public function show($enterprise, $id) {

        $entidad = new entidad();
        $objComunicado = new comunicado();

        $comunicado = $objComunicado->comunicado($id);

        if ($comunicado) { 

            return $this->crearRespuesta($comunicado, 200);
        }

        return $this->crearRespuestaError('Publicación no encotrado', 404);
    } 

    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['comunicado']['idempresa'] = $idempresa;
        $request['comunicado']['fecha'] = $this->formatFecha($request['comunicado']['fecha'], 'yyyy-mm-dd');
        $request['comunicado']['created_at'] = date('Y-m-d H:i:s');
        $request['comunicado']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {
            $comunicado = comunicado::create($request['comunicado']);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Publicación ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $comunicado = comunicado::find($id);

        if ($comunicado) {
            $request = $request->all();

            $request['comunicado']['fecha'] = $this->formatFecha($request['comunicado']['fecha'], 'yyyy-mm-dd');
            $request['comunicado']['updated_at'] = date('Y-m-d H:i:s');
            $request['comunicado']['id_updated_at'] = $this->objTtoken->my;
            
            $comunicado->fill($request['comunicado']);

            \DB::beginTransaction();
            try {                              
                $comunicado->save();  
                
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

        $comunicado = comunicado::find($id);

        if ($comunicado) { 

            \DB::beginTransaction();
            try {  
                $comunicado->delete(); 

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Comunicado a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Comunicado no encontrado', 404);
    }

}
