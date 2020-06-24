<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\sede;
use App\Models\cupondescuento;

class cupondescuentoController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    function generarCodigo($longitud = 4) {        
        $key = '';
        $pattern = '1234567890AEIOU';
        $max = strlen($pattern) - 1;
        
        for($i=0; $i < $longitud; $i++){
            $key .= $pattern{mt_rand(0,$max)};
        }
        return $key;
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $cupondescuento = new cupondescuento();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        $param['cupondescuento.idempresa'] = $idempresa;
        
        if (isset($paramsTMP['codigo']) && !empty($paramsTMP['codigo'])) {            
            $param['cupondescuento.codigo'] = $paramsTMP['codigo'];
        }

        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'cupondescuento.fecha';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;

        $data = $cupondescuento->grid($param, $pageSize, $orderName, $orderSort);

        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total());
        }
        return $this->crearRespuestaError('cupon no encontrada', 404);
    }
         
    public function newcupon(Request $request, $enterprise) {
         
        $empresa = new empresa();
        $sede = new sede();       
        
        $idempresa = $empresa->idempresa($enterprise);
        
        $listcombox = array( 
            'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre'])
        );
        
        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }
    
    public function show(Request $request, $enterprise, $id) {
        
        $empresa = new empresa(); 
        $objCupon = new cupondescuento();
        
        $request = $request->all();          
        $idempresa = $empresa->idempresa($enterprise);
        
        $cupondescuento = $objCupon->cupondescuento(['idempresa' => $idempresa, 'codigo' => $id]);
        
        if (empty($cupondescuento)) {
            return $this->crearRespuesta('Cup&oacute;n no encotrado.', [200, 'info']);
        } 

        return $this->crearRespuesta($cupondescuento, 200);
        
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        
        $request['idempresa'] = $idempresa; 
        
        $i = 1;
        do {           
            $request['codigo'] = $this->generarCodigo(); //1:Si 0:No
            $request['intento'] = $i;
            $row = cupondescuento::where('codigo', '=', $request['codigo'])->first();
            $i = $i + 1;            
        } while (!empty($row));

        
        /*Campos auditores*/
        $request['created_at'] = date('Y-m-d H:i:s');  
        $request['id_created_at'] = $this->objTtoken->my;  
        /*Campos auditores*/        
                                        
        $cupondescuento = cupondescuento::create($request);

        return $this->crearRespuesta('Cup&oacute;n "' . $cupondescuento->codigo . '" ha sido creado.', 201);
    }
    
    public function update(Request $request, $enterprise, $id) {
        
        $request = $request->all();
        $cupondescuento = cupondescuento::find($id);
        
        //VALIDACIONES            
        if(!empty($cupondescuento) && $cupondescuento->usado === '1'){
            return $this->crearRespuesta('Cup&oacute;n a sido usado. No se puede editar', [200, 'info']);
        } 
        //FIN VALIDACIONES 
        
        $request['fecha'] = $this->formatFecha($request['fecha'], 'yyyy-mm-dd');
        
        /*Campos auditores*/
        $request['updated_at'] = date('Y-m-d H:i:s');  
        $request['id_updated_at'] = $this->objTtoken->my;  
        /*Campos auditores*/ 
        
        if ($cupondescuento) { 
            
            $cupondescuento->fill($request);
            $cupondescuento->save();

            return $this->crearRespuesta('El cup&oacute;n ' . $cupondescuento->codigo . ' ha sido editado. ', 200, '', '', $request);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un cup&oacute;n', 404);
    }
    
    public function destroy($enterprise, $id) { 
        
        $cupondescuento = cupondescuento::find($id);     
         
        
        if ($cupondescuento) {          
            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my];
            $cupondescuento->fill($auditoria);                
            $cupondescuento->save();

            return $this->crearRespuesta('Cup&oacute;n "' . $cupondescuento->codigo . '" ha sido eliminado', 200, '', '', $auditoria);
        }
        
        return $this->crearRespuestaError('Cup&oacute;n no encotrado', 404);
    }

}
