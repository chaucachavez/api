<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\producto;
use App\Exports\DataExport;
use App\Models\diagnostico;
use Illuminate\Http\Request;
use App\Models\dxtratamiento; 


class dxtratamientoController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) {
        
        $objDxtratamiento = new dxtratamiento();
        
        $empresa = empresa::where('url', '=', $enterprise)->first();
        $idempresa = $empresa->idempresa;   

        $data = [];
        $listcombox = [];
        if (isset($request['others'])) {
            $others = explode(',', $request['others']);

            if (in_array('diag', $others)) { 
                $diagnosticos = $objDxtratamiento->diagnosticos(['dxtratamiento.idempresa' => $idempresa]);
                $listcombox['diag'] = $this->ordenarMultidimension($diagnosticos, 'nombre', SORT_ASC);
            }        
        } 

        return $this->crearRespuesta($data, 200, '', '', $listcombox); 
    } 
 

    public function index(Request $request, $enterprise) { 

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $dxtratamiento = new dxtratamiento();
        $diagnostico = new diagnostico();
        $producto = new producto();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['dxtratamiento.idempresa'] = $idempresa;
        
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'dxtratamiento.grupodx';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'asc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
          
        if (isset($paramsTMP['eva']) && !empty($paramsTMP['eva'])) {
            $param['dxtratamiento.eva'] = $paramsTMP['eva'];
        }

        if (isset($paramsTMP['aprobacion']) && !empty($paramsTMP['aprobacion'])) {
            $param['dxtratamiento.aprobacion'] = $paramsTMP['aprobacion'];
        }

        $iddiagnostico = null;
        if (isset($paramsTMP['iddiagnostico']) && !empty($paramsTMP['iddiagnostico'])) {
            $iddiagnostico = $paramsTMP['iddiagnostico']; 
        }
                   
        $data = $dxtratamiento->grid($param, $pageSize, $orderName, $orderSort, $iddiagnostico);

        // dd($data);
        $diagnosticosIn = [];
        $tratamientoIn = []; 
        foreach($data as $row){
            $diagnosticos = explode(',', $row->grupodx); 
            $strtratamientos = explode(',', $row->grupotra); 
            
            foreach($diagnosticos as $iddiagnostico){
                if (!in_array($iddiagnostico, $diagnosticosIn)) {
                    $diagnosticosIn[] = $iddiagnostico;
                }
            }

            foreach($strtratamientos as $tratamiento){
                $str = explode(':', $tratamiento);
                if (!in_array($str[0], $tratamientoIn)) {
                    $tratamientoIn[] = $str[0];
                }
            }  
        }
        //Esto produce una incosistencia. para item iddxtratamiento 3016, el campo grupodx esta almacenando 4 valores cuando debio ser 3.
        //Corregir en BD
        //Corregir al grabar la cita 
        //CONCLUSION: Al Eliminar campos grupodx, grupotra. Si yo elimino un producto del MAeSTRO  no hay forma de validar con el grupotra, seria brutal.
        //por tanto, para dxtratamiento conviene poner 5 campos grupodx y 7(trati, tratii, tratiii, trativ, tratv, tratvi, tratvii)

        // $diagnosticos = [];
        // foreach($data as $row){            
        //     if($row->dxi) 
        //         $diagnosticos[] = $row->dxi;
        //     if($row->dxii) 
        //         $diagnosticos[] = $row->dxii;
        //     if($row->dxiii) 
        //         $diagnosticos[] = $row->dxiii;
        // } 
        // $diagnosticosIn = array_unique($diagnosticos);
        // dd($diagnosticosIn);

        $datadiagnostico = [];
        $datadiagnosticos = $diagnostico->grid(['idempresa'=>$idempresa], '', '', '', '', [], '', $diagnosticosIn);
        foreach($datadiagnosticos as $row){
            $datadiagnostico[$row->iddiagnostico] = $row->nombre;
        }

        $datatratamiento = []; 
        if($tratamientoIn){
            $fieldsProducto = ['producto.idproducto', 'producto.nombre', 'producto.codigo'];
            $datatratamientos = $producto->grid(['producto.idempresa' => $idempresa], '', '', '', '', $fieldsProducto, [], $tratamientoIn);             
            foreach($datatratamientos as $row){
                $datatratamiento[$row->idproducto] = $row->codigo;
            }
        } 

        ksort($datatratamiento); 

        foreach($data as $row){ 
            //Registro de dx
            $diagnosticos = explode(',', $row->grupodx);  
            $row->nombrediagnostico = null;
            $i = 1;
            foreach($diagnosticos as $iddiagnostico){
                //$row->nombrediagnostico .= (strlen($row->nombrediagnostico) > 1 ? ',': '') . $datadiagnostico[$iddiagnostico];
                $row->nombrediagnostico .= (strlen($row->nombrediagnostico) > 1 ? ' ': '') . $i++ . '.- '. $datadiagnostico[$iddiagnostico];                
            } 

            foreach($datatratamiento as $codigo) {
                $row->$codigo = null;
            }

            $tratamientos = explode(',', $row->grupotra); 
            foreach($tratamientos as $tratamiento){
                $tratamiento = explode(':', $tratamiento); 

                $codigo = $datatratamiento[$tratamiento[0]];
                $row->$codigo = $tratamiento[1];
            }
        }
        
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }
 
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){
                $datatmp = array(); 
                foreach($data as $row){    
                    $tmpcolumn = array(
                        'CODIGO' => $row->iddxtratamiento, 
                        'DIAGNOSTICO' => $row->nombrediagnostico, 
                        'EVA' => $row->eva  
                    );

                    foreach($datatratamiento as $column) {
                        $tmpcolumn[$column] = $row->$column;
                    }

                    $tmpcolumn['RECURRENCIA'] = $row->puntuacion;
                    $tmpcolumn['APROBADO'] = $row->aprobacion;
                    $tmpcolumn['CREADO'] = $row->created;
                    $tmpcolumn['CREACION'] = $row->createdat;
                    $tmpcolumn['ACTUALIZADO'] = $row->updated;
                    $tmpcolumn['ACTUALIZACION'] = $row->updatedat; 
                    $datatmp[] = $tmpcolumn;
                } 

                return Excel::download(new DataExport($datatmp), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200, $total, '', $datatratamiento);  
        } 
    }

    public function sugerencias(Request $request, $enterprise) { 
 
        $empresa = new empresa();
        $dxtratamiento = new dxtratamiento(); 

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise); 

        $grupodx = explode(',',$request['grupodx']);     
        sort($grupodx);
        
        $grupodx = implode(',',$grupodx); 
        $dxtratamientofirst = $dxtratamiento->dxtratamiento(array('dxtratamiento.idempresa' => $idempresa, 'dxtratamiento.grupodx' => $grupodx, 'dxtratamiento.eva' => $request['eva'], 'aprobacion' => '1'));
        
        $data = array();
        if($dxtratamientofirst) {  
            $tratamientos = explode(',', $dxtratamientofirst->grupotra);  

            foreach($tratamientos as $tratamiento){            
                $tratamiento = explode(':', $tratamiento); 
                $producto = producto::find($tratamiento[0]); 
                $data[] = array('idproducto' => $tratamiento[0], 'nombre' =>  $producto->nombre, 'cantidad' => $tratamiento[1]);
            }  
        }
                        
        return $this->crearRespuesta($data, 200); 
    }
    
    public function update(Request $request, $enterprise, $id) {
          
        $dxtratamiento = dxtratamiento::find($id); 
        
        $request = $request->all();
        
        $grupotra = "";

        foreach($request['dxtratamiento']['tratamientos']as $value) {
            $grupotra .= (strlen($grupotra) > 1 ? ',' : '').$value['idproducto'].':'.$value['cantidad']; 
        } 
        // if($request['dxtratamiento']['TF']) 
        //     $grupotra = '2:'.$request['dxtratamiento']['TF'];

        // if($request['dxtratamiento']['AC'])  
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'3:'.$request['dxtratamiento']['AC']; 
        
        // if($request['dxtratamiento']['QT']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'4:'.$request['dxtratamiento']['QT']; 

        // if($request['dxtratamiento']['OCH']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'5:'.$request['dxtratamiento']['OCH']; 

        // if($request['dxtratamiento']['ESP']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'6:'.$request['dxtratamiento']['ESP']; 

        // if($request['dxtratamiento']['BL']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'11:'.$request['dxtratamiento']['BL']; 

        // if($request['dxtratamiento']['BMG']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'17:'.$request['dxtratamiento']['BMG']; 

        // if($request['dxtratamiento']['AA']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'10:'.$request['dxtratamiento']['AA']; 

        // if($request['dxtratamiento']['CPM2']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'13:'.$request['dxtratamiento']['CPM2']; 

        // if($request['dxtratamiento']['BE']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'14:'.$request['dxtratamiento']['BE']; 

        // if($request['dxtratamiento']['EA']) 
        //     $grupotra .= (strlen($grupotra) > 1 ? ',' : '').'15:'.$request['dxtratamiento']['EA']; 
        
        $dxtratamientofirst = $dxtratamiento->dxtratamiento(array(
            'dxtratamiento.grupodx' => $request['dxtratamiento']['grupodx'], 
            'dxtratamiento.eva' => $request['dxtratamiento']['eva'])
        );       

        // return $this->crearRespuesta('Ok.',[200, 'info'], '', '', [$grupotra, $dxtratamientofirst]);
        if($dxtratamientofirst && $dxtratamientofirst->iddxtratamiento !== $dxtratamiento->iddxtratamiento) {
            return $this->crearRespuesta('Tratamientos para el diagnóstico ya existe.', [200, 'info']);
        }

        if ($dxtratamiento) { 
            $update = array(
                'eva' => $request['dxtratamiento']['eva'],
                'grupotra' => $grupotra,
                'aprobacion' =>  $request['dxtratamiento']['aprobacion'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id_updated_at' => $this->objTtoken->my,
            );

            if(empty($request['dxtratamiento']['aprobacion'])){
                $update['fechaaprobacion'] = NULL;
            }else if(!empty($request['dxtratamiento']['aprobacion']) && empty($dxtratamiento->fechaaprobacion)){
                $update['fechaaprobacion'] = date('Y-m-d');
            }
 
            \DB::beginTransaction();
            try {      
                $dxtratamiento->fill($update);
                $dxtratamiento->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            
            return $this->crearRespuesta('El registro ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un registro', 404);
    }

    public function destroy($enterprise, $id) {

        $dxtratamiento = dxtratamiento::find($id); 
 
        
        if ($dxtratamiento) {
            \DB::beginTransaction();
            try {            
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $dxtratamiento->fill($auditoria);
                $dxtratamiento->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Registro a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Registro no encotrado', 404);
    }

}
 