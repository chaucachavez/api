<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\sede; 
use App\Models\autorizacionterapia; 

class autorizacionterapiaController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario 
         */
        $sede = new sede(); 
        $empresa = new empresa();
        
        $idempresa = $empresa->idempresa($enterprise); 
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );
        
        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
        );
        
        return $this->crearRespuesta($data, 200);        
    } 

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $autorizacionterapia = new autorizacionterapia();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        $param['autorizacionterapia.idempresa'] = $idempresa;
        $param['autorizacionterapia.idsede'] = $paramsTMP['idsede'];
        
        if (isset($paramsTMP['numerodoc']) && !empty($paramsTMP['numerodoc'])) {
            $param['cliente.numerodoc'] = $paramsTMP['numerodoc'];
        }

        if (isset($paramsTMP['numerodoc']) && !empty($paramsTMP['numerodoc'])) {
            $param['cliente.numerodoc'] = $paramsTMP['numerodoc'];
        }

        if (isset($paramsTMP['idcliente']) && !empty($paramsTMP['idcliente'])) {
            $param['autorizacionterapia.idcliente'] = $paramsTMP['idcliente'];
        }

        if (isset($paramsTMP['fecha']) && !empty($paramsTMP['fecha'])) {
            $param['autorizacionterapia.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        }
        
        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'autorizacionterapia.fecha';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        
        $data = $autorizacionterapia->grid($param, $like, $pageSize, $orderName, $orderSort);

        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total());
        }
        return $this->crearRespuestaError('cupon no encontrada', 404);
    }            
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $Objautorizacionterapia = new autorizacionterapia();
        
        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        
        $param = array(
            'idempresa' => $idempresa,
            'idsede' => $request['idsede'],
            'idcliente' => $request['idcliente'],
            'fecha' =>  date('Y-m-d')
        );
        $autorizacionterapia = $Objautorizacionterapia->autorizacionterapia('', $param); 
        
        //VALIDACIONES
        if(!empty($autorizacionterapia)){
            return $this->crearRespuesta('Autorizaci&oacute;n ya existe.', [200, 'info']);
        } 
        //FIN VALIDACIONES 
        
        $request['idempresa'] = $idempresa;
        $request['idpersonal'] = $this->objTtoken->my; 
        $request['fecha'] = date('Y-m-d');
        $request['usado'] = '0';
          
        /*Campos auditores*/
        $request['created_at'] = date('Y-m-d H:i:s');  
        $request['id_created_at'] = $this->objTtoken->my;  
        /*Campos auditores*/        
                                        
        autorizacionterapia::create($request);

        return $this->crearRespuesta('Autorizaci&oacute;n ha sido creado.', 201);
    }
     
    
    public function destroy($enterprise, $id) { 
        
        $autorizacionterapia = autorizacionterapia::find($id);     
        
        //VALIDACIONES
        if(!empty($autorizacionterapia) && $autorizacionterapia->usado === '1'){
            return $this->crearRespuesta('Autorizaci&oacute;n. No se puede eliminar', [200, 'info']);
        } 
        //FIN VALIDACIONES 
        
        if ($autorizacionterapia) {          
            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my];
            $autorizacionterapia->fill($auditoria);                
            $autorizacionterapia->save();

            return $this->crearRespuesta('Autorizaci&oacute;n ha sido eliminado', 200, '', '', $auditoria);
        }
        
        return $this->crearRespuestaError('Autorizaci&oacute;n no encotrado', 404);
    }

}
