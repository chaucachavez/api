<?php

namespace App\Http\Controllers;

use Excel;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\planhorario;
use App\Models\planhorariodet;
use App\Models\contratolaboral;

class contratolaboralController extends Controller { 
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $contratolaboral = new contratolaboral();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        
        $param['contratolaboral.idempresa'] = $idempresa;

        if (isset($paramsTMP['identidad']) && !empty($paramsTMP['identidad'])) {
            $param['contratolaboral.identidad'] = $paramsTMP['identidad'];
        }  

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'contratolaboral.inicio';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
         
        $data = $contratolaboral->grid($param, $pageSize, $orderName, $orderSort); 

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        return $this->crearRespuesta($data, 200, $total);
    }            
    
    public function newcontrato(Request $request, $enterprise) {

        $empresa = new empresa();

        $planhorario = new planhorario();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(); 
        $param['planhorario.idempresa'] = $idempresa;
        $listcombox = array( 
            'planhorarios' => $planhorario->grid($param) 
        );

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function show(Request $request, $enterprise, $id) {

        $objPlanhorario = new contratolaboral();
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise);  
         
        $contratolaboral = $objPlanhorario->contratolaboral(array('contratolaboral.idcontratolaboral' =>$id));

        if ($contratolaboral) { 

            return $this->crearRespuesta($contratolaboral, 200);
        }

        return $this->crearRespuestaError('Planhorario no encotrado', 404);
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();


        $request = $request->all(); 
        $idempresa = $empresa->idempresa($enterprise); 

        /*Campos auditores*/ 
        $request['contratolaboral']['idempresa'] = $idempresa;
        $request['contratolaboral']['inicio'] = $this->formatFecha($request['contratolaboral']['inicio'], 'yyyy-mm-dd');
        $request['contratolaboral']['fin'] = $this->formatFecha($request['contratolaboral']['fin'], 'yyyy-mm-dd');
        $request['contratolaboral']['created_at'] = date('Y-m-d H:i:s');
        $request['contratolaboral']['id_created_at'] = $this->objTtoken->my;
        /*Campos auditores*/        

        \DB::beginTransaction();
        try {
            $contratolaboral = contratolaboral::create($request['contratolaboral']);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit(); 

        return $this->crearRespuesta('Contrato laboral ha sido creado.', 201);
    }
     
    public function update(Request $request, $enterprise, $id) {

        $empresa = new empresa();        
        $contratolaboral = contratolaboral::find($id);
        $planhorario = new planhorario();
        
        $idempresa = $empresa->idempresa($enterprise); 

        $request = $request->all();

        $request['contratolaboral']['inicio'] = $this->formatFecha($request['contratolaboral']['inicio'], 'yyyy-mm-dd');
        $request['contratolaboral']['fin'] = $this->formatFecha($request['contratolaboral']['fin'], 'yyyy-mm-dd');


        $param = array(
            'planhorariodet.idplanhorario' => $request['contratolaboral']['idplanhorario'] 
        );
        $planhorariodet = $planhorario->planhorariodet($param);
        $diasPlanhorario = [];
        foreach ($planhorariodet as $row) {
            if (!in_array($row->dia, $diasPlanhorario)) {
                $diasPlanhorario[] = $row->dia;
            }
        }

        $firstDia = substr($this->_data_first_month_day(), -2);
        $lastDia = substr($this->_data_last_month_day(), -2);
        $year = date('Y');
        $month = date('m');

        $diasCalendario = [];
        for($i = 1; $i <= $lastDia; $i++) {
            $day = ($i < 10 ? '0' : '') . $i; 
            $dayweek = (int) $this->_data_dayweek_month_day($year.'-'.$month.'-'.$day); 

            if (in_array($dayweek, $diasPlanhorario)) { 

                $horario = array();
                foreach ($planhorariodet as $row) {
                    if ($row->dia === $dayweek) {
                        $horario[] = $row;
                    }
                }

                $diasCalendario[] = array(
                    'fecha' => $year.'-'.$month.'-'.$day,
                    'dayweek' => $dayweek,
                    'planhorariodet' => $horario
                );
            }
        } 

        $insertAsistencia = array();
        foreach($diasCalendario as $row) {

            foreach($row['planhorariodet'] as $rowdet) {

                $tmp = array(
                    'idempresa' => $idempresa,
                    'idplanhorario' => $rowdet->idplanhorario,
                    'nombre' => $rowdet->nombre,
                    'identidad' => $contratolaboral->identidad,
                    'laborfechainicio' => $row['fecha'],
                    'laborfechafin' => $row['fecha'],
                    'laborinicio' => $rowdet->inicio,
                    'laborfin' => $rowdet->fin,
                    'estado' => '0',
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my 
                );

                $insertAsistencia[] = $tmp;
            }
        }

        // return $this->crearRespuesta('Xd', [200, 'info'], '', '', $insertAsistencia); 


        if ($contratolaboral) {
 
            \DB::beginTransaction();
            try {
                $idplanhorarioAnterior = $contratolaboral->idplanhorario; 
                $contratolaboral->fill($request['contratolaboral']);
                $contratolaboral->save();  

                //Insertar asistencia /*Deshabilitado*/

                // \DB::table('asistencia')
                //     ->where([
                //         'idplanhorario' => $idplanhorarioAnterior,
                //         'identidad' => $contratolaboral->identidad
                //     ])
                //     ->delete();

                // if (!empty($insertAsistencia)) {
                //     \DB::table('asistencia')
                //         ->insert($insertAsistencia);
                // }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Contrato laboral ha sido editado.', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un plan horario', 404);
    }
    
    public function destroy($enterprise, $id) {         

        $contratolaboral = contratolaboral::find($id);
        
        if ($contratolaboral) { 

            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my]; 
            $contratolaboral->fill($auditoria);
            $contratolaboral->save();

            return $this->crearRespuesta('Contrato ha sido eliminado', 200);
        }
        
        return $this->crearRespuestaError('Contrato no encotrado', 404);
    }

}
