<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\entidad;
use App\Models\grupodx; 

class grupodxController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $grupodx = new grupodx();

        $param = array();
        $param['grupodx.idempresa'] = $empresa->idempresa($enterprise);

        if (isset($paramsTMP['idcicloatencion']) && !empty($paramsTMP['idcicloatencion'])) {
            $param['grupodx.idcicloatencion'] = $paramsTMP['idcicloatencion'];
        }
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'grupodx.nombre';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';
        $data = $grupodx->grid($param, $like, $pageSize, $orderName, $orderSort, []);

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
        $objgrupodx = new grupodx();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $grupodx = $objgrupodx->grupodx($id);

        if ($grupodx) {  

            return $this->crearRespuesta($grupodx, 200);
        }

        return $this->crearRespuestaError('Grupo no encotrado', 404);
    }


    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 

        $request = $request->all();

        if (empty($request['grupodx']['idcicloatencion'])) {
            return $this->crearRespuesta('Especifique ciclo de atención', [200, 'info']);
        }

        if (empty($request['grupodx']['nombre'])) {
            return $this->crearRespuesta('Especifique nombre de grupo', [200, 'info']);
        }

        $where = array(
            'idcicloatencion' => $request['grupodx']['idcicloatencion'],
            'nombre' => $request['grupodx']['nombre']
        );

        $grupodx = grupodx::where($where)->whereNull('deleted')->first();

        if ($grupodx) {
            return $this->crearRespuesta('Ya existe este nombre de grupo', [200, 'info']);
        }

        $idempresa = $empresa->idempresa($enterprise);

        $request['grupodx']['idempresa'] = $idempresa;
        $request['grupodx']['created_at'] = date('Y-m-d H:i:s');
        $request['grupodx']['id_created_at'] = $this->objTtoken->my;

        //VALIDACIONES 
        \DB::beginTransaction();
        try {           
            $grupodx = grupodx::create($request['grupodx']);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta($request['grupodx']['nombre'] .' ha sido creado.', 201);
    }
    
    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);

        $grupodx = grupodx::find($id);

        $request = $request->all(); 

        $where = array(
            'idcicloatencion' => $grupodx->idcicloatencion,
            'nombre' => $request['grupodx']['nombre']
        );

        $existente = grupodx::where($where)->whereNull('deleted')->first();
        
        if ($existente && $existente->idgrupodx !== $grupodx->idgrupodx) {
            return $this->crearRespuesta('Ya existe este nombre de grupo', [200, 'info']);
        }

        // return $this->crearRespuesta([$beforedx, $grupodx], [200, 'info']);

        if ($grupodx) { 
 
            $request['grupodx']['updated_at'] = date('Y-m-d H:i:s');
            $request['grupodx']['id_updated_at'] = $this->objTtoken->my;
            
            $grupodx->fill($request['grupodx']);

            \DB::beginTransaction();
            try {                              
                $grupodx->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('grupodx ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un grupo', 404);
    }
    
    public function destroy($enterprise, $id) {

        $grupodx = grupodx::find($id);

        if ($grupodx) {  
            
            // Examenfisico
            $data = \DB::table('examenfisico')
                    ->where('idgrupodx', $id)
                    ->whereNull('examenfisico.deleted_at')
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('GrupoDx tiene "Examen físico" en consulta médica', [200, 'info']);
            }

            // Diagnósticos
            $data = \DB::table('diagnosticomedico')                    
                    ->where('diagnosticomedico.idgrupodx', $id)
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('GrupoDx tienes "Diagnósticos" en consulta médica', [200, 'info']);
            }

            // Tratamientos
            $data = \DB::table('tratamientomedico')
                    ->where('idgrupodx', $id)
                    ->whereNull('tratamientomedico.deleted')
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('GrupoDx tiene "Indicación médica" en consulta médica', [200, 'info']);
            }

            // Terapias efectuadas / canceladas
            $data = \DB::table('terapiatratamiento')
                    ->where('idgrupodx', $id)
                    ->whereNull('terapiatratamiento.deleted')
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('GrupoDx tiene "Terapias" en área de Terapia', [200, 'info']);
            }

            // Tratamientos adicionales
            $data = \DB::table('ciclotratamiento')
                    ->where('idgrupodx', $id)
                    ->whereNull('ciclotratamiento.deleted_at')
                    ->get()->all();

            if ($data) {
                return $this->crearRespuesta('GrupoDx tiene "Tratamientos adicionales" en ciclo de atención', [200, 'info']);
            }
            
            \DB::beginTransaction();
            try {
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my]; 
                $grupodx->fill($auditoria);
                $grupodx->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta($grupodx->nombre . ' ha sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Grupo no encotrado', 404);
    }

}
