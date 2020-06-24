<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\tareadet;

class tareadetController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }  

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $tareadet = new tareadet();

        $param = array(
            'tareadet.idempresa' => $empresa->idempresa($enterprise),
            'tareadet.idtarea' => trim($paramsTMP['idtarea'])
        ); 

        $data = $tareadet->grid($param);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }
                        
        return $this->crearRespuesta($data, 200, $total); 
    } 

    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 
        $tareadet = new tareadet();
        
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();                
          
        /*Campos auditores*/
        $request['idempresa'] = $idempresa;  
        $request['created_at'] = date('Y-m-d H:i:s');  
        $request['id_created_at'] = $this->objTtoken->my;  
        /*Campos auditores*/        
          
        \DB::beginTransaction();
        try {

            tareadet::create($request);

            $param = array(
                'tareadet.idempresa' => $idempresa,
                'tareadet.idtarea' => trim($request['idtarea'])
            ); 
            
            $data = $tareadet->grid($param);

            $efectuado = false;
            foreach($data as $row) {
                if ($row->tiporesultado === '1' && ($row->tiporespuesta === '1' || $row->tiporespuesta === '2')) {
                    $efectuado = true;
                    break;
                }
            }

            //85: No efectuado 86: Efectuado 87: Vencido
            if ($efectuado) { 
                $idestado = 86;
            } else {
                $idestado = 85;
            }

            \DB::table('tarea')
                ->whereNull('deleted') 
                ->where(['idtarea' => trim($request['idtarea'])])
                ->update(['idestado' => $idestado]);

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Respuesta ha sido creado.', 201);
    }

    public function destroy($enterprise, $id) {

        $empresa = new empresa(); 
        
        $idempresa = $empresa->idempresa($enterprise);
        $tareadet = tareadet::find($id);

        if ($tareadet) {

            \DB::beginTransaction();
            try {
 
                $tareadet->delete();

                $param = array(
                    'tareadet.idempresa' => $idempresa,
                    'tareadet.idtarea' => $tareadet->idtarea
                );
                
                $data = $tareadet->grid($param);

                $efectuado = false;
                foreach($data as $row) {
                    if ($row->tiporesultado === '1' && ($row->tiporespuesta === '1' || $row->tiporespuesta === '2')) {
                        $efectuado = true;
                        break;
                    }
                }

                //85: No efectuado 86: Efectuado 87: Vencido
                if ($efectuado) { 
                    $idestado = 86;
                } else {
                    $idestado = 85;
                }

                \DB::table('tarea')
                    ->whereNull('deleted') 
                    ->where([
                        'idempresa' => $idempresa, 
                        'idtarea' => $tareadet->idtarea
                    ])
                    ->update(['idestado' => $idestado]);

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit(); 

            return $this->crearRespuesta('La respuesta a sido eliminado', 200);
        }

        return $this->crearRespuestaError('Respuesta no encotrado', 404);
    }
}
