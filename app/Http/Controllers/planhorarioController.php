<?php

namespace App\Http\Controllers;

use Excel;
use Illuminate\Http\Request;

use App\Models\empresa; 
use App\Models\planhorario; 

class planhorarioController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $planhorario = new planhorario();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        
        $param['planhorario.idempresa'] = $idempresa;

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'planhorario.nombre';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';
        $data = $planhorario->grid($param, $like, $pageSize, $orderName, $orderSort); 

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        return $this->crearRespuesta($data, 200, $total);
    }            
    
    public function show(Request $request, $enterprise, $id) {

        $objPlanhorario = new planhorario();
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise);  
         
        $planhorario = $objPlanhorario->planhorario(array('planhorario.idplanhorario' =>$id));

        if ($planhorario) {
            $listcombox = array( 
                'planhorariodet' => $objPlanhorario->planhorariodet(['planhorariodet.idplanhorario' => $id]),
            );

            return $this->crearRespuesta($planhorario, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Planhorario no encotrado', 404);
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $request = $request->all(); 
        $idempresa = $empresa->idempresa($enterprise); 

        /*Campos auditores*/
        $request['planhorario']['idempresa'] = $idempresa;
        $request['planhorario']['created_at'] = date('Y-m-d H:i:s');
        $request['planhorario']['id_created_at'] = $this->objTtoken->my;
        /*Campos auditores*/        

        \DB::beginTransaction();
        try {      
            $planhorario = planhorario::create($request['planhorario']);

            if (isset($request['planhorariodet'])) {
                $dataPlanhorariodet = [];
                foreach ($request['planhorariodet'] as $row) {
                    $dataPlanhorariodet[] = [
                        'idplanhorario' => $planhorario->idplanhorario, 
                        'nombre' => $row['nombre'],
                        'dia' => $row['dia'],
                        'inicio' => $row['inicio'],
                        'fin' => $row['fin'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my
                    ];
                }
                $planhorario->GrabarPlanhorariodet($dataPlanhorariodet, $planhorario->idplanhorario);
            } 

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();


        return $this->crearRespuesta('Plan horario ha sido creado.', 201);
    }
     
    public function update(Request $request, $enterprise, $id) {

        $planhorario = planhorario::find($id);
        
        $request = $request->all();

        if ($planhorario) {
 
            \DB::beginTransaction();
            try {
                $planhorario->fill($request['planhorario']);
                $planhorario->save(); 

                if (isset($request['planhorariodet'])) {
                    $dataPlanhorariodet = [];
                    foreach ($request['planhorariodet'] as $row) {
                        $dataPlanhorariodet[] = [
                            'idplanhorario' => $planhorario->idplanhorario, 
                            'nombre' => $row['nombre'],
                            'dia' => $row['dia'],
                            'inicio' => $row['inicio'],
                            'fin' => $row['fin'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'id_created_at' => $this->objTtoken->my
                        ];
                    }
                    
                    $planhorario->GrabarPlanhorariodet($dataPlanhorariodet, $planhorario->idplanhorario);
                }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Plan horario ha sido editado.', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un plan horario', 404);
    }
    
    public function destroy($enterprise, $id) {         

        $planhorario = planhorario::find($id);
        
        if ($planhorario) {
 
            $planhorario->GrabarPlanhorariodet([], $planhorario->idplanhorario);

            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my];

            $planhorario->fill($auditoria);
            $planhorario->save();

            return $this->crearRespuesta($planhorario->nombre.' ha sido eliminado', 200);
        }
        
        return $this->crearRespuestaError('Plan horario no encotrado', 404);
    }

}
